<div class="fpbx-container">
	<div class="display full-border">
		<form name="group" class="fpbx-submit" method="post" action="config.php?display=contactmanager" <?php if(isset($group['id'])) {?>data-fpbx-delete="config.php?display=contactmanager&amp;group=<?php echo $group['id']?>&amp;action=delgroup<?php }?>">
			<?php if(!empty($group)) {?>
				<input type="hidden" name="group" id="group" value="<?php echo $group['id']?>">
				<input type="hidden" name="grouptype" id="grouptype" value="<?php echo $group['id']?>">
				<h1><?php echo _("Edit Group")?></h1>
			<?php } else { ?>
				<input type="hidden" name="group" id="group" value="">
				<h1><?php echo _("Add Group")?></h1>
			<?php }?>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="groupname"><?php echo _('Name')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="groupname"></i>
								</div>
								<div class="col-md-9"><input id="groupname" name="groupname" class="form-control" value="<?php echo $group['name']?>"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="groupname-help" class="help-block fpbx-help-block"><?php echo _('Name of group')?></span>
					</div>
				</div>
			</div>
			<?php if(empty($group)) {?>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="grouptype"><?php echo _('Type')?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="grouptype"></i>
									</div>
									<div class="col-md-9">
										<select name="grouptype" id="grouptype" class="form-control">
											<option value="internal"><?php echo _('Internal')?></option>
											<option value="external"><?php echo _('External')?></option>
											<option value="userman"><?php echo _('User Manager')?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="grouptype-help" class="help-block fpbx-help-block"><?php echo _('Type of group')?></span>
						</div>
					</div>
				</div>
			<?php } ?>
		</form>
	</div>
</div>
<?php
return;
if ($group) {
	foreach ($users as $user) {
		$desc = NULL;
		if ($user['displayname']) {
			$desc = $user['displayname'];
		} else if ($user['fname'] && $user['lname']) {
			$desc = $user['fname'] . ' ' . $user['lname'];
		} else if ($user['default_extension'] && $user['default_extension'] != 'none') {
			$desc = 'Ext. ' . $user['default_extension'];
		} else if ($user['description']) {
			$desc = $user['description'];
		}

		$userlist[$user['id']] = ($desc ? $desc . ' ' : '') . '(' . $user['username'] . ')';
	}

	$html.= '<table id="entries">';
	$html.= '<tr>';
	switch ($group['type']) {
	case 'internal':
		$html.= '<th></th>';
		$html.= '<th>'._("Name").'</th>';
		$html.= '<th>'._("User").'</th>';
		break;
	case 'external':
		$html.= '<th></th>';
		$html.= '<th>'._("Name").'</th>';
		$html.= '<th>'._("Company").'</th>';
		$html.= '<th>'._("Numbers").'</th>';
		break;
	case 'userman':
		$html.= '<th></th>';
		$html.= '<th>'._("User").'</th>';
		break;
	}
	$html.= '</tr>';

	$numbertypes = array(
		'work' => _('Work'),
		'home' => _('Home'),
		'cell' => _('Cell'),
		'other' => _('Other'),
	);

	$count = 0;
	foreach ($entries as $id => $entry) {
		$html.= '<tr>';

		switch ($group['type']) {
		case 'internal':
			$html.= '<td>';
			$html.= '<a href="config.php?display=contactmanager&action=showentry&group=' . $group['id'] . '&entry=' . $id . '"><i class="fa fa-edit fa-fw"></i></a>';
			$html.= '<a href="config.php?display=contactmanager&action=delentry&group=' . $group['id'] . '&entry=' . $id . '"><i class="fa fa-ban fa-fw"></i></a>';
			$html.= '</td>';

			$html.= '<td>' . ($entry['displayname'] ? $entry['displayname'] : $entry['fname'] . ' ' . $entry['lname']) . '</td>';

			$html.= '<td>';
			$html.= $userlist[$entry['user']];
			$html.= '</td>';
			break;
		case 'external':
			$html.= '<td>';
			$html.= '<a href="config.php?display=contactmanager&action=showentry&group=' . $group['id'] . '&entry=' . $id . '"><i class="fa fa-edit fa-fw"></i></a>';
			$html.= '<a href="config.php?display=contactmanager&action=delentry&group=' . $group['id'] . '&entry=' . $id . '"><i class="fa fa-ban fa-fw"></i></a>';
			$html.= '</td>';
			$html.= '<td>' . ($entry['displayname'] ? $entry['displayname'] : $entry['fname'] . ' ' . $entry['lname']) . '</td>';
			$html.= '<td>' . $entry['company'] . '</td>';

			$html.= '<td>';
			$html.= '<span id="numbers_' . $count . '">';
			$numcount = 0;
			foreach ($entry['numbers'] as $number) {
				$html.= $number['number'] . ($number['extension'] ? ' x' . $number['extension'] : '') . ' (' . $numbertypes[$number['type']] . ')';
				$html.= br(1);

				$numcount++;
			}
			$html.= '</span>';
			$html.= '</td>';
			break;
		case 'userman':
			$html.= '<td>';
			$html.= '<a href="config.php?display=userman&action=showuser&user=' . $entry['user'] . '"><i class="fa fa-edit fa-fw"></i></a>';
			$html.= '</td>';

			$html.= '<td>';
			$html.= $userlist[$entry['user']];
			$html.= '</td>';
			break;
		}

		$html.= '</tr>';

		$count++;
	}
	$html.= '</table>';

	switch ($group['type']) {
	case 'internal':
	case 'external':
		$html.= '<a href="config.php?display=contactmanager&action=addentry&group=' . $group['id'] . '"><i class="fa fa-plus fa-fw"></i>Add Entry</a>';
		break;
	}
}

$html.= br(2);

$html.= form_close();

$html.= '<script language="javascript">
	$("form[name=\"group\"]").submit(function(event) {
		if ($("input[name=groupname]").val() == "") {
			alert("Group name cannot be blank.");
			event.preventDefault();
		}
	});
</script>';

echo $html;
?>
