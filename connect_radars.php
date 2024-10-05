#!/usr/bin/php
<?php
/* ****************************************************************
Speed Camera - Radar watchdog loop

Laucnch radar processes to read detected speeds from serial
radar devices.

(C) 2024 LUCEON LLC
***************************************************************** */

// run only once
$lockFile = fopen('/tmp/connect_radars.pid', 'c');
$gotLock = flock($lockFile, LOCK_EX | LOCK_NB, $wouldBlock);
if ($lockFile === false || (!$gotLock && !$wouldBlock)) {
        throw new Exception("Can't obtain lock.");
} else if (!$gotLock && $wouldBlock) {
        exit();
}//if

ftruncate($lockFile, 0);
fwrite($lockFile, getmypid() . "\n");

// create log
openlog("connect_radars.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$read_config = FALSE;
$config = [];

// main loop to keep checking
// if radar processes run
while(TRUE) {
	// read config
	// it might not exist yet, the config is written by a discovery process
	// keep trying until the config file is created
	if (!$read_config) {
		$config_file = "/tmp/radars.dev";

		if (file_exists($config_file) && filesize($config_file) > 0) {
			$json = file_get_contents($config_file);

			if ($config = json_decode($json, TRUE)) {
				$read_config = TRUE;
				syslog(LOG_INFO, "Loaded radar config file from $config_file");
			}//if
		}//if
	} else {
		foreach ($config as $tty => $c) {
			// check if radar process is running
			$pid = `/usr/bin/pgrep -f "[r]ead_radar.py {$tty}"`;

			// if not running, start it and log it
			if (empty($pid)) {
				syslog(LOG_INFO, "Starting read loop script for {$tty} on the {$c['location']}");
				syslog(LOG_DEBUG, "/app/speed/read_radar.py {$tty} {$c['serial']}");

				`/app/speed/read_radar.py {$tty} {$c['serial']} > /dev/null 2>&1 &`;
			}//if
		}//foreach
	}//if

	// rest
	sleep(1);
}//while

closelog();
