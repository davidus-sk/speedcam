#!/usr/bin/php
<?php
// run at boot up
// look for radar devices
// create dev file enumerating them and their position

// vars
$state = 0;
$tty = null;
$data = [];
$conf = [];

openlog("detect_radars.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

// read config file
$conf_file = "/app/speed/config.json";

if (file_exists($conf_file) && filesize($conf_file) > 0) {
	$file = file_get_contents($conf_file);
	$conf = json_decode($file, TRUE);
	syslog(LOG_INFO, "Read config file from $conf_file.");
} else {
	syslog(LOG_ERR, "Config file $conf_file missing.");
	exit();
}//if

// get devices
$serial_ports = `python3 -m  serial.tools.list_ports -v`;

//process
$lines = explode("\n", $serial_ports);

foreach ($lines as $line) {
	// /dev/ttyUSB4
	if ($state == 0) {
		if (preg_match("@(/dev/ttyUSB[0-9]+)@", $line, $m)) {
			$state = 1;
			$tty = $m[1];
			continue;
		}//if
	}//if

	if ($state == 1) {
		if (preg_match("@CP2102N@", $line)) {
			$state = 2;
			continue;
		}//if

		$state = 0;
	}//if

	if ($state == 2) {
		if (preg_match("@SER=([a-z0-9]+)@", $line, $m)) {
			$side = null;

			foreach ($conf as $s => $settings) {
				if (isset ($settings['radar']) && $settings['radar'] == $m[1]) {
					$side = $s;
				}//if
			}//foreach

			$data[$tty] = ["serial" => $m[1], "location" => $side];
		}//if

		$state = 0;
	}//if
}//foreach

// write to file
file_put_contents("/tmp/radars.dev", json_encode($data));

syslog(LOG_INFO, "Found these radars: " . json_encode($data));

closelog();
