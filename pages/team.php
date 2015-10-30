<?php defined('EVO') or die('Que fais-tu là?');

$groups = Db::QueryAll('select distinct group_id from {permissions} where name like "mod.%" and value = 1');
$users  = Db::QueryAll('SELECT a.*, g.name as gname, g.color as color FROM {users} as a JOIN {groups} as g ON g.id = a.group_id WHERE group_id IN (' . implode(', ', array_map('reset', $groups)) . ') ORDER BY g.priority asc, username ASC');

include_template('pages/team.php', [
	'groups' => $groups,
	'users'  => $users,
]);