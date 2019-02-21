<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($jobid){
		$configs = $this->getConfigs();
		$this->FreePBX->Contactmanager->bulkhandlerImport('contacts', $configs, true);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$tables = [
			'contactmanager_groups',
			'contactmanager_groups_entries',
			'contactmanager_entry_speeddials',
			'contactmanager_entry_numbers',
			'contactmanager_entry_images',
			'contactmanager_entry_userman_images',
			'contactmanager_entry_xmpps',
			'contactmanager_entry_emails',
			'contactmanager_entry_websites'
		];
		foreach($tables as $table) {
			$sth = $pdo->query("SELECT * FROM $table",\PDO::FETCH_ASSOC);
			$res = $sth->fetchAll();
			$this->addDataToTableFromArray($table, $res);
		}
	}

}
