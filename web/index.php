<?php
// load up system config
$config_file = dirname(__FILE__) . "/../config.json";
$conf = [];

if (file_exists($config_file)) {
	if ($json = file_get_contents($config_file)) {
		$conf = json_decode($json, TRUE);
	}//if
}//if

// get camera and radar data
$image_left = ['ts'=>0, 'name'=>null];
$image_right = ['ts'=>0, 'name'=>null];

foreach (glob("/data/frames/0_*.jpg") as $filename) {
	if ($image_left['ts'] < filemtime($filename)) {
		$image_left = ['ts'=>filemtime($filename), 'name'=>basename($filename)];
	}//if
}//foreach

foreach (glob("/data/frames/1_*.jpg") as $filename) {
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
$db = new SQLite3('/data/speed.db', SQLITE3_OPEN_READONLY);
$db_vms = new SQLite3('/app/speed/edge/vms/vms_videos.db', SQLITE3_OPEN_READONLY);

$cam0_results_cnt = $db->query('SELECT hour, count(time) as cnt FROM detections WHERE camera=0 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');
$cam0_results_cnt_y = $db->query('SELECT hour, count(time) as cnt FROM detections WHERE camera=0 AND month=' . date('n', time() - 86400) . ' AND day=' . date('j', time() - 86400) . ' GROUP BY hour');
$cam1_results_cnt = $db->query('SELECT hour, count(time) as cnt FROM detections WHERE camera=1 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');
$cam1_results_cnt_y = $db->query('SELECT hour, count(time) as cnt FROM detections WHERE camera=1 AND month=' . date('n', time() - 86400) . ' AND day=' . date('j', time() - 86400) . ' GROUP BY hour');
$cam0_results_speed = $db->query('SELECT hour, max(speed) as mspeed FROM detections WHERE camera=0 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');
$cam1_results_speed = $db->query('SELECT hour, max(speed) as mspeed FROM detections WHERE camera=1 AND month=' . date('n') . ' AND day=' . date('j') . ' GROUP BY hour');

$cam0_day_cnt = [];
$cam0_day_cnt_y = [];
$cam0_day_speed = [];
$cam0_top_speed = 0;

$cam1_day_cnt = [];
$cam1_day_cnt_y = [];
$cam1_day_speed = [];
$cam1_top_speed = 0;

while ($row = $cam0_results_cnt->fetchArray()) {
	$cam0_day_cnt[$row['hour']] = $row['cnt'];
}//while

while ($row = $cam0_results_cnt_y->fetchArray()) {
	$cam0_day_cnt_y[$row['hour']] = $row['cnt'];
}//while

while ($row = $cam1_results_cnt->fetchArray()) {
	$cam1_day_cnt[$row['hour']] = $row['cnt'];
}//while

while ($row = $cam1_results_cnt_y->fetchArray()) {
	$cam1_day_cnt_y[$row['hour']] = $row['cnt'];
}//while

while ($row = $cam0_results_speed->fetchArray()) {
	$cam0_day_speed[$row['hour']] = $row['mspeed'];

	if($row['mspeed'] > $cam0_top_speed) {
		$cam0_top_speed = $row['mspeed'];
	}//if
}//while

while ($row = $cam1_results_speed->fetchArray()) {
	$cam1_day_speed[$row['hour']] = $row['mspeed'];

	if($row['mspeed'] > $cam1_top_speed) {
		$cam1_top_speed = $row['mspeed'];
	}//if
}//while

// fill empty hours for graphing purposes
for ($i = 0; $i < 24; $i++) {
	$cam0_day_cnt[$i] = isset($cam0_day_cnt[$i]) ? $cam0_day_cnt[$i] : 0;
	$cam1_day_cnt[$i] = isset($cam1_day_cnt[$i]) ? $cam1_day_cnt[$i] : 0;
	$cam0_day_cnt_y[$i] = isset($cam0_day_cnt_y[$i]) ? $cam0_day_cnt_y[$i] : 0;
	$cam1_day_cnt_y[$i] = isset($cam1_day_cnt_y[$i]) ? $cam1_day_cnt_y[$i] : 0;
	$cam0_day_speed[$i] = isset($cam0_day_speed[$i]) ? $cam0_day_speed[$i] : 0;
	$cam1_day_speed[$i] = isset($cam1_day_speed[$i]) ? $cam1_day_speed[$i] : 0;
}//for

// you have to sort the arrays
ksort($cam0_day_cnt);
ksort($cam1_day_cnt);
ksort($cam0_day_cnt_y);
ksort($cam1_day_cnt_y);
ksort($cam0_day_speed);
ksort($cam1_day_speed);

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
					<div class="w3-col m6">
						<p>Channel number: <?php echo $conf["left"]["camera"]; ?></p>
						<p>Top speed: <?php echo $cam0_top_speed; ?> km/h</p>
					</div>
					<div class="w3-col m6">
						<p>Radar serial: <?php echo substr($conf["left"]["radar"], 0, 8); ?></p>
						<p class="w3-text-red">Speed limit: <?php echo $conf["left"]["speed_limit"]; ?> km/h</p>
					</div>
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
					<div class="w3-col m6">
						<p>Channel number: <?php echo $conf["right"]["camera"]; ?></p>
						<p>Top speed: <?php echo $cam1_top_speed; ?> km/h</p>
					</div>
					<div class="w3-col m6">
						<p>Radar serial: <?php echo substr($conf["right"]["radar"], 0, 8); ?></p>
						<p class="w3-text-red">Speed limit: <?php echo $conf["right"]["speed_limit"]; ?> km/h</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="w3-row-padding w3-margin-bottom">
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

	<div class="w3-row-padding w3-margin-bottom">
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

	<div class="w3-row-padding w3-margin-bottom">
		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container">
					<h3>Last 10 detections</h3>
				</header>
				<div class="w3-container">
					<table class="w3-table w3-striped w3-bordered">
						<tr>
							<th>Time</th>
							<th>Speed</th>
							<th>Video</th>
						</tr>
						
						<?php
						$r = $db->query('SELECT * FROM detections WHERE camera = 0 ORDER BY time DESC LIMIT 10');
						while ($row = $r->fetchArray()) {
							$r_vms = $db_vms->query('SELECT * FROM videos WHERE ts_from <= ' . $row['time'] . ' AND ts_to >= ' . $row['time']);
							$video = $r_vms->fetchArray();
						?>

						<tr>
							<td><?php echo date('Y-m-d H:i:s', $row['time']); ?></td>
							<td><?php echo $row['speed']; ?> km/h</td>
							<td><a href="/vms/<?php echo $video['filename']; ?>">play</a></td>
						</tr>

						<?php
						}//while
						?>
					</table>
				</div>
			</div>
		</div>

		<div class="w3-col m6">
			<div class="w3-card">
				<header class="w3-container">
					<h3>Last 10 detections</h3>
				</header>
				<div class="w3-container">
					<table class="w3-table w3-striped w3-bordered">
						<tr>
							<th>Time</th>
							<th>Speed</th>
							<th>Video</th>
						</tr>
						
						<?php
						$r = $db->query('SELECT * FROM detections WHERE camera = 1 ORDER BY time DESC LIMIT 10');
						while ($row = $r->fetchArray()) {
							$r_vms = $db_vms->query('SELECT * FROM videos WHERE ts_from <= ' . $row['time'] . ' AND ts_to >= ' . $row['time']);
							$video = $r_vms->fetchArray();
						?>

						<tr>
							<td><?php echo date('Y-m-d H:i:s', $row['time']); ?></td>
							<td><?php echo $row['speed']; ?> km/h</td>
							<td><a href="/vms/<?php echo $video['filename']; ?>">play</a></td>
						</tr>

						<?php
						}//while
						?>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="w3-row-padding">
		<div class="w3-col">
			<div class="w3-row-padding w3-blue">
				<div class="w3-col m6">
					<p>Copyright &copy; <?php echo date('Y'); ?> <b>LUCEON LLC</b>. All rights reserved.</p>
				</div>
				<div class="w3-col m6">
					<p>
					Free shmem: <?php echo trim(`/usr/bin/df | /usr/bin/awk '/([0-9]+)% \/data/{print $5; exit}'`);?>
					<span class="w3-text-light-blue">|</span>
					Utilization: <?php echo trim(`/usr/bin/uptime | /usr/bin/awk '{print $11}' | /usr/bin/sed 's/,$//'`);?>
					<span class="w3-text-light-blue">|</span>
					Temperature: <?php echo floor(trim(`/usr/bin/cat /sys/class/thermal/thermal_zone0/temp`) / 1000); ?> &deg;C
					</p>
				</div>
			</div>
		</div>
	</div>

	<script>
	const cnt_0_x = <?php echo json_encode(array_keys($cam0_day_cnt)); ?>;
	const cnt_0_y = <?php echo json_encode(array_values($cam0_day_cnt)); ?>;
	const cnt_y_0_y = <?php echo json_encode(array_values($cam0_day_cnt_y)); ?>;

	const cnt_1_x = <?php echo json_encode(array_keys($cam1_day_cnt)); ?>;
	const cnt_1_y = <?php echo json_encode(array_values($cam1_day_cnt)); ?>;
	const cnt_y_1_y = <?php echo json_encode(array_values($cam1_day_cnt_y)); ?>;

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
      backgroundColor: "#2196F3"
    },
	      {
      data: cnt_y_0_y,
      backgroundColor: "#8acbff"
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
      backgroundColor: "#2196F3"
    },
	      {
      data: cnt_y_1_y,
      backgroundColor: "#8acbff"
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
      backgroundColor: "#2196F3"
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
      backgroundColor: "#2196F3"
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

<?php
// destroy DB connections
$db->close();
$db_vms->close();
?>