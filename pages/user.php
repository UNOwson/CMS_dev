<?php
defined('EVO') or die('Que fais-tu là?');
has_permission('user.view_uprofile', true);

$profil_id = _Get('id', $user_session['id']);

$user_info = Db::Get('
				select a.*, g.name as gname, g.color as color, b.reason as ban_reason
				from {users} as a 
				left join {groups} as g ON a.group_id = g.id 
				left join {banlist} as b on a.username like b.rule and b.type = "username"
				where a.id = ? or a.username = ?', 
				is_numeric($profil_id) ? (int)$profil_id : -1, $profil_id
			);

if (!$user_info) {
	throw new Warning(lang::get('user.not_found'), lang::get('user.not_found'));
}

$_title = lang::get('user.page_title', ['%user%' => $user_info['username']]);

array_walk($user_info, 'html_encode');

if (trim(_POST('report')) !== '') {
	Db::Insert('reports', array(
		'user_id'  => $user_session['id'],
		'type'     => 'profile', 
		'rel_id'   => _POST('pid'), 
		'reason'   => _POST('report'), 
		'reported' => time(),
		'user_ip'  => $_SERVER['REMOTE_ADDR'],
	));
	log_event($user_info['id'], 'user', lang::get('user.reported', ['%user%' => $user_info['username']]));
}


include_template('pages/user.php',  [
	'num_friends'  => Db::Get('select count(*) from {friends} where u_id = ?', $user_info['id']),
	'num_comments' => Db::Get('select count(*) from {comments} where user_id = ?', $user_info['id']),
	'can_edit'     => $user_info['id'] === $user_session['id'] || has_permission('admin.edit_uprofile'),
	'can_mod'      => has_permission('mod.') || has_permission('admin.'),
	'is_mine'      => $user_info['id'] === $user_session['id'],
	'user_info'    => $user_info,
	'_countries'   => $_countries,
]);