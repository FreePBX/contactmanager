<?php
/**
 * This is the User Control Panel Object.
 *
 * Copyright (C) 2014 Schmooze Com, INC
 */
namespace UCP\Modules;
use \UCP\Modules as Modules;

class Contactmanager extends Modules{
	protected $module = 'Contactmanager';
	private $ext = 0;

	public function __construct($Modules) {
		$this->Modules = $Modules;
		$this->cm = $this->UCP->FreePBX->Contactmanager;
		$this->user = $this->UCP->User->getUser();
	}

	/**
	* Determine what commands are allowed
	*
	* Used by Ajax Class to determine what commands are allowed by this class
	*
	* @param string $command The command something is trying to perform
	* @param string $settings The Settings being passed through $_POST or $_PUT
	* @return bool True if pass
	*/
	function ajaxRequest($command, $settings) {
		switch($command) {
			case 'updatecontact':
			case 'deletecontact':
			case 'addcontact':
			case 'deletegroup':
			case 'addgroup':
			case 'grid':
				return true;
			default:
				return false;
			break;
		}
	}

	/**
	* The Handler for all ajax events releated to this class
	*
	* Used by Ajax Class to process commands
	*
	* @return mixed Output if success, otherwise false will generate a 500 error serverside
	*/
	function ajaxHandler() {
		$return = array("status" => false, "message" => "");
		switch($_REQUEST['command']) {
			case 'grid':
				//$limit = $_REQUEST['limit'];
				$group = $_REQUEST['id'];
				$order = $_REQUEST['order'];
				$orderby = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : "displayname";
				$search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : "";
				if(empty($group)) {
					$groups = $this->cm->getGroupsByOwner($this->user['id']);
					$allContacts = array();
					foreach($groups as $group) {
						$contacts = $this->cm->getEntriesByGroupID($group['id']);
						$allContacts = array_merge($allContacts,$contacts);
					}
					@usort($allContacts, function($a, $b) {
						return strnatcmp($a[$orderby], $b[$orderby]);
					});
					$contacts = array_values($allContacts);
				} else {
					$contacts = $this->cm->getEntriesByGroupID($group);
					$contacts = array_values($contacts);
				}
				if(!empty($search)) {
					$temp = $contacts;
					$contacts = array();
					foreach($temp as $c) {
						if($this->pregRecursiveArraySearch($search,$c, array('displayname','fname','lname','title','company')) !== false) {
							$contacts[] = $c;
						}
					}
				}
				if($order == 'asc') {
					$contacts = array_reverse($contacts);
				}
				return $contacts;
			break;
			case 'updatecontact':
				$entry = $this->cm->getEntryByID($_REQUEST['id']);
				if(!empty($entry)) {
					$entry[$_REQUEST['key']] = $_REQUEST['value'];
					$return = $this->cm->updateEntry($_REQUEST['id'], $entry);
					break;
				}
				$return = array("status" => false, "message" => _("Unauthorized"));
			break;
			case 'deletecontact':
				$entry = $this->cm->getEntryByID($_REQUEST['id']);
				if(!empty($entry)) {
					$g = $this->cm->getGroupByID($entry['groupid']);
					if($g['owner'] == $this->user['id']) {
						$return = $this->cm->deleteEntryByID($_REQUEST['id']);
						break;
					}
				}
				$return = array("status" => false, "message" => _("Unauthorized"));
			break;
			case 'addcontact':
				$g = $this->cm->getGroupByID($_REQUEST['id']);
				if($g['owner'] == $this->user['id']) {
					$contact = $_REQUEST['contact'];
					$contact['user'] = -1;
					$return = $this->cm->addEntryByGroupID($_REQUEST['id'], $contact);
				} else {
					$return = array("status" => false, "message" => _("Unauthorized"));
				}
			break;
			case 'deletegroup':
				$g = $this->cm->getGroupByID($_REQUEST['id']);
				if($g['owner'] == $this->user['id']) {
					$return = $this->cm->deleteGroupByID($_REQUEST['id']);
					$return['name'] = $g['name'];
				} else {
					$return = array("status" => false, "message" => _("Unauthorized"));
				}
			break;
			case 'addgroup':
				$return = $this->cm->addGroup($_POST['name'], 'external', $this->user['id']);
			break;
			default:
				return false;
			break;
		}
		return $return;
	}

