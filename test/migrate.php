<?php

include '../edge/DB.php';

// read config
$conf_file = '../cloud/config.json';

if (file_exists($conf_file)) {
	$json = file_get_contents($conf_file);

	if ($conf = json_decode($json, TRUE)) {
	}//if
}//if

// include DB
$mysqli = new mysqli($conf['host'], $conf['username'], $conf['password'], $conf['database']);

$db = new DB('../cloud/speed_cloud.db');

// select all
$r = $db->fetchResult('SELECT * FROM detections ORDER BY ts ASC');

while ($row = $r->fetchArray()) {
	echo "{$row['ts']}\n";

	$mysqli->query("INSERT INTO detections (location_id, ts, year, month, day, hour, camera_id, radar_id, speed, direction, plate, image, video) VALUES
		({$row['location_id']}, {$row['ts']}, {$row['year']}, {$row['month']}, {$row['day']}, {$row['hour']}, {$row['camera_id']}, '{$row['radar_id']}', {$row['speed']}, '{$row['direction']}', null, null, null)");

	var_dump($mysqli->error);
}//while
