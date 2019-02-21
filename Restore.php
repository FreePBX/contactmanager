<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($jobid){
		$configs = $this->getConfigs();
		$this->FreePBX->Contactmanager->bulkhandlerImport('contacts', $configs, true);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyDatabaseKvstore($pdo);
	}

}
