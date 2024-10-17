<?php
include 'DB.php';
$db = new DB('speed_cloud.db');

// get counts today and yesterday
$count_today_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE month=? AND day=? AND year=? GROUP BY hour', [date('n'), date('j'), date('Y')]);
$count_yesterday_r = $db->fetchResult('SELECT hour, count(ts) as cnt FROM detections WHERE month=? AND day=? AND year=? GROUP BY hour', [date('n', time() - 86400), date('j', time() - 86400), date('Y', time() - 86400)]);


// get data into arrays
$count_today = [];
$count_yesterday = [];

while ($row = $count_today_r->fetchArray()) {
	$count_today[$row['hour']] = $row['cnt'];
}//while

while ($row = $count_yesterday_r->fetchArray()) {
	$count_yesterday[$row['hour']] = $row['cnt'];
}//while

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SHAME System Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body>
    <div class="container">
      <div class="p-4 p-md-5 mb-4 rounded text-body-emphasis bg-body-secondary">
        <div class="col-lg-6 px-0">
          <h1 class="display-4 fst-italic">Title of a longer featured blog post</h1>
          <p class="lead my-3">Multiple lines of text that form the lede, informing new readers quickly and efficiently about what’s most interesting in this post’s contents.</p>
          <p class="lead mb-0"><a href="#" class="text-body-emphasis fw-bold">Continue reading...</a></p>
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              Speeding Detections by Hour (today)
            </div>
            <div class="card-body">
              <canvas id="g_cnt_today" style="width:100%"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-md-12">
            <div class="card">
            <div class="card-header">
              Speeding Detections by Hour (week)
            </div>
            <div class="card-body">
              <h5 class="card-title">Special title treatment</h5>
              <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
              <a href="#" class="btn btn-primary">Go somewhere</a>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              Speeding Detections by Day
            </div>
            <div class="card-body">
              <h5 class="card-title">Special title treatment</h5>
              <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
              <a href="#" class="btn btn-primary">Go somewhere</a>
            </div>
          </div>  
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              Speed Range Distribution
            </div>
            <div class="card-body">
              <h5 class="card-title">Special title treatment</h5>
              <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
              <a href="#" class="btn btn-primary">Go somewhere</a>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              Average Speed by Day
            </div>
            <div class="card-body">
              <h5 class="card-title">Special title treatment</h5>
              <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
              <a href="#" class="btn btn-primary">Go somewhere</a>
            </div>
          </div>  
        </div>
      </div>
  </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>

  	<script>
  	const count_today_x = <?php echo json_encode(array_keys($count_today)); ?>;
  	const count_today_y = <?php echo json_encode(array_values($count_today)); ?>;
  	const count_yesterday_y = <?php echo json_encode(array_values($count_yesterday)); ?>;
  
    new Chart("g_count_today", {
      type: "bar",
      data: {
        labels: count_today_x,
        datasets: [{
          data: count_today_y,
          backgroundColor: "#2196F3"
        },
    	      {
          data: count_yesterday_y,
          backgroundColor: "#8acbff"
        }]
      },
      options: {
        legend: {display: false},
        title: {
          display: false,
        },
        scales: {
          xAxes: [{
            gridLines: {
              display: false // Disables grid lines on the x-axis
            }
          }],
        }
      }
    });
    </script>
  </body>
</html>
