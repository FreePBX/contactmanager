<?php
$list[] = '<li><a href="config.php?display=contactmanager&action=addgroup">' . _('Add New Group') . '</a></li>';

$ligroups = array(
	'internal' => array(),
	'external' => array(),
	'userman' => array()
);
foreach ($groups as $g) {
	if ($g['owner'] != -1) {
		continue;
	}

	switch ($g['type']) {
	case "internal":
		if (count($ligroups[$g['type']]) < 1) {
			$ligroups[$g['type']][] = '<li class="rnavH3">Internal Groups</li>';
		}
		$url = 'config.php?display=contactmanager&action=showgroup&group=' . $g['id'];

		$li = '<li><a href="' . $url . '"' .
			(($group == $g['id']) ? ' class="current ui-state-highlight"' : '') .
			'>' . $g['name'] . '</a></li>';

		$ligroups[$g['type']][] = $li;
		break;
	case "external":
		if (count($ligroups[$g['type']]) < 1) {
			$ligroups[$g['type']][] = '<li class="rnavH3">External Groups</li>';
		}
		$url = 'config.php?display=contactmanager&action=showgroup&group=' . $g['id'];

		$li = '<li><a href="' . $url . '"' .
			(($group == $g['id']) ? ' class="current ui-state-highlight"' : '') .
			'>' . $g['name'] . '</a></li>';

		$ligroups[$g['type']][] = $li;
		break;
	case "userman":
		if (count($ligroups[$g['type']]) < 1) {
			$ligroups[$g['type']][] = '<li class="rnavH3">User Manager</li>';
		}
		$url = 'config.php?display=contactmanager&action=showgroup&group=' . $g['id'];

		$li = '<li><a href="' . $url . '"' .
			(($group == $g['id']) ? ' class="current ui-state-highlight"' : '') .
			'>' . $g['name'] . '</a></li>';

		$ligroups[$g['type']][] = $li;
		break;
	}
}
foreach ($ligroups as $li) {
	$list = array_merge($list, $li);
}

echo '<div class="rnav">';
echo '<ul>';
foreach ($list as $li) {
	echo $li;
}
echo '</ul>';
echo '</div>';
?>
