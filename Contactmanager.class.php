<?php
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2014 Schmooze Com Inc.
//

class Contactmanager extends FreePBX_Helpers implements BMO {
	private $message = '';

	public function __construct($freepbx = null) {
		$this->db = $freepbx->Database;
	}

	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}

	public function doConfigPageInit($display) {
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delgroup') {
			$ret = $this->deleteGroupByID($_REQUEST['group']);
			$this->message = array(
				'message' => $ret['message'],
				'type' => $ret['type']
			);
			return true;
		}
		if (isset($_POST['submit'])) {
			$group = !empty($_POST['group']) ? $_POST['group'] : '';
			$name = !empty($_POST['name']) ? $_POST['name'] : '';
			$type = !empty($_POST['type']) ? $_POST['type'] : '';
			switch ($type) {
			case 'internal':
				foreach($_POST['user'] as $index => $value) {
					if (!$value) {
						continue;
					}

					$entries[] = array(
						'user' => $value,
						'number' => NULL,
						'fname' => NULL,
						'lname' => NULL,
					);
				}
				break;
			case 'external':
				foreach($_POST['number'] as $index => $value) {
					if (!$value) {
						continue;
					}

					$entries[] = array(
						'user' => -1,
						'number' => $value,
						'fname' => $_POST['fname'][$index],
						'lname' => $_POST['lname'][$index],
					);
				}
				break;
			}

			if ($name) {
				if ($group) {
					$ret = $this->updateGroup($group, $name);
					if ($ret['status']) {
						$ret = $this->updateGroupEntries($group, $entries);
					}
				} else {
					$ret = $this->addGroup($name, $type);
				}
				$this->message = array(
					'message' => $ret['message'],
					'type' => $ret['type']
				);
				return true;
			} else {
				$this->message = array(
					'message' => _('Group name can not be blank'),
					'type' => 'danger'
				);
				return false;
			}
		}
	}

	public function myShowPage() {
		$groups = $this->getGroups();
		$userman = setup_userman();
		$users = $userman->getAllUsers();

		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$html = '';

		$html .= load_view(dirname(__FILE__).'/views/rnav.php', array("groups" => $groups));
		switch($action) {
		case "showgroup":
		case "addgroup":
			if ($action == "showgroup" && !empty($_REQUEST['group'])) {
				$group = $this->getGroupByID($_REQUEST['group']);
				$entries = $this->getEntriesByGroupID($_REQUEST['group']);
			} else {
				$group = array();
			}

			$html .= load_view(dirname(__FILE__).'/views/group.php', array("group" => $group, "entries" => $entries, "users" => $users, "message" => $this->message));
			break;
		default:
			$html .= load_view(dirname(__FILE__).'/views/main.php', array("message" => $this->message));
			break;
		}

		return $html;
	}

	/**
	 * Get All Groups
	 *
	 * Get a List of all groups and their data
	 *
	 * @return array
	 */
	public function getGroups() {
		$sql = "SELECT * FROM contactmanager_groups ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Get Group Information by Group ID
	 *
	 * This gets group information by Contact Manager Group ID
	 *
	 * @param string $id The ID of the group from Contact Manager
	 * @return array
	 */
	public function getGroupByID($id) {
		$sql = "SELECT * FROM contactmanager_groups WHERE id = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$group = $sth->fetch(PDO::FETCH_ASSOC);
		return $group;
	}

	public function deleteGroupByID($id) {
		$group = $this->getGroupByID($id);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		$ret = $this->deleteEntriesByGroupID($id);
		if (!$ret['status']) {
			return $ret;
		}

		$sql = "DELETE FROM contactmanager_groups WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group successfully deleted"));
	}

	public function addGroup($name, $type='internal', $owner = -1) {
		if (!$name || empty($name)) {
			return array("status" => false, "type" => "danger", "message" => _("Group name can not be blank"));
		}
		$sql = "INSERT INTO contactmanager_groups (`name`, `owner`, `type`) VALUES (:name, :owner, :type)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':name' => $name, ':owner' => $owner, ':type' => $type));

		$id = $this->db->lastInsertId();
		return array("status" => true, "type" => "success", "message" => _("Group successfully added"), "id" => $id);
	}

	public function updateGroup($id, $name, $owner = -1) {
		$group = $this->getGroupByID($id);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => sprintf(_("Group '%s' does not exist"), $id));
		}
		if (!$name || empty($name)) {
			return array("status" => false, "type" => "danger", "message" => _("Group name can not be blank"));
		}
		$sql = "UPDATE contactmanager_groups SET `name` = :name, `owner` = :owner WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':name' => $name, ':owner' => $owner, ':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group successfully updated"), "id" => $id);
	}

	public function getEntriesByGroupID($groupid) {
		$fields = array(
			'e.id',
			'e.groupid',
			'e.user',
			'COALESCE(e.number, u.default_extension) as number',
			'COALESCE(e.fname, u.fname) as fname',
			'COALESCE(e.lname, u.lname) as lname',
		);
		$sql = "SELECT " . implode(', ', $fields) . " from contactmanager_group_entries as e 
			LEFT JOIN freepbx_users as u ON (e.user = u.id) WHERE groupid = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$entries = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $entries;
	}

	public function deleteEntriesByGroupID($groupid) {
		$group = $this->getGroupByID($groupid);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		$sql = "DELETE FROM contactmanager_group_entries WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entries successfully deleted"));
	}
	public function updateGroupEntries($groupid, $entries) {
		$sql = "DELETE FROM contactmanager_group_entries WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		$sql = "INSERT INTO contactmanager_group_entries (`groupid`, `user`, `number`, `fname`, `lname`) VALUES (:groupid, :user, :number, :fname, :lname)";
		foreach ($entries as $entry) {
			$sth = $this->db->prepare($sql);
			$sth->execute(array(':groupid' => $groupid, ':user' => $entry['user'], ':number' => $entry['number'], ':fname' => $entry['fname'], ':lname' => $entry['lname']));
		}

		return array("status" => true, "type" => "success", "message" => _("Group entries successfully updated"));
	}
}
