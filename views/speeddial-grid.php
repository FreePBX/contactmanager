<h1><?php echo _("Speed Dials")?></h1>
<?php if(!$speeddialcode['enabled']) { ?>
	<div class="alert alert-info"><?php echo _("Speed Dial Feature code has been disabled in Feature Code Admin")?></div>
<?php } else { ?>
	<div id="toolbar-sd">

	</div>
	<table id="sd-grid" data-url="ajax.php?module=contactmanager&amp;command=sdgrid" data-cache="false" data-toolbar="#toolbar-sd" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped">
		<thead>
			<tr>
				<th data-field="speeddial" data-formatter="speeddialformat" data-sortable="true"><?php echo _("Speed Dial Feature Code")?></th>
				<th data-field="number" data-sortable="true"><?php echo _("Number")?></th>
				<th data-field="displayname" data-sortable="true"><?php echo _("Display Name")?></th>
				<th data-field="fname" data-sortable="true"><?php echo _("First Name")?></th>
				<th data-field="lname" data-sortable="true"><?php echo _("Last Name")?></th>
				<th data-formatter="userActions"><?php echo _("Action") ?></th>
			</tr>
		</thead>
	</table>
	<script>
		var speeddialcode = <?php echo json_encode($speeddialcode)?>;
	</script>
<?php } ?>
