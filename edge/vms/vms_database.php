#!/usr/bin/php
<?php

// load libraries
include dirname(__FILE__) . '/../DB.php';
require(dirname(__FILE__) . '/../../common/functions.php');

// run once
run_once('/tmp/vms_database.pid', $fh);

// db
$db = new DB('/data/vms_videos.db');
$db->query("CREATE TABLE IF NOT EXISTS videos (ts_from INTEGER, ts_to INTEGER, filename TEXT, camera INTEGER)");

foreach (glob("/data/vms/*.mp4") as $path) {
	// check if file exists
	$filename = basename($path);

	$row = $db->fetchRow('SELECT * FROM videos WHERE filename = ?', [$filename]);

	if ($row === false) {
		if (preg_match("/([0-9])_([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)/", $filename, $m)) {
			$camera = $m[1];
			$date = "{$m[2]}-{$m[3]}-{$m[4]}";
			$time = "{$m[5]}:{$m[6]}:{$m[7]}";

			$dt = new DateTime("{$date} {$time}", new DateTimeZone("America/New_York"));
			$ts = $dt->getTimestamp();

			$duration = (float)trim(`/usr/bin/ffprobe -i {$path} -show_entries format=duration -v quiet -of csv="p=0"`);

			// save
			$db->query('INSERT INTO videos VALUES (?, ?, ?, ?)', [$ts, $ts + round($duration), $filename, $camera]);

			echo "Inserting new video into DB: file={$filename}, from={$ts}, duration={$duration}\n";
		}//if
	} else {
		// too old - delete the file
		if ($row['ts_from'] < (time() - 86400)) {
			if (file_exists($path)) {
				unlink($path);
			}//if

			$db->query("DELETE FROM videos WHERE filename = ?", [$filename]);

			echo "Deleting file from DB and drive: file={$filename}\n";
		}//if
	}//if
}//foreach

unset($db);
