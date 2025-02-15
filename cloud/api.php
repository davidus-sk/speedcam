<?php
// read config
$conf_file = 'config.json';

if (file_exists($conf_file)) {
	$json = file_get_contents($conf_file);

	if ($conf = json_decode($json, TRUE)) {
	}//if
}//if

// include DB
include 'DB.php';
$db = new DB($conf['host'], $conf['username'], $conf['password'], $conf['database']);

// check if GET request
// if get, retrieve latest settings and exit
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$location = empty($_GET['location']) ? null : (int)$_GET['location'];

	if (!is_null($location)) {
		$row = $db->fetchAssoc($db->query('SELECT * FROM locations WHERE location_id = ' . $location));
		echo json_encode(['status' => 'OK', 'speedlimit' => $row['speed_limit'], 'flashers' => (bool)$row['flashers']]);
		exit();
	}//if

	echo json_encode(['status' => 'ERROR', 'msg' => 'MISSING DATA', 'method' => $_SERVER['REQUEST_METHOD']]);
	exit();
}//if

// get POST data and process
$camera = !isset($_POST['camera']) ? null : (int)$_POST['camera'];
$speed = empty($_POST['speed']) ? null : (float)$_POST['speed'];
$ts = empty($_POST['ts']) ? null : $_POST['ts'];
$radar = empty($_POST['radar']) ? null : $_POST['radar'];
$direction = empty($_POST['direction']) ? null : $_POST['direction'];
$location = empty($_POST['location']) ? null : (int)$_POST['location'];
$storage = empty($_POST['storage']) ? null : (int)$_POST['storage'];

// save location data
if (!is_null($storage)) {
	$db->query('UPDATE locations SET storage = ' . $storage . ' WHERE location_id = ' . $location);
}//if

// save data
if (!is_null($camera) && !is_null($speed) && !is_null($ts) && !is_null($radar) && !is_null($location)) {
  // date time
  $dt = new DateTime();
  $dt->setTimezone(new DateTimeZone("America/New_York"));
  $dt->setTimestamp($ts);

  // init the schema
  $db->createSchemas();

  // check if we have this entry already
  $row = $db->fetchAssoc($db->query('SELECT * FROM detections WHERE camera_id = ' . $camera . ' AND location_id = ' . $location . ' AND ts = ' . $ts));

  if (!empty($row)) {
    echo json_encode(['status' => 'ERROR', 'msg' => 'EXISTS', 'method' => $_SERVER['REQUEST_METHOD']]);
    exit();
  }//if

  // store data
  $db->query("INSERT INTO detections VALUES ({$location}, {$ts}, " . (int)$dt->format("Y") . ", " . (int)$dt->format("n") . ", " . (int)$dt->format("j") . ", " . (int)$dt->format("G") . ", {$camera}, {$radar}, {$speed}, '{$direction}', null, null, null");
  $insertId = $db->insertId();

  if ($insertId) {
    $row = $db->fetchAssoc($db->query('SELECT * FROM locations WHERE location_id = ' . $location));
    echo json_encode(['status' => 'OK', 'rowid' => $insertId, 'speedlimit' => $row['speed_limit'], 'flashers' => (bool)$row['flashers']]);
  } else {
    echo json_encode(['status' => 'ERROR', 'msg' => 'NO ROW ID', 'method' => $_SERVER['REQUEST_METHOD']]);
  }//if

  exit();
}//if

echo json_encode(['status' => 'ERROR', 'msg' => 'MISSING DATA', 'method' => $_SERVER['REQUEST_METHOD']]);
