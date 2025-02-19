#!/usr/bin/php
<?php

// create log
openlog("vms_wd.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

// read config
$conf = [];
$conf_file = "/app/speed/config.json";
$sleep = 1;

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

while (TRUE) {
	foreach ($cams as $cam) {
		// check if running
		$lines = trim(`/usr/bin/tail -n 2 /data/logs/camera_{$cam['camera']}.log`);

		// [segment @ 0x559c4052d0] Opening '/data/vms/0_2025-02-12_13-52-34.mp4' for writing
		if (preg_match("/_([0-9]{4})-([0-9]{2})-([0-9]{2})_([0-9]{2})-([0-9]{2})-([0-9]{2})/", $lines, $m)) {
			$date = "{$m[1]}-{$m[2]}-{$m[3]}";
			$time = "{$m[4]}:{$m[5]}:{$m[6]}";

			$dt = new DateTime("{$date} {$time}", new DateTimeZone("America/New_York"));
			$ts = $dt->getTimestamp();

			if ($ts < (time() - 10)) {
				syslog(LOG_INFO, "Last viedo file is older than 10 seconds. Killing process for: {$cam['url']}.");
				`/usr/bin/pkill -f "\-[i] {$cam['url']}"`;
				$sleep = 60;
			}//if
		}//if

		// if log is empty, restart
		if (empty($lines)) {
			syslog(LOG_INFO, "Log file is empty. Killing process for: {$cam['url']}.");
			`/usr/bin/pkill -f "\-[i] {$cam['url']}"`;
			$sleep = 60;
		}//if
	}//foreach

	// rest
	sleep($sleep);
	$sleep = 1;
}//while
