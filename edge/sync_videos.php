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

$db_det = new DB('/data/speed.db');
$db_vms = new DB('/data/vms_videos.db');

//while(TRUE) {
	$detections = $db_det->query('SELECT *,rowid FROM detections WHERE time >= ' . (time() - 300) . ' AND uploaded = 0 ORDER BY time DESC');

	while ($row = $detections->fetchArray()) {
		$video = $db_vms->fetchRow('SELECT * FROM videos WHERE camera = ' . $row['camera'] . ' AND ts_from <= ' . $row['time'] . ' AND ts_to >= ' . $row['time']);

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
				$db_det->query("UPDATE detections SET uploaded = 1 WHERE rowid = {$row['rowid']}");
				syslog(LOG_INFO, "File {$video['filename']} uploaded: " . $response);
			} else {
				syslog(LOG_ERR, "File {$video['filename']} not uploaded: " . $response);
			}//if
		}//if
	}//while


//	sleep(5);
//}//while

// close DB handles
unset($db_det);
unset($db_vms);

// close log
closelog();
