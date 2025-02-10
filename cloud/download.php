<?php
// include DB
include 'DB.php';
$db = new DB('speed_cloud.db');

// get params
$tf = $_GET['tf'];
$week = (int)$_GET['week'];
$year = (int)$_GET['year'];

// headers
header('Content-Description: File Transfer');
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=speed-report-' . $tf . '-' . date("Ymd_His") . '.csv'); 


// decide what to do
switch ($tf) {
  // day - broken down by hour
  case 'hour': {
    if (empty($week) || empty($year)) {
      $dt = new DateTime('now', new DateTimeZone("America/New_York"));
      $result = $db->fetchResult('SELECT hour, COUNT(rowid) AS cnt FROM detections WHERE month=? AND day=? AND year=? GROUP BY hour', [$dt->format('n'), $dt->format('j'), $dt->format('Y')]);

      echo "Report for " . $dt->format('Y-m-d') . "\r\n";
    } else {
      $dt = new DateTime($year . 'W' . sprintf("%02d", $week) . ' 00:00:00', new DateTimeZone("America/New_York"));
      $result = $db->fetchResult('SELECT hour, COUNT(rowid) AS cnt FROM detections WHERE ts >= ? AND ts < ? GROUP BY hour', [$dt->getTimestamp(), $dt->getTimestamp()+604800]);

      echo "Report for " . $dt->format('Y') . " week #" . $dt->format('W') . "\r\n";
    }//if

    echo "Hour,Count\r\n";

    while ($row = $result->fetchArray()) {
      echo "{$row['hour']},{$row['cnt']}\r\n";
    }//while
  } break;

  // week - broken down by DoW
  case 'day': {
    if (empty($week) || empty($year)) {
      $dtw = new DateTime('Monday this week 00:00:00', new DateTimeZone("America/New_York"));
    } else {
      $dtw = new DateTime($year . 'W' . sprintf("%02d", $week) . ' 00:00:00', new DateTimeZone("America/New_York"));
    }

    $result = $db->fetchResult('SELECT * FROM detections WHERE ts >= ? AND ts < ?', [$dtw->getTimestamp(), $dtw->getTimestamp()+604800]);

    echo "Report for " . $dtw->format('Y') . " week #" . $dtw->format('W') . "\r\n";
    echo "Day,Count\r\n";
    $data = [];

    while ($row = $result->fetchArray()) {
      $dtw->setTimestamp($row['ts']);
      $data[$dtw->format('l')] = empty($data[$dtw->format('l')]) ? 1 : $data[$dtw->format('l')] + 1;
    }//while

    foreach ($data as $day=>$cnt) {
      echo "{$day},{$cnt}\r\n";
    }//foreach
  } break;

  // all
  case 'all': {
    // spit out data
    echo "Year,Month,Day,Time,Speed,Direction\r\n";

    while($row = $result->fetchArray()) {
      $speed = round($row['speed'] * 0.621372);
      $d = new DateTime('now', new DateTimeZone("America/New_York"));
      $d->setTimestamp($row['ts']);
      $time = $d->format("H:i:s");

      echo "{$row['year']},{$row['month']},{$row['day']},{$time},{$speed},{$row['direction']}\r\n";
    }//while
  } break;

  // error
  default: {
    echo "ERROR";
    exit();
  }
}//switch


