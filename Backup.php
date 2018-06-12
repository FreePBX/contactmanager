<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $files = [];
    $dirs = [];
    $configs = $this->FreePBX->Contactmanager->bulkHandlerExport('contacts');
    $this->addDependency('userman');
    $this->addConfigs($configs);
  }
}