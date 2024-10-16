<?php
// include DB
include 'DB.php';
$db = new DB('speed_cloud.db');

// get posted data
$camera = empty($_POST['camera']) ? null : (int)$_POST['camera'];
$speed = empty($_POST['speed']) ? null : (float)$_POST['speed'];
$ts = empty($_POST['ts']) ? null : $_POST['ts'];
$radar = empty($_POST['radar']) ? null : $_POST['radar'];
$plate = empty($_FILES['plate']) ? null : $_FILES['plate'];
$car = empty($_FILES['car']) ? null : $_FILES['car'];

// save data
if (!is_null($camera) && !is_null($speed) && !is_null($ts) && !is_null($radar)) {
  // date time
  $dt = new DateTime("@{$ts}", new DateTimeZone("America/New_York"));
  
  // init the schema
  $db->createSchemas();

  // store data
  // (time, month INTEGER, day INTEGER, hour INTEGER, year INTEGER, camera INTEGER, radar, speed REAL, plate, image1, image2)
  $db->query('INSERT INTO detections VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$ts, (int)$dt->format("n"), (int)$dt->format("j"), (int)$dt->format("G"), (int)$dt->format("Y"), $camera, $radar, $speed, null, null, null]);

  echo "OK: {$db->lastInsertId()}";
  exit();
}//if

echo "ERROR: {$camera}, {$speed}, {$ts}, {$radar}";
