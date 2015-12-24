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
	private $types = array();

	public function __construct($freepbx = null) {
		$this->db = $freepbx->Database;
		$this->freepbx = $freepbx;
		$this->userman = $this->freepbx->Userman;

		$this->types = array(
			"internal" => array(
				"name" => _("Internal"),
				"fields" => array(
					"displayname" => _("Display Name"),
					"fname" => _("First Name"),
					"lname" => _("Last Name"),
					"username" => _("User"),
					"actions" => _("Actions")
				)
			),
			"external" => array(
				"name" => _("External"),
				"fields" => array(
					"displayname" => _("Display Name"),
					"company" => _("Company"),
					"numbers" => _("Numbers"),
					"actions" => _("Actions")
				)
			),
			"userman" => array(
				"name" => _("User Manager"),
				"fields" => array(
					"displayname" => _("User"),
					"actions" => _("Actions")
				)
			)
		);
	}

	public function install() {
		global $db;

		$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_groups` (
		 `id` int(11) NOT NULL AUTO_INCREMENT,
		 `owner` int(11) NOT NULL,
		 `name` varchar(80) NOT NULL,
		 `type` varchar(25) NOT NULL,
		 PRIMARY KEY (`id`)
		);';

		$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_group_entries` (
		 `id` int(11) NOT NULL AUTO_INCREMENT,
		 `groupid` int(11) NOT NULL,
		 `user` int(11) NOT NULL,
		 `displayname` varchar(100) default NULL,
		 `fname` varchar(100) default NULL,
		 `lname` varchar(100) default NULL,
		 `title` varchar(100) default NULL,
		 `company` varchar(100) default NULL,
		 `address` varchar(200) default NULL,
		 PRIMARY KEY (`id`)
		);';

		$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_numbers` (
		 `id` int(11) NOT NULL AUTO_INCREMENT,
		 `entryid` int(11) NOT NULL,
		 `number` varchar(100) default NULL,
		 `extension` varchar(100) default NULL,
		 `type` varchar(100),
		 `flags` varchar(100),
		 PRIMARY KEY (`id`)
		);';

		$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_xmpps` (
		 `id` int(11) NOT NULL AUTO_INCREMENT,
		 `entryid` int(11) NOT NULL,
		 `xmpp` varchar(100) default NULL,
		 PRIMARY KEY (`id`)
		);';

		$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_emails` (
		 `id` int(11) NOT NULL AUTO_INCREMENT,
		 `entryid` int(11) NOT NULL,
		 `email` varchar(100) default NULL,
		 PRIMARY KEY (`id`)
		);';

		$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_websites` (
		 `id` int(11) NOT NULL AUTO_INCREMENT,
		 `entryid` int(11) NOT NULL,
		 `website` varchar(100) default NULL,
		 PRIMARY KEY (`id`)
		);';

		foreach ($sql as $statement){
			$check = $db->query($statement);
			if (\DB::IsError($check)){
				die_freepbx("Can not execute $statement : " . $check->getMessage() .  "\n");
			}
		}

		outn(_("checking for title field.."));
		$sql = "SELECT `title` FROM contactmanager_group_entries";
		$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
		if(\DB::IsError($check)) {
			// add new field
			$sql = "ALTER TABLE contactmanager_group_entries ADD `title` varchar(100), ADD `company` varchar(100)";
			$result = $db->query($sql);
			if(\DB::IsError($result)) {
				out(_("ERROR failed to update title field"));
			} else {
				out(_("OK"));
			}
		} else {
			out(_("already exists"));
		}

		outn(_("checking for displayname field.."));
		$sql = "SELECT `displayname` FROM contactmanager_group_entries";
		$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
		if(\DB::IsError($check)) {
			// add new field
			$sql = "ALTER TABLE contactmanager_group_entries ADD `displayname` varchar(100)";
			$result = $db->query($sql);
			if(\DB::IsError($result)) {
				out(_("ERROR failed to update displayname field"));
			} else {
				out(_("OK"));
			}
		} else {
			out(_("already exists"));
		}

		outn(_("checking for address field.."));
		$sql = "SELECT `address` FROM contactmanager_group_entries";
		$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
		if(\DB::IsError($check)) {
			// add new field
			$sql = "ALTER TABLE contactmanager_group_entries ADD `address` varchar(200)";
			$result = $db->query($sql);
			if(\DB::IsError($result)) {
				out(_("ERROR failed to update address field"));
			} else {
				out(_("OK"));
			}
		} else {
			out(_("already exists"));
		}

		outn(_("checking for extension field.."));
		$sql = "SELECT `extension` FROM contactmanager_entry_numbers";
		$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
		if(\DB::IsError($check)) {
			// add new field
			$sql = "ALTER TABLE contactmanager_entry_numbers ADD `extension` varchar(100)";
			$result = $db->query($sql);
			if(\DB::IsError($result)) {
				out(_("ERROR failed to update extension field"));
			} else {
				out(_("OK"));
			}
		} else {
			out(_("already exists"));
		}

		$sql = "SELECT * FROM contactmanager_groups WHERE type = 'userman'";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$grps = $sth->fetchAll(\PDO::FETCH_ASSOC);
		if(empty($grps)) {
			$ret = $this->addGroup(_("User Manager Group"),"userman");
			$id = $this->freepbx->Userman->getAutoGroup();
			$id = !empty($id) ? $id : 1;
			$this->freepbx->Userman->setModuleSettingByGID($id,'contactmanager','groups',array($ret['id']));
		}
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

	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'grid':
				return true;
			break;
		}
		return false;
	}

	public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case 'grid':
				$group = $this->getGroupByID($_REQUEST['group']);
				$entries = $this->getEntriesByGroupID($_REQUEST['group']);
				$entries = array_values($entries);
				switch($group['type']) {
					case "internal":
						foreach($entries as &$entry) {
							$user = $this->freepbx->Userman->getUserByID($entry['user']);
							$entry['fname'] = !empty($entry['fname']) ? $entry['fname'] : $user['fname'];
							$entry['lname'] = !empty($entry['lname']) ? $entry['lname'] : $user['lname'];
							$entry['displayname'] = !empty($entry['displayname']) ? $entry['displayname'] : (!empty($user['displayname']) ? $user['displayname'] : $entry['fname'] . " " . $entry['lname']);
							$entry['displayname'] = !empty($entry['displayname']) ? $entry['displayname'] : $user['username'];
							$entry['title'] = !empty($entry['title']) ? $entry['title'] : $user['title'];
							$entry['company'] = !empty($entry['company']) ? $entry['company'] : $user['company'];
							$entry['username'] = !empty($user['displayname']) ? $user['displayname'] : $entry['fname'] . " " . $entry['lname'];
							$entry['actions'] = '<a href="config.php?display=contactmanager&amp;action=showentry&amp;group='.$_REQUEST['group'].'&amp;entry='.$entry['uid'].'"><i class="fa fa-edit fa-fw"></i></a><a href="config.php?display=contactmanager&amp;action=delentry&amp;group='.$_REQUEST['group'].'&amp;entry='.$entry['uid'].'"><i class="fa fa-ban fa-fw"></i></a>';
						}
					break;
					case "userman":
						foreach($entries as &$entry) {
							$user = $this->freepbx->Userman->getUserByID($entry['user']);
							$entry = $user;
							$entry['displayname'] = !empty($user['displayname']) ? $user['displayname'] : $user['fname'] . " " . $user['lname'];
							$entry['displayname'] = !empty($user['displayname']) ? $user['displayname'] . " (".$user['username'].")" : $user['username'];
							$entry['actions'] = '<a href="config.php?display=userman&action=showuser&user='.$entry['id'].'"><i class="fa fa-edit fa-fw"></i></a>';
						}
					break;
					case "external":
						foreach($entries as &$entry) {
							$entry['numbers'] = !empty($entry['numbers']) ? $entry['numbers'] : array();
							$nums = array();
							foreach($entry['numbers'] as &$number) {
								$nums[] = $number['number'] . "(".$number['type'].")";
							}
							$entry['numbers'] = !empty($entry['numbers']) ? implode("<br>",$nums) : "";
							$entry['actions'] = '<a href="config.php?display=contactmanager&amp;action=showentry&amp;group='.$_REQUEST['group'].'&amp;entry='.$entry['uid'].'"><i class="fa fa-edit fa-fw"></i></a><a href="config.php?display=contactmanager&amp;action=delentry&amp;group='.$_REQUEST['group'].'&amp;entry='.$entry['uid'].'"><i class="fa fa-ban fa-fw"></i></a>';
						}
					break;
				}
				return $entries;
			break;
		}
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
				if(!empty($_POST['number']) && is_array($_POST['number'])) {
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
				}

				$xmpps = array();
				if(!empty($_POST['xmpp']) && is_array($_POST['xmpp'])) {
					foreach ($_POST['xmpp'] as $index => $xmpp) {
						if (!$xmpp) {
							continue;
						}
						$xmpps[$index]['xmpp'] = $xmpp;
					}
				}

				$emails = array();
				if(!empty($_POST['email']) && is_array($_POST['email'])) {
					foreach ($_POST['email'] as $index => $email) {
						if (!$email) {
							continue;
						}
						$emails[$index]['email'] = $email;
					}
				}

				$website = array();
				if(!empty($_POST['website']) && is_array($_POST['website'])) {
					foreach ($_POST['website'] as $index => $website) {
						if (!$website) {
							continue;
						}
						$websites[$index]['website'] = $website;
					}
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
		$groups = $this->getGroupsGroupedByType();
		$userman = setup_userman();
		$users = $userman->getAllUsers();

		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
		if ($action == "delentry") {
			$action = "";
		}

		$numbertypes = array(
			'work' => _('Work'),
			'home' => _('Home'),
			'cell' => _('Cell'),
			'other' => _('Other'),
		);

		$content = '';
		$rnav = load_view(dirname(__FILE__).'/views/rnav.php', array("action" => $action));

		switch($action) {
			case "showgroup":
			case "addgroup":
				if ($action == "showgroup" && !empty($_REQUEST['group'])) {
					$group = $this->getGroupByID($_REQUEST['group']);
					$entries = $this->getEntriesByGroupID($_REQUEST['group']);
				}

				$content = load_view(dirname(__FILE__).'/views/group.php', array("group" => $group, "entries" => $entries, "users" => $users, "message" => $this->message));
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

					$content = load_view(dirname(__FILE__).'/views/entry.php', array("numbertypes" => $numbertypes, "group" => $group, "entry" => $entry, "users" => $users, "message" => $this->message));
				}
			break;
			default:
				$file['post'] = ini_get('post_max_size');
				$file['upload'] = ini_get('upload_max_filesize');
				$content = load_view(dirname(__FILE__).'/views/grid.php', array("groups" => $groups, "types" => $this->types, "file" => $file));
			break;
		}

		return load_view(dirname(__FILE__).'/views/main.php', array("message" => $this->message, "content" => $content, "rnav" => $rnav));
	}

	public function getActionBar($request) {
		$buttons = array();

		switch ($request['display']) {
		case 'contactmanager':
			switch($request['action']) {
			case 'delentry':
			case 'showgroup':
				$buttons['delete'] = array(
					'name' => 'delete',
					'id' => 'delete',
					'value' => _('Delete')
				);
			/* Fall through */
			case 'addgroup':
				$buttons['reset'] = array(
					'name' => 'reset',
					'id' => 'reset',
					'value' => _('Reset')
				);
				$buttons['submit'] = array(
					'name' => 'submit',
					'id' => 'submit',
					'value' => _('Submit')
				);
				break;
			case 'showentry':
				$buttons['delete'] = array(
					'name' => 'delete',
					'id' => 'delete',
					'value' => _('Delete')
				);
			/* Fall through */
			case 'addentry':
				$buttons['reset'] = array(
					'name' => 'reset',
					'id' => 'reset',
					'value' => _('Reset')
				);
				$buttons['submit'] = array(
					'name' => 'submit',
					'id' => 'submit',
					'value' => _('Submit')
				);
				break;
			}
			break;
		}

		return $buttons;
	}

	public function usermanDelGroup($id,$display,$data) {
	}

	public function usermanAddGroup($id, $display, $data) {
		$this->usermanUpdateGroup($id,$display,$data);
	}

	public function usermanUpdateGroup($id,$display,$data) {
		if($display == 'userman' && isset($_POST['contactmanager_show'])) {
			if($_POST['contactmanager_show'] == "true") {
				$this->userman->setModuleSettingByGID($id,'contactmanager','show', true);
			} else {
				$this->userman->setModuleSettingByGID($id,'contactmanager','show', false);
			}

			if(!$this->checkCOSStatus()) {
				if(!empty($_POST['contactmanager_groups'])) {
					$this->freepbx->Userman->setModuleSettingByGID($id,'contactmanager','groups',$_POST['contactmanager_groups']);
				} else {
					$this->freepbx->Userman->setModuleSettingByGID($id,'contactmanager','groups',null);
				}
			}
		}
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
			$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',true);
		} else if($display == 'userman' && isset($_POST['contactmanager_show'])) {
			if($_POST['contactmanager_show'] == "true") {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',true);
			} elseif($_POST['contactmanager_show'] == "false") {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',false);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',null);
			}
			if(!$this->checkCOSStatus()) {
				if(!empty($_POST['contactmanager_groups'])) {
					$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',$_POST['contactmanager_groups']);
				} else {
					$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',null);
				}
			}
		}
	}

	public function usermanUpdateUser($id, $display, $data) {
		if($display == 'userman' && isset($_POST['contactmanager_show'])) {
			if($_POST['contactmanager_show'] == "true") {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',true);
			} elseif($_POST['contactmanager_show'] == "false") {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',false);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','show',null);
			}
			if(!$this->checkCOSStatus()) {
				if(!empty($_POST['contactmanager_groups'])) {
					$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',$_POST['contactmanager_groups']);
				} else {
					$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',null);
				}
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

	public function getGroupsGroupedByType() {
		$final = array();
		$sql = "SELECT * FROM contactmanager_groups ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$array = $sth->fetchAll(\PDO::FETCH_ASSOC);
		foreach($array as $a) {
			$final[$a['type']][] = $a;
		}
		return $final;
	}

	/**
	 * Get all groups by owner
	 * @param {int} $owner The owner ID
	 */
	public function getGroupsbyOwner($owner) {
		if($this->checkCOSStatus()) {
			$sql = "SELECT * FROM contactmanager_groups WHERE `owner` = :id OR `owner` = -1 ORDER BY id";
			$user = $this->freepbx->Userman->getUserByID($owner);
			if(empty($user) || $user['default_extension'] == "none") {
				return array();
			}
			$coses = $this->freepbx->cos->getCoSforUser($user['default_extension']);
			$coses = is_array($coses) ? $coses : array();
			$assigned = array();
			foreach($coses as $c) {
				$all = $this->freepbx->cos->getAll($c);
				if(!empty($all)) {
					$grps = array_keys($all['contactgroupsallow']);
					$assigned = array_merge($assigned,$grps);
				}
			}
		} else {
			$user = $this->freepbx->Userman->getUserByID($owner);
			$assigned = $this->freepbx->Userman->getModuleSettingByID($user['id'],'contactmanager','groups',true);
			$assigned = is_array($assigned) ? $assigned : array();
		}
		$sql = "SELECT * FROM contactmanager_groups WHERE `owner` = :id";
		if (!empty($assigned)) {
			$sql .= " OR `id` IN (".implode(',',$assigned).")";
		}
		$sql .= " ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $owner));
		$ret = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $ret;
	}

	/**
	 * Get all groups by owner unrestricted by Userman Settings
	 * @param  int $owner Owner ID (-1 for all)
	 * @return array        Array of groups
	 */
	public function getUnrestrictedGroupsbyOwner($owner) {
		$sql = "SELECT * FROM contactmanager_groups WHERE `owner` = :id ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $owner));
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Check if COS is enabled or not.
	 * TODO: There has to be a better way to do this
	 */
	private function checkCOSStatus() {
		if($this->freepbx->Modules->checkStatus("cos") && $this->freepbx->Cos->isLicensed()) {
			return true;
		}
		return false;
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
		'e.displayname',
		'e.fname',
		'e.lname',
		'e.title',
		'e.company',
		'e.address as address',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_group_entries as e WHERE e.id = :id";
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
					$number['flags'][] = 'phone';
					$entry['numbers'][$number['id']] = array(
					'number' => $number['number'],
					'extension' => $number['extension'],
					'type' => $number['type'],
					'flags' => $number['flags'],
					'primary' => isset($number['flags'][0]) ? implode(",", $number['flags']) : 'phone'
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
				if(!$this->freepbx->Userman->getCombinedModuleSettingByID($entry['user'],"contactmanager","show")) {
					return false;
				} else {
					$user = $this->freepbx->Userman->getUserByID($entry['user']);
					if(!empty($user)) {
						$entry = array_merge($entry,$user);
					} else {
						$this->deleteEntryByID($entry['uid']);
					}
				}
			case "internal":
			$user = $this->freepbx->Userman->getUserByID($entry['user']);
			if(!empty($user)) {
				$entries[$key] = array_merge($entry,$user);
			} else {
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
		$entries = array();
		$fields = array(
		'e.id',
		'e.id as uid',
		'e.groupid',
		'e.user',
		'e.displayname',
		'e.fname',
		'e.lname',
		'e.title',
		'e.company',
		'e.address',
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_group_entries as e WHERE `groupid` = :groupid ORDER BY e.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$e = $sth->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE);

		$group = $this->getGroupByID($groupid);
		$entries = array();
		switch($group['type']) {
			case "userman":
			foreach($e as $key => $entry) {
				if($this->freepbx->Userman->getCombinedModuleSettingByID($entry['user'],"contactmanager","show")) {
					$user = $this->freepbx->Userman->getUserByID($entry['user']);
					if(!empty($user)) {
						$entries[$key] = array_merge($entry,$user);
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
					$entries[$key] = array_merge($entry,$user);
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
		':displayname' => !empty($entry['displayname']) ? $entry['displayname'] : '',
		':fname' => !empty($entry['fname']) ? $entry['fname'] : '',
		':lname' => !empty($entry['lname']) ? $entry['lname'] : '',
		':title' => !empty($entry['title']) ? $entry['title'] : '',
		':company' => !empty($entry['company']) ? $entry['company'] : '',
		':address' => !empty($entry['address']) ? $entry['address'] : '',
		));

		$id = $this->db->lastInsertId();

		$this->addNumbersByEntryID($id, !empty($entry['numbers']) ? $entry['numbers'] : '');

		$this->addXMPPsByEntryID($id, !empty($entry['xmpps']) ? $entry['xmpps'] : '');

		$this->addEmailsByEntryID($id, !empty($entry['emails']) ? $entry['emails'] : '');

		$this->addWebsitesByEntryID($id, !empty($entry['websites']) ? $entry['websites'] : '');

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

		$entry['numbers'] = !empty($entry['numbers']) ? $entry['numbers'] : array();
		$entry['xmpps'] = !empty($entry['xmpps']) ? $entry['xmpps'] : array();
		$entry['emails'] = !empty($entry['emails']) ? $entry['emails'] : array();
		$entry['websites'] = !empty($entry['websites']) ? $entry['websites'] : array();

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
			':extension' => isset($number['extension']) ? $number['extension'] : "",
			':type' => isset($number['type']) ? $number['type'] : "",
			':flags' => !empty($number['flags']) ? implode('|', $number['flags']) : "",
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
		if($id == -1) {
			$groups = $this->getGroups();
		} else {
			$groups = $this->getGroupsByOwner($id);
		}
		$contacts = array();
		$entries = array();
		foreach($groups as $group) {
			switch($group['type']) {
				case "userman":
				$entries = $umentries;
				if(!empty($entries) && is_array($entries)) {
					$final = array();
					foreach($entries as $entry) {
						if(!$this->freepbx->Userman->getCombinedModuleSettingByID($entry['id'],"contactmanager","show")) {
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
				}
				break;
				case "external":
				$entries = $this->getEntriesByGroupID($group['id']);
				if(!empty($entries) && is_array($entries)) {
					foreach($entries as &$entry) {
						$numbers = array();
						if(!empty($entry['numbers']) && is_array($entry['numbers'])) {
							foreach($entry['numbers'] as $number) {
								$numbers[$number['type']] = preg_replace("/\D/","",$number['number']);
							}
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
				if(!empty($entries) && is_array($entries)) {
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
				}
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
			$groups = $this->getUnrestrictedGroupsbyOwner(-1);
			$cos = $this->checkCOSStatus();
			switch($_REQUEST['action']) {
				case 'showgroup':
					$assigned = $this->freepbx->Userman->getModuleSettingByGID($_REQUEST['group'],"contactmanager","groups",true);
					if(is_null($assigned)) {
						foreach($groups as $group) {
							$assigned[] = $group['id'];
						}
					}
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = in_array($group['id'],$assigned);
					}
					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("mode" => "group", "cos" => $cos, "groups" => $groups, "enabled" => $this->userman->getModuleSettingByGID($_REQUEST['group'],'contactmanager','show')))
						)
					);
				case 'addgroup':
					foreach($groups as $group) {
						$assigned[] = $group['id'];
					}
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = in_array($group['id'],$assigned);
					}
					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("mode" => "group", "cos" => $cos, "groups" => $groups, "enabled" => true))
						)
					);
				break;
				case 'adduser':
					$assigned = array();
					foreach($groups as $group) {
						$assigned[] = $group['id'];
					}
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = in_array($group['id'],$assigned);
					}
					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("mode" => "user", "cos" => $cos, "groups" => $groups, "enabled" => true))
						)
					);
				break;
				case 'showuser':
					$assigned = $this->freepbx->Userman->getModuleSettingByID($_REQUEST['user'],"contactmanager","groups",true);
					if(is_null($assigned)) {
						$assigned = array();
					}
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = in_array($group['id'],$assigned);
					}

					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("mode" => "user", "cos" => $cos, "groups" => $groups, "enabled" => $this->freepbx->Userman->getModuleSettingByID($_REQUEST['user'],"contactmanager","show",true)))
						)
					);
				break;
				default:
				break;
			}
		}
	}

	public function bulkhandlerGetTypes() {
		return array(
			'contacts' => array(
				'name' => _('Contacts'),
				'description' => _('Contacts and internal/external groups from the Contact Manager module.')
			)
		);
	}

	public function bulkhandlerGetHeaders($type) {
		switch ($type) {
		case 'contacts':
			return array(
				'groupname' => array(
					'required' => true,
					'identifier' => _('Group Name'),
					'description' => _('Name of group for contact.  If group does not exist, it will be created.'),
				),
				'grouptype' => array(
					'required' => true,
					'identifier' => _('Group Type'),
					'description' => _('Type of group for contact.'),
					'values' => array(
						'internal' => _('Internal'),
						'external' => _('External')
					),
				),
				'displayname' => array(
					'identifier' => _('Display Name'),
					'description' => _('Display Name'),
				),
				'fname' => array('description' => _('First Name')),
				'lname' => array('description' => _('Last Name')),
				'title' => array('description' => _('Title')),
				'company' => array('description' => _('Company')),
				'address' => array('description' => _('Address')),
				'userman_username' => array('description' => _('User Manager username this contact should point to.  Internal contacts only.')),
				'phone_1_number' => array('description' => _('Phone number.  External contacts only.')),
				'phone_1_type' => array(
					'description' => _('Type of phone number.  External contacts only.'),
					'values' => array(
						'work' => _('Work'),
						'home' => _('Home'),
						'cell' => _('Cell'),
						'other' => _('Other')
					),
				),
				'phone_1_extension' => array('description' => _('Extension.  External contacts only.')),
				'phone_1_flags' => array('description' => _('Comma-delimited list of flags.  (Example: sms,fax)  External contacts only.')),
				'phone_2_number' => array('description' => _('Phone number.  External contacts only.')),
				'phone_2_type' => array(
					'description' => _('Type of phone number.  External contacts only.'),
					'values' => array(
						'work' => _('Work'),
						'home' => _('Home'),
						'cell' => _('Cell'),
						'other' => _('Other')
					),
				),
				'phone_2_extension' => array('description' => _('Extension.  External contacts only.')),
				'phone_2_flags' => array('description' => _('Comma-delimited list of flags.  (Example: sms,fax)  External contacts only.')),
				'phone_3_number' => array('description' => _('Phone number.  External contacts only.')),
				'phone_3_type' => array(
					'description' => _('Type of phone number.  External contacts only.'),
					'values' => array(
						'work' => _('Work'),
						'home' => _('Home'),
						'cell' => _('Cell'),
						'other' => _('Other')
					),
				),
				'phone_3_extension' => array('description' => _('Extension.  External contacts only.')),
				'phone_3_flags' => array('description' => _('Comma-delimited list of flags.  (Example: sms,fax)  External contacts only.')),
				'email_1' => array('description' => _('E-mail address.  External contacts only.')),
				'email_2' => array('description' => _('E-mail address.  External contacts only.')),
				'email_3' => array('description' => _('E-mail address.  External contacts only.')),
			);

			break;
		}
	}

	public function bulkhandlerImport($type, $rawData) {
		$ret = NULL;

		switch ($type) {
		case 'contacts':
			foreach ($rawData as $data) {
				if (empty($data['groupname'])) {
					return array(
						'status' => false,
						'message' => _('Group name is required.'),
					);
				}

				if (empty($data['grouptype'])) {
					return array(
						'status' => false,
						'message' => _('Group type is required.'),
					);
				}

				$group = NULL;

				$groups = $this->getGroups();
				foreach ($groups as $g) {
					if ($g['name'] == $data['groupname'] && $g['type'] == $data['grouptype']) {
						/* Found an existing group.  Let's bail. */
						$group = $g;
						break;
					}
				}

				if (!$group) {
					$res = $this->addGroup($data['groupname'], $data['grouptype']);
					if ($res['status'] && $res['id']) {
						$group = $this->getGroupByID($res['id']);
					} else {
						$ret = array(
							'status' => false,
							'message' => _('Group not found and could not be created.'),
						);
					}
				}

				if (!empty($data['userman_username'])) {
					$user = $this->userman->getUserByUsername($data['userman_username']);
				}
				$contact = array(
					'id' => '',
					'groupid' => $group['id'],
					'user' => $user ? $user['username'] : -1,
					'displayname' => $data['displayname'],
					'fname' => $data['fname'],
					'lname' => $data['lname'],
					'title' => $data['title'],
					'company' => $data['company'],
					'address' => $data['address'],
				);

				$grep = preg_grep('/^\D+_\d+/', array_keys($data));
				foreach ($grep as $key) {
					if (preg_match('/^(.*)_(\d+)_(.*)$/', $key, $matches)) {
						$extras[$matches[1]][$matches[2] - 1][$matches[3]] = $data[$key];
					} else if (preg_match('/^(.*)_(\d+)$/', $key, $matches)) {
						$extras[$matches[1]][$matches[2] - 1] = $data[$key];
					}
				}

				foreach ($extras as $key => $type) {
					foreach ($type as $value) {
						switch ($key) {
						case 'phone':
							$contact['numbers'][] = array(
								'number' => $value['number'],
								'type' => isset($value['type']) ? $value['type'] : 'other',
								'extension' => isset($value['extension']) ? $value['extension'] : '',
								'flags' => isset($value['flags']) ? explode(',', $value['flags']) : array(),
							);
							break;
						case 'email':
							$contact['emails'][] = array(
								'email' => $value,
							);
							break;
						case 'website':
							$contact['websites'][] = array(
								'website' => $value,
							);
							break;
						default:
							return array("status" => false, "message" => _("Unknown data type."));
							break;
						}
					}
				}

				$this->addEntryByGroupID($group['id'], $contact);

				$ret = array(
					'status' => true,
				);
			}

			break;
		}

		return $ret;
	}

	public function bulkhandlerExport($type) {
		$data = NULL;

		switch ($type) {
		case 'contacts':
			$groups = $this->getGroups();
			foreach ($groups as $group) {
				if ($group['type'] != 'internal' && $group['type'] != 'external') {
					continue;
				}

				$entries = $this->getEntriesByGroupID($group['id']);
				foreach ($entries as $entry) {
					$entry['numbers'] = !empty($entry['numbers']) ? array_values($entry['numbers']) : array();
					$entry['emails'] = !empty($entry['emails']) ? array_values($entry['emails']) : array();
					$entry['websites'] = !empty($entry['websites']) ? array_values($entry['websites']) : array();

					$contact = array(
						"groupname" => $group['name'],
						"grouptype" => $group['type'],
						"displayname" => $entry['displayname'],
						"fname" => $entry['fname'],
						"lname" => $entry['lname'],
						"title" => $entry['title'],
						"company" => $entry['company'],
						"address" => $entry['address'],
					);

					if ($group['type'] == 'internal' && $entry['user']) {
						$user = $this->userman->getUserByID($entry['user']);
						$contact["userman_username"] = $user['username'];
					}

					foreach ($entry['numbers'] as $key => $value) {
						$id = $key + 1;
						$contact["phone_" . $id . "_type"] = $value['type'];
						$contact["phone_" . $id . "_number"] = $value['number'];
						$contact["phone_" . $id . "_extension"] = $value['extension'];
						$contact["phone_" . $id . "_flags"] = implode(',', $value['flags']);
					}

					foreach ($entry['emails'] as $key => $value) {
						$id = $key + 1;
						$contact["email_" . $id] = $value['email'];
					}

					foreach ($entry['websites'] as $key => $value) {
						$id = $key + 1;
						$contact["website_" . $id] = $value['website'];
					}

					$data[] = $contact;
				}
			}

			break;
		}

		return $data;
	}
}