	function pregRecursiveArraySearch($needle,$haystack,$validKeys=array()) {
		foreach($haystack as $key=>$value) {
			if(!empty($validKeys) && !in_array($key, $validKeys)) {
				continue;
			}
			$current_key = $key;
			if(preg_match('/'.$needle.'/i',$value) OR (is_array($value) && $this->pregRecursiveArraySearch($needle,$value) !== false)) {
				return $current_key;
			}
		}
		return false;
	}

	/**
	* Generate the display in UCP
	*/
	public function getDisplay() {
		$view = !empty($_REQUEST['view']) ? $_REQUEST['view'] : '';

		$displayvars = array();
		$displayvars['groups'] = $this->cm->getGroupsByOwner($this->user['id']);
		$displayvars['activeList'] = "mycontacts";
		$displayvars['total'] = 0;
		$c = 1;
		foreach($displayvars['groups'] as &$group) {
			$group['readonly'] = ($group['owner'] == -1);
			$group['contacts'] = $this->cm->getEntriesByGroupID($group['id']);
			$group['count'] = count($group['contacts']);
			$displayvars['total'] = $displayvars['total'] + $group['count'];
			if(!empty($_REQUEST['view']) && $_REQUEST['view'] == "group" && $_REQUEST['id'] == $group['id']) {
				$displayvars['activeList'] = $group['name'];
				$displayvars['readonly'] = $group['readonly'];
			}else if(!empty($_REQUEST['view']) && $_REQUEST['view'] == "contact" && $_REQUEST['group'] == $group['id']) {
				$displayvars['activeList'] = $group['name'];
				$displayvars['readonly'] = $group['readonly'];
			}
		}

		switch($view) {
			case "addcontact":
				$g = $this->cm->getGroupByID($_REQUEST['group']);
				if(!empty($g)) {
					if($g['owner'] != -1) {
						$displayvars['activeList'] = $g['name'];
						$displayvars['add'] = true;
						$mainDisplay = $this->load_view(__DIR__.'/views/contact.php',$displayvars);
						break;
					}
				}
				$displayvars['activeList'] = '';
				$mainDisplay = _("Not Authorized");
			break;
			case "addgroup":
				$displayvars['activeList'] = "addgroup";
				$mainDisplay = $this->load_view(__DIR__.'/views/groupcreate.php',$displayvars);
			break;
			case "contact":
				$g = $this->cm->getGroupByID($_REQUEST['group']);
				if(!empty($g)) {
					$displayvars['contact'] = $this->cm->getEntryByID($_REQUEST['id']);
					if($g['owner'] == -1) {
						$mainDisplay = $this->load_view(__DIR__.'/views/contactro.php',$displayvars);
					} else {
						if(!empty($_REQUEST['mode']) && $_REQUEST['mode'] == 'edit') {
							$mainDisplay = $this->load_view(__DIR__.'/views/contact.php',$displayvars);
						} else {
							$displayvars['editable'] = true;
							$mainDisplay = $this->load_view(__DIR__.'/views/contactro.php',$displayvars);
						}
					}
				} else {
					$mainDisplay = _("Not Authorized");
				}
			break;
			default:
				$mainDisplay = $this->load_view(__DIR__.'/views/contacts.php',$displayvars);
			break;
		}

		$html = $this->load_view(__DIR__.'/views/nav.php',$displayvars);
		$html .= $mainDisplay;
		return $html;
	}

	public function lookupMultiple($search) {
		$entry = $this->cm->lookupMultipleByUserID($this->user['id'],$search);
		return $entry;
	}

	public function lookup($search) {
		$entry = $this->cm->lookupByUserID($this->user['id'],$search);
		return $entry;
	}

	/**
	* Setup Menu Items for display in UCP
	*/
	public function getMenuItems() {
		$menu = array(
			"rawname" => "contactmanager",
			"name" => _("Contacts")
		);
		return $menu;
	}

	public function poll() {
		$contacts = $this->cm->getContactsByUserID($this->user['id']);
		if(!empty($contacts)) {
			return array(
				'enabled' => true,
				'contacts' => $contacts
			);
		} else {
			return array('enabled' => false);
		}
	}

	/**
	* Send settings to UCP upon initalization
	*/
	public function getStaticSettings() {
		$contacts = $this->cm->getContactsByUserID($this->user['id']);
		if(!empty($contacts)) {
			return array(
				'enabled' => true,
				'contacts' => $contacts
			);
		} else {
			return array('enabled' => false);
		}
	}
}
