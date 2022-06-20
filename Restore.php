<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$this->importKVStore($configs['kvstore']);
		if ( array_key_exists('contactmanager_groups', $configs) && isset($configs['contactmanager_groups']) ) {
			$sql = "Truncate contactmanager_groups";
			$sth = $this->FreePBX->Database->prepare($sql);
			$sth->execute();
			foreach($configs['contactmanager_groups'] as $entry){
				$sql = "INSERT INTO contactmanager_groups (`id`,`owner`, `name`,`type`) VALUES(?,?,?,?)";
				$sth = $this->FreePBX->Database->prepare($sql);
				$sth->execute(array($entry['id'],$entry['owner'],$entry['name'],$entry['type']));
			}
		}
		if ( array_key_exists('contactmanager_group_entries', $configs) && isset($configs['contactmanager_group_entries']) ) {
			$sql = "Delete From contactmanager_group_entries WHERE user !='-1' ";
			$sth = $this->FreePBX->Database->prepare($sql);
			$sth->execute();
			foreach($configs['contactmanager_group_entries'] as $entry){
				$sql = "INSERT INTO contactmanager_group_entries (`groupid`, `user`,`uuid`,`displayname`,`fname`,`lname`,`title`,`company`,`address`) VALUES(?,?,?,?,?,?,?,?,?)";
				$sth = $this->FreePBX->Database->prepare($sql);
				$sth->execute(array($entry['groupid'],$entry['user'],$entry['uuid'],$entry['displayname'],$entry['fname'],$entry['lname'],$entry['title'],$entry['company'],$entry['address']));
			}
		}
		$this->FreePBX->Contactmanager->bulkhandlerImport('contacts', $configs['data'], true);
		$this->importFeatureCodes($configs['features']);
		$this->importAdvancedSettings($configs['settings']);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyAll($pdo);
	}
}
