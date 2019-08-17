<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$this->importKVStore($configs['kvstore']);
		$this->FreePBX->Contactmanager->bulkhandlerImport('contacts', $configs['data'], true);
		$this->importFeatureCodes($config['features']);
		$this->importAdvancedSettings($config['settings']);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyAll($pdo);
	}
}
