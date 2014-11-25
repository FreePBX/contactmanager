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

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->cm = $this->UCP->FreePBX->Contactmanager;
		$this->user = $this->UCP->User->getUser();
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
	* Generate the display in UCP
	*/
	function getDisplay() {
		$view = !empty($_REQUEST['view']) ? $_REQUEST['view'] : '';

		$displayvars['groups'] = $this->cm->getGroupsByOwner($this->user['id']);
		$displayvars['activeList'] = "mycontacts";
		$displayvars['total'] = 0;
		$allContacts = array();
		$c = 1;
		foreach($displayvars['groups'] as &$group) {
			$group['contacts'] = $this->cm->getEntriesByGroupID($group['id']);
			$group['count'] = count($group['contacts']);
			$displayvars['total'] = $displayvars['total'] + $group['count'];
			$allContacts = array_merge($allContacts,$group['contacts']);
		}

		usort($allContacts, function($a, $b) {
			return strnatcmp($a['displayname'], $b['displayname']);
		});

		switch($view) {
			default:
				$displayvars['contacts'] = $allContacts;
			break;
		}

		$mainDisplay = $this->load_view(__DIR__.'/views/contacts.php',$displayvars);
		$html = $this->load_view(__DIR__.'/views/nav.php',$displayvars);
		$html .= $mainDisplay;
		return $html;
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

	/**
	* Send settings to UCP upon initalization
	*/
	function getStaticSettings() {
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
