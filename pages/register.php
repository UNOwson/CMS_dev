<?php defined('EVO') or die(__('403.msg'));

if (isset($_REQUEST['raf']))
	$raf = Db::Get('select username from {users} where raf_token = ?', $_REQUEST['raf']);
else
	$raf = '';

if (!has_permission('admin.') && (Site('open_registration') == 0 || (Site('open_registration') == 2 && !$raf))) {
	return $_warning = __('register.closed');
}

$fields = [
	'username' => [
		'label' => __('register.field_username'),
		'type' => 'text',
		'value' => _POST('username'),
		'validation' => PREG_USERNAME,
		'required' => true,
	],
	'email' => [
		'label' => __('register.field_email'),
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
				'label' => __('register.checkbox_email'),
				'type' => 'checkbox',
				'checked' => (!$_POST || isset($_POST['hide_email'])),
				'value' => 1,
				'validation' => PREG_DIGIT,
			],
			'newsletter' => [
				'label' => __('register.checkbox_newsletter'),
				'type' => 'checkbox',
				'checked' => (!$_POST || isset($_POST['newsletter'])),
				'value' => 1,
				'validation' => PREG_DIGIT,
			],
		],
	],
	'password' => [
		'label' => __('register.field_password'),
		'type' => 'password',
		'value' => _POST('password'),
		'validation' => PREG_USERNAME,
		'required' => true,
	],
	'password_confirm' => [
		'label' => __('register.field_passconfirm'),
		'type' => 'password',
		'value' => _POST('password_confirm'),
	],
	'raf' => [
		'label' => __('register.field_recruiter'),
		'type' => 'text',
		'value' => $raf,
		'attributes' => 'disabled',
	],
	'avatar' => [
		'label' => __('register.field_avatar'),
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
			$_warning .= __('register.field_invalid').' : '.$field.'<br>';
		} elseif ($f['required'] === true && $value === '') {
			$_warning .= __('register.field_require').' : '.$field.'<br>';
		}
	}
	
	if ($ban = check_banlist($_POST)) {
		$_warning .= __('register.check_banlist').' : ' . html_encode($ban['reason']) .' <br>';
	}
	
	$user_exists = Db::Get('select username FROM {users} WHERE username = ? or email = ?', $_POST['username'], $_POST['email']);

	if ($user_exists) {
		$_warning .= strcasecmp($user_exists, $_POST['username']) !== 0 ? ''.__('register.exist_email').' !' : ''.__('register.exist_username').' !';
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
			
			log_event($uid, 'user', ''.__('register.log').'');
			
			plugins::trigger('account_created', array($uid));
			
			if (!has_permission('admin.') && Site('open_registration') == 3) {
				if (send_activation_email($_POST['username'])) {
					return print '<div class="bs-callout bs-callout-success"><h4>'.__('register.congratulation').'</h4><p>'.__('register.success').'<br>'.__('register.mail_confirm').'</p></div>';
				}
				else {
					log_event('admin', 'Unable to send mail to: ' . $_POST['email']);
					log_event('user', 'Rolling back inscription of ' . $_POST['username'] . ' because activation link couldn\'t be sent.');
					Db::Exec('delete from {users} where id = ?', $uid);
					return $_warning = ''.__('register.mail_error').'';
				}
			}
			
			if (!has_permission('admin.')) {
				cookie_login($uid);
			}
			
			return print '<div class="bs-callout bs-callout-success">
							<h4>'.__('register.congratulation').'</h4><p>
							'.__('register.success_connect').'
						  </p></div>';
		} else {
			return print '<div class="bs-callout bs-callout-warning">
							<h4>'.__('register.damn').'</h4><p>'.__('register.error').'</p></div>';
		}
	}
}
echo build_form(''.__('register.title').'', $fields);
?>

<script>
$('form').submit(function() {
	if ($('#password').val() != $('#password_confirm').val()) {
		alert(''.__('register.field_passconfirm_wrong').'');
		return false;
	}
});
</script>