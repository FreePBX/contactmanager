<?php
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2014 Schmooze Com Inc.
//
namespace FreePBX\modules;
class Contactmanager extends \FreePBX_Helpers implements \BMO {
	private $message = '';
	private $lookupCache = array();
	private $contactsCache = array();

	public function __construct($freepbx = null) {
		$this->db = $freepbx->Database;
		$this->freepbx = $freepbx;
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
		if (isset($_REQUEST['action'])) {
			switch ($_REQUEST['action']) {
			case "delgroup":
				$ret = $this->deleteGroupByID($_REQUEST['group']);
				$this->message = array(
					'message' => $ret['message'],
					'type' => $ret['type']
				);
				return true;
			case "delentry":
				$ret = $this->deleteEntryByID($_REQUEST['entry']);
				$this->message = array(
					'message' => $ret['message'],
					'type' => $ret['type']
				);
				return true;
			}
		}

		if (isset($_POST['editgroup'])) {
			$group = !empty($_POST['group']) ? $_POST['group'] : '';
			$groupname = !empty($_POST['groupname']) ? $_POST['groupname'] : '';
			$grouptype = !empty($_POST['grouptype']) ? $_POST['grouptype'] : '';

			if ($groupname) {
				if ($group) {
					$ret = $this->updateGroup($group, $groupname);
				} else {
					$ret = $this->addGroup($groupname, $grouptype);
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

		if (isset($_POST['editentry'])) {
			$group = !empty($_POST['group']) ? $_POST['group'] : '';
			$grouptype = !empty($_POST['grouptype']) ? $_POST['grouptype'] : '';

			if (!$group) {
				$this->message = array(
					'message' => _('Group can not be empty'),
					'type' => 'danger'
				);
				return false;
			}

			$numbers = array();
			foreach ($_POST['number'] as $index => $number) {
				if (!$number) {
					continue;
				}
				$numbers[$index]['number'] = $number;
				$numbers[$index]['type'] = $_POST['numbertype'][$index];
				if ($_POST['sms'][$index]) {
					$numbers[$index]['flags'][] = 'sms';
				}
				if ($_POST['fax'][$index]) {
					$numbers[$index]['flags'][] = 'fax';
				}
			}

			$entry = array(
				'id' => $_POST['entry'] ? $_POST['entry'] : '',
				'groupid' => $group,
				'user' => $_POST['user'] ? $_POST['user'] : -1,
				'numbers' => $numbers,
				'fname' => $_POST['fname'] ? $_POST['fname'] : NULL,
				'lname' => $_POST['lname'] ? $_POST['lname'] : NULL,
				'title' => $_POST['title'] ? $_POST['title'] : NULL,
				'company' => $_POST['company'] ? $_POST['company'] : NULL,
			);

			switch ($grouptype) {
			case "internal":
				if ($entry['user'] == -1) {
					$this->message = array(
						'message' => _('An entry must have a user.'),
						'type' => 'danger'
					);
					return false;
				}
				break;
			case "external":
				if (count($entry['numbers']) < 1) {
					$this->message = array(
						'message' => _('An entry must have numbers.'),
						'type' => 'danger'
					);
					return false;
				}
				break;
			}

			if ($entry['id']) {
				$ret = $this->updateEntry($entry['id'], $entry);
			} else {
				$ret = $this->addEntryByGroupID($group, $entry);
			}

			$this->message = array(
				'message' => $ret['message'],
				'type' => $ret['type']
			);
			return true;
		}
	}

	public function myShowPage() {
		$groups = $this->getGroups();
		$userman = setup_userman();
		$users = $userman->getAllUsers();

		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
		if ($action == "delentry") {
			$action = "showgroup";
		}

		$html = '';
		$html .= load_view(dirname(__FILE__).'/views/rnav.php', array("groups" => $groups, "group" => $_REQUEST['group']));

		switch($action) {
		case "showgroup":
		case "addgroup":
			if ($action == "showgroup" && !empty($_REQUEST['group'])) {
				$group = $this->getGroupByID($_REQUEST['group']);
				$entries = $this->getEntriesByGroupID($_REQUEST['group']);
			}

			$html .= load_view(dirname(__FILE__).'/views/group.php', array("group" => $group, "entries" => $entries, "users" => $users, "message" => $this->message));
			break;
		case "showentry":
		case "addentry":
			if (!empty($_REQUEST['group'])) {
				$group = $this->getGroupByID($_REQUEST['group']);

				if ($action == "showentry" && !empty($_REQUEST['entry'])) {
					$entry = $this->getEntryByID($_REQUEST['entry']);
				} else {
					$entry = array();
				}

				$html .= load_view(dirname(__FILE__).'/views/entry.php', array("group" => $group, "entry" => $entry, "users" => $users, "message" => $this->message));
			}
			break;
		default:
			$html .= load_view(dirname(__FILE__).'/views/main.php', array("message" => $this->message));
			break;
		}

		return $html;
	}

	public function usermanDelUser($id, $display, $data) {
		$groups = $this->getGroups();
		foreach ($groups as $group) {
			if ($group['owner'] == $id) {
				/* Remove groups owned by user. */
				$this->deleteGroupByID($group['id']);
				continue;
			}

			/* Remove user from all groups they're in. */
			$entries = $this->getEntriesByGroupID($group['id']);
			foreach ($entries as $entryid => $entry) {
				if ($entry['user'] == $id) {
					$this->deleteEntryByID($entryid);
				}
			}
		}
	}

	public function usermanAddUser($id, $display, $data) {
		$groups = $this->getGroups();
		foreach ($groups as $group) {
			if ($group['type'] == 'userman') {
				$this->addEntryByGroupID($group['id'], array('user' => $id));
			}
		}
	}

	public function usermanUpdateUser($id, $display, $data) {
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
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Get all groups by owner
	 * @param {int} $owner The owner ID
	 */
	public function getGroupsbyOwner($owner) {
		$sql = "SELECT * FROM contactmanager_groups WHERE `owner` = :id OR `owner` = -1 ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $owner));
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
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
		$sql = "SELECT * FROM contactmanager_groups WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$group = $sth->fetch(\PDO::FETCH_ASSOC);
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
		$sth->execute(array(
			':name' => $name,
			':owner' => $owner,
			':type' => $type,
		));

		$id = $this->db->lastInsertId();

		if ($type == 'userman') {
			$userman = setup_userman();
			$users = $userman->getAllUsers();

			foreach ($users as $user) {
				$this->addEntryByGroupID($id, array('user' => $user['id']));
			}
		}

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
		$sth->execute(array(
			':name' => $name,
			':owner' => $owner,
			':id' => $id,
		));

		return array("status" => true, "type" => "success", "message" => _("Group successfully updated"), "id" => $id);
	}

	public function getEntryByID($id) {
		$fields = array(
			'e.id',
			'e.groupid',
			'e.user',
			'e.fname',
			'e.lname',
			'e.title',
			'e.company',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_group_entries as e
			LEFT JOIN freepbx_users as u ON (e.user = u.id) WHERE e.id = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$entry = $sth->fetch(\PDO::FETCH_ASSOC);

		$numbers = $this->getNumbersByEntryID($id);
		if ($numbers) {
			foreach ($numbers as $number) {
				$entry['numbers'][$number['id']] = array(
					'number' => $number['number'],
					'type' => $number['type'],
					'flags' => $number['flags'] ? explode('|', $number['flags']) : array(),
				);
			}
		}

		return $entry;
	}

	public function getEntriesByGroupID($groupid) {
		$fields = array(
			'e.id',
			'e.groupid',
			'e.user',
			'e.fname',
			'e.lname',
			'e.title',
			'e.company',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_group_entries as e
			LEFT JOIN freepbx_users as u ON (e.user = u.id) WHERE `groupid` = :groupid ORDER BY e.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$entries = $sth->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE);

		$numbers = $this->getNumbersByGroupID($groupid);
		if ($numbers) {
			foreach ($numbers as $number) {
				$entries[$number['entryid']]['numbers'][$number['id']] = array(
					'number' => $number['number'],
					'type' => $number['type'],
					'flags' => $number['flags'] ? explode('|', $number['flags']) : array(),
				);
			}
		}

		return $entries;
	}

	public function deleteEntryByID($id) {
		$ret = $this->deleteNumbersByEntryID($id);
		if (!$ret['status']) {
			return $ret;
		}

		$sql = "DELETE FROM contactmanager_group_entries WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group entry successfully deleted"));
	}

	public function deleteEntriesByGroupID($groupid) {
		$ret = $this->deleteNumbersByGroupID($groupid);
		if (!$ret['status']) {
			return $ret;
		}

		$sql = "DELETE FROM contactmanager_group_entries WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entries successfully deleted"));
	}

	public function addEntryByGroupID($groupid, $entry) {
		$group = $this->getGroupByID($groupid);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		$sql = "INSERT INTO contactmanager_group_entries (`groupid`, `user`, `fname`, `lname`, `title`, `company`) VALUES (:groupid, :user, :fname, :lname, :title, :company)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':groupid' => $groupid,
			':user' => $entry['user'],
			':fname' => $entry['fname'],
			':lname' => $entry['lname'],
			':title' => $entry['title'],
			':company' => $entry['company'],
		));

