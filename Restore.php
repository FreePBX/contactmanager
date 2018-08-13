<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = $this->getConfigs();
    $this->FreePBX->Contactmanager->bulkhandlerImport('contacts', $configs, true);
  }

  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
    $tables = array_flip($tables + $unknownTables);
    if (!isset($tables['meetme'])) {
      return $this;
    }
    $cb = $this->FreePBX->Contactmanager;
    $cb->setDatabase($pdo);
    $configs = $cb->bulkhandlerExport('contacts');
    $cb->resetDatabase();
    $cb->bulkhandlerImport('contacts', $configs, true);
    return $this;
  }

}