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
	);
	$label = fpbx_label(_('Type'), _('Type of group'));
	$table->add_row($label, form_dropdown('grouptype', $grouptypes, 'internal'));
}

$label = fpbx_label(_('Name'), _('Name of group'));
$table->add_row($label, form_input('groupname', $group['name']));

$html.= $table->generate();

if ($group) {
	foreach ($users as $user) {
		if ($user['fname'] && $user['lname']) {
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
	$html.= '<th></th>';
	$html.= '<th>Name</th>';
	switch ($group['type']) {
	case 'internal':
		$html.= '<th>User</th>';
		break;
	case 'external':
		$html.= '<th>Numbers</th>';
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

		$html.= '<td>';
		$html.= '<a href="config.php?display=contactmanager&action=showentry&group=' . $group['id'] . '&entry=' . $id . '"><i class="fa fa-edit fa-fw"></i></a>';
		$html.= '<a href="config.php?display=contactmanager&action=delentry&group=' . $group['id'] . '&entry=' . $id . '"><i class="fa fa-ban fa-fw"></i></a>';
		$html.= '</td>';

		$html.= '<td>' . $entry['fname'] . ' ' . $entry['lname'] . '</td>';

		switch ($group['type']) {
		case 'internal':
			$html.= '<td>';
			$html.= $userlist[$entry['user']];
			$html.= '</td>';
			break;
		case 'external':
			$html.= '<td>';
			$html.= '<span id="numbers_' . $count . '">';
			$numcount = 0;
			foreach ($entry['numbers'] as $number) {
				$html.= $number['number'] . ' (' . $numbertypes[$number['type']] . ')';
				$html.= br(1);

				$numcount++;
			}
			$html.= '</span>';
			$html.= '</td>';
			break;
		}

		$html.= '</tr>';

		$count++;
	}
	$html.= '</table>';

	$html.= '<a href="config.php?display=contactmanager&action=addentry&group=' . $group['id'] . '"><i class="fa fa-plus fa-fw"></i>Add Entry</a>';
}

$html.= br(2);
$html.= form_submit('editgroup', _('Submit'));

$html.= form_close();

echo $html;
?>
