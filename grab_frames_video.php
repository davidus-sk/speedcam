#!/usr/bin/php
<?php

// get command line params
$side = $argv[1];
$fps = empty($argv[2]) ? 10 : (int)$argv[2];

$lockFile = fopen("/tmp/grab_frames_video_{$side}.pid", 'c');
$gotLock = flock($lockFile, LOCK_EX | LOCK_NB, $wouldBlock);
if ($lockFile === false || (!$gotLock && !$wouldBlock)) {
        throw new Exception("Can't obtain lock.");
} else if (!$gotLock && $wouldBlock) {
        exit();
}//if

ftruncate($lockFile, 0);
fwrite($lockFile, getmypid() . "\n");

// create log
openlog("grab_frames_video.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

// read settings
$conf_file = "/app/speed/config.json";
$config = [];
$camera = null;
$url = null;
$keep_files = 50;
$ts = time();

if (file_exists($conf_file) && filesize($conf_file) > 0) {
	$json = file_get_contents($conf_file);

	if (!($config = json_decode($json, TRUE))) {
		exit();
	}//if

	$camera = $config[$side]["camera"];
	$url = str_replace("#channel#", $camera, $config["settings"]["camera"]["video_url"]);
}//if

// create needed dirs
$output_dir = "/dev/shm/ffmpeg/";

if (!is_dir($output_dir)) {
	mkdir($output_dir);
}//if

while (TRUE) {
	// check if ffmpeg running
	$pid = trim(`/usr/bin/pgrep -f "[i] {$url}"`);

	// not running, start
	if (empty($pid)) {
		$ts = time();
		syslog(LOG_INFO, "Starting ffmpeg for side {$side} at {$ts} for URL {$url}.");

		// start the recorder
		`/usr/bin/ffmpeg -hide_banner -y -rtsp_transport tcp -i {$url} -vf "drawtext=fontfile=/app/speed/camingo.ttf:fontsize=40:fontcolor=red:x=30:y=30:textfile=/tmp/{$camera}.osd:reload=1" -frames:v 500 -r {$fps} {$output_dir}{$camera}_{$ts}_%09d.jpg  > /dev/null 2>&1 &`;
	}//if

	// remove old images
	$files = glob($output_dir . "{$camera}_*");
	$file_count = count($files);

	if (($file_count - 2) > 0) {
		usort($files, function($a, $b) {
			$a = basename($a);
			$b = basename($b);

			// 0_1727576540_000000005.jpg
			$at = (int)substr($a, 2, 10);
			$bt = (int)substr($b, 2, 10);

			$af = $at + ((int)substr($a, 13, 9) / 1000);
			$bf = $bt + ((int)substr($b, 13, 9) / 1000);

			return $af > $bf;
		});

		$file_diff = $file_count - $keep_files;

		if ($file_diff > 0) {
			for ($i = 0; $i < $file_diff; $i++) {
				unlink($files[$i]);
			}//for
		}//if
	}//if

	sleep(1);
}//while
