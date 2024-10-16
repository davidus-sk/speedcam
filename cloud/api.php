<?php
// include DB
include 'DB.php';
$db = new DB('speed_cloud.db');

// get posted data
$camera = empty($_POST['camera']) ? null : (int)$_POST['camera'];
$speed = empty($_POST['speed']) ? null : (float)$_POST['speed'];
$ts = empty($_POST['ts']) ? null : $_POST['ts'];
$radar = empty($_POST['radar']) ? null : $_POST['radar'];
$direction = empty($_POST['direction']) ? null : $_POST['direction'];
$location = empty($_POST['location']) ? null : $_POST['location'];
$plate = empty($_FILES['plate']) ? null : $_FILES['plate'];
$car = empty($_FILES['car']) ? null : $_FILES['car'];

// save data
if (!is_null($camera) && !is_null($speed) && !is_null($ts) && !is_null($radar)) {
  // date time
  $dt = new DateTime();
  $dt->setTimezone(new DateTimeZone("America/New_York"));
  $dt->setTimestamp($ts);
  
  // init the schema
  $db->createSchemas();

  // store data
  $db->query('INSERT INTO detections VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$ts, (int)$dt->format("n"), (int)$dt->format("j"), (int)$dt->format("G"), (int)$dt->format("Y"), $camera, $radar, $speed, $direction, $location, null, null, null]);

  echo "OK: {$db->lastInsertId()}";
  exit();
}//if

echo "ERROR: {$camera}, {$speed}, {$ts}, {$radar}";
