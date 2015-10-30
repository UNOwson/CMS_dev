<?php 
namespace Plugins;

class PunBB extends \Plugins {
	const NAME 			= 'PunBB';
	const VERSION 		= '0';
	const DESCRIPTION 	= 'PunBB';
	const AUTHOR 		= 'alex';
}

return;

const table_users = '`forum_mi`.`punbb_users`';
const table_posts = '`forum_mi`.`punbb_posts`';
const table = '`forum_mi`.`punbb_users`';
const cookie_name = 'mine-infinity';
const cookie_domain = '.mine-infinity.com';


Plugins::hook('cookie_login', function($user_session, $expire) {
	if (isset($user_session['username'])) {
		if ($r = \Db::QuerySingle('select id, password, salt from '.table_users.' where username = ?', $user_session['username'], false, true)) {
			setcookie(cookie_name, base64_encode($r['id'].'|'.$r['password'].'|'.$expire.'|'.sha1($r['salt'].$r['password'].sha1($r['salt'].sha1($expire)))), $expire, '/', cookie_domain);
		}
	}
});


Plugins::hook('cookie_destroy', function () { return setcookie(cookie_name, '', 100, '/', cookie_domain); });


Plugins::hook('account_created', function($user_id)  {
	\Db::Exec('INSERT INTO '.table_users.' (username, group_id, password, salt, email, email_setting, registration_ip, registered, facebook, twitter, skype, url)
				VALUES(?, 10, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), ?, ?, ?, ?)', 
				$_POST['username'], 
				$password, 
				$salt, 
				$_POST['email'], 
				$_POST['hide_email'], 
				$_SERVER['REMOTE_ADDR'], 
				$_POST['facebook'], 
				$_POST['twitter'], 
				$_POST['youtube'],
				$_POST['website'],
				false
	);
});


Plugins::hook('account_updated', function($user_info, $edits) {
	//if (isset($edits['username']))
	
	\Db::Exec('UPDATE '.table_users.' SET email = ?, email_setting = ?, facebook = ? , twitter = ? , skype = ? , url =? where username = ?', 
				$user_info['email'], 
				$user_info['hide_email'], 
				$user_info['facebook'], 
				$user_info['twitter'], 
				$user_info['youtube'],
				$user_info['website'],
				$user_info['username'],
				false
	);
});


Plugins::hook('account_deleted', function($user_info){
	if (isset($_POST['del_forum_posts'])) {
		if ($c = \Db::Exec('delete from '.table_users.' where username = ?', $user_info['username']))
			echo '<li>'.$c.' Profil forum supprimé</li>';
		if ($c = \Db::Exec('delete from '.table_posts.' where poster = ?', $user_info['username']))
			echo '<li>'.$c.' posts(s) supprimé(s)</li>';
	}
});
