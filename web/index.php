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

// get network info
$modem = `/usr/bin/mmcli -m 1`;
$items = ['tech' => 'access tech: ([a-z0-9]+)', 'signal' => 'signal quality: ([a-z0-9]+)%', 'operator' => 'operator name: ([a-z0-9]+)'];
$cell = ['tech' => null, 'signal' => null, 'operator' => null];
foreach($items as $i => $item) {
	if(preg_match("/$item/i", $modem, $m)) {
		$cell[$i] = $m[1];
	}//if
}//foreach

?>

<!DOCTYPE html>
<html>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
	<body>

	<div class="w3-container w3-blue">
		<div class="w3-col m6">
			<div class="w3-col m6">
				<h1>Speed Camera</h1>
				<p>Deerwoord</p>
			</div>
			<div class="w3-col m6">
				<?php var_dump($cell); ?>
			</div>
		</div>
	</div>


	<div class="w3-row-padding">

		<div class="w3-col m6">
			<div class="w3-card-4">
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
			<div class="w3-card-4">
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

	</body>
</html>
