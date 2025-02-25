#!/usr/bin/php
<?php

// create log
openlog("vms_record.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

// read config
$conf = [];
$conf_file = "/app/speed/config.json";

if (file_exists($conf_file) && filesize($conf_file) > 0) {
	$file = file_get_contents($conf_file);
	$conf = json_decode($file, TRUE);
	syslog(LOG_INFO, "Read config file from $conf_file.");
} else {
	syslog(LOG_ERR, "Config file $conf_file missing.");
	exit();
}//if

$cams = [];
foreach(['right', 'left'] as $direction) {
	$cams[$direction] = ['camera' => $conf[$direction]['camera'], 'url' => str_replace('#channel#', $conf[$direction]['camera'], $conf['settings']['camera']['vms_url'])];
}

foreach ($cams as $cam) {
	// check if running
	$pid = trim(`/usr/bin/pgrep -f "\-[i] {$cam['url']}"`);

	if (empty($pid)) {
		syslog(LOG_INFO, "Starting capture for {$cam['url']}.");
		`/usr/bin/ffmpeg -hide_banner -rtsp_transport udp -fflags discardcorrupt -flags low_delay -r 15 -i {$cam['url']} -f segment -segment_time 5 -reset_timestamps 1 -strftime 1 -c copy "/data/vms/{$cam['camera']}_%Y-%m-%d_%H-%M-%S.mp4" > /data/logs/camera_{$cam['camera']}.log 2>&1 &`;
	}//if
}//foreach
