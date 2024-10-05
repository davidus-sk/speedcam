<?php
// load up system config
$config_file = "/app/speed/config.json";
$conf = [];

if (file_exists($config_file)) {
	if ($json = file_get_contents($config_file)) {
		$conf = json_decode($json, TRUE);
	}//if
}//if

// get camera and radar data
$image_left = ['ts'=>0, 'name'=>null];
$image_right = ['ts'=>0, 'name'=>null];
$speed_left = file_get_contents("/dev/shm/{$conf['left']['radar']}.speed");
$speed_right = file_get_contents("/dev/shm/{$conf['right']['radar']}.speed");
$top_speed_left = file_get_contents("/dev/shm/{$conf['left']['radar']}.top");
$top_speed_right = file_get_contents("/dev/shm/{$conf['right']['radar']}.top");

foreach (glob("/dev/shm/frames/0_*.jpg") as $filename) {
	if ($image_left['ts'] < filemtime($filename)) {
		$image_left = ['ts'=>filemtime($filename), 'name'=>basename($filename)];
	}//if
}//foreach

foreach (glob("/dev/shm/frames/1_*.jpg") as $filename) {
	if ($image_right['ts'] < filemtime($filename)) {
		$image_right = ['ts'=>filemtime($filename), 'name'=>basename($filename)];
	}//if
}//foreach

// get modem ID and network info
$modem_id = `/usr/bin/mmcli -L`;

if (preg_match("@/Modem/([0-9]+)@", $modem_id, $m)) {
	$modem = `/usr/bin/mmcli -m {$m[1]}`;
	$items = ['tech' => 'access tech: ([a-z0-9]+)', 'signal' => 'signal quality: ([a-z0-9]+)%', 'operator' => 'operator name: ([a-z0-9-]+)'];
	$cell = ['tech' => null, 'signal' => null, 'operator' => null];
	foreach($items as $i => $item) {
		if(preg_match("/$item/i", $modem, $m)) {
			$cell[$i] = $m[1];
		}//if
	}//foreach
}//if

// get SQL data
$db = new SQLite3('/dev/shm/speed.db', SQLITE3_OPEN_READONLY);
$cam0_results_cnt = $db->query('SELECT hour, count(time) as cnt FROM detections WHERE camera=0 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');
$cam1_results_cnt = $db->query('SELECT hour, count(time) as cnt FROM detections WHERE camera=1 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');
$cam0_results_speed = $db->query('SELECT hour, max(speed) as mspeed FROM detections WHERE camera=0 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');
$cam1_results_speed = $db->query('SELECT hour, max(speed) as mspeed FROM detections WHERE camera=1 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');

$cam0_day_cnt = [];
$cam0_day_speed = [];
$cam1_day_cnt = [];
$cam1_day_speed = [];

while ($row = $cam0_results_cnt->fetchArray()) {
	$cam0_day_cnt[$row['hour']] = $row['cnt'];
}//while

while ($row = $cam1_results_cnt->fetchArray()) {
	$cam1_day_cnt[$row['hour']] = $row['cnt'];
}//while

while ($row = $cam0_results_speed->fetchArray()) {
	$cam0_day_speed[$row['hour']] = $row['mspeed'];
}//while

while ($row = $cam1_results_speed->fetchArray()) {
	$cam1_day_speed[$row['hour']] = $row['mspeed'];
}//while

// fill empty hours for graphing purposes
for ($i = 0; $i < 24; $i++) {
	$cam0_day_cnt[$i] = isset($cam0_day_cnt[$i]) ? $cam0_day_cnt[$i] : 0;
	$cam1_day_cnt[$i] = isset($cam1_day_cnt[$i]) ? $cam1_day_cnt[$i] : 0;
	$cam0_day_speed[$i] = isset($cam0_day_speed[$i]) ? $cam0_day_speed[$i] : 0;
	$cam1_day_speed[$i] = isset($cam1_day_speed[$i]) ? $cam1_day_speed[$i] : 0;
}//for

// you have to sort the arrays
ksort($cam0_day_cnt);
ksort($cam1_day_cnt);
ksort($cam0_day_speed);
ksort($cam1_day_speed);

$db->close();

?>

