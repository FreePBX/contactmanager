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

$userlist[''] = '';
foreach ($users as $u) {
	if ($entry['user'] == $u['id']) {
		$user = $u;
	}

	if ($u['displayname']) {
		$desc = $u['displayname'];
	} else if ($u['fname'] && $u['lname']) {
		$desc = $u['fname'] . ' ' . $u['lname'];
	} else if ($u['default_extension'] && $u['default_extension'] != 'none') {
		$desc = 'Ext. ' . $u['default_extension'];
	} else if ($u['description']) {
		$desc = $u['description'];
	}

	$userlist[$u['id']] = ($desc ? $desc . ' ' : '') . '(' . $u['username'] . ')';
}

$table = new CI_Table;

$label = fpbx_label(_('Display Name'), _('Display Name (overrides Display Name from User Manager)'));
$table->add_row($label, form_input('displayname', $entry['displayname'], ($user ? 'placeholder="' . $user['displayname'] . '"' : '')));

$label = fpbx_label(_('First Name'), _('First Name (overrides First Name from User Manager)'));
$table->add_row($label, form_input('fname', $entry['fname'], ($user ? 'placeholder="' . $user['fname'] . '"' : '')));

$label = fpbx_label(_('Last Name'), _('Last Name (overrides Last Name from User Manager)'));
$table->add_row($label, form_input('lname', $entry['lname'], ($user ? 'placeholder="' . $user['lname'] . '"' : '')));

$label = fpbx_label(_('Title'), _('Title  (overrides Title from User Manager)'));
$table->add_row($label, form_input('title', $entry['title'], ($user ? 'placeholder="' . $user['title'] . '"' : '')));

$label = fpbx_label(_('Company'), _('Company (overrides Company from User Manager)'));
$table->add_row($label, form_input('company', $entry['company'], ($user ? 'placeholder="' . $user['company'] . '"' : '')));

$label = fpbx_label(_('Address'), _('Address'));
$table->add_row($label, form_textarea(array('name' => 'address', 'rows' => 4), $entry['address']));

$extrahtml = '';

