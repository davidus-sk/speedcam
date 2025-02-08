<?php
// import libs
include 'DB.php';
$db = new DB('speed_cloud.db');

// get global vars
$location = empty($_GET['l']) ? 1 : (int)$_GET['l'];
$week = $_GET['week'];
$_route = !empty($_GET['r']) ? trim($_GET['r']) : 'home';

if (!empty($week) && ($week != date('W'))) {
	$day_offset = ($week - 1) * 7;
	$dayy_offset = ($week - 2) * 7;
	
	$dtw = new DateTime(date('Y-01-01 00:00:00'), new DateTimeZone("America/New_York"));
	$dtyw = new DateTime(date('Y-01-01 00:00:00'), new DateTimeZone("America/New_York"));

	$dtw->modify("+{$day_offset} days");
	$dtyw->modify("+{$dayy_offset} days");

	// get counts week
	$count_today_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE ts >= ? AND ts < ? GROUP BY hour', [$dtw->getTimestamp(), $dtw->getTimestamp() + 604800]);
	$count_yesterday_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE 1=0');
} else {
	$dtw = new DateTime('Monday this week 00:00:00', new DateTimeZone("America/New_York"));
	$dtyw = new DateTime('Monday previous week 00:00:00', new DateTimeZone("America/New_York"));

	$dt = new DateTime('today 00:00:00', new DateTimeZone("America/New_York"));
	$dty = new DateTime('yesterday 00:00:00', new DateTimeZone("America/New_York"));

	// get counts today and yesterday
	$count_today_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE ts >= ? AND ts < ? GROUP BY hour', [$dt->getTimestamp(), $dt->getTimestamp() + 86400]);
	$count_yesterday_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE ts >= ? AND ts < ? GROUP BY hour', [$dty->getTimestamp(), $dty->getTimestamp() + 86400]);
}

$count_total_r = $db->fetchRow('SELECT COUNT(*) as CNT FROM detections WHERE ts >= ? AND ts < ? ORDER BY ts DESC', [$dtw->getTimestamp(), $dtw->getTimestamp()+604800]);
$count_total = $count_total_r['CNT'];

// start output buffer
ob_start();

if( file_exists($_route . '.php') )
{
        // If all good, load the page
        include $_route . '.php';
}
else
{
        // Can't find the page, show error
        include '404.php';
}//if


// store output
$_content = ob_get_clean();
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>SHAME System</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	</head>
	<body>
		<div class="container">
			<nav class="navbar bg-body-secondary mb-4 mt-4 rounded">
				<div class="container-fluid">
					<a href="/" class="navbar-brand">Safe Homeowners Accessible Motorist Enforcement</a>
					<div class="d-flex">
						<b>Week #<?php echo $dtw->format('W'); ?></b>
					</div>
				</div>
			</nav>

			<?php if (!in_array($_route, ['settings'])) { ?>

			<div class="row mb-4">
				<div class="col-md-6">
					<div class="card">
						<div class="card-body">
							Week:
							<?php
							for ($i=date('W') - 13; $i<=date('W'); $i++) {
								if ($i >= 1) {
									echo '<a href="/?week=' . $i . '" class="badge ' . ($i==$week ? ' text-bg-primary ' : ($i==date('W') ? ' text-bg-secondary ' : 'text-bg-light')) . '">' . $i . '</a> ';
								}//if
							}//for
							?>
						</div>
					</div>
				</div>
				<div class="col-md-6 mt-4 mt-md-0">
					<div class="card">
						<div class="card-body">
							<select name="location" id="location" style="display: block; width: 100%;">
								<option>Select a location</option>
								<?php
								$locations_r = $db->fetchResult('SELECT rowid, * FROM locations');

								while ($row = $locations_r->fetchArray()) {
									echo '<option value="' . $row['rowid'] . '">' . $row['name'] . '</option>';
								}//while
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="mb-4 rounded bg-body-secondary">
				<div class="row">
					<div class="col-md-6">
						<p class="p-3 m-0"><b>
							<?php
							$r = $db->fetchRow('SELECT * FROM locations WHERE rowid = ' . $location);
							echo $r['name'];
							?>
						</b></p>
					</div>
					<div class="col-md-2">
						<p class="p-3 m-0 text-end">Storage: <?php echo $r['storage']; ?> % free</p>
					</div>
					<div class="col-md-2">
						<p class="p-3 m-0 text-end">Detections: <a href="/?r=detections&l=<?php echo $location; ?>"><?php echo $count_total; ?></a></p>
					</div>
					<div class="col-md-2">
						<p class="p-3 m-0 text-end">Speed limit: <a href="/?r=settings"><?php echo round($r['speedlimit'] * 0.621371); ?> mph</a></p>
					</div>
				</div>
			</div>

			<?php }//if ?>

			<?php echo $_content; ?>

			<div class="mb-4 rounded bg-body-tertiary">
				<div class="row">
					<div class="col-md-12"><p class="p-2 m-0">Copyright &copy; 2024 LUCEON LLC | All rights reserved | Made in Florida with Love</p></div>
				</div>
			</div>
		</div>

		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	</body>
</html>
