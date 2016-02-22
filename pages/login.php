<?php defined('EVO') or die('Que fais-tu là?');

$action = _GP('action', 'login');

if ($action === 'logout')
{
	cookie_destroy();
	http_redirect('/');
}
elseif ($action === 'login' && !empty($_POST['login']))
{
	if ($r = Db::Get('SELECT * FROM {users} WHERE username = ? or email = ?', $_POST['login'], $_POST['login']))
		{
			if ($r['locked']) {
				if (isset($_POST['resendkey'])) {
					if (send_activation_email($r['username']))
						return $_notice = __('login.login_notice_mail_send');
					else 
						return $_warning = __('login.login_warning_mail_send');
				}
				$warning = __('login.login_warning_inactiv_acc');
				if ((int)$r['locked'] === 2) {
					$warning .= '<br><form method="post">
										<input type="hidden" name="login" value="' . html_encode($r['username']). '">
										<button type="submit" name="resendkey" value="1">Renvoyer le lien d\'activation</button>
								      </form>';
				}
				
				throw new WarningException('login.login_warning_inactiv_acc', $warning);
			}
			if (compare_password($r['password'], $_POST['pass'], $r['salt'])) 
				{
					$user_session = $r;
					log_event($r['id'], 'user', __('login.login_log_connected'));
					
					cookie_login($r['id'], $_POST['remember'] ? time() + 63072000 : 0);

					$redir = _GP('redir');
					if (stripos($redir, site('url')) === false) {
						$redir = Site('url') . '/' . ltrim($redir, '/');
					}
					http_redirect($redir);
				}
			else {
				$_warning = __('login.login_warning_invalid_pass');
			}	
		}
	else {
		$_warning = __('login.active_warning_unknow_acc');
	}
}
elseif ($action === 'forget' && !empty($_POST['login']))
{
	if ($r = Db::Get('SELECT * FROM {users} WHERE username = ? or email = ?', $_POST['login'], $_POST['login'])) {
		
		$key = sha1(rand(0, time()).uniqid($r['username']));
		Db::Exec('UPDATE {users} SET reset_key = ? WHERE id = ?', $key, $r['id']);
		
		log_event($r['id'], 'user', __('login.forget_new_pass_demand'));
		
		$message = parse_template('mail/activate_password.tpl', array('username' => $r['username'], 'resetlink' => create_url('login', ['action'=>'reset','key'=>$key,'username'=>$r['username']])));
		
		if (sendmail($r['email'], 'Oublie de mot de passe', $message)) {
			return $_success = __('login.forget_success_req_send');
		}
		else {
			$_warning = __('login.forget_warning_email_send');
		}
	}
	else {
		$_warning = __('login.active_warning_unknow_acc');
	}
}
elseif($action === 'reset')
{
	if (empty($_GET['key']) || empty($_GET['username']))
		throw new Warning('login.reset_warning_invalid_link');
	
	$r = Db::Get('SELECT id,username,salt,password,reset_key FROM {users} WHERE username = ? AND reset_key = ?', $_GET['username'], $_GET['key']);
	
	if (!$r)
		throw new Warning('login.reset_warning_invalid_link');
	
	if (isset($_POST['new_password'], $_POST['new_password1'])) {
		if ($_POST['new_password'] != $_POST['new_password1']) {
			$_warning = __('login.reset_warning_same_password');
		} else {
			$salt = salt_password(10);
			$password = hash_password($_POST['new_password'], $salt);

			Db::Exec('UPDATE {users} SET reset_key = null, password = ?, salt = ? WHERE username = ?', $password, $salt, $r['username']);

			log_event($r['id'], 'user', __('login.reset_log_newpass_activated'));
			
			$_success = __('login.reset_success_newpass_reg');
			$action = 'login';
		}
	}
}
elseif ($action === 'activate' && !empty($_GET['key']) && !empty($_GET['username']))
{
	if ($r = Db::Get('SELECT id, username, locked, activity FROM {users} WHERE username = ?', $_GET['username']))
		{
			$hash = sha1(sprintf('%d/%s/%d/%d/s', $r['id'], $r['username'], $r['locked'], $r['activity'], Site('salt')));
			
			if ($hash === $_GET['key'])
				{
					Db::Exec('update {users} set locked = 0 where username = ?', $r['username']);
					log_event($r['id'], 'user', 'Activation d\'un compte depuis lien d\'dactivation.');
					cookie_login($r['id'], 0);
					http_redirect(create_url('user'));
				}
			else {
				return $_warning = __('login.active_warning_already_key');
			}
		}
	else {
		return $_warning = __('login.active_warning_unknow_acc');
	}
}

include_template('pages/login.php', [
	'action' => $action,
	'login' => isset($_POST['login']) ? $_POST['login'] : '',
	'password' => isset($_POST['pass']) ? $_POST['pass'] : '',
]);
