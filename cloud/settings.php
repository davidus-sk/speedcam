<?php
$locations_r = $db->fetchResult('SELECT rowid,* FROM locations');

while ($row = $locations_r->fetchArray()) {
?>

<div class="row mb-4">
	<div class="col-md-12">
		<div class="card">
			<div class="card-header">
				<b><?php echo $row['name']; ?></b>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						Speed limit:
					</div>
					<div class="col-md-6">
						<input type="text" name="speedlimit[<?php echo $row['rowid']; ?>]" value="<?php echo $row['speedlimit']; ?>" />
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
}//while
?>
