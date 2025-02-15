<?php

// if we have post, save data
if (isset($_POST['speedlimit'])) {
	foreach ($_POST['speedlimit'] as $k=>$v) {
		$db->query('UPDATE locations SET speed_limit = ' . round($v * 1.60934, 4) . ' WHERE location_id = ' . (int)$k);
	}//foreach
}//if

// if we have post, save data
if (isset($_POST['flashers'])) {
	foreach ($_POST['flashers'] as $k=>$v) {
		$db->query('UPDATE locations SET flashers = ' . (int)$v . ' WHERE location_id = ' . (int)$k);
	}//foreach
}//if

// get settings
$locations = $db->fetchAllAssoc($db->query('SELECT * FROM locations'));

// show editing forms
foreach ($locations as $row) {
?>

<form method="post" action="">

<div class="row mb-4">
	<div class="col-md-12">
		<div class="card">
			<div class="card-header">
				<b><?php echo $row['name']; ?></b>
			</div>
			<div class="card-body">
				<div class="row mb-4">
					<div class="col-md-6">
						Speed limit:
					</div>
					<div class="col-md-6">
						<div class="input-group">
							<input type="number" class="form-control" name="speedlimit[<?php echo $row['location_id']; ?>]" value="<?php echo round($row['speed_limit']*0.621371); ?>" />
							<span class="input-group-text" id="basic-addon2">mph</span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						Flashers:
					</div>
					<div class="col-md-6">
						<div class="input-group">
							<select name="flashers[<?php echo $row['location_id']; ?>]" class="form-control" style="display: block; width: 100%;">
								<option value="1" <?php echo $row['flashers'] == '1' ? 'selected' : ''; ?>>ON</option>
								<option value="0" <?php echo $row['flashers'] == '0' ? 'selected' : ''; ?>>OFF</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="mb-4 rounded bg-body-secondary">
	<div class="row">
		<div class="col-md-12">
			<p class="p-3 m-0 text-center"><button type="submit" class="btn btn-primary">Save Settings</button></p>
		</div>
	</div>
</div>

</form>

<?php
}//while
?>
