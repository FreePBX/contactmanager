<?php
$li[] = '<a href="config.php?display=contactmanager&action=addgroup">' . _('Add New Group') . '</a>';

$igrp[] = 'Internal Groups';
$egrp[] = 'External Groups';
foreach ($groups as $g) {
	if ($g['owner'] != -1) {
		continue;
	}

	switch ($g['type']) {
	case "internal":
		$url = 'config.php?display=contactmanager&action=showgroup&group=' . $g['id'];

		$a = '<a href="' . $url . '"' .
			(($g == $g['id']) ? ' class="current ui-state-highlight"' : '') .
			'>' . $g['name'] . '</a>';

		$igrp[] = $a;
		break;
	case "external":
		$url = 'config.php?display=contactmanager&action=showgroup&group=' . $g['id'];

		$a = '<a href="' . $url . '"' .
			(($g == $g['id']) ? ' class="current ui-state-highlight"' : '') .
			'>' . $g['name'] . '</a>';

		$egrp[] = $a;
		break;
	}
}
$li = array_merge($li, $igrp, $egrp);

echo '<div class="rnav">' . ul($li) . '</div>';
?>
