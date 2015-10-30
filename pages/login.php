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
						return $_notice = 'Le message a été envoyé!';
					else 
						return $_warning = 'Erreur lors de l\'envoi du message!';
				}
				$warning = 'Ce compte n\'est pas actif.';
				if ((int)$r['locked'] === 2) {
					$warning .= '<br><form method="post">
										<input type="hidden" name="login" value="' . html_encode($r['username']). '">
										<button type="submit" name="resendkey" value="1">Renvoyer le lien d\'activation</button>
								      </form>';
				}
				
				throw new WarningException('Ce compte n\'est pas actif', $warning);
			}
			if (compare_password($r['password'], $_POST['pass'], $r['salt'])) 
				{
					$user_session = $r;
					log_event($r['id'], 'user', 'Connexion sur le site depuis le formulaire login.');
					
					cookie_login($r['id'], $_POST['remember'] ? time() + 63072000 : 0);

					$redir = _GP('redir');
					if (stripos($redir, site('url')) === false) {
						$redir = Site('url') . '/' . ltrim($redir, '/');
					}
					http_redirect($redir);
				}
			else {
				$_warning = 'Mot de passe invalide.';
			}	
		}
	else {
		$_warning = 'Compte non reconnu.';
	}
}
elseif ($action === 'forget' && !empty($_POST['login']))
{
	if ($r = Db::Get('SELECT * FROM {users} WHERE username = ? or email = ?', $_POST['login'], $_POST['login'])) {
		
		$key = sha1(rand(0, time()).uniqid($r['username']));
		Db::Exec('UPDATE {users} SET reset_key = ? WHERE id = ?', $key, $r['id']);
		
		log_event($r['id'], 'user', 'Demande de nouveau mot de passe.');
		
		$message = parse_template('mail/activate_password.tpl', array('username' => $r['username'], 'resetlink' => create_url('login', ['action'=>'reset','key'=>$key,'username'=>$r['username']])));
		
		if (sendmail($r['email'], 'Oublie de mot de passe', $message)) {
			return $_success = 'Vous devriez recevoir un lien d\'ici peu !';
		}
		else {
			$_warning = 'Erreur lors de l\'envoi du mail !';
		}
	}
	else {
		$_warning = 'Compte non reconnu.';
	}
}
elseif($action === 'reset')
{
	if (empty($_GET['key']) || empty($_GET['username']))
		throw new Warning('Ce lien est invalide.');
	
	$r = Db::Get('SELECT id,username,salt,password,reset_key FROM {users} WHERE username = ? AND reset_key = ?', $_GET['username'], $_GET['key']);
	
	if (!$r)
		throw new Warning('Ce lien est invalide.');
	
	if (isset($_POST['new_password'], $_POST['new_password1'])) {
		if ($_POST['new_password'] != $_POST['new_password1']) {
			$_warning = 'Mot de passe non identiques !';
		} else {
			$salt = salt_password(10);
			$password = hash_password($_POST['new_password'], $salt);

			Db::Exec('UPDATE {users} SET reset_key = null, password = ?, salt = ? WHERE username = ?', $password, $salt, $r['username']);

			log_event($r['id'], 'user', 'Activation de nouveau mot de passe.');
			
			$_success = 'Votre nouveau mot de passe a été enregistré !';
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
				return $_warning = 'Votre compte est déjà actif ou la clée est invalide.';
			}
		}
	else {
		return $_warning = 'Compte non reconnu.';
	}
}

include_template('pages/login.php', [
	'action' => $action,
	'login' => isset($_POST['login']) ? $_POST['login'] : '',
	'password' => isset($_POST['pass']) ? $_POST['pass'] : '',
]);
