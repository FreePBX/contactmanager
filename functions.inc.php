<?php
function contactmanager_destinations() {
	$cm = \FreePBX::Contactmanager();
	$entries = $cm->getAllSpeedDials();

	$contextname = 'ext-contactmanager-sd';
	$fcc = new \featurecode('contactmanager', 'app-contactmanager-sd');
	$code = $fcc->getCodeActive();
	if (empty($code)) {
		return array();
	}

	$destinations = array();
	$extens = array();
	foreach($entries as $entry) {
		$name = !empty($entry['displayname']) ? $entry['displayname'] : $entry['fname'] . " " . $entry['lname'];
		$extens[] = array('destination' => 'ext-contactmanager-sd,'.$entry['speeddial'].',1', 'category' => 'Contact Manager Speed Dials', 'description' => sprintf(_("%s (Speed Dial: %s) [%s]"),$name,$code.$entry['speeddial'],$entry['number']));
	}
	return $extens;
}

function contactmanager_getdest($id) {
	return array('ext-contactmanager-sd,'.$id.',1');
}

function contactmanager_getdestinfo($dest) {
	if (substr(trim($dest),0,22) == 'ext-contactmanager-sd,') {
		$fcc = new \featurecode('contactmanager', 'app-contactmanager-sd');
		$code = $fcc->getCodeActive();

		if (empty($code)) {
			return false;
		}

		$parts = explode(',',$dest);
		$id = $parts[1];
		$cm = \FreePBX::Contactmanager();
		$speeddial = $cm->getSpeedDialByID($id);
		if(!empty($speeddial)) {
			$name = !empty($speeddial['displayname']) ? $speeddial['displayname'] : $speeddial['fname'] . " " . $speeddial['lname'];
			switch($speeddial['grouptype']) {
				case "private":
				case "internal":
				case "external":
					return array('description' => sprintf(_("Contact Manager: %s (Speed Dial: %s) [%s]"),$name,$code.$speeddial['id'],$speeddial['number']),'edit_url' => 'config.php?display=contactmanager&action=showentry&group='.urlencode($speeddial['groupid']).'&entry='.urlencode($speeddial['entryid']));
				break;
				case "userman":
					return array('description' => sprintf(_("Contact Manager: %s (Speed Dial: %s) [%s]"),$name,$code.$speeddial['id'],$speeddial['number']),'edit_url' => 'display=userman&action=showuser&user='.urlencode($speeddial['entryid']));
				break;
			}
			return false;
		}
		return false;
	}
}