<!DOCTYPE html>
<html>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
	<body>

	<div class="w3-row-padding w3-margin-bottom">
		<div class="w3-col">
			<div class="w3-row-padding w3-blue">
				<div class="w3-col m6">
					<p><b>Speed Camera</b></p>
					<p>Deerwoord</p>
				</div>
				<div class="w3-col m6">
					<p><?php echo $cell['operator'] . ' (' . strtoupper($cell['tech']) . ' - ' . $cell['signal'] . '%)'; ?></p>
					<p><?php echo gethostname(); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="w3-row-padding w3-margin-bottom">
		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container w3-blue">
					<h2>LEFT</h2>
				</header>
				<img src="/frames/<?php echo $image_left['name']; ?>" width="100%" />
				<div class="w3-container">
					<p>Channel number: <?php echo $conf["left"]["camera"]; ?></p>
					<p>Radar serial: <?php echo $conf["left"]["radar"]; ?></p>
					<p>Speed limit: <?php echo $conf["left"]["speed_limit"]; ?> km/h</p>
					<p>Top speed: <?php echo $top_speed_left; ?> km/h</p>
					<p>Curent speed: <?php echo $speed_left; ?> km/h</p>
				</div>
			</div>
		</div>

		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container w3-blue">
					<h2>RIGHT</h2>
				</header>
				<img src="/frames/<?php echo $image_right['name']; ?>" width="100%" />
				<div class="w3-container">
					<p>Channel number: <?php echo $conf["right"]["camera"]; ?></p>
					<p>Radar serial: <?php echo $conf["right"]["radar"]; ?></p>
					<p>Speed limit: <?php echo $conf["right"]["speed_limit"]; ?> km/h</p>
					<p>Top speed: <?php echo $top_speed_right; ?> km/h</p>
					<p>Curent speed: <?php echo $speed_right; ?> km/h</p>
				</div>
			</div>
		</div>
	</div>

	<div class="w3-row-padding">
		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container">
					<h3>Detections by hour</h3>
				</header>
				<div class="w3-container">
					<canvas id="cnt_0_graph" style="width:100%"></canvas>
				</div>
			</div>
		</div>

		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container">
					<h3>Detections by hour</h3>
				</header>
				<div class="w3-container">
					<canvas id="cnt_1_graph" style="width:100%"></canvas>
				</div>
			</div>
		</div>
	</div>

	<div class="w3-row-padding">
		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container">
					<h3>Top speed by hour</h3>
				</header>
				<div class="w3-container">
					<canvas id="speed_0_graph" style="width:100%"></canvas>
				</div>
			</div>
		</div>

		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container">
					<h3>Top speed by hour</h3>
				</header>
				<div class="w3-container">
					<canvas id="speed_1_graph" style="width:100%"></canvas>
				</div>
			</div>
		</div>
	</div>

<script>
const cnt_0_x = <?php echo json_encode(array_keys($cam0_day_cnt)); ?>;
const cnt_0_y = <?php echo json_encode(array_values($cam0_day_cnt)); ?>;

const cnt_1_x = <?php echo json_encode(array_keys($cam1_day_cnt)); ?>;
const cnt_1_y = <?php echo json_encode(array_values($cam1_day_cnt)); ?>;

const speed_0_x = <?php echo json_encode(array_keys($cam0_day_speed)); ?>;
const speed_0_y = <?php echo json_encode(array_values($cam0_day_speed)); ?>;

const speed_1_x = <?php echo json_encode(array_keys($cam1_day_speed)); ?>;
const speed_1_y = <?php echo json_encode(array_values($cam1_day_speed)); ?>;

new Chart("cnt_0_graph", {
  type: "bar",
  data: {
    labels: cnt_0_x,
    datasets: [{
      data: cnt_0_y,
      backgroundColor: "blue"
    }]
  },
  options: {
    legend: {display: false},
    title: {
      display: false,
    },
    scales: {
      xAxes: [{
        gridLines: {
          display: false // Disables grid lines on the x-axis
        }
      }],
    }
  }
});

new Chart("cnt_1_graph", {
  type: "bar",
  data: {
    labels: cnt_1_x,
    datasets: [{
      data: cnt_1_y,
      backgroundColor: "blue"
    }]
  },
  options: {
    legend: {display: false},
    title: {
      display: false,
    },
    scales: {
      xAxes: [{
        gridLines: {
          display: false // Disables grid lines on the x-axis
        }
      }],
    }
  }
});

new Chart("speed_0_graph", {
  type: "bar",
  data: {
    labels: speed_0_x,
    datasets: [{
      data: speed_0_y,
      backgroundColor: "blue"
    }]
  },
  options: {
    legend: {display: false},
    title: {
      display: false,
    },
    scales: {
      xAxes: [{
        gridLines: {
          display: false // Disables grid lines on the x-axis
        }
      }],
    }
  }
});

new Chart("speed_1_graph", {
  type: "bar",
  data: {
    labels: speed_1_x,
    datasets: [{
      data: speed_1_y,
      backgroundColor: "blue"
    }]
  },
  options: {
    legend: {display: false},
    title: {
      display: false,
    },
    scales: {
      xAxes: [{
        gridLines: {
          display: false // Disables grid lines on the x-axis
        }
      }],
    }
  }
});
</script>

	</body>
</html>
