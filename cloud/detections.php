<?php
// get detections for the last week
$detections = $db->fetchAllAssoc($db->query('SELECT * FROM detections WHERE ts >= ' . $dtw->getTimestamp() . ' AND ts < ' . ($dtw->getTimestamp()+604800) . ' AND location_id = ' . $location . ' ORDER BY ts DESC'));
?>

<div class="row mb-4">
	<div class="col-md-12">
		<div class="card">
			<div class="card-header">
				<b>Detections Week #<?php echo $dtw->format('W'); ?></b>
				<a href="/download.php?tf=all&week=<?php echo $week; ?>&year=<?php echo $year; ?>" class="float-end">Download CSV (Week)</a>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table">
						<tr>
							<th>Date</th>
							<th>Time</th>
							<th>Speed</th>
							<th>Direction</th>
							<th>Video</th>
							<th>Image</th>
							<th>Plate</th>
						</tr>

						<?php
						foreach ($detections as $row) {
							$d = new DateTime('now', new DateTimeZone("America/New_York"));
							$d->setTimestamp($row['ts']);
						?>

						<tr>
							<td><?php echo $d->format('m/d/Y'); ?></td>
							<td><?php echo $d->format('h:i A'); ?></td>
							<td><?php echo floor($row['speed'] * 0.621372); ?> mph</td>
							<td><?php echo $row['direction']; ?></td>
							<td><?php echo empty($row['video']) ? '-' : '<a href="/videos/' . $row['video'] . '">play</a>'; ?></td>
							<td><?php echo empty($row['image']) ? '-' : $row['image']; ?></td>
							<td><?php echo empty($row['plate']) ? '-' : $row['plate']; ?></td>
						</tr>

						<?php
						}//while
						?>

					</table>
				</div>
			</div>
		</div>
	</div>
</div>