switch ($group['type']) {
case "internal":
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

	$numhtml = '<table id="numbers">';

	$numcount = 0;
	foreach ($entry['numbers'] as $number) {
		$numhtml.= '<tr id="number_' . $numcount . '">';

		$numhtml.= '<td>';
		$numhtml.= '<a href="#" onclick="delNumber(' . $numcount . ')"><i class="fa fa-ban fa-fw"></i></a>';
		$numhtml.= '</td>';

		$numhtml.= '<td>';
		$numhtml.= form_input('number[' . $numcount . ']', $number['number']);
		$numhtml.= 'Ext.' . form_input('extension[' . $numcount . ']', $number['extension']);
		$numhtml.= form_dropdown('numbertype[' . $numcount . ']', $numbertypes, $number['type']);
		$numhtml.= '</td>';

		$numhtml.= '<td>';
		$numhtml.= form_checkbox('sms[' . $numcount . ']', 1, in_array('sms', $number['flags'])) . _('SMS');
		$numhtml.= '<br>';
		$numhtml.= form_checkbox('fax[' . $numcount . ']', 1, in_array('fax', $number['flags'])) . _('FAX');
		$numhtml.= '</td>';

		$numhtml.= '</tr>';

		$numcount++;
	}
	$numhtml.= '</table>';

	$numhtml.= '<a href="#" onclick="addNumber()"><i class="fa fa-plus fa-fw"></i>Add Number</a>';

	$label = fpbx_label(_('Numbers'), _('A list of numbers belonging to this entry'));
	$table->add_row($label, $numhtml);

	$xmpphtml = '<table id="xmpps">';

	$xmppcount = 0;
	foreach ($entry['xmpps'] as $xmpp) {
		$xmpphtml.= '<tr id="xmpp_' . $xmppcount . '">';

		$xmpphtml.= '<td>';
		$xmpphtml.= '<a href="#" onclick="delXMPP(' . $xmppcount . ')"><i class="fa fa-ban fa-fw"></i></a>';
		$xmpphtml.= '</td>';

		$xmpphtml.= '<td>';
		$xmpphtml.= form_input('xmpp[' . $xmppcount . ']', $xmpp['xmpp']);
		$xmpphtml.= '</td>';

		$xmpphtml.= '</tr>';

		$xmppcount++;
	}
	$xmpphtml.= '</table>';

	$xmpphtml.= '<a href="#" onclick="addXMPP()"><i class="fa fa-plus fa-fw"></i>Add XMPP</a>';

	$label = fpbx_label(_('XMPP'), _('A list of XMPP addresses belonging to this entry'));
	$table->add_row($label, $xmpphtml);

	$emailhtml = '<table id="emails">';

	$emailcount = 0;
	foreach ($entry['emails'] as $email) {
		$emailhtml.= '<tr id="email_' . $emailcount . '">';

		$emailhtml.= '<td>';
		$emailhtml.= '<a href="#" onclick="delEmail(' . $emailcount . ')"><i class="fa fa-ban fa-fw"></i></a>';
		$emailhtml.= '</td>';

		$emailhtml.= '<td>';
		$emailhtml.= form_input('email[' . $emailcount . ']', $email['email']);
		$emailhtml.= '</td>';

		$emailhtml.= '</tr>';

		$emailcount++;
	}
	$emailhtml.= '</table>';

	$emailhtml.= '<a href="#" onclick="addEmail()"><i class="fa fa-plus fa-fw"></i>Add E-Mail</a>';

	$label = fpbx_label(_('E-Mail'), _('A list of E-Mail addresses belonging to this entry'));
	$table->add_row($label, $emailhtml);

	$websitehtml = '<table id="websites">';

	$websitecount = 0;
	foreach ($entry['websites'] as $website) {
		$websitehtml.= '<tr id="website_' . $websitecount . '">';

		$websitehtml.= '<td>';
		$websitehtml.= '<a href="#" onclick="delWebsite(' . $websitecount . ')"><i class="fa fa-ban fa-fw"></i></a>';
		$websitehtml.= '</td>';

		$websitehtml.= '<td>';
		$websitehtml.= form_input('website[' . $websitecount . ']', $website['website']);
		$websitehtml.= '</td>';

		$websitehtml.= '</tr>';

		$websitecount++;
	}
	$websitehtml.= '</table>';

	$websitehtml.= '<a href="#" onclick="addWebsite()"><i class="fa fa-plus fa-fw"></i>Add Website</a>';

	$label = fpbx_label(_('Website'), _('A list of websites belonging to this entry'));
	$table->add_row($label, $websitehtml);

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

			$xmpps = $("#xmpps input[name^=\"xmpp[\"]");
			if ($xmpps.size() > 0) {
				$xmpps.each(function(index) {
					if ($(this).val() == "") {
						alert("XMPP address cannot be blank.");
						event.preventDefault();
						return false;
					}
				});
			}

			$emails = $("#emails input[name^=\"email[\"]");
			if ($emails.size() > 0) {
				$emails.each(function(index) {
					if ($(this).val() == "") {
						alert("E-Mail address cannot be blank.");
						event.preventDefault();
						return false;
					}
				});
			}

			$websites = $("#websites input[name^=\"website[\"]");
			if ($websites.size() > 0) {
				$websites.each(function(index) {
					if ($(this).val() == "") {
						alert("Website cannot be blank.");
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
			row+= "Ext.<input type=\"text\" name=\"extension[" + index + "]\" value=\"\"/>";
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
			row+= "<input type=\"checkbox\" name=\"sms[" + index + "]\" value=\"1\"/>' . _('SMS') . '";
			row+= "<br>";
			row+= "<input type=\"checkbox\" name=\"fax[" + index + "]\" value=\"1\"/>' . _('FAX') . '";
			row+= "</td>";

			$("#numbers").append(row);
		}

		function delNumber(index) {
			$("#number_" + index).remove();
		}

		function addXMPP() {
			lastid = $("#xmpps tr[id^=\"xmpp_\"]:last-child").attr("id");
			if (lastid) {
				index = lastid.substr(5); // Everything after "xmpp_"
				index++;
			} else {
				index = 0;
			}

			row = "<tr id=\"xmpp_" + index + "\">";
			row+= "<td>";
			row+= "<a href=\"#\" onclick=\"delXMPP(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
			row+= "</td>";
			row+= "<td>";
			row+= "<input type=\"text\" name=\"xmpp[" + index + "]\" value=\"\"/>";
			row+= "</td>";

			$("#xmpps").append(row);
		}

		function delXMPP(index) {
			$("#xmpp_" + index).remove();
		}

		function addEmail() {
			lastid = $("#emails tr[id^=\"email_\"]:last-child").attr("id");
			if (lastid) {
				index = lastid.substr(6); // Everything after "email_"
				index++;
			} else {
				index = 0;
			}

			row = "<tr id=\"email_" + index + "\">";
			row+= "<td>";
			row+= "<a href=\"#\" onclick=\"delEmail(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
			row+= "</td>";
			row+= "<td>";
			row+= "<input type=\"text\" name=\"email[" + index + "]\" value=\"\"/>";
			row+= "</td>";

			$("#emails").append(row);
		}

		function delEmail(index) {
			$("#email_" + index).remove();
		}

		function addWebsite() {
			lastid = $("#websites tr[id^=\"website_\"]:last-child").attr("id");
			if (lastid) {
				index = lastid.substr(8); // Everything after "website_"
				index++;
			} else {
				index = 0;
			}

			row = "<tr id=\"website_" + index + "\">";
			row+= "<td>";
			row+= "<a href=\"#\" onclick=\"delWebsite(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
			row+= "</td>";
			row+= "<td>";
			row+= "<input type=\"text\" name=\"website[" + index + "]\" value=\"\"/>";
			row+= "</td>";

			$("#websites").append(row);
		}

		function delWebsite(index) {
			$("#website_" + index).remove();
		}
	</script>';

	break;
}

$html.= $table->generate();

$html.= $extrahtml;

$html.= br(2);
$html.= form_submit('submit', _('Submit'));

$html.= form_close();

echo $html;
?>
