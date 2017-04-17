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
	private $groupCache = array();
	private $groupsCache = array();
	private $token = null;
	public $tmp;

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
			)
		);

		$this->tmp = $this->freepbx->Config->get("ASTSPOOLDIR") . "/tmp";
	}


	public function usermanAddContactInfo($user) {
		if(empty($this->allImages)) {
			$sql = "SELECT * FROM contactmanager_entry_userman_images";
			$sth = $this->db->prepare($sql);
			$sth->execute();
			$tmp = $sth->fetchAll(\PDO::FETCH_ASSOC);
			$this->allImages = array();
			foreach($tmp as $t) {
				$this->allImages[$t['uid']] = true;
			}
		}
		if(!empty($this->allImages[$user['id']])) {
			$user['image'] = true;
		}
		return $user;
	}

	public function install() {
		global $db;

		$fcc = new \featurecode('contactmanager', 'app-contactmanager-sd');
		$fcc->setDescription('Contact Manager Speed Dials');
		$fcc->setDefault('*10');
		$fcc->setProvideDest();
		$fcc->update();
		unset($fcc);

		$sql = "SELECT * FROM contactmanager_groups WHERE type = 'userman'";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$oldgrps = $sth->fetchAll(\PDO::FETCH_ASSOC);

		$sql = "UPDATE contactmanager_groups SET type = 'internal' WHERE type = 'userman'";
		$sth = $this->db->prepare($sql);
		$sth->execute();

		//remove useless internal groups without any contacts
		/*
		$sql = "SELECT * FROM contactmanager_groups WHERE type = 'internal' AND id NOT IN (SELECT DISTINCT g.id FROM contactmanager_groups g, contactmanager_group_entries e WHERE g.id = e.groupid)";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$grps = $sth->fetchAll(\PDO::FETCH_ASSOC);
		foreach($grps as $grp) {
			$sql = "DELETE FROM contactmanager_groups WHERE type = 'internal' AND id = ?";
			$sth = $this->db->prepare($sql);
			$sth->execute(array($grp['id']));
		}
		*/

		$info = $this->freepbx->Modules->getInfo("contactmanager");
		$newinstall = ($info['contactmanager']['status'] == MODULE_STATUS_NOTINSTALLED);
		if(empty($oldgrps) && $newinstall) {
			$ret = $this->addGroup(_("User Manager Group"),"internal");
			$defaultgrp = $ret['id'];
		} elseif(isset($oldgrps[0])) {
			$defaultgrp = $oldgrps[0]['id'];
		}

		if(!empty($info['contactmanager']['dbversion']) && version_compare_freepbx($info['contactmanager']['dbversion'],"13.0.37","<")) {
			$sql = "SELECT e.* FROM contactmanager_group_entries e, contactmanager_groups g WHERE type = 'internal' AND e.groupid = g.id";
			$sth = $this->db->prepare($sql);
			$sth->execute();
			$entries = $sth->fetchAll(\PDO::FETCH_ASSOC);
			foreach($entries as $entry) {
				$uid = $entry['user'];
				$gs = $this->userman->getModuleSettingByID($uid,"contactmanager","showingroups");
				$gs = is_array($gs) ? $gs : array();
				if(!in_array($entry['groupid'],$gs)) {
					$gs[] = $entry['groupid'];
				}
				$this->userman->setModuleSettingByID($uid,"contactmanager","showingroups", $gs);
			}
		}

		if(isset($defaultgrp) && !$newinstall) {
			//Now scan all the old users/groups from userman and get the setting
			$users = $this->userman->getAllUsers();
			foreach($users as $user) {
				$show = $this->userman->getModuleSettingByID($user['id'],"contactmanager","show");
				if($show) {
					$gs = $this->userman->getModuleSettingByID($user['id'],"contactmanager","showingroups");
					$gs = is_array($gs) ? $gs : array();
					if(!in_array($defaultgrp,$gs)) {
						$gs[] = $defaultgrp;
					}
					$this->userman->setModuleSettingByID($user['id'],"contactmanager","showingroups", $gs);
				}
				$this->usermanUpdateUser($user['id'],'',$user);
			}
			$groups = $this->userman->getAllGroups();
			foreach($groups as $group) {
				$show = $this->userman->getModuleSettingByGID($group['id'],"contactmanager","show");
				if($show) {
					$this->userman->setModuleSettingByGID($group['id'],"contactmanager","showingroups", array($defaultgrp));
				}
				$this->usermanUpdateGroup($group['id'],'',$group);
			}
		} elseif($newinstall) {
			$id = $this->freepbx->Userman->getAutoGroup();
			$id = !empty($id) ? $id : 1;
			$group = $this->freepbx->Userman->getGroupByGID($id);
			if(!empty($group)) {
				$this->userman->setModuleSettingByGID($id,"contactmanager","showingroups", array($defaultgrp));
				$this->usermanUpdateGroup($id,'',$group);
			}
		}

		$sql = "SELECT i.*, e.user FROM contactmanager_entry_images i, contactmanager_group_entries e, contactmanager_groups g WHERE i.entryid = e.id AND e.groupid = g.id AND g.type = 'internal'";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$entries = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$sql = "INSERT INTO contactmanager_entry_userman_images (`uid`,`image`,`format`,`gravatar`) VALUES (:uid, :image, :format, :gravatar)";
		$sth = $this->db->prepare($sql);
		$sql1 = "DELETE FROM contactmanager_entry_images WHERE entryid = :id";
		$sth1 = $this->db->prepare($sql1);
		foreach($entries as $entry) {
			try {
				$sth->execute(array(
					"uid" => $entry['user'],
					"image" => $entry['image'],
					"format" => $entry['format'],
					"gravatar" => $entry['gravatar']
				));
			} catch(\Exception $e) {}
			$sth1->execute(array("id" => $entry['entryid']));
		}


		// CONTACTMANLOOKUPLENGTH in Advanced Settings of FreePBX
		//
		$set['value'] = 7;
		$set['defaultval'] =& $set['value'];
		$set['readonly'] = 0;
		$set['hidden'] = 0;
		$set['level'] = 1;
		$set['module'] = 'contactmanager'; //This will help delete the settings when module is uninstalled
		$set['category'] = 'Contact Manager Module';
		$set['emptyok'] = 0;
		$set['name'] = 'Partial Match Length';
		$set['description'] = 'How many digits should a number be before a partial match is used when looking up a contact';
		$set['type'] = CONF_TYPE_INT;
		$set['options'] = array(1,86400);
		$this->freepbx->Config->define_conf_setting('CONTACTMANLOOKUPLENGTH',$set,true);
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
							$ext->add($contextname, $number, '', new \ext_goto('from-internal,'.$number.',1', ''));
						}
					}
				}
			}
		}

		$contextname = 'app-contactmanager-sd';
		$fcc = new \featurecode('contactmanager', $contextname);
		$code = $fcc->getCodeActive();
		unset($fcc);

		if (!empty($code)) {
			$this->syncSpeedDials();
			$ext->add($contextname, "_".$code."X!", '', new \ext_answer());
			$ext->add($contextname, "_".$code."X!", '', new \ext_macro('user-callerid'));
			$ext->add($contextname, "_".$code."X!", '', new \ext_gotoif('$[${DB_EXISTS(CM/speeddial/${EXTEN:'.strlen($code).'})}=1]','from-internal,${DB(CM/speeddial/${EXTEN:'.strlen($code).'})},1'));
			$ext->add($contextname, "_".$code."X!", '', new \ext_goto('bad-number,s,1'));

			$ext->addInclude('from-internal-additional', $contextname);
		}
	}

	public static function myDialplanHooks() {
		return 500;
	}

	/**
	 * Get the Contact Image URL
	 * @param  integer $did The incoming DID to lookup
	 * @param  integer $ext The local extension to use for lookups
	 * @return string      The link to return
	 */
	public function getExternalImageUrl($did,$ext=null) {
		if(!empty($ext)) {
			return 'ajax.php?module=contactmanager&command=image&token='.$this->getToken().'&ext='.$ext.'&did='.$did;
		} else {
			return 'ajax.php?module=contactmanager&command=image&token='.$this->getToken().'&did='.$did;
		}
	}

	/**
	 * Get Token for unauthenticated Ajax requests
	 * Will generate a token if one does not exist
	 * @return string The Token
	 */
	public function getToken() {
		if(!empty($this->token)) {
			return $this->token;
		}
		$this->token = $this->getConfig("token");
		if(empty($this->token)) {
			$this->token = bin2hex(openssl_random_pseudo_bytes(16));
			$this->setConfig("token",$this->token);
		}
		return $this->token;
	}

	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'image':
				$setting['authenticate'] = false;
				$setting['allowremote'] = true;
			case 'limage':
			case 'uploadimage':
			case 'delimage':
			case 'grid':
			case 'getgravatar':
			case 'checksd':
			case 'sdgrid':
				return true;
			break;
		}
		return false;
	}

	public function ajaxCustomHandler() {
		switch($_REQUEST['command']) {
			case "image":
				$token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : "";
				$stoken = $this->getToken();
				if($token != $stoken) {
					header('HTTP/1.0 403 Forbidden');
					return true;
				}
			case "limage":
				$entryid = !empty($_REQUEST['entryid']) ? $_REQUEST['entryid'] : null;
				$type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : null;
				$this->displayContactImage($entryid, $type);
				return true;
			break;
		}
	}

	/**
	 * Get and Display Contact Image
	 */
	public function displayContactImage($entryid=null,$type=null) {
		$buffer = '';
		if(!empty($entryid)) {
			if(!empty($type)) {
				switch($type) {
					case "internal":
						$data = $this->freepbx->Userman->getUserByID($entryid);
						$data = $this->getImageByID($data['id'], $data['email'], 'internal');
					break;
					case "external";
						$data = $this->getEntryByID($entryid);
						$email = !empty($data['email']) ? $data['email'] : (!empty($data['emails'][0]) ? $data['emails'][0] : '');
						$id = ($data['type'] == "internal") ? $data['user'] : $data['id'];
						$data = $this->getImageByID($id, $email, 'external');
					break;
				}
			} else {
				$data = $this->getEntryByID($entryid);
				$email = !empty($data['email']) ? $data['email'] : (!empty($data['emails'][0]) ? $data['emails'][0] : '');
				$id = ($data['type'] == "internal") ? $data['user'] : $data['id'];
				$data = $this->getImageByID($id, $email, $data['type']);
			}

			if(!empty($data['image'])) {
				$buffer = $data['image'];
			}
		} elseif(!empty($_REQUEST['temporary'])) {
			$name = basename($_REQUEST['name']);
			$buffer = file_get_contents($this->tmp."/".$name);
		} elseif(!empty($_REQUEST['entryid'])) {
			$data = $this->getEntryByID($_REQUEST['entryid']);
			$email = !empty($data['email']) ? $data['email'] : (!empty($data['emails'][0]) ? $data['emails'][0] : '');
			$id = ($data['type'] == "internal") ? $data['user'] : $data['id'];
			$data = $this->getImageByID($id, $email, $data['type']);
			if(!empty($data['image'])) {
				$buffer = $data['image'];
			}
		} elseif(!empty($_REQUEST['did'])) {
			$parts = explode(".",$_REQUEST['did']);
			$did = $parts[0];
			if(!empty($did)) {
				$did = preg_replace("/\D/","",$parts[0]);
				if(!empty($_POST['ext'])) {
					$user = $this->userman->getUserByDefaultExtension($_POST['ext']);
					if(!empty($user)) {
						$data = $this->lookupByUserID($user['id'], $did,"/\D/");
					}
				}
				if(empty($data)) {
					$data = $this->lookupByUserID(-1, $did,"/\D/");
				}
				if(!empty($data) && !empty($data['image'])) {
					$data = $this->getImageByID($data['id'],$data['email'], $data['type']);
					if(!empty($data['image'])) {
						$buffer = $data['image'];
					}
				}
			}
		}
		if(!empty($buffer)) {
			$finfo = new \finfo(FILEINFO_MIME);
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			header("Content-type: ".$finfo->buffer($buffer));
			echo $buffer;
		} else {
			header('HTTP/1.0 404 Not Found');
		}
	}

	public function getAllSpeedDials() {
		$sql = "SELECT e.*, s.id as speeddial, n.number FROM contactmanager_entry_speeddials s, contactmanager_group_entries e, contactmanager_entry_numbers n WHERE e.id = s.entryid AND n.id = s.numberid ORDER BY s.id";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function syncSpeedDials() {
		$sds = $this->getAllSpeedDials();
		$active = array();
		foreach($sds as $sd) {
			$this->freepbx->astman->database_put("CM","speeddial/".$sd['speeddial'],$sd['number']);
			$active[] = '/CM/speeddial/'.$sd['speeddial'];
		}
		$all = $this->freepbx->astman->database_show('CM');
		foreach($all as $key => $value) {
			if(!in_array($key,$active)) {
				preg_match('/^\/CM\/speeddial\/(\d+)$/',$key,$match);
				$this->freepbx->astman->database_del("CM","speeddial/".$match[1]);
			}
		}
	}

	public function checkSpeedDialConflict($id,$entryid=null) {
		if(is_null($entryid)) {
			$sql = "SELECT * FROM contactmanager_entry_speeddials WHERE id = :id";
			$sth = $this->db->prepare($sql);
			$sth->execute(array(
				':id' => $id
			));
		} else {
			$sql = "SELECT * FROM contactmanager_entry_speeddials WHERE id = :id AND entryid != :entryid";
			$sth = $this->db->prepare($sql);
			$sth->execute(array(
				':id' => $id,
				':entryid' => $entryid
			));
		}

		$ret = $sth->fetch(\PDO::FETCH_ASSOC);
		return empty($ret);
	}

	public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case 'sdgrid':
				return $this->getAllSpeedDials();
			break;
			case 'checksd':
				if(empty($_POST['entryid'])) {
					$ret = $this->checkSpeedDialConflict($_POST['id']);
				} else {
					$ret = $this->checkSpeedDialConflict($_POST['id'],$_POST['entryid']);
				}
				return array("status" => $ret);
			break;
			case 'getgravatar':
				$type = !empty($_POST['grouptype']) ? $_POST['grouptype'] : "";
				$id = !empty($_POST['id']) ? $_POST['id'] : "";
				switch($type) {
					case "external":
						$email = $_POST['email'];
					break;
					case "userman":
					case "internal":
						$email = !empty($_POST['email']) ? $_POST['email'] : '';
						if(empty($email)) {
							$data = $this->freepbx->Userman->getUserByID($id);
							$email = $data['email'];
						}
					break;
				}
				if(empty($email)) {
					return array("status" => false, "message" => _("Please enter a valid email address"));
				}
				$data = $this->getGravatar($email);
				if(!empty($data)) {
					$dname = "cm-".rand()."-".md5($email);
					imagepng(imagecreatefromstring($data), $this->tmp."/".$dname.".png");
					return array("status" => true, "name" => $dname, "filename" => $dname.".png");
				} else {
					return array("status" => false, "message" => sprintf(_("Unable to find gravatar for %s"),$email));
				}

			break;
			case "delimage":
			$type = !empty($_POST['type']) ? $_POST['type'] : 'external';
				if(!empty($_POST['id'])) {
					$this->delImageByID($_POST['id'],$type);
					return array("status" => true);
				} elseif(!empty($_POST['img'])) {
					$name = basename($_POST['img']);
					if(file_exists($this->tmp."/".$name)) {
						unlink($this->tmp."/".$name);
						return array("status" => true);
					}
				}
				return array("status" => false, "message" => _("Invalid"));
			break;
			case 'uploadimage':
				// XXX If the posted file was too large,
				// we will get here, but $_FILES is empty!
				// Specifically, if the file that was posted is
				// larger than 'post_max_size' in php.ini.
				// So, php will throw an error, as index
				// $_FILES["files"] does not exist, because
				// $_FILES is empty.
				if (!isset($_FILES)) {
					return array("status" => false,
						"message" => _("File upload failed"));
				}
				$this->freepbx->Media();
				foreach ($_FILES["files"]["error"] as $key => $error) {
					switch($error) {
						case UPLOAD_ERR_OK:
							$extension = pathinfo($_FILES["files"]["name"][$key], PATHINFO_EXTENSION);
							$extension = strtolower($extension);
							$supported = array("jpg","png");
							if(in_array($extension,$supported)) {
								$tmp_name = $_FILES["files"]["tmp_name"][$key];
								$dname = \Media\Media::cleanFileName($_FILES["files"]["name"][$key]);
								$dname = "cm-".rand()."-".pathinfo($dname,PATHINFO_FILENAME);
								//imagepng(imagecreatefromstring(file_get_contents($tmp_name)), $this->tmp."/".$dname.".png");
								$this->resizeImage(file_get_contents($tmp_name),$dname);
								return array("status" => true, "name" => $dname, "filename" => $dname.".png");
							} else {
								return array("status" => false, "message" => _("Unsupported file format"));
								break;
							}
						break;
						case UPLOAD_ERR_INI_SIZE:
							return array("status" => false, "message" => _("The uploaded file exceeds the upload_max_filesize directive in php.ini"));
						break;
						case UPLOAD_ERR_FORM_SIZE:
							return array("status" => false, "message" => _("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"));
						break;
						case UPLOAD_ERR_PARTIAL:
							return array("status" => false, "message" => _("The uploaded file was only partially uploaded"));
						break;
						case UPLOAD_ERR_NO_FILE:
							return array("status" => false, "message" => _("No file was uploaded"));
						break;
						case UPLOAD_ERR_NO_TMP_DIR:
							return array("status" => false, "message" => _("Missing a temporary folder"));
						break;
						case UPLOAD_ERR_CANT_WRITE:
							return array("status" => false, "message" => _("Failed to write file to disk"));
						break;
						case UPLOAD_ERR_EXTENSION:
							return array("status" => false, "message" => _("A PHP extension stopped the file upload"));
						break;
					}
				}
				return array("status" => false, "message" => _("Can Not Find Uploaded Files"));
			break;
			case 'grid':
				$group = $this->getGroupByID($_REQUEST['group']);
				$entries = $this->getEntriesByGroupID($_REQUEST['group']);
				$entries = array_values($entries);
				$final = array();
				switch($group['type']) {
					case "internal":
						$i = 0;
						foreach($entries as $entry) {
							if(empty($entry['user'])) {
								continue;
							}
							$user = $this->freepbx->Userman->getUserByID($entry['user']);
							$final[$i] = $user;
							$final[$i]['displayname'] = !empty($user['displayname']) ? $user['displayname'] : $user['fname'] . " " . $user['lname'];
							$final[$i]['displayname'] = !empty($user['displayname']) ? $user['displayname'] . " (".$user['username'].")" : $user['username'];
							$final[$i]['actions'] = '<a href="config.php?display=userman&action=showuser&user='.$user['id'].'"><i class="fa fa-edit fa-fw"></i></a><a href="config.php?display=contactmanager&amp;action=delentry&amp;group='.$_REQUEST['group'].'&amp;entry='.$entry['uid'].'"><i class="fa fa-ban fa-fw"></i></a>';
							$i++;
						}
					break;
					case "external":
						$i = 0;
						foreach($entries as $entry) {
							$entry['numbers'] = !empty($entry['numbers']) ? $entry['numbers'] : array();
							$nums = array();
							foreach($entry['numbers'] as &$number) {
								$nums[] = $number['number'] . "(".$number['type'].")";
							}
							$entry['numbers'] = !empty($entry['numbers']) ? implode("<br>",$nums) : "";
							$entry['actions'] = '<a href="config.php?display=contactmanager&amp;action=showentry&amp;group='.$_REQUEST['group'].'&amp;entry='.$entry['uid'].'"><i class="fa fa-edit fa-fw"></i></a><a href="config.php?display=contactmanager&amp;action=delentry&amp;group='.$_REQUEST['group'].'&amp;entry='.$entry['uid'].'"><i class="fa fa-ban fa-fw"></i></a>';
							$final[$i] = $entry;
							$i++;
						}
					break;
				}
				return $final;
			break;
		}
	}

	/**
	 * Resize and image using constraints
	 * @param  string  $data         Binary image data
	 * @param  string  $filename     The final filename
	 * @param  integer $thumb_width  The max width
	 * @param  integer $thumb_height The max height
	 * @return string                The basename of the filepath
	 */
	public function resizeImage($data, $filename, $thumb_width=90, $thumb_height=75) {
		$image = imagecreatefromstring($data);
		$filename = $this->tmp.'/'.$filename.'.png';
		$width = imagesx($image);
		$height = imagesy($image);
		$original_aspect = $width / $height;
		$thumb_aspect = $thumb_width / $thumb_height;
		if ( $original_aspect >= $thumb_aspect ) {
			// If image is wider than thumbnail (in aspect ratio sense)
			$new_height = $thumb_height;
			$new_width = $width / ($height / $thumb_height);
		} else {
			// If the thumbnail is wider than the image
			$new_width = $thumb_width;
			$new_height = $height / ($width / $thumb_width);
		}
		$thumb = imagecreatetruecolor( $thumb_width, $thumb_height );
		// Resize and crop
		imagecopyresampled($thumb,
		$image,
		0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
		0 - ($new_height - $thumb_height) / 2, // Center the image vertically
		0, 0,
		$new_width, $new_height,
		$width, $height);
		imagepng($thumb, $filename, 9);
		return basename($filename);
	}

	/**
	 * Get either a Gravatar URL or complete image tag for a specified email address.
	 *
	 * @param string $email The email address
	 * @source https://gravatar.com/site/implement/images/php/
	 */
	function getGravatar($email) {
		$s = 75; //Size in pixels, defaults to 80px [ 1 - 2048 ]
		$d = '404'; //Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
		$r = 'g'; //Maximum rating (inclusive) [ g | pg | r | x ]
		$pest = new \Pest('https://www.gravatar.com/avatar/');
		$email = md5( strtolower( trim( $email ) ) );
		try{
			return $pest->get($email.'?s='.$s.'&d='.$d.'&r='.$r);
		} catch(\Exception $e) {
			switch(get_class($e)) {
				case "Pest_NotFound":
					return false;
				break;
				default:
					return false;
				break;
			}
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
				$image = !empty($_POST['image']) ? $_POST['image'] : '';
				$gravatar = !empty($_POST['gravatar']) && $_POST['gravatar'] == 'on' ? true : false;
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
						$numbers[$index]['speeddial'] = isset($_POST['numbersde'][$index]) ? $_POST['numbersd'][$index] : "";
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
					'image' => $image,
					'gravatar' => $gravatar
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

	public function getFeatureCodeStatus() {
		$fcc = new \featurecode('contactmanager', 'app-contactmanager-sd');
		return array(
			"code" => $fcc->getCode(),
			"enabled" => $fcc->isEnabled()
		);
	}

	public function getRightNav($request) {
		$action = '';
		$rnav = load_view(dirname(__FILE__).'/views/rnav.php', array("action" => $action));
		return $rnav;
	}

	/**
	 * Function used in page.contactmanager.php
	 */
	public function myShowPage() {
		$groups = $this->getGroupsGroupedByType();
		$users = $this->userman->getAllUsers();

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
		//

		switch($action) {
			case "speeddials":
				$speeddialcode = $this->getFeatureCodeStatus();
				$content = load_view(dirname(__FILE__).'/views/speeddial-grid.php', array("speeddialcode" => $speeddialcode));
			break;
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
					$speeddialcode = $this->getFeatureCodeStatus();

					$content = load_view(dirname(__FILE__).'/views/entry.php', array("speeddialcode" => $speeddialcode, "numbertypes" => $numbertypes, "group" => $group, "entry" => $entry, "users" => $users, "message" => $this->message));
				}
			break;
			default:
				$file['post'] = ini_get('post_max_size');
				$file['upload'] = ini_get('upload_max_filesize');
				$content = load_view(dirname(__FILE__).'/views/grid.php', array("groups" => $groups, "types" => $this->types, "file" => $file));
			break;
		}

		return load_view(dirname(__FILE__).'/views/main.php', array("message" => $this->message, "content" => $content));
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
		$groups = $this->getGroups();
		foreach($data['users'] as $user) {
			if($this->freepbx->Userman->getCombinedModuleSettingByID($user,'contactmanager','show')) {
				foreach ($groups as $group) {
					if ($group['type'] == 'internal') {
						$data = $this->Userman->getUserByID($user);
						$data['user'] = $user;
						$this->updateEntryByGroupID($group['id'], $data);
					}
				}
			}
		}
	}

	public function usermanAddGroup($id, $display, $data) {
		$this->usermanUpdateGroup($id,$display,$data);
	}

	public function usermanUpdateGroup($id,$display,$data) {
		if($display == 'userman') {
			if(!empty($_POST['contactmanager_showingroups'])) {
				$grps = !in_array("*",$_POST['contactmanager_showingroups']) ? $_POST['contactmanager_showingroups'] : array("*");
				$this->freepbx->Userman->setModuleSettingByGID($id,'contactmanager','showingroups',$grps);
			} else {
				$this->freepbx->Userman->setModuleSettingByGID($id,'contactmanager','showingroups',null);
			}

			if(!empty($_POST['contactmanager_groups'])) {
				$grps = !in_array("*",$_POST['contactmanager_groups']) ? $_POST['contactmanager_groups'] : array("*");
				$this->freepbx->Userman->setModuleSettingByGID($id,'contactmanager','groups',$grps);
			} else {
				$this->freepbx->Userman->setModuleSettingByGID($id,'contactmanager','groups',null);
			}
		}
		$groups = $this->getGroups();
		foreach($data['users'] as $user) {
			$showingroups = $this->freepbx->Userman->getCombinedModuleSettingByID($user,'contactmanager','showingroups');
			$showingroups = is_array($showingroups) ? $showingroups : array();
			$groups = $this->getGroups();
			foreach ($groups as $group) {
				if ($group['type'] != 'internal') {
					continue;
				}
				if (in_array($group['id'],$showingroups) || in_array("*",$showingroups)) {
					$data = $this->Userman->getUserByID($user);
					$data['user'] = $user;
					$this->updateEntryByGroupID($group['id'], $data);
				} else {
					$entries = $this->getEntriesByGroupID($group['id']);
					foreach ($entries as $entryid => $entry) {
						if ($entry['user'] == $user) {
							$this->deleteEntryByID($entryid);
						}
					}
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
		if($display == 'extensions' || $display == 'users') {
		} else if($display == 'userman') {
			if(!empty($_POST['contactmanager_showingroups'])) {
				$grps = !in_array("*",$_POST['contactmanager_showingroups']) ? $_POST['contactmanager_showingroups'] : array("*");
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','showingroups',$grps);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','showingroups',null);
			}
			if(!empty($_POST['contactmanager_groups'])) {
				$grps = !in_array("*",$_POST['contactmanager_groups']) ? $_POST['contactmanager_groups'] : array("*");
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',$grps);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',null);
			}
			if(isset($_POST['contactmanager_image'])) {
				$this->updateImageByID($id, $_POST['contactmanager_image'], ($_POST['contactmanager_gravatar'] == "on" ? 1 : 0), 'internal');
			}
		}
		$showingroups = $this->freepbx->Userman->getCombinedModuleSettingByID($id,'contactmanager','showingroups');
		if(!empty($showingroups)) {
			$groups = $this->getGroups();
			foreach ($groups as $group) {
				if ($group['type'] != 'internal') {
					continue;
				}
				if (in_array($group['id'],$showingroups) || in_array("*",$showingroups)) {
					$data['user'] = $id;
					$out = $this->addEntryByGroupID($group['id'], $data);
				}
			}
		}
	}

	public function usermanUpdateUser($id, $display, $data) {
		if($display == 'userman') {
			if(!empty($_POST['contactmanager_showingroups'])) {
				$grps = !in_array("*",$_POST['contactmanager_showingroups']) ? $_POST['contactmanager_showingroups'] : array("*");
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','showingroups',$grps);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','showingroups',null);
			}
			if(!empty($_POST['contactmanager_groups'])) {
				$grps = !in_array("*",$_POST['contactmanager_groups']) ? $_POST['contactmanager_groups'] : array("*");
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',$grps);
			} else {
				$this->freepbx->Userman->setModuleSettingByID($id,'contactmanager','groups',null);
			}
			if(isset($_POST['contactmanager_image'])) {
				$this->updateImageByID($id, $_POST['contactmanager_image'], ($_POST['contactmanager_gravatar'] == "on" ? 1 : 0), 'internal');
			}
		}

		$showingroups = $this->freepbx->Userman->getCombinedModuleSettingByID($id,'contactmanager','showingroups');
		$showingroups = is_array($showingroups) ? $showingroups : array();
		$groups = $this->getGroups();
		foreach ($groups as $group) {
			if ($group['type'] != 'internal') {
				continue;
			}
			if (in_array($group['id'],$showingroups) || in_array("*",$showingroups)) {
				$data['user'] = $id;
				$out = $this->updateEntryByGroupID($group['id'], $data);
			} else {
				$entries = $this->getEntriesByGroupID($group['id']);
				foreach ($entries as $entryid => $entry) {
					if ($entry['user'] == $id) {
						$this->deleteEntryByID($entryid);
					}
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
		if(!empty($this->groupsCache)) {
			return $this->groupsCache;
		}
		$sql = "SELECT * FROM contactmanager_groups ORDER BY `id`";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$this->groupsCache = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $this->groupsCache;
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
		$user = $this->freepbx->Userman->getUserByID($owner);
		if(empty($user)) {
			return array();
		}
		$assigned = $this->freepbx->Userman->getCombinedModuleSettingByID($user['id'],'contactmanager','groups');
		$assigned = is_array($assigned) ? $assigned : array();
		$sql = "SELECT * FROM contactmanager_groups WHERE `owner` = :id";
		if (!empty($assigned) && !in_array("*",$assigned)) {
			$sql .= " OR `id` IN (".implode(',',$assigned).")";
		} else if(!empty($assigned) && in_array("*",$assigned)) {
			$sql .= " OR `owner` = -1";
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
	 * Get Group Information by Group ID
	 *
	 * This gets group information by Contact Manager Group ID
	 *
	 * @param string $id The ID of the group from Contact Manager
	 * @return array
	 */
	public function getGroupByID($id) {
		if(!empty($this->groupCache[$id])) {
			return $this->groupCache[$id];
		}
		$sql = "SELECT * FROM contactmanager_groups WHERE `id` = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$group = $sth->fetch(\PDO::FETCH_ASSOC);
		$this->groupCache[$id] = $group;
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

		$this->groupCache[$id] = null;
		$this->groupsCache = null;
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

		if ($type == 'internal') {
			$groups = $this->userman->getAllGroups();
			$users = $this->userman->getAllUsers();
			foreach($groups as $group) {
				$showingroups = $this->freepbx->Userman->getModuleSettingByGID($group['id'],"contactmanager","showingroups",true);
				$showingroups = is_array($showingroups) ? $showingroups : array();
				if(in_array("*",$showingroups)) {
					foreach ($users as $user) {
						if(in_array($user['id'],$group['users'])) {
							$user['user'] = $id;
							$this->addEntryByGroupID($id, $user);
						}
					}
				}
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

		$this->groupCache[$id] = null;
		$this->groupsCache = null;
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
		'g.type as type'
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_group_entries as e, contactmanager_groups as g WHERE e.id = :id AND e.groupid = g.id";
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
					'primary' => isset($number['flags'][0]) ? implode(",", $number['flags']) : 'phone',
					'speeddial' => $number['speeddial']
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

			$email = !empty($entry['emails'][0]) ? $entry['emails'][0] : '';
			$image = $this->getImageByID($id, $email, 'external');
			$entry['image'] = $image;
			break;
			case "internal":
				$user = $this->freepbx->Userman->getUserByID($entry['user']);
				if(!empty($user)) {
					unset($user['id']);
					$entry = array_merge($entry,$user);
					$entry['displayname'] = !empty($entry['displayname']) ? $entry['displayname'] : $user['username'];

					$numbers = array(
						"default_extension" => _("extension"),
						"cell" => _("cell"),
						"work" => _("work"),
						"home" => _("home"),
						"fax" => _("fax")
					);
					foreach($numbers as $key => $name) {
						if(isset($entry[$key])) {
							$entry['numbers'][] = array(
							'number' => $entry[$key],
							'extension' => '',
							'type' => $name,
							'flags' => array((($key == 'fax') ? 'fax' : 'phone')),
							'primary' => ($key == 'fax') ? 'fax' : 'phone'
							);
						}
					}

					if(isset($entry['email'])) {
						$entry['emails'][] = array("email" => $entry['email']);
					}

					if(isset($entry['xmpp'])) {
						$entry['xmpps'][] = array("xmpp" => $entry['xmpp']);
					}

					$image = $this->getImageByID($entry['user'], $user['email'], 'internal');
					$entry['image'] = $image;
				} else {
					$this->deleteEntryByID($entry['id']);
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
		$sql = "SELECT id, id as uid, groupid, user, displayname, fname, lname, title, company, address FROM contactmanager_group_entries WHERE `groupid` = :groupid ORDER BY id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
		$ents = $sth->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE);
		$e = array();
		$tmp = array();
		//Code to cleanup dups
		$del = array();
		foreach($ents as $uid => $entry) {
			$u = $entry['user'] > 0 ? $entry['user'] : "-f";
			$id = $entry['groupid'].$u;
			if($entry['user'] > 0) {
				if(isset($tmp[$id]) && !empty($entry['id'])) {
					$del[] = $entry['id'];
					continue;
				}
			}
			$tmp[$id] = $uid;
			$e[$uid] = $entry;
		}
		unset($tmp);
		if(!empty($del)) {
			$sql = "DELETE FROM id IN (".implode(",",$del).")";
			$sth = $this->db->prepare($sql);
			$sth->execute();
		}
		//end cleanup
		$group = $this->getGroupByID($groupid);
		$entries = array();
		switch($group['type']) {
			case "internal":
				$images = $this->getImagesByGroupID($groupid,'internal');
				$umanusers = array();
				foreach($this->freepbx->Userman->getAllUsers() as $u) {
					$umanusers[$u['id']] = $u;
				}
				foreach($e as $key => $entry) {
					if(empty($umanusers[$entry['user']])) {
						$this->deleteEntryByID($entry['uid']);
						continue;
					}
					$entries[$key] = array_merge($entry,$umanusers[$entry['user']]);
					$entries[$key]['displayname'] = !empty($entries[$key]['displayname']) ? $entries[$key]['displayname'] : $umanusers[$entry['user']]['username'];
					foreach($images as $image) {
						if($image['uid'] == $entry['user']) {
							$entries[$key]['image'] = true; //we do this to not explode the size of the json
						}
					}
				}

			break;
			case "external":
			default:
				$entries = $e;
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
				$images = $this->getImagesByGroupID($groupid,'external');
				if($images) {
					foreach ($images as $image) {
						$entries[$image['entryid']]['image'] = true; //we do this to not explode the size of the json
					}
				}
			break;
		}


		return $entries;
	}

	/**
	 * Delete Entry by ID
	 * @param {int} $id The entry ID
	 */
	public function deleteEntryByID($id) {
		//getEntryByID loops back here dont use it
		$sql = "SELECT e.groupid, e.user FROM contactmanager_group_entries as e, contactmanager_groups as g WHERE e.id = :id AND e.groupid = g.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$entry = $sth->fetch(\PDO::FETCH_ASSOC);
		if(empty($entry)) {
			return true;
		}

		$group = $this->getGroupByID($entry['groupid']);

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

		if($group['type'] == "internal") {
			$this->userman->setModuleSettingByID($entry['user'],'contactmanager','show', false);
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
	 * Update Group Entry by Group ID and User Data
	 * @param  int $groupid The group ID
	 * @param  array $entry   Array of entry data
	 * @return [type]          [description]
	 */
	public function updateEntryByGroupID($groupid, $entry) {
		$group = $this->getGroupByID($groupid);
		if (!$group) {
			return array("status" => false, "type" => "danger", "message" => _("Group does not exist"));
		}

		$sql = "SELECT * FROM contactmanager_group_entries WHERE `user` = :user AND `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':groupid' => $groupid,
		':user' => $entry['user']
		));
		$data = $sth->fetch(\PDO::FETCH_ASSOC);
		if(empty($data)) {
			return $this->addEntryByGroupID($groupid, $entry);
		}

		$sql = "UPDATE contactmanager_group_entries SET `displayname` = :displayname, `fname` = :fname, `lname` = :lname, `title` = :title, `company` = :company, `address` = :address WHERE `user` = :user AND `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
		':groupid' => $groupid,
		':user' => $entry['user'],
		':displayname' => !empty($entry['displayname']) ? $data['displayname'] : '',
		':fname' => !empty($entry['fname']) ? $data['fname'] : '',
		':lname' => !empty($entry['lname']) ? $data['lname'] : '',
		':title' => !empty($entry['title']) ? $data['title'] : '',
		':company' => !empty($entry['company']) ? $data['company'] : '',
		':address' => !empty($entry['address']) ? $data['address'] : '',
		));

		$this->updateImageByID($data['id'], !empty($entry['image']) ? $entry['image'] : '', !empty($entry['gravatar']) ? $entry['gravatar'] : '', 'external');

		$this->addNumbersByEntryID($data['id'], !empty($entry['numbers']) ? $entry['numbers'] : '');

		$this->addXMPPsByEntryID($data['id'], !empty($entry['xmpps']) ? $entry['xmpps'] : '');

		$this->addEmailsByEntryID($data['id'], !empty($entry['emails']) ? $entry['emails'] : '');

		$this->addWebsitesByEntryID($data['id'], !empty($entry['websites']) ? $entry['websites'] : '');

		return array("status" => true, "type" => "success", "message" => _("Group entry successfully updated"), "id" => $data['id']);
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
		':displayname' => isset($entry['displayname']) ? $entry['displayname'] : "",
		':fname' => isset($entry['fname']) ? $entry['fname'] : "",
		':lname' => isset($entry['lname']) ? $entry['lname'] : "",
		':title' => isset($entry['title']) ? $entry['title'] : "",
		':company' => isset($entry['company']) ? $entry['company'] : "",
		':address' => isset($entry['address']) ? $entry['address'] : ""
		));

		$id = $this->db->lastInsertId();

		$this->updateImageByID($id, !empty($entry['image']) ? $entry['image'] : '', !empty($entry['gravatar']) ? $entry['gravatar'] : '', 'external');

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

			$this->updateImageByID($id, $entry['image'], $entry['gravatar'], 'external');

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

		$this->updateImageByID($id, $entry['image'], $entry['gravatar'], 'external');

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
		'n.id',
		'n.entryid',
		'n.number',
		'n.extension',
		'n.type',
		'n.flags',
		's.id as speeddial'
		);
		$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_numbers as n
		LEFT JOIN contactmanager_entry_speeddials as s ON (s.numberid = n.id) WHERE n.entryid = :entryid ORDER BY n.id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':entryid' => $entryid));
		$numbers = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $numbers;
	}

	/**
	 * Get all images by group ID
	 * @param {int} $groupid The group ID
	 */
	public function getImagesByGroupID($groupid,$type="internal") {
		if($type == "external") {
			$fields = array(
			'e.id',
			'n.entryid',
			'n.image',
			'n.format'
			);
			$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_images as n
			LEFT JOIN contactmanager_group_entries as e ON (n.entryid = e.id) WHERE `groupid` = :groupid ORDER BY e.id";
		} else {
			$fields = array(
			'e.id',
			'n.uid',
			'n.image',
			'n.format'
			);
			$sql = "SELECT " . implode(', ', $fields) . " FROM contactmanager_entry_userman_images as n
			LEFT JOIN contactmanager_group_entries as e ON (n.uid = e.user) WHERE `groupid` = :groupid ORDER BY e.id";
		}
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));
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
		'n.flags'
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

		$this->removeSpeedDialNumberByNumberID($id);

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

		$this->removeSpeedDialNumbersByEntryID($entryid);

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

		$sql = "DELETE n FROM contactmanager_entry_speeddials as n
		LEFT JOIN contactmanager_group_entries as e ON (n.entryid = e.id) WHERE `groupid` = :groupid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':groupid' => $groupid));

		$this->syncSpeedDials();

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
	 * Update Image By Entry ID
	 * @param  int $entryid The entry ID to update
	 * @param  string $filename The image filename
	 * @return array
	 */
	public function updateImageByID($id, $filename, $gravatar = false, $type="external") {
		if(empty($filename) || is_array($filename)) {
			return;
		}
		$name = basename($filename);
		if(!file_exists($this->tmp."/".$name)) {
			return;
		}
		if($type == "external") {
			$sql = "REPLACE INTO contactmanager_entry_images (entryid, image, format, gravatar) VALUES (:id, :image, 'image/png', :gravatar)";
		} else {
			$sql = "REPLACE INTO contactmanager_entry_userman_images (uid, image, format, gravatar) VALUES (:id, :image, 'image/png', :gravatar)";
		}


		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':id' => $id,
			':image' => file_get_contents($this->tmp."/".$name),
			':gravatar' => $gravatar ? 1 : 0
		));

		unlink($this->tmp."/".$name);

		return array("status" => true, "type" => "success", "message" => _("Group entry image successfully added"), "id" => $id);
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

			if(isset($number['speeddial'])) {
				$numberid = $this->db->lastInsertId();
				if(trim($number['speeddial']) !== "") {
					$this->addSpeedDialNumber($entryid,$numberid,$number['speeddial']);
				} else {
					$this->removeSpeedDialNumberByNumberID($id);
				}
			}

		}

		return array("status" => true, "type" => "success", "message" => _("Group entry numbers successfully added"));
	}

	public function getSpeedDialByID($id) {
		$sql = "SELECT * FROM contactmanager_entry_speeddials WHERE id = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':id' => $id
		));
		return $sth->fetch(\PDO::FETCH_ASSOC);
	}

	public function addSpeedDialNumber($entryid, $numberid,$speeddial) {
		$sql = "REPLACE INTO contactmanager_entry_speeddials (id, entryid, numberid) VALUES (:id, :entryid, :numberid)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':id' => $speeddial,
			':entryid' => $entryid,
			':numberid' => $numberid
		));
		$this->syncSpeedDials();
	}

	public function removeSpeedDialNumberByNumberID($numberid) {
		$sql = "DELETE FROM contactmanager_entry_speeddials WHERE numberid = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			":id" => $entryid
		));
		$this->syncSpeedDials();
	}

	public function removeSpeedDialNumbersByEntryID($entryid) {
		$sql = "DELETE FROM contactmanager_entry_speeddials WHERE entryid = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			":id" => $entryid
		));
		$this->syncSpeedDials();
	}

	/**
	 * Get Image By Entry ID
	 * @param  int $entryid The entryid
	 * @param  string $email   The email addres of entry (for automatic gravatar updates)
	 * @return array          Array of information about the image
	 */
	public function getImageByID($id, $email=false, $type='external') {
		if($type == 'external') {
			$sql = "SELECT image, format, gravatar FROM contactmanager_entry_images WHERE `entryid` = :id";
		} else {
			$sql = "SELECT image, format, gravatar FROM contactmanager_entry_userman_images WHERE `uid` = :id";
		}

		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$image = $sth->fetch(\PDO::FETCH_ASSOC);
		if(!empty($image['gravatar']) && !empty($email)) {
			$data = $this->getGravatar($email);
			if(empty($data)) {
				$this->delImageByID($id, $type);
				return false;
			} else {
				$rand = rand();
				imagepng(imagecreatefromstring($data), $this->tmp."/".$rand.".png");
				$image['image'] = file_get_contents($this->tmp."/".$rand.".png");
				$this->updateImageByID($id, $this->tmp."/".$rand.".png", true, $type);
				return $image;
			}
		}
		return $image;
	}

	/**
	 * Delete image by Entry ID
	 * @param  int $id The entry id
	 * @param  int $type    The entry type
	 */
	public function delImageByID($id, $type='external') {
		if($type == "external") {
			$sql = "DELETE FROM contactmanager_entry_images WHERE `entryid` = :id";
		} else {
			$sql = "DELETE FROM contactmanager_entry_userman_images WHERE `uid` = :id";
		}

		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
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
				case "internal":
					$entries = $this->getEntriesByGroupID($group['id']);
					if(!empty($entries) && is_array($entries)) {
						$final = array();
						foreach($entries as $entry) {
							$entry['type'] = "internal";
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
							$entry['displayname'] = !empty($entry['displayname']) ? $entry['displayname'] : $entry['username'];
							$entry['groupid'] = $group['id'];
							$entry['groupname'] = $group['name'];
							$final[] = $entry;
						}
						$contacts = array_merge($contacts, $final);
					}
				break;
				case "external":
					$entries = $this->getEntriesByGroupID($group['id']);
					if(!empty($entries) && is_array($entries)) {
						$final = array();
						foreach($entries as $id => $entry) {
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
							$entry['groupid'] = $group['id'];
							$entry['groupname'] = $group['name'];
							$entry['id'] = $entry['uid'];
							$final[] = $entry;
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
	 * @param {boolean} $regexpsearch Allow regular expressions to be passed into search. Make sure you preg_quote!
	 */
	public function lookupByUserID($id, $search, $regexp = null, $regexpsearch = false) {
		if(trim($search) == "") {
			return false;
		}
		$skip = array(
			"uid",
			"groupid",
			"user",
			"id",
			"auth",
			"authid",
			"username",
			"password",
			"primary_group",
			"permissions",
			"type",
			"image"
		);
		if(!$regexpsearch) {
			$search = preg_quote($search,"/");
		}
		$search = trim($search);
		$contacts = $this->getContactsByUserID($id);
		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($contacts));
		$lookuplen = (int)$this->freepbx->Config->get('CONTACTMANLOOKUPLENGTH');
		foreach($iterator as $key => $value) {
			if(in_array($key,$skip)) {
				continue;
			}
			$value = !empty($regexp) ? preg_replace($regexp,'',$value) : $value;
			$value = trim($value);
			if(empty($value)) {
				continue;
			}
			if(preg_match('/^' . $search . '$/i',$value) || (strlen($search) > $lookuplen && preg_match('/' . $search . '/i',$value))) {
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
	 * @param {boolean} $regexpsearch Allow regular expressions to be passed into search. Make sure you preg_quote!
	 */
	public function lookupMultipleByUserID($id, $search, $regexp = null, $regexpsearch = false) {
		$contacts = $this->getContactsByUserID($id);
		$final = array();
		$list = array();
		$skip = array(
			"uid",
			"groupid",
			"user",
			"id",
			"auth",
			"authid",
			"username",
			"password",
			"primary_group",
			"permissions",
			"type"
		);
		if(!$regexpsearch) {
			$search = preg_quote($search,"/");
		}
		$search = trim($search);
		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($contacts));
		$lookuplen = (int)$this->freepbx->Config->get('CONTACTMANLOOKUPLENGTH');
		foreach($iterator as $key => $value) {
			if(in_array($key,$skip)) {
				continue;
			}
			$value = !empty($regexp) ? preg_replace($regexp,'',$value) : $value;
			$value = trim($value);
			$k = $iterator->getSubIterator(0)->key();
			if(empty($value)) {
				continue;
			}
			if(!in_array($k, $list) && (preg_match('/' . $search . '/i',$value) || (strlen($search) > $lookuplen && preg_match('/' . $search . '/i',$value)))) {
				$final[] = $contacts[$k];
				$list[] = $k;
			}
		}
		return $final;
	}

	public function usermanUserDetails($user) {
		$image = $this->getImageByID($user['id'], $user['email'], 'internal');
		$user['image'] = $image;
		return array(load_view(dirname(__FILE__).'/views/user_details_hook.php',array("cmdata" => $user)));
	}

	/**
	 * Userman Page hook
	 */
	public function usermanShowPage() {
		if(isset($_REQUEST['action'])) {
			$groups = $this->getUnrestrictedGroupsbyOwner(-1);
			$visiblegroups = array();
			foreach($groups as $group) {
				if($group['type'] != "internal") {
					continue;
				}
				$visiblegroups[] = $group;
			}
			array_unshift($visiblegroups,array(
				'id' => '*',
				'owner' => '*',
				'name' => _("All Internal Groups"),
				'type' => '*'
			));
			array_unshift($groups,array(
				'id' => '*',
				'owner' => '*',
				'name' => _("All Public Groups"),
				'type' => '*'
			));
			switch($_REQUEST['action']) {
				case 'showgroup':
					$showingroups = $this->freepbx->Userman->getModuleSettingByGID($_REQUEST['group'],"contactmanager","showingroups",true);
					$showingroups = is_array($showingroups) ? $showingroups : array();
					$assigned = $this->freepbx->Userman->getModuleSettingByGID($_REQUEST['group'],"contactmanager","groups",true);
					$assigned = is_array($assigned) ? $assigned : array();
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = in_array($group['id'],$assigned);
					}
					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("visiblegroups" => $visiblegroups, "showingroups" => $showingroups, "mode" => "group", "groups" => $groups, "enabled" => $this->userman->getModuleSettingByGID($_REQUEST['group'],'contactmanager','show')))
						)
					);
				case 'addgroup':
					$assigned = array("*");
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = in_array($group['id'],$assigned);
					}
					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("visiblegroups" => $visiblegroups, "showingroups" => array(), "mode" => "group", "groups" => $groups, "enabled" => true))
						)
					);
				break;
				case 'adduser':
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = false;
					}
					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("visiblegroups" => $visiblegroups, "showingroups" => array(), "mode" => "user", "groups" => $groups, "enabled" => true))
						)
					);
				break;
				case 'showuser':
					$showingroups = $this->freepbx->Userman->getModuleSettingByID($_REQUEST['user'],"contactmanager","showingroups",true);
					$showingroups = is_array($showingroups) ? $showingroups : array();
					$assigned = $this->freepbx->Userman->getModuleSettingByID($_REQUEST['user'],"contactmanager","groups",true);
					$assigned = is_array($assigned) ? $assigned : array();
					foreach($groups as $k=>$group) {
						$groups[$k]['selected'] = in_array($group['id'],$assigned);
					}

					return array(
						array(
							"title" => _("Contact Manager"),
							"rawname" => "contactmanager",
							"content" => load_view(dirname(__FILE__).'/views/userman_hook.php',array("visiblegroups" => $visiblegroups, "showingroups" => $showingroups, "mode" => "user", "groups" => $groups))
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

	public function bulkhandlerImport($type, $rawData, $replaceExisting = true) {
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
					'user' => !empty($user) ? $user['username'] : -1,
					'displayname' => $data['displayname'],
					'fname' => $data['fname'],
					'lname' => $data['lname'],
					'title' => $data['title'],
					'company' => $data['company'],
					'address' => $data['address'],
					'image' => ''
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
				if ($group['type'] != 'external') {
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

	public function getNamebyNumber($number, $group = array()){
		$result = $this->lookupByUserID(-1, $number,"/\D/");
		if($result && !empty($group)){
			if(!in_array($result['groupid'], $group)){
				$result = array();
			}
		}
		return $result;
	}
}
