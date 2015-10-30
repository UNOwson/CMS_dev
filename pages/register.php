<?php defined('EVO') or die('Que fais-tu là?');

if (isset($_REQUEST['raf']))
	$raf = Db::Get('select username from {users} where raf_token = ?', $_REQUEST['raf']);
else
	$raf = '';

if (!has_permission('admin.') && (Site('open_registration') == 0 || (Site('open_registration') == 2 && !$raf))) {
	return $_warning = 'Désolé, les inscriptions publiques sont closes.';
}

$fields = [
	'username' => [
		'label' => 'Nom d\'utilisateur: ',
		'type' => 'text',
		'value' => _POST('username'),
		'validation' => PREG_USERNAME,
		'required' => true,
	],
	'email' => [
		'label' => 'Votre Email: ',
		'type' => 'text',
		'value' => _POST('email'),
		'validation' => PREG_EMAIL,
		'required' => true,
	],
	[
		'label' => 'Options',
		'type' => 'multiple',
		'validation' => PREG_DIGIT,
		'fields' => [
			'hide_email' => [
				'label' => ' Cacher mon email des autres membres',
				'type' => 'checkbox',
				'checked' => (!$_POST || isset($_POST['hide_email'])),
				'value' => 1,
				'validation' => PREG_DIGIT,
			],
			'newsletter' => [
				'label' => 'Je désire recevoir la newsletter',
				'type' => 'checkbox',
				'checked' => (!$_POST || isset($_POST['newsletter'])),
				'value' => 1,
				'validation' => PREG_DIGIT,
			],
		],
	],
	'password' => [
		'label' => 'Mot de passe: ',
		'type' => 'password',
		'value' => _POST('password'),
		'validation' => PREG_USERNAME,
		'required' => true,
	],
	'password_confirm' => [
		'label' => 'Confirmation: ',
		'type' => 'password',
		'value' => _POST('password_confirm'),
	],
	'raf' => [
		'label' => 'Parrain: ',
		'type' => 'text',
		'value' => $raf,
		'attributes' => 'disabled',
	],
	'avatar' => [
		'label' => 'Mon avatar: ',
		'type' => 'avatar',
		'value' => _POST('avatar'),
		'attributes' => 'disabled',
		'validation' => PREG_FILEPATH,
		'required' => false,
	],
];

if (isset($_community['ingame_pattern'])) {
	$fields['ingame'] = [
		'label' => (@$_community['ingame_label'] ?: 'Ingame') . ': ',
		'type' => 'text',
		'value' => _POST('ingame'),
		'validation' => $_community['ingame_pattern'],
		'required' => false,
	];
}


if ($_POST) {
	$reset = ['hide_email' => 0, 'newsletter' => 0, 'discuss' => 0];
	$form_values = array_intersect_key($_POST + $reset, $fields); // We keep only valid elements in case of form forgery 

	foreach ($form_values as $field => $value) {
		$f = $fields[$field];
		if (!isset($f['validation'])) {
			continue;
		}
		if (
			((is_array($f['validation'])  && !in_array($value, $f['validation'])) || // If value not within array
			(is_string($f['validation']) && !preg_match($f['validation'], $value))) // OR if not acceptable string
			&& !($f['required'] !== true && $value === '') // AND if the parameter is not both empty and optional
		) {
			$_warning .= 'Champ invalide: '.$field.'<br>';
		} elseif ($f['required'] === true && $value === '') {
			$_warning .= 'Champ requis: '.$field.'<br>';
		}
	}
	
	if ($ban = check_banlist($_POST)) {
		$_warning .= 'Désolé cet utilisateur ou email a été banni: ' . html_encode($ban['reason']) .' <br>';
	}
	
	$user_exists = Db::Get('select username FROM {users} WHERE username = ? or email = ?', $_POST['username'], $_POST['email']);

	if ($user_exists) {
		$_warning .= strcasecmp($user_exists, $_POST['username']) !== 0 ? 'Un membre utilisant cet email existe déjà!' : 'Un membre utilisant cet utilisateur existe déjà!';
	}
	
	if ($_POST['avatar'] === '/assets/img/gravatar.jpg') { //Temp hack
		$_POST['avatar'] = '';
	}
	
	if (empty($_warning)) {
		list($password, $salt) = hash_password($_POST['password']);

		$q = Db::Insert('users', [
			'username'   => $_POST['username'],
			'country'    => null,
			'group_id'   => Site('default_user_group'),
			'locked'     => Site('open_registration') == 3 ? 2 : 0,
			'password'   => $password,
			'salt'       => $salt,
			'email'      => $_POST['email'],
			'hide_email' => _POST('hide_email') ? 1 : 0,
			'newsletter' => _POST('newsletter') ? 1 : 0,
			'ingame'     => _POST('ingame') ?: null,
			'raf'        => $raf,
			'avatar'     => $_POST['avatar'],
			'registered' => time(),
			'registration_ip' => $_SERVER['REMOTE_ADDR'],
		]);
		
		if ($q !== false) {
			$uid = Db::$insert_id;
			
			log_event($uid, 'user', 'Inscription sur le site.');
			
			plugins::trigger('account_created', array($uid));
			
			if (!has_permission('admin.') && Site('open_registration') == 3) {
				if (send_activation_email($_POST['username'])) {
					return print '<div class="bs-callout bs-callout-success"><h4>Félicitation</h4><p>
									Votre compte a été créé avec succès ! 
									Vous devriez recevoir un email sous peu afin d\'activer votre compte.
									</p></div>';
				}
				else {
					log_event('admin', 'Unable to send mail to: ' . $_POST['email']);
					log_event('user', 'Rolling back inscription of ' . $_POST['username'] . ' because activation link couldn\'t be sent.');
					Db::Exec('delete from {users} where id = ?', $uid);
					return $_warning = 'Erreur lors de l\'envoi du mail d\'activation. Veuillez nous contacter ou réessyaer plus tard!';
				}
			}
			
			if (!has_permission('admin.')) {
				cookie_login($uid);
			}
			
			return print '<div class="bs-callout bs-callout-success">
							<h4>Félicitation</h4><p>
							Votre compte a été créé avec succès et vous êtes maintenant connecté !
						  </p></div>';
		} else {
			return print '<div class="bs-callout bs-callout-warning">
							<h4>Oh oh</h4><p>La création de votre compte a échouée!</p></div>';
		}
	}
}
echo build_form('Création de compte', $fields);
?>

<script>
$('form').submit(function() {
	if ($('#password').val() != $('#password_confirm').val()) {
		alert('Les mots de passe de sont pas identiques!');
		return false;
	}
});
</script>