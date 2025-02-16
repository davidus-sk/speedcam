<?php

// get counts for this and last week
$count_week_r = $db->fetchAllAssoc($db->query('SELECT * FROM detections WHERE ts >= ' . $dtw->getTimestamp() . ' AND ts < ' . ($dtw->getTimestamp()+604800) . ' ORDER BY ts DESC'));
$count_yesterweek_r = $db->fetchAllAssoc($db->query('SELECT * FROM detections WHERE ts >= ' . $dtyw->getTimestamp() . ' AND ts < ' .($dtyw->getTimestamp()+604800)));

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

foreach ($count_today_r as $row) {
	$count_today[$row['hour']] = $row['cnt'];
}//foreach

foreach ($count_yesterday_r as $row) {
	$count_yesterday[$row['hour']] = $row['cnt'];
}//foreach

foreach ($count_week_r as $row) {
	$d = new DateTime('now', new DateTimeZone("America/New_York"));
	$d->setTimestamp($row['ts']);
	$count_week[$d->format('l')]++;
	$count_total++;
	$top_speed = $row['speed'] > $top_speed ? $row['speed'] : $top_speed;
	$speed_range = floor($row['speed'] * 0.621372 / 10) * 10;
	$speed_buckets[$speed_range]++;
	$average_week_i[$d->format('l')][0] = $average_week_i[$d->format('l')][0] + ($row['speed'] * 0.621372);
	$average_week_i[$d->format('l')][1]++;
	$average_week[$d->format('l')] = round($average_week_i[$d->format('l')][0] / $average_week_i[$d->format('l')][1]);
}//foreach

foreach ($count_yesterweek_r as $row) {
	$d = new DateTime('now', new DateTimeZone("America/New_York"));
	$d->setTimestamp($row['ts']);
	$count_yesterweek[$d->format('l')]++;
}//foreach

// pad arrays
for ($i = 0; $i < 24; $i++) {
	$count_today[$i] = isset($count_today[$i]) ? $count_today[$i] : 0;
	$count_yesterday[$i] = isset($count_yesterday[$i]) ? $count_yesterday[$i] : 0;
}//for

// sort the padded arrays
ksort($count_today);
ksort($count_yesterday);

?>

			<div class="row mb-4">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<b>Speeding Detections by Hour</b> 
							<span class="float-end"><a href="/download.php?tf=hour&week=<?php echo $week; ?>&year=<?php echo $year; ?>">Download CSV (<?php echo empty($week) ? 'Today' : 'Week'; ?>)</a></span>
						</div>
						<div class="card-body">
							<canvas id="g_count_today" style="width:100%"></canvas>
						</div>
						<div class="card-footer">
							<?php if (empty($week) || $week == date('W')) { ?>
							<span class="badge text-bg-primary" style="background-color:#2196F3 !important"><?php echo $dt->format('l'); ?></span> <span class="badge text-bg-primary" style="background-color:#8acbff !important"><?php echo $dty->format('l'); ?></span>
							<?php } else { ?>
							<span class="badge text-bg-primary" style="background-color:#2196F3 !important">Week <?php echo $dtw->format('W'); ?></span>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>

			<div class="row mb-4">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<b>Speeding Detections by Day</b>
							<a href="/download.php?tf=day&week=<?php echo $week; ?>&year=<?php echo $year; ?>" class="float-end">Download CSV (Week)</a>
						</div>
						<div class="card-body">
							<canvas id="g_count_week" style="width:100%"></canvas>
						</div>
						<div class="card-footer">
							<span class="badge text-bg-primary" style="background-color:#2196F3 !important">Week <?php echo $dtw->format('W'); ?></span> <span class="badge text-bg-primary" style="background-color:#8acbff !important">Week <?php echo $dtyw->format('W'); ?></span>
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
						<div class="card-footer">
							<span class="badge text-bg-primary" style="background-color:#00d700 !important">30-39 mph</span>
							<span class="badge text-bg-primary" style="background-color:#0000FF !important">40-49 mph</span>
							<span class="badge text-bg-primary" style="background-color:#f3c50f !important">50-59 mph</span>
							<span class="badge text-bg-primary" style="background-color:#FF0000 !important">60+ mph</span>
						</div>
					</div>
				</div>

				<div class="col-md-6 mt-4 mt-md-0">
					<div class="card">
						<div class="card-header">
							<b>Average Speed by Day</b>
						</div>
						<div class="card-body">
							<canvas id="g_speed_average" style="width:100%"></canvas>
						</div>
						<div class="card-footer">
							<span class="badge text-bg-primary" style="background-color:#2196F3 !important">Week <?php echo $dtw->format('W'); ?></span>
						</div>
					</div>
				</div>
			</div>

			<div class="row mb-4">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header">
							<b>Last 20 Detections</b>
							<span class="float-end"><a href="/?r=detections&l=<?php echo $location; ?>&week=<?php echo $week; ?>&year=<?php echo $year; ?>">List offenders</a></span>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table">
									<tr>
										<th>Date</th>
										<th>Time</th>
										<th>Speed</th>
										<th>Direction</th>
										<th>Video</th>
										<th>Image</th>
										<th>Plate</th>
									</tr>

									<?php
									$limit = 20;
									foreach ($count_week_r as $row) {
										$limit--;
										$d = new DateTime('now', new DateTimeZone("America/New_York"));
										$d->setTimestamp($row['ts']);
									?>

									<tr>
										<td><?php echo $d->format('m/d/Y'); ?></td>
										<td><?php echo $d->format('h:i A'); ?></td>
										<td><?php echo floor($row['speed'] * 0.621372); ?> mph</td>
										<td><?php echo $row['direction']; ?></td>
										<td><?php echo empty($row['video']) ? '-' : $row['video']; ?></td>
										<td><?php echo empty($row['image']) ? '-' : $row['image']; ?></td>
										<td><?php echo empty($row['plate']) ? '-' : $row['plate']; ?></td>
									</tr>

									<?php
										if ($limit == 0) {
											break;
										}//if
									}//while
									?>

								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

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
				'#00d700', '#0000FF', '#f3c50f', '#FF0000'
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
