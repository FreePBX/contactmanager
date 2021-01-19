<?php


function contactmanager_getdest($id) {
	return array('ext-contactmanager-sd,'.$id.',1');
}

function contactmanager_getdestinfo($dest) {
	if (substr(trim($dest),0,22) == 'ext-contactmanager-sd,') {
		$fcc = new \featurecode('contactmanager', 'app-contactmanager-sd');
		$code = $fcc->getCodeActive();

		if (empty($code)) {
			return array();
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
			return array();
		}
		return array();
	} else {
		return false;
	}
}
