<?php
namespace FreePBX\modules\Contactmanager;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		$this->addDependency('userman');
		//contactmanger internal contacts are userman users and this is the table which links them
		$sql = "SELECT * FROM contactmanager_group_entries WHERE user !='-1'";
		$db = \FreePBX::Database();
		$sth = $db->prepare($sql);
		$sth->execute();
		$res = $sth->fetchAll(\PDO::FETCH_ASSOC);
		foreach($res as $dat) {
			$contactmanager_group_entries[] = $dat;
		}
		$sql = "SELECT * FROM contactmanager_groups WHERE `type`='internal' ";
		$db = \FreePBX::Database();
		$sth = $db->prepare($sql);
		$sth->execute();
		$res = $sth->fetchAll(\PDO::FETCH_ASSOC);
		foreach($res as $data) {
			$contactmanager_groups[] = $data;
		}
		$this->addConfigs([
			'data' => $this->FreePBX->Contactmanager->bulkHandlerExport('contacts'),
			'kvstore' => $this->dumpKVStore(),
			'features' => $this->dumpFeatureCodes(),
			'settings' => $this->dumpAdvancedSettings(),
			'contactmanager_group_entries'=> $contactmanager_group_entries,
			'contactmanager_groups' => $contactmanager_groups
		]);
	}
}