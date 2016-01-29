<?php 
defined('EVO') or die(__('403.msg')); 
has_permission('user.raf', true); 

if (empty($user_session['raf_token'])) {
	$user_session['raf_token'] = base64url_encode(sha1($user_session['id'] . '/' . uniqid(sha1(rand()), true), true));
	Db::Exec('update {users} set raf_token = ? where id =? ', $user_session['raf_token'], $user_session['id']);
}

$users = Db::QueryAll('SELECT a.*, g.name as gname, g.color as color FROM {users} as a LEFT JOIN {groups} as g ON g.id = a.group_id WHERE a.raf = ? ORDER BY group_id DESC', $user_session['username']);
$raf_url = create_url('register', ['raf' => $user_session['raf_token']]);

include_template('pages/recruit-a-friend.php', compact('users', 'raf_url'));
