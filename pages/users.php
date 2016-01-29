<?php
defined('EVO') or die(__('403.msg'));
has_permission('user.view_uprofile', true);

if (isset($_REQUEST['filter'])) {
	$q = array('%'.$_REQUEST['filter'].'%', $_REQUEST['filter']);
	$where = 'a.username like ? or a.email = ?'; // We want the exact to be a perfect match, in case it's private.
} else {
	$where = '1';
	$q = array();
}
$num_users = Db::Get('select count(*) from {users} as a where '.$where, $q);
$default_sort = 'gpriority asc, activity desc, registered desc';

$columns = [
	__('users.username') => 'username',
	__('users.ingame')   => 'ingame',
	__('users.comments') => 'cmt',
	__('users.friends')  => 'fnd',
];

$display = _Get('view', 'grid');
$perpage = $display === 'grid' ? 25 : 50;

$sort = _Get('sort', $default_sort);

$pn = _Get('pn', 1) ?: 1;
$start = $perpage * ($pn-1);
$ptotal = ceil($num_users / $perpage);

$sort = in_array(strstr($sort.' ', ' ', true), $columns) ? $sort : $default_sort;

$users = Db::QueryAll('select *, g.color as color, g.name as gname, g.priority as gpriority,
							  (select count(*) from {comments} where user_id = a.id) as cmt,
							  (select count(*) from {friends} where u_id = a.id and state = 1) as fnd
						from {users} as a left join {groups} as g on g.id = a.group_id
						where '.$where.'
						order by '.$sort.'
						limit '. $start.','.$perpage, $q);
						

$paginator = paginator($ptotal, $pn);

include_template('pages/users.php', compact('users', 'columns', 'display', 'sort', '_countries', 'paginator'));
