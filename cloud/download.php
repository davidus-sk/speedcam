<?php
// include DB
include 'DB.php';
$db = new DB('speed_cloud.db');

// get params
$tf = $_GET['tf'];

// time frames
$dt = new DateTime('now', new DateTimeZone("America/New_York"));

// decide what to do
switch ($tf) {
  // day
  case 'day': {
    $result = $db->fetchResult('SELECT * FROM detections WHERE month=? AND day=? AND year=?', [$dt->format('n'), $dt->format('j'), $dt->format('Y')]);
  } break;

  // week
  case 'week': {
    $dtw = new DateTime('Monday this week 00:00:00', new DateTimeZone("America/New_York"));
    $db->fetchResult('SELECT * FROM detections WHERE ts >= ? AND ts < ?', [$dtw->getTimestamp(), $dtw->getTimestamp()+604800]);
  } break;

  // error
  default: {
    echo "ERROR";
    exit();
  }
}//switch

header('Content-Description: File Transfer');
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=speed-report-' . $dt->format("Ymd_His") . '.csv'); 

// spit out data
echo "Year,Month,Day,Time,Speed,Direction\r\n";
  
while($row = $result->fetchArray()) {
  $speed = round($row['speed'] * 0.621372);
  $d = new DateTime('now', new DateTimeZone("America/New_York"));
  $d->setTimestamp($row['ts']);
  $time = $d->format("H:i:s");
  
  echo "{$row['year']},{$row['month']},{$row['day']},{$time},{$speed},{$row['direction']}\r\n";
}//while
