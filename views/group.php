<?php
$html = '';
$html.= form_open($_SERVER['REQUEST_URI']);
$html.= form_hidden('group', $group['id']);

if (!$group) {
	$html.= form_hidden('grouptype', $group['type']);
}

$html.= heading($group ? _("Edit Group") : _("Add Group"));

if (!empty($message)) {
	$html.= '<div class="alert alert-' . $message['type'] . '">' . $message['message'] . '</div>';
}

if ($group) {
	$html.= '<p><a href="config.php?display=contactmanager&action=delgroup&group=' . $group['id'] . '">
		<i class="fa fa-trash-o fa-fw"></i>' . sprintf(_('Delete Group: %s'), $group['name']) . '</a></p>';
}

$table = new CI_Table;

if (!$group) {
	$grouptypes = array(
		'internal' => _('Internal'),
		'external' => _('External'),
		'userman' => _('User Manager'),
	);
	$label = fpbx_label(_('Type'), _('Type of group'));
	$table->add_row($label, form_dropdown('grouptype', $grouptypes, 'internal'));
}

$label = fpbx_label(_('Name'), _('Name of group'));
$table->add_row($label, form_input('groupname', $group['name']));

$html.= $table->generate();

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
		$html.= '<th>Name</th>';
		$html.= '<th>User</th>';
		break;
	case 'external':
		$html.= '<th></th>';
		$html.= '<th>Name</th>';
		$html.= '<th>Company</th>';
		$html.= '<th>Numbers</th>';
		break;
	case 'userman':
		$html.= '<th></th>';
		$html.= '<th>User</th>';
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
$html.= form_submit('submit', _('Submit'));

$html.= form_close();

$html.= '<script language="javascript">
	$("form").submit(function(event) {
		if ($("input[name=groupname]").val() == "") {
			alert("Group name cannot be blank.");
			event.preventDefault();
		}
	});
</script>';

echo $html;

if ($group) {
	echo br(2);
	echo '<a href="' . $_SERVER['PHP_SELF'] . '?type=tool&display=contactmanager&action=export&group=' . $group['id'] . '&quietmode=1">' . _("Export CSV") . '</a>';

	switch ($group['type']) {
	case "internal":
	case "external":
		$html = '';
		$html.= form_open_multipart($_SERVER['REQUEST_URI']);
		$html.= form_hidden('group', $group['id']);
		$html.= form_hidden('action', 'import');
		$html.= form_hidden('MAX_FILE_SIZE', '30000');

		$html.= heading(_("Import CSV"), 3);

		$html.= form_upload('csv');
		$html.= form_submit('upload', _('Upload'));

		$html.= form_close();

		echo $html;

		break;
	}
}
?>
