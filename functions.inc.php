<?php
function contactmanager_destinations() {
	$cm = \FreePBX::Contactmanager();
	$entries = $cm->getContactsByUserID(-1);
	$destinations = array();
	$extens = array();
	foreach($entries as $entry) {
		$name = !empty($entry['displayname']) ? $entry['displayname'] : $entry['fname'] . " " . $entry['lname'];
		if(!empty($entry['numbers'])) {
			foreach($entry['numbers'] as $type => $number) {
				if(!empty($number)) {
					$extens[] = array('destination' => 'ext-contactmanager,'.$number.',1', 'description' => $name . "(" . $type . ")");
				}
			}
		}
	}
	return $extens;
}

function contactmanager_getdest($number) {
	return array('ext-contactmanager,'.$number.',1');
}

function contactmanager_getdestinfo($dest) {
	if (substr(trim($dest),0,19) == 'ext-contactmanager,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];
		$cm = \FreePBX::Contactmanager();
		$entries = $cm->getContactsByUserID(-1);
		$destinations = array();
		foreach($entries as $entry) {
			$name = !empty($entry['displayname']) ? $entry['displayname'] : $entry['fname'] . " " . $entry['lname'];
			if(!empty($entry['numbers'])) {
				foreach($entry['numbers'] as $type => $number) {
					if($number == $exten) {
						switch($entry['type']) {
							case "internal":
							case "external":
								return array('description' => sprintf(_("Contact Manager: %s"),$name . "(" . $type . ")"),'edit_url' => 'config.php?display=contactmanager&action=showentry&group='.urlencode($entry['groupid']).'&entry='.urlencode($entry['uid']));
							break;
							case "userman":
								return array('description' => sprintf(_("Contact Manager: %s"),$name . "(" . $type . ")"),'edit_url' => 'display=userman&action=showuser&user='.urlencode($entry['id']));
							break;
						}
						break;
					}
				}
			}
		}
		return array();
	} else {
		return false;
	}
	/*
	global $active_modules;

	if (substr(trim($dest),0,14) == 'ext-miscdests,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];
		$thisexten = miscdests_get($exten);
		if (empty($thisexten)) {
			return array();
		} else {
			//$type = isset($active_modules['announcement']['type'])?$active_modules['announcement']['type']:'setup';
			return array('description' => sprintf(_("Misc Destination: %s"),$thisexten['description']),
			'edit_url' => 'config.php?display=miscdests&id='.urlencode($exten),
		);
	}
} else {
	return false;
}
*/
}
