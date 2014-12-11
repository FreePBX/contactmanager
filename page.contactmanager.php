<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
$contactmanager = FreePBX::Contactmanager();
echo $contactmanager->myShowPage();


$f = fopen("/usr/src/freepbx-dev/contactmanager/google2.csv", "r");

while (($line = fgetcsv($f, 0, ',', '"', '\\'))) {
	if ($header) {
		$csv[] = $line;
	} else {
		$header = $line;
	}
}

$group = 37;
foreach ($csv as $row) {
	$a = array_combine($header, $row);
	$contact = array(
		'id' => '',
		'groupid' => $group,
		'user' => -1,
		'displayname' => getField($a, array("Display Name", "Name")),
		'fname' => getField($a, array("First Name", "Given Name")),
		'lname' => getField($a, array("Last Name", "Family Name")),
		'title' => getField($a, array("Title", "Organization 1 - Title")),
		'company' => getField($a, array("Company", "Organization 1 - Name")),
		'address' => getField($a, array("Address", "Address 1 - Formatted")),

		'numbers' => getField($a, array(array("Phone"), array("Number"), array("Phone 1 - Value", "Phone 1 - Type"), array("Phone 2 - Value", "Phone 2 - Type"), array("Phone 3 - Value", "Phone 3 - Type")), true, 'numbers'),
		'emails' => getField($a, array(array("E-mail"), array("Email"), array("E-mail 1 - Value"), array("E-mail 2 - Value"), array("E-mail 3 - Value")), true, "emails"),
		'websites' => getField($a, array(array("Website"), array("Website 1 - Value"), array("Website 2 - Value"), array("Website 3 - Value")), true, "websites"),
	);

//$contactmanager->addEntryByGroupID($group, $contact);
}

function getField($a, $names, $multiple = false, $type) {
	$field = array();

	foreach ($names as $name) {
		if (is_array($name)) {
			$data = array();
			foreach ($name as $key => $val) {
				if (isset($a[$val]) && $a[$val]) {
					$d = explode(" ::: ", $a[$val]);
					$data[$key] = trim($d[0]);
				}
			}

			if (count($data) > 0) {
				$field[] = $data;
			}
		} else {
			if (isset($a[$name]) && $a[$name]) {
				$d = explode(" ::: ", $a[$name]);
				$data = trim($d[0]);

				$field[] = $data;
			}
		}
	}

	foreach ($field as $key => $val) {
		switch ($type) {
		case "numbers":
			$data['number'] = preg_replace('/\D/', '', $val[0]);
			$data['type'] = "other";

			switch (strtolower($val[1])) {
			case "home":
				$data['type'] = "home";
				break;
			case "work":
				$data['type'] = "work";
				break;
			case "mobile":
			case "cell":
				$data['type'] = "cell";
				break;
			case "home fax":
				$data['type'] = "home";
				$data['flags'][] = 'fax';
				break;
			case "work fax":
				$data['type'] = "work";
				$data['flags'][] = 'fax';
				break;
			case "fax":
				$data['flags'][] = 'fax';
				break;
			}

			$field[$key] = $data;
		case "emails":
			$data['email'] = $val[0];

			$field[$key] = $data;
			break;
		case "websites":
			$data['website'] = $val[0];

			$field[$key] = $data;
			break;
		}
	}

	if (count($field) > 0) {
		if ($multiple) {
			return $field;
		} else {
			return $field[0];
		}
	}

	return NULL;
}
