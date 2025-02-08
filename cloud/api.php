<?php
// include DB
include 'DB.php';
$db = new DB('speed_cloud.db');

// check if GET request
// if get, retrieve latest settings and exit
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$location = empty($_GET['location']) ? null : $_GET['location'];
	
	if (!is_null($location)) {
		$r = $db->fetchRow('SELECT * FROM locations WHERE rowid = ' . $location);
		echo json_encode(['status' => 'OK', 'speedlimit' => $r['speedlimit'], 'flashers' => (bool)$r['flashers']]);
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
$location = empty($_POST['location']) ? null : $_POST['location'];
$plate = empty($_FILES['plate']) ? null : $_FILES['plate'];
$car = empty($_FILES['car']) ? null : $_FILES['car'];
$storage = empty($_POST['storage']) ? null : $_POST['storage'];

// save location data
if (!is_null($storage)) {
  $db->query('UPDATE locations SET storage = ? WHERE rowid = ?', [$storage, $location]);
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
  $row = $db->fetchRow('SELECT * FROM detections WHERE camera = ? AND location = ? AND ts = ?', [$camera, $location, $ts]);

  if ($row != false) {
    echo json_encode(['status' => 'ERROR', 'msg' => 'EXISTS', 'method' => $_SERVER['REQUEST_METHOD']]);
    exit();
  }//if
  
  // store data
  $db->query('INSERT INTO detections VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$ts, (int)$dt->format("n"), (int)$dt->format("j"), (int)$dt->format("G"), (int)$dt->format("Y"), $camera, $radar, $speed, $direction, $location, null, null, null]);
  $insertId = $db->lastInsertId();

  if ($insertId) {
    $r = $db->fetchRow('SELECT * FROM locations WHERE rowid = ' . $location);
    echo json_encode(['status' => 'OK', 'rowid' => $insertId, 'speedlimit' => $r['speedlimit'], 'flashers' => (bool)$r['flashers']]);
  } else {
    echo json_encode(['status' => 'ERROR', 'msg' => 'NO ROW ID', 'method' => $_SERVER['REQUEST_METHOD']]);
  }//if

  exit();
}//if

echo json_encode(['status' => 'ERROR', 'msg' => 'MISSING DATA', 'method' => $_SERVER['REQUEST_METHOD']]);
