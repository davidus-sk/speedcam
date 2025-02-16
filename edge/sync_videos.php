#!/usr/bin/php
<?php

include dirname(__FILE__) . '/DB.php';

openlog("sync_videos.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

// read config file
$conf_file = "/app/speed/config.json";

if (file_exists($conf_file) && filesize($conf_file) > 0) {
	$json = file_get_contents($conf_file);
	$conf = json_decode($json, TRUE);
	syslog(LOG_INFO, "Read config file from $conf_file.");
} else {
	syslog(LOG_ERR, "Config file $conf_file missing.");
	exit();
}//if

$db_det = new SQLite3('/data/speed.db', SQLITE3_OPEN_READONLY);
$db_vms = new SQLite3('/data/vms_videos.db', SQLITE3_OPEN_READONLY);

//while(TRUE) {
	$detections = $db_det->query('SELECT * FROM detections WHERE time >= ' . (microtime(true) - 120) . ' AND uploaded = 0 ORDER BY time DESC LIMIT 10');

	while ($row = $detections->fetchArray()) {
		$videos = $db_vms->query('SELECT * FROM videos WHERE camera = ' . $row['camera'] . ' AND ts_from <= ' . $row['time'] . ' AND ts_to >= ' . $row['time']);
		$video = $videos->fetchArray();

		if ($video) {
			syslog(LOG_INFO, "Found matching video: {$video['filename']}.");

			$ch = curl_init($conf['settings']['api']['post_url']);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, [
				'location' => $conf['settings']['location'],
				'camera' => $row['camera'],
				'ts' => $row['time'],
				'video' => curl_file_create('/data/vms/' . $video['filename'], 'image/jpeg', $video['filename'])
			]);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);

			if (preg_match("/OK/", $response)) {
				$db = new DB('/data/speed.db');
				$db->query("UPDATE detections SET uploaded = 1 WHERE time = {$row['camera']}");
				unset($db);
			} else {
				syslog(LOG_ERR, "File {$video['filename']} not uploaded.");
			}//if
		}//if
	}//while


//	sleep(5);
//}//while


$db_det->close();
$db_vms->close();


closelog();
