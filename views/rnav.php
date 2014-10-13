<?php
$list[] = '<li><a href="config.php?display=contactmanager&action=addgroup">' . _('Add New Group') . '</a></li>';

$igrp[] = '<li class="rnavH3">Internal Groups</li>';
$egrp[] = '<li class="rnavH3">External Groups</li>';
foreach ($groups as $g) {
	if ($g['owner'] != -1) {
		continue;
	}

	switch ($g['type']) {
	case "internal":
		$url = 'config.php?display=contactmanager&action=showgroup&group=' . $g['id'];

		$a = '<li><a href="' . $url . '"' .
			(($g == $g['id']) ? ' class="current ui-state-highlight"' : '') .
			'>' . $g['name'] . '</a></li>';

		$igrp[] = $a;
		break;
	case "external":
		$url = 'config.php?display=contactmanager&action=showgroup&group=' . $g['id'];

		$a = '<li><a href="' . $url . '"' .
			(($g == $g['id']) ? ' class="current ui-state-highlight"' : '') .
			'>' . $g['name'] . '</a></li>';

		$egrp[] = $a;
		break;
	}
}
$list = array_merge($list, $igrp, $egrp);

echo '<div class="rnav">';
echo '<ul>';
foreach ($list as $li) {
	echo $li;
}
echo '</ul>';
echo '</div>';
?>
