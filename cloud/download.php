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
      $result = $db->fetchAllAssoc($db->query('SELECT hour, COUNT(detection_id) AS cnt FROM detections WHERE month = ' . (int)$dt->format('n') . ' AND day = ' . (int)$dt->format('j') . ' AND year = ' . (int)$dt->format('Y') . ' GROUP BY hour'));

      echo "Report for " . $dt->format('Y-m-d') . "\r\n";
    } else {
      $dt = new DateTime($year . 'W' . sprintf("%02d", $week) . ' 00:00:00', new DateTimeZone("America/New_York"));
      $result = $db->fetchAllAssoc($db->query('SELECT hour, COUNT(detection_id) AS cnt FROM detections WHERE ts >= ' . $dt->getTimestamp() . ' AND ts < ' . ($dt->getTimestamp()+604800) . ' GROUP BY hour'));

      echo "Report for " . $dt->format('Y') . " week #" . $dt->format('W') . "\r\n";
    }//if

    echo "Hour,Count\r\n";

    foreach ($result as $row) {
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

    $result = $db->fetchAllAssoc($db->query('SELECT * FROM detections WHERE ts >= ' . $dtw->getTimestamp() . ' AND ts < ' . ($dtw->getTimestamp()+604800)));

    echo "Report for " . $dtw->format('Y') . " week #" . $dtw->format('W') . "\r\n";
    echo "Day,Count\r\n";
    $data = [];

    foreach ($result as $row) {
      $dtw->setTimestamp($row['ts']);
      $data[$dtw->format('l')] = empty($data[$dtw->format('l')]) ? 1 : $data[$dtw->format('l')] + 1;
    }//while

    foreach ($data as $day=>$cnt) {
      echo "{$day},{$cnt}\r\n";
    }//foreach
  } break;

  // all
  case 'all': {
    if (empty($week) || empty($year)) {
      $dtw = new DateTime('Monday this week 00:00:00', new DateTimeZone("America/New_York"));
    } else {
      $dtw = new DateTime($year . 'W' . sprintf("%02d", $week) . ' 00:00:00', new DateTimeZone("America/New_York"));
    }

    $result = $db->fetchAllAssoc($db->query('SELECT * FROM detections WHERE ts >= ' . $dtw->getTimestamp() . ' AND ts < ' . ($dtw->getTimestamp()+604800)));

    // spit out data
    echo "Report for " . $dtw->format('Y') . " week #" . $dtw->format('W') . "\r\n";
    echo "Year,Month,Day,Time,Speed,Direction\r\n";

    while($result as $row) {
      $speed = round($row['speed'] * 0.621372);
      $dtw->setTimestamp($row['ts']);
      $time = $dtw->format("H:i:s");

      echo "{$row['year']},{$row['month']},{$row['day']},{$time},{$speed},{$row['direction']}\r\n";
    }//while
  } break;

  // error
  default: {
    echo "ERROR";
    exit();
  }
}//switch


