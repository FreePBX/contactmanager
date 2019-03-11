<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		$this->addDependency('userman');
		$this->addConfigs([
			'data' => $this->FreePBX->Contactmanager->bulkHandlerExport('contacts'),
			'kvstore' => $this->dumpKVStore(),
			'features' => $this->dumpFeatureCodes(),
			'settings' => $this->dumpAdvancedSettings()
		]);
	}
}