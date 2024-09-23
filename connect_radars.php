#!/usr/bin/php
<?php

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

while(TRUE) {
	// read config
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
			$pid = `/usr/bin/pgrep -f "[r]ead_radar.py {$tty}"`;

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
