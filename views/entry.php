<?php
$html = '';
$html.= form_open($_SERVER['REQUEST_URI']);
$html.= form_hidden('group', $group['id']);
$html.= form_hidden('grouptype', $group['type']);
$html.= form_hidden('entry', $entry['id']);

$html.= heading('<a href="config.php?display=contactmanager&action=showgroup&group=' . $group['id'] . '">' . $group['name'] . '</a> - ' . ($entry ? _("Edit Entry") : _("Add Entry")), 2);

if (!empty($message)) {
	$html.= '<div class="alert alert-' . $message['type'] . '">' . $message['message'] . '</div>';
}

$table = new CI_Table;

$label = fpbx_label(_('First Name'), _('First Name'));
$table->add_row($label, form_input('fname', $entry['fname']));

$label = fpbx_label(_('Last Name'), _('Last Name'));
$table->add_row($label, form_input('lname', $entry['lname']));

$label = fpbx_label(_('Title'), _('Title'));
$table->add_row($label, form_input('title', $entry['title']));

$label = fpbx_label(_('Company'), _('Company'));
$table->add_row($label, form_input('company', $entry['company']));

$extrahtml = '';

switch ($group['type']) {
case "internal":
	$userlist[''] = '';
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

	$label = fpbx_label(_('User'), _('A user from the User Management module'));
	$table->add_row($label, form_dropdown('user', $userlist, $entry['user']));

	$extrahtml.= '<script language="javascript">
		$("form").submit(function(event) {
			if ($("select[name=user]").val() == "") {
				alert("An entry must have a user.");
				event.preventDefault();
			}
		});
	</script>';

	break;
case "external":
	$numbertypes = array(
		'work' => _('Work'),
		'home' => _('Home'),
		'cell' => _('Cell'),
		'other' => _('Other'),
	);

	$label = fpbx_label(_('Numbers'), _('A list of numbers belonging to this entry'));
	$table->add_row($label, '');

	$extrahtml.= '<table id="numbers">';

	$numcount = 0;
	foreach ($entry['numbers'] as $number) {
		$extrahtml.= '<tr id="number_' . $numcount . '">';

		$extrahtml.= '<td>';
		$extrahtml.= '<a href="#" onclick="delNumber(' . $numcount . ')"><i class="fa fa-ban fa-fw"></i></a>';
		$extrahtml.= '</td>';

		$extrahtml.= '<td>';
		$extrahtml.= form_input('number[' . $numcount . ']', $number['number']);
		$extrahtml.= form_dropdown('numbertype[' . $numcount . ']', $numbertypes, $number['type']);
		$extrahtml.= '</td>';

		$extrahtml.= '<td>';
		$extrahtml.= form_checkbox('sms[' . $numcount . ']', 1, in_array('sms', $number['flags'])) . _('SMS') . '<br>';
		$extrahtml.= form_checkbox('fax[' . $numcount . ']', 1, in_array('fax', $number['flags'])) . _('FAX');
		$extrahtml.= '</td>';

		$extrahtml.= '</tr>';

		$numcount++;
	}
	$extrahtml.= '</table>';

	$extrahtml.= '<a href="#" onclick="addNumber()"><i class="fa fa-plus fa-fw"></i>Add Number</a>';

	$extrahtml.= br(2);

	$extrahtml.= '<script language="javascript">
		$("form").submit(function(event) {
			$numbers = $("#numbers input[name^=\"number[\"]");
			if ($numbers.size() < 1) {
				alert("An entry must have a number.");
				event.preventDefault();
			} else {
				$numbers.each(function(index) {
					if ($(this).val() == "") {
						alert("Number cannot be blank.");
						event.preventDefault();
						return false;
					}
				});
			}
		});

		function addNumber() {
			lastid = $("#numbers tr[id^=\"number_\"]:last-child").attr("id");
			if (lastid) {
				index = lastid.substr(7); // Everything after "number_"
				index++;
			} else {
				index = 0;
			}

			row = "<tr id=\"number_" + index + "\">";
			row+= "<td>";
			row+= "<a href=\"#\" onclick=\"delNumber(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
			row+= "</td>";
			row+= "<td>";
			row+= "<input type=\"text\" name=\"number[" + index + "]\" value=\"\"/>";
			row+= "<select name=\"numbertype[" + index + "]\">"
	';
	foreach ($numbertypes as $id => $type) {
		$extrahtml.= '
			row+= "<option value=\"' . $id . '\">' . $type . '</option>"
		';
	}
	$extrahtml.= '
			row+= "</select>";
			row+= "</td>";

			row+= "<td>";
			row+= "<input type=\"checkbox\" name=\"sms[" + index + "]\" value=\"1\"/>' . _('SMS') . '<br>";
			row+= "<input type=\"checkbox\" name=\"fax[" + index + "]\" value=\"1\"/>' . _('FAX') . '";
			row+= "</td>";

			$("#numbers").append(row);
		}

		function delNumber(index) {
			$("#number_" + index).remove();
		}
	</script>';

	break;
}

$html.= $table->generate();

$html.= $extrahtml;

$html.= br(2);
$html.= form_submit('editentry', _('Submit'));

$html.= form_close();

echo $html;
?>
