<?php
$html = '';
$html.= form_open($_SERVER['REQUEST_URI']);
$html.= form_hidden('group', $group['id']);

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'showgroup') {
	$newgroup = false;
} else {
	$newgroup = true;
}

if (!$newgroup) {
	$html.= form_hidden('grouptype', $group['type']);
}

$html.= heading($newgroup ? _("Add Group") : _("Edit Group"));

if (!empty($message)) {
	$html.= '<div class="alert alert-' . $message['type'] . '">' . $message['message'] . '</div>';
}

if (!$newgroup) {
	$html.= '<p><a href="config.php?display=contactmanager&action=delgroup&group=' . $group['id'] . '"><span>
			<img width="16" height="16" border="0" src="images/core_delete.png">' . sprintf(_('Delete Group: %s'), $group['name']) . '</span></a></p>';
}

$table = new CI_Table;

if ($newgroup) {
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

$html.= br(2);

if (!$newgroup) {
	$userlist[''] = '';
	foreach ($users as $user) {
		if ($user['description']) {
			$desc = $user['description'] . ' (' . $user['username'] . ')';
		} else if ($user['fname'] && $user['lname']) {
			$desc = $user['fname'] . ' ' . $user['lname'] . ' (' . $user['username'] . ')';
		} else {
			$desc = '(' . $user['username'] . ')';
		}

		$userlist[$user['id']] = $desc;
	}

	$html.= '<table id="entries">';
	switch ($group['type']) {
	case 'internal':
		$html.= '<tr><th>User</th></tr>';
		break;
	case 'external':
		$html.= '<tr><th>Numbers</th><th>First Name</th><th>Last Name</th></tr>';
		break;
	}
	$count = 0;
	foreach ($entries as $entry) {
		$html.= '<tr id="entry_' . $count . '">';

		switch ($group['type']) {
		case 'internal':
			$html.= '<td>' . form_dropdown('user[' . $count . ']', $userlist, $entry['user']) . '</td>';
			break;
		case 'external':
			$html.= '<td>';
			$html.= '<span id="numbers_' . $count . '">';
			$numcount = 0;
			foreach ($entry['numbers'] as $number) {
				$html.= form_input('number[' . $count . '][' . $numcount . ']', $number['number']);
				$html.= br(1);

				$numcount++;
			}
			$html.= '</span>';
			$html.= '</td>';
			$html.= '<td style="vertical-align:top">' . form_input('fname[' . $count . ']', $entry['fname']) . '</td>';
			$html.= '<td style="vertical-align:top">' . form_input('lname[' . $count . ']', $entry['lname']) . '</td>';
			break;
		}

		$html.= '<td><img src="images/core_add.png" style="cursor:pointer" alt="' . _("insert") . '" title="' . _("Click here to insert a new entry") . '" onclick="addEntry()">';
		$html.= '<td><img src="images/trash.png" style="cursor:pointer" alt="' . _("remove") . '" title="' . _("Click here to remove this entry") . '" onclick="delEntry(' . $count . ')">';
		$html.= '</tr>';

		$count++;
	}
	$html.= '</table>';

	$html.= form_button('entry-add', _('Add Entry'));
}

$html.= br(2);

$html.= form_submit('submit', _('Submit'));

$html.= '<script language="javascript">
$(document).ready(function() {
	$("button[name=entry-add]").click(function() {
		addEntry();
	});
});

function addEntry() {
	lastid = $("#entries tr[id^=\"entry_\"]:last-child").attr("id");
	if (lastid) {
		index = lastid.substr(6); /* Everything after "entry_" */
		index++;
	} else {
		index = 0;
	}

	row = "<tr id=\"entry_" + index + "\">";
';

switch ($group['type']) {
case 'internal':
	$html.= '
	row+= "<td><select name=\"user[" + index + "]\">"
	';
	foreach ($userlist as $id => $user) {
		$html.= '
		row+= "<option value=\"' . $id . '\">' . $user . '</option>"
		';
	}
	$html.= '
	row+= "</select></td>";
	';
	break;
case 'external':
	$html.= '
	row+= "<td><input type=\"text\" name=\"number[" + index + "][0]\" value=\"\"/></td>";
	row+= "<td style=\"vertical-align:top\"><input type=\"text\" name=\"fname[" + index + "]\" value=\"\"/></td>";
	row+= "<td style=\"vertical-align:top\"><input type=\"text\" name=\"lname[" + index + "]\" value=\"\"/></td>";
	';
	break;
}

$html.= '
	row+= "<td><img src=\"images/core_add.png\" style=\"cursor:pointer\" alt=\"' . _("insert") . '\" title=\"' . _("Click here to insert a new entry") . '\" onclick=\"addEntry()\">";
	row+= "<td><img src=\"images/trash.png\" style=\"cursor:pointer\" alt=\"' . _("remove") . '\" title=\"' . _("Click here to remove this entry") . '\" onclick=\"delEntry(" + index + ")\">";
	row+= "</tr>";

	$("#entries").append(row);
}
function delEntry(index) {
	$("#entry_" + index).remove();
}
</script>';

$html.= form_close();

echo $html;
?>
