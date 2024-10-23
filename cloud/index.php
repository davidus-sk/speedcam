<?php
include 'DB.php';
$db = new DB('speed_cloud.db');

// get week number
$week = $_GET['week'];

if (!empty($week)) {
	$day_offset = ($week - 1) * 7;
	$dayy_offset = ($week - 2) * 7;
	
	$dtw = new DateTime(date('Y-01-01 00:00:00'), new DateTimeZone("America/New_York"));
	$dtyw = new DateTime(date('Y-01-01 00:00:00'), new DateTimeZone("America/New_York"));

	$dtw->modify("+{$day_offset} days");
	$dtyw->modify("+{$dayy_offset} days");
} else {
	$dtw = new DateTime('Monday this week 00:00:00', new DateTimeZone("America/New_York"));
	$dtyw = new DateTime('Monday previous week 00:00:00', new DateTimeZone("America/New_York"));
}

// time frames
$dt = new DateTime('now', new DateTimeZone("America/New_York"));
$dty = new DateTime('yesterday', new DateTimeZone("America/New_York"));



// get counts today and yesterday
$count_today_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE month=? AND day=? AND year=? GROUP BY hour', [$dt->format('n'), $dt->format('j'), $dt->format('Y')]);
$count_yesterday_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE month=? AND day=? AND year=? GROUP BY hour', [$dty->format('n'), $dty->format('j'), $dty->format('Y')]);

// get counts for this and last week
$count_week_r = $db->fetchResult('SELECT * FROM detections WHERE ts >= ? AND ts < ?', [$dtw->getTimestamp(), $dtw->getTimestamp()+604800]);
$count_yesterweek_r = $db->fetchResult('SELECT * FROM detections WHERE ts >= ? AND ts < ?', [$dtyw->getTimestamp(), $dtyw->getTimestamp()+604800]);