		$id = $this->db->lastInsertId();

		$this->addNumbersByEntryID($id, $entry['numbers']);

		return array("status" => true, "type" => "success", "message" => _("Group entry successfully added"), "id" => $id);
	}

	public function addEntriesByGroupID($groupid, $entries) {
		$group = $this->getGroupByID($groupid);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		$sql = "INSERT INTO contactmanager_group_entries (`groupid`, `user`, `fname`, `lname`, `title`, `company`) VALUES (:groupid, :user, :fname, :lname, :title, :company)";
		$sth = $this->db->prepare($sql);
		foreach ($entries as $entry) {
			$sth->execute(array(
				':groupid' => $groupid,
				':user' => $entry['user'],
				':fname' => $entry['fname'],
				':lname' => $entry['lname'],
				':title' => $entry['title'],
				':company' => $entry['company'],
			));

			$id = $this->db->lastInsertId();
			$this->addNumbersByEntryID($id, $entry['numbers']);
		}

		return array("status" => true, "type" => "success", "message" => _("Group entries successfully added"));
	}

	public function updateEntry($id, $entry) {
		$group = $this->getGroupByID($entry['groupid']);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		if (!$this->getEntryByID($id)) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "UPDATE contactmanager_group_entries SET `groupid` = :groupid, `user` = :user, `fname` = :fname, `lname` = :lname, `title` = :title, `company` = :company WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':groupid' => $entry['groupid'],
			':user' => $entry['user'],
			':fname' => $entry['fname'],
			':lname' => $entry['lname'],
			':title' => $entry['title'],
			':company' => $entry['company'],
			':id' => $id,
		));

		$ret = $this->deleteNumbersByEntryID($id);
		$this->addNumbersByEntryID($id, $entry['numbers']);

		return array("status" => true, "type" => "success", "message" => _("Group entry successfully updated"), "id" => $id);
	}

	public function getNumbersByEntryID($entryid) {
		$fields = array(
			'id',
			'entryid',
			'number',
			'type',
			'flags',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_numbers WHERE `entryid` = :entryid ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));
		$numbers = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $numbers;
	}

	public function getNumbersByGroupID($groupid) {
		$fields = array(
			'n.id',
			'n.entryid',
			'n.number',
			'n.type',
			'n.flags',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_numbers as n
			LEFT JOIN contactmanager_group_entries as e ON (n.entryid = e.id) WHERE `groupid` = :groupid ORDER BY e.id, n.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$numbers = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $numbers;
	}

	public function deleteNumberByID($id) {
		$sql = "DELETE FROM contactmanager_entry_numbers WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group entry number successfully deleted"));
	}

	public function deleteNumbersByEntryID($entryid) {
		$sql = "DELETE FROM contactmanager_entry_numbers WHERE `entryid` = :entryid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));

		return array("status" => true, "type" => "success", "message" => _("Group entry numbers successfully deleted"));
	}

	public function deleteNumbersByGroupID($groupid) {
		$sql = "DELETE n FROM contactmanager_entry_numbers as n
			LEFT JOIN contactmanager_group_entries as e ON (n.entryid = e.id) WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entry numbers successfully deleted"));
	}

	public function addNumberByEntryID($entryid, $number) {
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_numbers (entryid, number, type, flags) VALUES (:entryid, :number, :type, :flags)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':entryid' => $entryid,
			':number' => $number['number'],
			':type' => $number['type'],
			':flags' => implode('|', $number['flags']),
		));

		$id = $this->db->lastInsertId();
		return array("status" => true, "type" => "success", "message" => _("Group entry number successfully added"), "id" => $id);
	}

	public function addNumbersByEntryID($entryid, $numbers) {
		if(empty($numbers)) {
			return array("status" => true, "type" => "success", "message" => _("No Numbers to add"));
		}
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_numbers (entryid, number, type, flags) VALUES (:entryid, :number, :type, :flags)";
		$sth = $this->db->prepare($sql);
		foreach ($numbers as $number) {
			$sth->execute(array(
				':entryid' => $entryid,
				':number' => $number['number'],
				':type' => $number['type'],
				':flags' => implode('|', $number['flags']),
			));
		}

		return array("status" => true, "type" => "success", "message" => _("Group entry numbers successfully added"));
	}

	public function getContactsByUserID($id) {
		if(!empty($this->contactsCache)) {
			return $this->contactsCache;
		}
		$groups = $this->getGroupsByOwner($id);
		$contacts = array();
		foreach($groups as $group) {
			switch($group['type']) {
				case "userman":
					$entries = $this->freepbx->Userman->getAllContactInfo();
					foreach($entries as &$entry) {
						$entry['type'] = "userman";
					}
					$contacts = array_merge($contacts, $entries);
				case "external":
				break;
				case "internal":
				break;
			}
		}
		$this->contactsCache = $contacts;
		return $this->contactsCache;
	}

	public function lookupByUserID($id, $search) {
		if(!empty($this->contactsCache[$search])) {
			return $this->contactsCache[$search];
		}
		$contacts = $this->getContactsByUserID($id);
		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($contacts));
		foreach($iterator as $key => $value) {
			$value = trim($value);
			if(!empty($value) && preg_match('/' . $search . '/',$value)) {
				$k = $iterator->getSubIterator(0)->key();
				$this->contactsCache[$search] = $contacts[$k];
				return $this->contactsCache[$search];
				break;
			}
		}
		return false;
	}
}
