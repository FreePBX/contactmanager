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

	public function doDialplanHook(&$ext, $engine, $priority) {
		$contextname = 'ext-contactmanager';
		$entries = $this->getContactsByUserID(-1);
		$destinations = array();
		$used = array();
		foreach($entries as $entry) {
			$name = !empty($entry['displayname']) ? $entry['displayname'] : $entry['fname'] . " " . $entry['lname'];
			if(!empty($entry['numbers'])) {
				foreach($entry['numbers'] as $type => $number) {
					if(!in_array($number,$used)) {
						$used[] = $number;
						if(!empty($number)) {
							$ext->add($contextname, $number, '', new \ext_noop('Contact Manager: '. $name . "(" . $type . ")"));
							if($type == "internal") {
								$ext->add($contextname, $number, '', new \ext_goto('from-internal,'.$number.',1', ''));
							} else {
								$ext->add($contextname, $number, '', new \ext_dial($number));
							}
						}
					}
				}
			}
		}
	}

	public static function myDialplanHooks() {
		return 500;
	}

	/**
	 * Get Inital Display
	 * @param {string} $display The Page name
	 */
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
			case "import":
				if (!empty($_POST['group']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
					$this->importCSV($_FILES['csv']['tmp_name'], $_POST['group']);
				}
				return true;
			}
		}

		if (isset($_POST['group'])) {
			$group = !empty($_POST['group']) ? $_POST['group'] : '';

			if (!isset($_POST['entry'])) {
				$entry = !empty($_POST['entry']) ? $_POST['entry'] : '';
				$grouptype = !empty($_POST['grouptype']) ? $_POST['grouptype'] : '';
				$groupname = !empty($_POST['groupname']) ? $_POST['groupname'] : '';

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
			} else {
				$grouptype = !empty($_POST['grouptype']) ? $_POST['grouptype'] : '';

				$numbers = array();
				foreach ($_POST['number'] as $index => $number) {
					if (!$number) {
						continue;
					}
					$numbers[$index]['number'] = $number;
					$numbers[$index]['extension'] = $_POST['extension'][$index];
					$numbers[$index]['type'] = $_POST['numbertype'][$index];
					if ($_POST['sms'][$index]) {
						$numbers[$index]['flags'][] = 'sms';
					}
					if ($_POST['fax'][$index]) {
						$numbers[$index]['flags'][] = 'fax';
					}
				}

				$xmpps = array();
				foreach ($_POST['xmpp'] as $index => $xmpp) {
					if (!$xmpp) {
						continue;
					}
					$xmpps[$index]['xmpp'] = $xmpp;
				}

				$emails = array();
				foreach ($_POST['email'] as $index => $email) {
					if (!$email) {
						continue;
					}
					$emails[$index]['email'] = $email;
				}

				$website = array();
				foreach ($_POST['website'] as $index => $website) {
					if (!$website) {
						continue;
					}
					$websites[$index]['website'] = $website;
				}

				$entry = array(
				'id' => $_POST['entry'] ? $_POST['entry'] : '',
				'groupid' => $group,
				'user' => $_POST['user'] ? $_POST['user'] : -1,
				'numbers' => $numbers,
				'xmpps' => $xmpps,
				'emails' => $emails,
				'websites' => $websites,
				'displayname' => $_POST['displayname'] ? $_POST['displayname'] : NULL,
				'fname' => $_POST['fname'] ? $_POST['fname'] : NULL,
				'lname' => $_POST['lname'] ? $_POST['lname'] : NULL,
				'title' => $_POST['title'] ? $_POST['title'] : NULL,
				'company' => $_POST['company'] ? $_POST['company'] : NULL,
				'address' => $_POST['address'] ? $_POST['address'] : NULL,
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
	}

	/**
	 * Function used in page.contactmanager.php
	 */
	public function myShowPage() {
		$groups = $this->getGroups();
		$userman = setup_userman();
		$users = $userman->getAllUsers();

		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
		if ($action == "delentry") {
			$action = "showgroup";
		} elseif ($action == "import") {
			$action = "showgroup";
		} elseif ($action == "export") {
			if (!empty($_REQUEST['group'])) {
				$this->exportCSV($_REQUEST['group']);
			}
			return;
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

	/**
	 * Call to be run when user is deleted from user manager
	 * @param {int} $id      The usermanager id
	 * @param {string} $display The page executing this command
	 * @param {array} $data    Array of data about the user
	 */
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
		if($display == 'extensions' || $display == 'users') {
			$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','processed',true);
			$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',true);
		} else if($display == 'userman' && isset($_POST['contactmanager_show'])) {
			if($_POST['contactmanager_show'] == "true") {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','processed',true);
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',true);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','processed',true);
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',false);
			}
		}
	}

	public function usermanUpdateUser($id, $display, $data) {
		if($display == 'userman' && isset($_POST['contactmanager_show'])) {
			if($_POST['contactmanager_show'] == "true") {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','processed',true);
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',true);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','processed',true);
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',false);
			}
		}
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

	/**
	 * Delete Group by ID
	 * @param {int} $id The group ID
	 */
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

	/**
	 * Add group
	 * @param {string} $name            The group name
	 * @param {string} $type='internal' The type of group, can be internal or external
	 * @param {int} $owner           =             -1 The group owner, if -1 then everyone owns
	 */
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

	/**
	 * Update Group
	 * @param {int} $id    The group ID
	 * @param {string} $name  The updated group name
	 * @param {int} $owner =             -1 The owner
	 */
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

	/**
	 * Get all information about an Entry
	 * @param {int} $id The entry ID
	 */
	public function getEntryByID($id) {
		$fields = array(
		'e.id',
		'e.groupid',
		'e.user',
		'COALESCE(e.displayname,u.displayname,u.fname,u.username) as displayname',
		'COALESCE(e.fname,u.fname) as fname',
		'COALESCE(e.lname,u.lname) as lname',
		'COALESCE(e.title,u.title) as title',
		'COALESCE(e.company,u.company) as company',
		'e.address as address',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_group_entries as e
		LEFT JOIN freepbx_users as u ON (e.user = u.id) WHERE e.id = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$entry = $sth->fetch(\PDO::FETCH_ASSOC);

		$group = $this->getGroupByID($entry['groupid']);

		switch($group['type']) {
			case "external":
			$numbers = $this->getNumbersByEntryID($id);
			if ($numbers) {
				foreach ($numbers as $number) {
					$number['flags'] = !empty($number['flags']) ? explode('|', $number['flags']) : array();
					$entry['numbers'][$number['id']] = array(
					'number' => $number['number'],
					'extension' => $number['extension'],
					'type' => $number['type'],
					'flags' => $number['flags'],
					'primary' => isset($number['flags'][0]) ? $number['flags'][0] : 'phone'
					);
				}
			}

			$xmpps = $this->getXMPPsByEntryID($id);
			if ($xmpps) {
				foreach ($xmpps as $xmpp) {
					$entry['xmpps'][$xmpp['id']] = array(
					'xmpp' => $xmpp['xmpp'],
					);
				}
			}

			$emails = $this->getEmailsByEntryID($id);
			if ($emails) {
				foreach ($emails as $email) {
					$entry['emails'][$email['id']] = array(
					'email' => $email['email'],
					);
				}
			}

			$websites = $this->getWebsitesByEntryID($id);
			if ($websites) {
				foreach ($websites as $website) {
					$entry['websites'][$website['id']] = array(
					'website' => $website['website'],
					);
				}
			}
			break;
			case "userman":
			if(!$this->showUsermanContact($entry['user'])) {
				return false;
			}
			case "internal":
			$user = $this->freepbx->Userman->getUserByID($entry['user']);
			if(empty($user)) {
				$this->deleteEntryByID($entry['uid']);
				return false;
			}
			if(!empty($user['default_extension']) && $user['default_extension'] != "none") {
				$entry['numbers'][] = array(
				'number' => $user['default_extension'],
				'type' => 'internal',
				'flags' => array(),
				'primary' => 'phone'
				);
			}
			if(!empty($user['cell'])) {
				$entry['numbers'][] = array(
				'number' => $user['cell'],
				'type' => 'cell',
				'flags' => array(),
				'primary' => 'sms'
				);
			}
			if(!empty($user['work'])) {
				$entry['numbers'][] = array(
				'number' => $user['work'],
				'type' => 'work',
				'flags' => array(),
				'primary' => 'phone'
				);
			}
			if(!empty($user['home'])) {
				$entry['numbers'][] = array(
				'number' => $user['home'],
				'type' => 'home',
				'flags' => array(),
				'primary' => 'phone'
				);
			}
			if(!empty($user['fax'])) {
				$entry['numbers'][] = array(
				'number' => $user['fax'],
				'type' => 'fax',
				'flags' => array(),
				'primary' => 'fax'
				);
			}
			if(!empty($user['email'])) {
				$entry['emails'][] = array(
				'email' => $user['email']
				);
			}
			if(!empty($user['xmpp'])) {
				$entry['xmpps'][] = array(
				'xmpp' => $user['xmpp']
				);
			}
			break;
		}
		return $entry;
	}

	/**
	 * Get all Entries by Group ID
	 * @param {int} $groupid The group ID
	 */
	public function getEntriesByGroupID($groupid) {
		$fields = array(
		'e.id',
		'e.id as uid',
		'e.groupid',
		'e.user',
		'COALESCE(e.displayname,u.displayname,u.fname,u.username) as displayname',
		'COALESCE(e.fname,u.fname) as fname',
		'COALESCE(e.lname,u.lname) as lname',
		'COALESCE(e.title,u.title) as title',
		'COALESCE(e.company,u.company) as company',
		'e.address as address',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_group_entries as e
		LEFT JOIN freepbx_users as u ON (e.user = u.id) WHERE `groupid` = :groupid ORDER BY e.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$e = $sth->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE);

		$group = $this->getGroupByID($groupid);
		switch($group['type']) {
			case "userman":
			foreach($e as $key => $entry) {
				if($this->showUsermanContact($entry['user'])) {
					$user = $this->freepbx->Userman->getUserByID($entry['user']);
					if(!empty($user)) {
						$entries[$key] = $entry;
					} else {
						$this->deleteEntryByID($entry['uid']);
					}
				}
			}
			break;
			case "internal":
			foreach($e as $key => $entry) {
				$user = $this->freepbx->Userman->getUserByID($entry['user']);
				if(!empty($user)) {
					$entries[$key] = $entry;
				} else {
					$this->deleteEntryByID($entry['uid']);
				}
			}
			break;
			case "external":
			default:
			$entries = $e;
			break;
		}

		$numbers = $this->getNumbersByGroupID($groupid);
		if ($numbers) {
			foreach ($numbers as $number) {
				$entries[$number['entryid']]['numbers'][$number['id']] = array(
				'number' => $number['number'],
				'extension' => $number['extension'],
				'type' => $number['type'],
				'flags' => $number['flags'] ? explode('|', $number['flags']) : array(),
				);
			}
		}

		$xmpps = $this->getXMPPsByGroupID($groupid);
		if ($xmpps) {
			foreach ($xmpps as $xmpp) {
				$entries[$xmpp['entryid']]['xmpps'][$xmpp['id']] = array(
				'xmpp' => $xmpp['xmpp'],
				);
			}
		}

		$emails = $this->getEmailsByGroupID($groupid);
		if ($emails) {
			foreach ($emails as $email) {
				$entries[$email['entryid']]['emails'][$email['id']] = array(
				'email' => $email['email'],
				);
			}
		}

		$websites = $this->getWebsitesByGroupID($groupid);
		if ($websites) {
			foreach ($websites as $website) {
				$entries[$website['entryid']]['websites'][$website['id']] = array(
				'website' => $website['website'],
				);
			}
		}

		return $entries;
	}

	/**
	 * Delete Entry by ID
	 * @param {int} $id The entry ID
	 */
	public function deleteEntryByID($id) {
		$ret = $this->deleteNumbersByEntryID($id);
		if (!$ret['status']) {
			return $ret;
		}

		$ret = $this->deleteXMPPsByEntryID($id);
		if (!$ret['status']) {
			return $ret;
		}

		$ret = $this->deleteEmailsByEntryID($id);
		if (!$ret['status']) {
			return $ret;
		}

		$ret = $this->deleteWebsitesByEntryID($id);
		if (!$ret['status']) {
			return $ret;
		}

		$sql = "DELETE FROM contactmanager_group_entries WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group entry successfully deleted"));
	}

	/**
	 * Delete Entries by Group ID
	 * @param {int} $groupid The group ID
	 */
	public function deleteEntriesByGroupID($groupid) {
		$ret = $this->deleteNumbersByGroupID($groupid);
		if (!$ret['status']) {
			return $ret;
		}

		$ret = $this->deleteXMPPsByGroupID($groupid);
		if (!$ret['status']) {
			return $ret;
		}

		$ret = $this->deleteEmailsByGroupID($groupid);
		if (!$ret['status']) {
			return $ret;
		}

		$ret = $this->deleteWebsitesByGroupID($groupid);
		if (!$ret['status']) {
			return $ret;
		}

		$sql = "DELETE FROM contactmanager_group_entries WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entries successfully deleted"));
	}

	/**
	 * Add Entry to Group
	 * @param {int} $groupid The group ID
	 * @param {array} $entry   Array of Entry information
	 */
	public function addEntryByGroupID($groupid, $entry) {
		$group = $this->getGroupByID($groupid);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		$sql = "INSERT INTO contactmanager_group_entries (`groupid`, `user`, `displayname`, `fname`, `lname`, `title`, `company`, `address`) VALUES (:groupid, :user, :displayname, :fname, :lname, :title, :company, :address)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':groupid' => $groupid,
		':user' => $entry['user'],
		':displayname' => $entry['displayname'],
		':fname' => $entry['fname'],
		':lname' => $entry['lname'],
		':title' => $entry['title'],
		':company' => $entry['company'],
		':address' => $entry['address'],
		));

		$id = $this->db->lastInsertId();

		$this->addNumbersByEntryID($id, $entry['numbers']);

		$this->addXMPPsByEntryID($id, $entry['xmpps']);

		$this->addEmailsByEntryID($id, $entry['emails']);

		$this->addWebsitesByEntryID($id, $entry['websites']);

		return array("status" => true, "type" => "success", "message" => _("Group entry successfully added"), "id" => $id);
	}

	/**
	 * Add Entries by Group ID
	 * @param {int} $groupid The group ID
	 * @param {array} $entries Array of Entry data
	 */
	public function addEntriesByGroupID($groupid, $entries) {
		$group = $this->getGroupByID($groupid);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		$sql = "INSERT INTO contactmanager_group_entries (`groupid`, `user`, `displayname`, `fname`, `lname`, `title`, `company`, `address`) VALUES (:groupid, :user, :displayname, :fname, :lname, :title, :company, :address)";
		$sth = $this->db->prepare($sql);
		foreach ($entries as $entry) {
			$sth->execute(array(
			':groupid' => $groupid,
			':user' => $entry['user'],
			':displayname' => $entry['displayname'],
			':fname' => $entry['fname'],
			':lname' => $entry['lname'],
			':title' => $entry['title'],
			':company' => $entry['company'],
			':address' => $entry['address'],
			));

			$id = $this->db->lastInsertId();
			$this->addNumbersByEntryID($id, $entry['numbers']);

			$this->addXMPPsByEntryID($id, $entry['xmpps']);

			$this->addEmailsByEntryID($id, $entry['emails']);

			$this->addWebsitesByEntryID($id, $entry['websites']);
		}

		return array("status" => true, "type" => "success", "message" => _("Group entries successfully added"));
	}

	/**
	 * Update Entry
	 * @param {int} $id    The entry ID
	 * @param {array} $entry Array of Entry Data
	 */
	public function updateEntry($id, $entry) {
		$group = $this->getGroupByID($entry['groupid']);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		if (!$this->getEntryByID($id)) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "UPDATE contactmanager_group_entries SET `groupid` = :groupid, `user` = :user, `displayname` = :displayname, `fname` = :fname, `lname` = :lname, `title` = :title, `company` = :company, `address` = :address WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':groupid' => $entry['groupid'],
		':user' => $entry['user'],
		':displayname' => $entry['displayname'],
		':fname' => $entry['fname'],
		':lname' => $entry['lname'],
		':title' => $entry['title'],
		':company' => $entry['company'],
		':address' => $entry['address'],
		':id' => $id,
		));

		$ret = $this->deleteNumbersByEntryID($id);
		$this->addNumbersByEntryID($id, $entry['numbers']);

		$ret = $this->deleteXMPPsByEntryID($id);
		$this->addXMPPsByEntryID($id, $entry['xmpps']);

		$ret = $this->deleteEmailsByEntryID($id);
		$this->addEmailsByEntryID($id, $entry['emails']);

		$ret = $this->deleteWebsitesByEntryID($id);
		$this->addWebsitesByEntryID($id, $entry['websites']);

		return array("status" => true, "type" => "success", "message" => _("Group entry successfully updated"), "id" => $id);
	}

	/**
	 * Get all numbers by entry ID
	 * @param {int} $entryid The entry ID
	 */
	public function getNumbersByEntryID($entryid) {
		$fields = array(
		'id',
		'entryid',
		'number',
		'extension',
		'type',
		'flags',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_numbers WHERE `entryid` = :entryid ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));
		$numbers = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $numbers;
	}

	/**
	 * Get allm numbers by group ID
	 * @param {int} $groupid The group ID
	 */
	public function getNumbersByGroupID($groupid) {
		$fields = array(
		'n.id',
		'n.entryid',
		'n.number',
		'n.extension',
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

	/**
	 * Delete a number by ID
	 * @param {int} $id The number ID
	 */
	public function deleteNumberByID($id) {
		$sql = "DELETE FROM contactmanager_entry_numbers WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group entry number successfully deleted"));
	}

	/**
	 * Delete all numbers by Entry ID
	 * @param {int} $entryid The entry ID
	 */
	public function deleteNumbersByEntryID($entryid) {
		$sql = "DELETE FROM contactmanager_entry_numbers WHERE `entryid` = :entryid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));

		return array("status" => true, "type" => "success", "message" => _("Group entry numbers successfully deleted"));
	}

	/**
	 * Delete number from group
	 * @param {int} $groupid The group ID
	 */
	public function deleteNumbersByGroupID($groupid) {
		$sql = "DELETE n FROM contactmanager_entry_numbers as n
		LEFT JOIN contactmanager_group_entries as e ON (n.entryid = e.id) WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entry numbers successfully deleted"));
	}

	/**
	 * Add Number by Entry ID
	 * @param {int} $entryid The entry ID
	 * @param {string} $number  The Number
	 */
	public function addNumberByEntryID($entryid, $number) {
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_numbers (entryid, number, extension, type, flags) VALUES (:entryid, :number, :extension, :type, :flags)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':entryid' => $entryid,
		':number' => $number['number'],
		':extension' => $number['extension'],
		':type' => $number['type'],
		':flags' => implode('|', $number['flags']),
		));

		$id = $this->db->lastInsertId();
		return array("status" => true, "type" => "success", "message" => _("Group entry number successfully added"), "id" => $id);
	}

	/**
	 * Add Numbers by Entry ID
	 * @param {int} $entryid The entry ID
	 * @param {array} $numbers Array of numbers to add
	 */
	public function addNumbersByEntryID($entryid, $numbers) {
		if(empty($numbers)) {
			return array("status" => true, "type" => "success", "message" => _("No Numbers to add"));
		}
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_numbers (entryid, number, extension, type, flags) VALUES (:entryid, :number, :extension, :type, :flags)";
		$sth = $this->db->prepare($sql);
		foreach ($numbers as $number) {
			$sth->execute(array(
			':entryid' => $entryid,
			':number' => $number['number'],
			':extension' => $number['extension'],
			':type' => $number['type'],
			':flags' => implode('|', $number['flags']),
			));
		}

		return array("status" => true, "type" => "success", "message" => _("Group entry numbers successfully added"));
	}

	/**
	 * Get all XMPP information about an entry
	 * @param {int} $entryid The entry ID
	 */
	public function getXMPPsByEntryID($entryid) {
		$fields = array(
		'id',
		'entryid',
		'xmpp',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_xmpps WHERE `entryid` = :entryid ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));
		$xmpps = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $xmpps;
	}

	/**
	 * Get all XMPPs By Group ID
	 * @param {int} $groupid The group ID
	 */
	public function getXMPPsByGroupID($groupid) {
		$fields = array(
		'x.id',
		'x.entryid',
		'x.xmpp',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_xmpps as x
		LEFT JOIN contactmanager_group_entries as e ON (x.entryid = e.id) WHERE `groupid` = :groupid ORDER BY e.id, x.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$xmpps = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $xmpps;
	}

	/**
	 * Delete XMPP information by id
	 * @param {int} $id The XMPP ID
	 */
	public function deleteXMPPByID($id) {
		$sql = "DELETE FROM contactmanager_entry_xmpps WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group entry XMPP successfully deleted"));
	}

	/**
	 * Delete XMPPs by Entry ID
	 * @param {int} $entryid The Entry ID
	 */
	public function deleteXMPPsByEntryID($entryid) {
		$sql = "DELETE FROM contactmanager_entry_xmpps WHERE `entryid` = :entryid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));

		return array("status" => true, "type" => "success", "message" => _("Group entry XMPPs successfully deleted"));
	}

	/**
	 * Delete all XMPPS from a group
	 * @param {int} $groupid The group ID
	 */
	public function deleteXMPPsByGroupID($groupid) {
		$sql = "DELETE x FROM contactmanager_entry_xmpps as x
		LEFT JOIN contactmanager_group_entries as e ON (x.entryid = e.id) WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entry XMPPs successfully deleted"));
	}

	/**
	 * Add XMPP Entry by ID
	 * @param {int} $entryid The entry ID
	 * @param {string} $xmpp    The xmpp user
	 */
	public function addXMPPByEntryID($entryid, $xmpp) {
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_xmpps (entryid, xmpp) VALUES (:entryid, :xmpp)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':entryid' => $entryid,
		':xmpp' => $xmpp['xmpp'],
		));

		$id = $this->db->lastInsertId();
		return array("status" => true, "type" => "success", "message" => _("Group entry XMPP successfully added"), "id" => $id);
	}

	/**
	 * All mulitple xmpps per user
	 * @param {int} $entryid The Entry ID
	 * @param {array} $xmpps   Array of Xmpps
	 */
	public function addXMPPsByEntryID($entryid, $xmpps) {
		if(empty($xmpps)) {
			return array("status" => true, "type" => "success", "message" => _("No XMPPs to add"));
		}
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_xmpps (entryid, xmpp) VALUES (:entryid, :xmpp)";
		$sth = $this->db->prepare($sql);
		foreach ($xmpps as $xmpp) {
			$sth->execute(array(
			':entryid' => $entryid,
			':xmpp' => $xmpp['xmpp'],
			));
		}

		return array("status" => true, "type" => "success", "message" => _("Group entry XMPPs successfully added"));
	}

	/**
	 * Get emails by Entry ID
	 * @param {int} $entryid The Entry ID
	 */
	public function getEmailsByEntryID($entryid) {
		$fields = array(
		'id',
		'entryid',
		'email',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_emails WHERE `entryid` = :entryid ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));
		$emails = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $emails;
	}

	public function getEmailsByGroupID($groupid) {
		$fields = array(
		'm.id',
		'm.entryid',
		'm.email',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_emails as m
		LEFT JOIN contactmanager_group_entries as e ON (m.entryid = e.id) WHERE `groupid` = :groupid ORDER BY e.id, m.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$emails = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $emails;
	}

	public function deleteEmailByID($id) {
		$sql = "DELETE FROM contactmanager_entry_emails WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group entry E-Mail successfully deleted"));
	}

	public function deleteEmailsByEntryID($entryid) {
		$sql = "DELETE FROM contactmanager_entry_emails WHERE `entryid` = :entryid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));

		return array("status" => true, "type" => "success", "message" => _("Group entry E-Mails successfully deleted"));
	}

	public function deleteEmailsByGroupID($groupid) {
		$sql = "DELETE m FROM contactmanager_entry_emails as m
		LEFT JOIN contactmanager_group_entries as e ON (m.entryid = e.id) WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entry E-Mails successfully deleted"));
	}

	public function addEmailByEntryID($entryid, $email) {
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_emails (entryid, email) VALUES (:entryid, :email)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':entryid' => $entryid,
		':email' => $email['email'],
		));

		$id = $this->db->lastInsertId();
		return array("status" => true, "type" => "success", "message" => _("Group entry E-Mail successfully added"), "id" => $id);
	}

	public function addEmailsByEntryID($entryid, $emails) {
		if(empty($emails)) {
			return array("status" => true, "type" => "success", "message" => _("No E-Mails to add"));
		}
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_emails (entryid, email) VALUES (:entryid, :email)";
		$sth = $this->db->prepare($sql);
		foreach ($emails as $email) {
			$sth->execute(array(
			':entryid' => $entryid,
			':email' => $email['email'],
			));
		}

		return array("status" => true, "type" => "success", "message" => _("Group entry E-Mails successfully added"));
	}

	public function getWebsitesByEntryID($entryid) {
		$fields = array(
		'id',
		'entryid',
		'website',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_websites WHERE `entryid` = :entryid ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));
		$websites = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $websites;
	}

	public function getWebsitesByGroupID($groupid) {
		$fields = array(
		'w.id',
		'w.entryid',
		'w.website',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_websites as w
		LEFT JOIN contactmanager_group_entries as e ON (w.entryid = e.id) WHERE `groupid` = :groupid ORDER BY e.id, w.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$websites = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $websites;
	}

	public function deleteWebsiteByID($id) {
		$sql = "DELETE FROM contactmanager_entry_websites WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));

		return array("status" => true, "type" => "success", "message" => _("Group entry Website successfully deleted"));
	}

	public function deleteWebsitesByEntryID($entryid) {
		$sql = "DELETE FROM contactmanager_entry_websites WHERE `entryid` = :entryid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));

		return array("status" => true, "type" => "success", "message" => _("Group entry Websites successfully deleted"));
	}

	public function deleteWebsitesByGroupID($groupid) {
		$sql = "DELETE w FROM contactmanager_entry_websites as w
		LEFT JOIN contactmanager_group_entries as e ON (w.entryid = e.id) WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		return array("status" => true, "type" => "success", "message" => _("Group entry Websites successfully deleted"));
	}

	public function addWebsiteByEntryID($entryid, $website) {
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_websites (entryid, website) VALUES (:entryid, :website)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':entryid' => $entryid,
		':website' => $website['website'],
		));

		$id = $this->db->lastInsertId();
		return array("status" => true, "type" => "success", "message" => _("Group entry Website successfully added"), "id" => $id);
	}

	public function addWebsitesByEntryID($entryid, $websites) {
		if(empty($websites)) {
			return array("status" => true, "type" => "success", "message" => _("No Websites to add"));
		}
		$entry = $this->getEntryByID($entryid);
		if (!$entry) {
			return array("status" => false, "type" => "danger", "message" => _("Group entry does not exist"));
		}

		$sql = "INSERT INTO contactmanager_entry_websites (entryid, website) VALUES (:entryid, :website)";
		$sth = $this->db->prepare($sql);
		foreach ($websites as $website) {
			$sth->execute(array(
			':entryid' => $entryid,
			':website' => $website['website'],
			));
		}

		return array("status" => true, "type" => "success", "message" => _("Group entry Websites successfully added"));
	}

	/**
	 * Get all contacts for a userman user ID
	 * @param {int} $id A valid userman ID
	 */
	public function getContactsByUserID($id) {
		if(!empty($this->contactsCache)) {
			return $this->contactsCache;
		}
		$umentries = $this->freepbx->Userman->getAllContactInfo();
		if($id = -1) {
			$groups = $this->getGroups();
		} else {
			$groups = $this->getGroupsByOwner($id);
		}
		$contacts = array();
		foreach($groups as $group) {
			switch($group['type']) {
				case "userman":
				$entries = $umentries;
				$final = array();
				foreach($entries as $entry) {
					if(!$this->showUsermanContact($entry['id'])) {
						continue;
					}
					$entry['type'] = "userman";
					//standardize all phone numbers, digits only
					$entry['numbers'] = array(
					'cell' => preg_replace('/\D/','',$entry['cell']),
					'work' => preg_replace('/\D/','',$entry['work']),
					'home' => preg_replace('/\D/','',$entry['home']),
					'fax' => preg_replace('/\D/','',$entry['fax']),
					);
					unset($entry['cell']);
					unset($entry['work']);
					unset($entry['home']);
					unset($entry['fax']);
					if(isset($entry['xmpp'])) {
						$entry['xmpps']['xmpp'] = $entry['xmpp'];
						unset($entry['xmpp']);
					}
					$entry['displayname'] = !empty($entry['displayname']) ? $entry['displayname'] : $entry['fname'] . " " . $entry['lname'];
					$final[] = $entry;
				}
				$contacts = array_merge($contacts, $final);
				break;
				case "external":
				$entries = $this->getEntriesByGroupID($group['id']);
				if(is_array($entries)) {
					foreach($entries as &$entry) {
						$numbers = array();
						foreach($entry['numbers'] as $number) {
							$numbers[$number['type']] = preg_replace("/\D/","",$number['number']);
						}
						$xmpps = array();
						if(!empty($entry['xmpps'])) {
							foreach($entry['xmpps'] as $xmpp) {
								$xmpps[] = $xmpp['xmpp'];
							}
						}
						unset($entry['emails']);
						unset($entry['websites']);
						unset($entry['numbers']);
						unset($entry['xmpps']);
						$entry['xmpps'] = $xmpps;
						$entry['numbers'] = $numbers;
						$entry['displayname'] = !empty($entry['displayname']) ? $entry['displayname'] : $entry['fname'] . " " . $entry['lname'];
						$entry['type'] = "external";
					}
					$contacts = array_merge($contacts, $entries);
				}
				break;
				case "internal":
				$entries = $this->getEntriesByGroupID($group['id']);
				$final = array();
				foreach($entries as &$entry) {
					foreach($umentries as $um) {
						if($um['id'] == $entry['user']) {
							$entry['type'] = "internal";
							$entry['displayname'] = !empty($entry['displayname']) ? $entry['displayname'] : $entry['fname'] . " " . $entry['lname'];
							//standardize all phone numbers, digits only
							$entry['numbers'] = array(
							'cell' => preg_replace('/\D/','',$um['cell']),
							'work' => preg_replace('/\D/','',$um['work']),
							'home' => preg_replace('/\D/','',$um['home']),
							'fax' => preg_replace('/\D/','',$um['fax']),
							);
							$final[] = $entry;
						}
					}
				}
				$contacts = array_merge($contacts, $final);
				break;
			}
		}
		$this->contactsCache = $contacts;
		return $this->contactsCache;
	}

	/**
	 * Lookup a contact in the global and local directory
	 * @param {int} $id The userman user id
	 * @param {string} $search search string
	 * @param {string} $regexp Regular Expression pattern to replace
	 */
	public function lookupByUserID($id, $search, $regexp = null) {
		if(!empty($this->contactsCache[$search])) {
			return $this->contactsCache[$search];
		}
		$contacts = $this->getContactsByUserID($id);
		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($contacts));
		foreach($iterator as $key => $value) {
			$value = !empty($regexp) ? preg_replace($regexp,'',$value) : $value;
			$value = trim($value);
			if(!empty($value) && preg_match('/' . $search . '/i',$value)) {
				$k = $iterator->getSubIterator(0)->key();
				$this->contactsCache[$search] = $contacts[$k];
				return $this->contactsCache[$search];
				break;
			}
		}
		return false;
	}

	/**
	 * Lookup a contact in the global and local directory
	 * @param {int} $id The userman user id
	 * @param {string} $search search string
	 * @param {string} $regexp Regular Expression pattern to replace
	 */
	public function lookupMultipleByUserID($id, $search, $regexp = null) {
		$contacts = $this->getContactsByUserID($id);
		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($contacts));
		$final = array();
		$list = array();
		foreach($iterator as $key => $value) {
			$value = !empty($regexp) ? preg_replace($regexp,'',$value) : $value;
			$value = trim($value);
			$k = $iterator->getSubIterator(0)->key();
			if(!in_array($k, $list) && !empty($value) && preg_match('/' . $search . '/i',$value)) {
				$final[] = $contacts[$k];
				$list[] = $k;
			}
		}
		return $final;
	}

	/**
	 * Userman Page hook
	 */
	public function usermanShowPage() {
		if(isset($_REQUEST['action'])) {
			switch($_REQUEST['action']) {
				case 'adduser':
				return load_view(dirname(__FILE__).'/views/userman_hook.php',array("enabled" => true));
				break;
				case 'showuser':
				return load_view(dirname(__FILE__).'/views/userman_hook.php',array("enabled" => $this->showUsermanContact($_REQUEST['user'])));
				break;
				default:
				break;
			}
		}
	}

	/**
	 * Whether to show the Userman Contact In Contact Manager or not
	 * @param {int} $id The userman user id
	 */
	public function showUsermanContact($id) {
		if($this->freepbx->Userman->getModuleSettingByID($id,'contactmanager','processed')) {
			return $this->freepbx->Userman->getModuleSettingByID($id,"contactmanager","show");
		} else {
			return true;
		}
	}

	function importCSV($filename, $group) {
		$f = fopen($filename, "r");

		while (($line = fgetcsv($f, 0, ',', '"', '\\'))) {
			if ($header) {
				$csv[] = $line;
			} else {
				$header = $line;
			}
		}

		foreach ($csv as $row) {
			$a = array_combine($header, $row);
			$contact = array(
				'id' => '',
				'groupid' => $group,
				'user' => -1,
				'displayname' => $this->getField($a, array("Display Name", "Name")),
				'fname' => $this->getField($a, array("First Name", "Given Name")),
				'lname' => $this->getField($a, array("Last Name", "Family Name")),
				'title' => $this->getField($a, array("Title", "Organization 1 - Title")),
				'company' => $this->getField($a, array("Company", "Organization 1 - Name")),
				'address' => $this->getField($a, array("Address", "Address 1 - Formatted")),

				'numbers' => $this->getField($a, array(array("Phone"), array("Number"), array("Phone 1 - Value", "Phone 1 - Type"), array("Phone 2 - Value", "Phone 2 - Type"), array("Phone 3 - Value", "Phone 3 - Type")), true, 'numbers'),
				'emails' => $this->getField($a, array(array("E-mail"), array("Email"), array("E-mail 1 - Value"), array("E-mail 2 - Value"), array("E-mail 3 - Value")), true, "emails"),
				'websites' => $this->getField($a, array(array("Website"), array("Website 1 - Value"), array("Website 2 - Value"), array("Website 3 - Value")), true, "websites"),
			);

			$user = $this->getField($a, array("UserManID"));
			if ($user) {
				$contact['user'] = $user;
			}

			$this->addEntryByGroupID($group, $contact);
		}
	}

	function exportCSV($group) {
		$entries = $this->getEntriesByGroupID($group);
		foreach ($entries as $entry) {
			$entry['numbers'] = array_values($entry['numbers']);
			$entry['emails'] = array_values($entry['emails']);
			$entry['websites'] = array_values($entry['websites']);

			$contact = array(
				"Display Name" => $entry['displayname'],
				"First Name" => $entry['fname'],
				"Last Name" => $entry['lname'],
				"Title" => $entry['title'],
				"Company" => $entry['company'],
				"Address" => $entry['address'],

				"Phone 1 - Type" => (count($entry['numbers']) >= 1) ? $entry['numbers'][0]['type'] : NULL,
				"Phone 1 - Value" => (count($entry['numbers']) >= 1) ? $entry['numbers'][0]['number'] : NULL,
				"Phone 2 - Type" => (count($entry['numbers']) >= 2) ? $entry['numbers'][1]['type'] : NULL,
				"Phone 2 - Value" => (count($entry['numbers']) >= 2) ? $entry['numbers'][1]['number'] : NULL,
				"Phone 3 - Type" => (count($entry['numbers']) >= 3) ? $entry['numbers'][2]['type'] : NULL,
				"Phone 3 - Value" => (count($entry['numbers']) >= 3) ? $entry['numbers'][2]['number'] : NULL,

				"E-mail 1 - Value" => (count($entry['emails']) >= 1) ? $entry['emails'][0]['email'] : NULL,
				"E-mail 2 - Value" => (count($entry['emails']) >= 2) ? $entry['emails'][1]['email'] : NULL,
				"E-mail 3 - Value" => (count($entry['emails']) >= 3) ? $entry['emails'][2]['email'] : NULL,

				"Website 1 - Value" => (count($entry['websites']) >= 1) ? $entry['websites'][0]['website'] : NULL,
				"Website 2 - Value" => (count($entry['websites']) >= 2) ? $entry['websites'][1]['website'] : NULL,
				"Website 3 - Value" => (count($entry['websites']) >= 3) ? $entry['websites'][2]['website'] : NULL,

				"UserManID" => $entry['user'],
			);

			foreach ($contact as $key => $val) {
				if (strpos($val, "\"")) {
					$contact[$key] = str_replace("\"", "\"\"", $val);
				} else if (strpos($val, ",")) {
					$contact[$key] = "\"" . $val . "\"";
				}
			}

			if (!isset($file)) {
				$file = implode(",", array_keys($contact)) . "\n";
			}

			$file.= implode(",", $contact) . "\n";
		}

		header('Content-Type: text/csv');
		header('Content-disposition: attachment; filename=contacts.csv');
		print($file);
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
}