// get data into arrays
$top_speed = 0;
$speed_buckets = ['30' => 0, '40' => 0, '50' => 0, '60' => 0];
$count_total = 0;
$count_today = [];
$count_yesterday = [];
$count_week = ['Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0];
$count_yesterweek = ['Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0];
$average_week_i = ['Monday' => [0,0], 'Tuesday' => [0,0], 'Wednesday' => [0,0], 'Thursday' => [0,0], 'Friday' => [0,0], 'Saturday' => [0,0], 'Sunday' => [0,0]];
$average_week = ['Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0];

while ($row = $count_today_r->fetchArray()) {
	$count_today[$row['hour']] = $row['cnt'];
}//while

while ($row = $count_yesterday_r->fetchArray()) {
	$count_yesterday[$row['hour']] = $row['cnt'];
}//while

while($row = $count_week_r->fetchArray()) {
	$dtw->setTimestamp($row['ts']);
	$count_week[$dtw->format('l')]++;
	$count_total++;
	$top_speed = $row['speed'] > $top_speed ? $row['speed'] : $top_speed;
	$speed_range = floor($row['speed'] * 0.621372 / 10) * 10;
	$speed_buckets[$speed_range]++;
	$average_week_i[$dtw->format('l')][0] = $average_week_i[$dtw->format('l')][0] + ($row['speed'] * 0.621372);
	$average_week_i[$dtw->format('l')][1]++;
	$average_week[$dtw->format('l')] = round($average_week_i[$dtw->format('l')][0] / $average_week_i[$dtw->format('l')][1]);
}//while

while($row = $count_yesterweek_r->fetchArray()) {
	$dtw->setTimestamp($row['ts']);
	$count_yesterweek[$dtw->format('l')]++;
}//while

// pad arrays
for ($i = 0; $i < 24; $i++) {
	$count_today[$i] = isset($count_today[$i]) ? $count_today[$i] : 0;
	$count_yesterday[$i] = isset($count_yesterday[$i]) ? $count_yesterday[$i] : 0;
}//for

// sort the padded arrays
ksort($count_today);
ksort($count_yesterday);

?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>SHAME System Report</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	</head>
	<body>
		<div class="container">
			<div class="p-4 p-md-5 mb-4 mt-4 rounded text-body-emphasis bg-body-secondary">
				<div class="row">
					<div class="col-lg-9 px-0">
						<h2 class="display-3"><b>S</b>afe <b>H</b>omeowners <b>A</b>ccessible <b>M</b>otorist <b>E</b>nforcement</h2>
						<h4>Deerwood, Jacksonville, Florida 32256</h4>
					</div>
					<div class="col-lg-3 px-0">
						<p class="lead my-3"><b>Week #<?php echo $dtw->format('W'); ?></b></p>
						<p class="lead my-3">Detections: <?php echo $count_total; ?></p>
						<p class="lead my-3">Top speed: <?php echo floor($top_speed * 0.621372); ?> mph</p>
						<p class="lead my-3">Speed limit: 30 mph</p>
					</div>
				</div>
			</div>

			<div class="row mb-4">
				<div class="col-md-12">
					<div class="card">
						<div class="card-body">
							Week:
							<?php
							for ($i=1; $i<=date('W');$i++) {
								echo '<a href="/?week=' . $i . '" class="badge ' . ($i==$week ? ' text-bg-primary ' : '') . ($i==date('W') ? ' text-bg-secondary ' : '') . ' text-bg-white">'. $i . '</a>, ';
							}
							?>
						</div>
					</div>
				</div>
			</div>

			<div class="row mb-4">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<b>Speeding Detections by Hour</b> 
							<a href="/download.php?tf=day" class="float-end">Download CSV</a>
						</div>
						<div class="card-body">
							<canvas id="g_count_today" style="width:100%"></canvas>
						</div>
						<div class="card-footer">
							<span class="badge text-bg-primary" style="background-color:#2196F3 !important">today</span> and <span class="badge text-bg-primary" style="background-color:#8acbff !important">yesterday</span>
						</div>
					</div>
				</div>
			</div>

			<div class="row mb-4">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<b>Speeding Detections by Day</b>
							<a href="/download.php?tf=week" class="float-end">Download CSV</a>
						</div>
						<div class="card-body">
							<canvas id="g_count_week" style="width:100%"></canvas>
						</div>
						<div class="card-footer">
							<span class="badge text-bg-primary" style="background-color:#2196F3 !important">this</span> and <span class="badge text-bg-primary" style="background-color:#8acbff !important">last</span> week
						</div>
					</div>
				</div>
			</div>

      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <b>Speed Range Distribution</b>
            </div>
            <div class="card-body">
		<canvas id="g_speed_range" style="width:100%"></canvas>
            </div>
						<div class="card-footer">Speed groups
							<span class="badge text-bg-primary" style="background-color:#FF0000 !important">30-39 mph</span>,
							<span class="badge text-bg-primary" style="background-color:#00d700 !important">40-49 mph</span>,
							<span class="badge text-bg-primary" style="background-color:#0000FF !important">50-59 mph</span>, and
							<span class="badge text-bg-primary" style="background-color:#f3c50f !important">60+ mph</span>
						</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <b>Average Speed by Day</b>
            </div>
            <div class="card-body">
		<canvas id="g_speed_average" style="width:100%"></canvas>
            </div>
          </div>  
        </div>
      </div>
  </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>

  	<script>
  	const count_today_x = <?php echo json_encode(array_keys($count_today)); ?>;
  	const count_today_y = <?php echo json_encode(array_values($count_today)); ?>;
  	const count_yesterday_y = <?php echo json_encode(array_values($count_yesterday)); ?>;

  	const count_week_x = <?php echo json_encode(array_keys($count_week)); ?>;
  	const count_week_y = <?php echo json_encode(array_values($count_week)); ?>;
  	const count_yesterweek_y = <?php echo json_encode(array_values($count_yesterweek)); ?>;
	const average_week_y = <?php echo json_encode(array_values($average_week)); ?>;
  
    new Chart("g_count_today", {
      type: "bar",
      data: {
        labels: count_today_x,
        datasets: [{
          data: count_today_y,
          backgroundColor: "#2196F3"
        },
    	      {
          data: count_yesterday_y,
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

    new Chart("g_count_week", {
      type: "bar",
      data: {
        labels: count_week_x,
        datasets: [{
          data: count_week_y,
          backgroundColor: "#2196F3"
        },
    	      {
          data: count_yesterweek_y,
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

	new Chart("g_speed_range", {
		type: "pie",
		data: {
			labels: <?php echo json_encode(array_keys($speed_buckets)); ?>,
			datasets: [{
			data: <?php echo json_encode(array_values($speed_buckets)); ?>,
			backgroundColor: [
				'#FF0000', '#00d700', '#0000FF', '#f3c50f'
			],
			}]
		},
		options: {
			legend: {display: false},
			title: {display: false},
			scales: { },
    plugins: {
      datalabels: {
        display: true,
        formatter: (val, ctx) => {
          // Grab the label for this value
          const label = ctx.chart.data.labels[ctx.dataIndex];

          // Format the number with 2 decimal places
          const formattedVal = Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
          }).format(val);

          // Put them together
          return `${label}: ${formattedVal}`;
        },
        color: '#fff',
        backgroundColor: '#404040',
      },
    },
		}
	});

    new Chart("g_speed_average", {
      type: "bar",
      data: {
        labels: count_week_x,
        datasets: [{
          data: average_week_y,
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
