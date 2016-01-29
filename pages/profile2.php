<?php defined('EVO') or die(__('403.msg'));
has_permission(null, true);

$avatars['Base'] = new htmlSelectGroup([
	'/assets/img/avatar.png' => 'Inconnu',
	'' => 'Gravatar'
]);

if (!empty($_community['avatar'])) {
	$avatars['Base']['ingame'] = 'Ingame';
}


foreach(glob(ROOT_DIR.'/upload/avatar/*/') as $cat_dir) {
	$cat = ucfirst(basename($cat_dir));
	$avatars[$cat] = new htmlSelectGroup();
	if ($pictures = glob($cat_dir . '*.{jpg,png,gif}', GLOB_BRACE)) {
		foreach($pictures as $avatar_path) {
			$avatar = ucfirst(basename($avatar_path));
			$avatar = substr($avatar, 0, strrpos($avatar, '.'));
			$avatar_path = str_replace(ROOT_DIR, '', $avatar_path);
			$avatars[$cat][$avatar_path] = $avatar;
		}
	}
}

$my_files = Db::QueryAll('select * 
			              from {files} 
						  where type = "image" and poster = ? and size <= 10240
						  order by id desc',
						  $user_session['id']);

$label_my_files = lang::get('profile.my_files');

$avatars[$label_my_files] = new htmlSelectGroup();

foreach($my_files as $file) {
	// getimagesize();
	$avatars[$label_my_files][$file['path']] = basename($file['path']);
}

$grouplist = Db::QueryAll('select * from {groups} order by priority asc', true);
$groups = [];
foreach($grouplist as $group) {
	$groups[] = [
				  $group['id'], 
				  $group['name'], 
				  ['style' => 'color:'.$group['color']]
				 ];
}
// ['' => ' - Heure du CMS: ' . date_in_tz('Y-m-d H:i', Site('timezone')) . ' - '] + 
$timezones = generate_tz_list();

$groups = Db::QueryAll('select * from {groups} order by priority asc');


$fields = [ // regex/enum validation, is_required, filter
			'username' 	   => [PREG_USERNAME, true],
			'password' 	   => ['/^.{4,512}$/', false],
			'email'        => [PREG_EMAIL, true], 
			'country'      => [array_keys($_countries), false],
			'timezone'     => [array_keys($timezones), false],
			'avatar'       => [PREG_FILEPATH, false],
			'hide_email'   => [[0, 1], true],
			'newsletter'   => [[0, 1], true],
			'discuss'      => [[0, 1], true],

			'ingame'       => [@$_community['ingame_pattern'], false],

			'skype'        => [PREG_USERNAME, false],
			'facebook'     => [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(facebook\\.com/)?#i'],
			'twitter'      => [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(twitter\\.com/)?#i'],
			'twitch'       => [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(twitch\\.tv/)?#i'],
			'youtube'      => [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(yotu\\.be/user/|youtube\\.com/user/)?#i'],

			'website'      => [PREG_URL, false],
			'about'        => ['/^.{0,1024}$/m', false],
];

$reset = ['hide_email' => 0, 'newsletter' => 0, 'discuss' => 0];


if (has_permission('admin.edit_raf')) $fields['raf'] = [PREG_USERNAME, false];
if (has_permission('admin.edit_ugroup')) $fields['group_id'] = [array_column($groups, 'id'), true];
if (Site('change_theme')) $fields['theme'] = [PREG_FILENAME, false];

if (_GET('id') && _GET('id') != $user_session['id'] && has_permission('admin.edit_uprofile', true)) 
{
	$user_info = get_user(_GET('id'));
}
else {
	$user_info = $user_session;
}

if (!$user_info) {
	return $_warning = lang::get('profile.not_found');
}

if ($user_info['group']['priority'] < $user_session['group']['priority']) {
	return $_warning = 'Vous ne pouvez modifier le profil de quelqu\'un plus gradé.';
}


if ($_POST) {
	$edits = [];
	
	if (!has_permission('admin.edit_uprofile', true) && $ban = check_banlist($_POST)) {
		$_warning .= 'Désolé cet utilisateur ou email a été banni: ' . html_encode($ban['reason']) . '<br>';
		unset($fields['username'], $fields['email']);
	}
	
	$form_values = array_intersect_key($_POST, $fields); // We keep only valid elements in case of form forgery 
	
	$form_values = $form_values + $reset;
	
	foreach ($form_values as $field => $value) {
		$f = $fields[$field];
		if (isset($f[2])) {
			$value = preg_replace($f[2], '', $value);
		}
		if ((string)$user_info[$field] === $value) {
			continue;
		}
		if (
			((is_array($f[0])  && !in_array($value, $f[0])) || // If value not within array
			(is_string($f[0]) && !preg_match($f[0], $value))) // OR if not acceptable string
			&& !($f[1] !== true && $value === '') // AND if the parameter is not both empty and optional
		) {
			$_warning .= 'Champ invalide: '.$field.'<br>';
		} elseif ($f[1] === true && $value === '') {
			$_warning .= 'Champ requis: '.$field.'<br>';
		} else {
			$edits[$field] = $value;
		}
	}
	
	if (isset($edits['password']) && $edits['password'] !== '') {
		if (!compare_password($user_info['password'], _POST('password_old'), $user_info['salt'])) {
			$_warning .= 'Vous devez entrer votre mot de passe actuel avant de le mettre à jour!';
		} else {
			list($edits['password'], $edits['salt']) = hash_password($edits['password']);
		}
	} else {
		unset($edits['password']);
	}
	
	if (isset($edits['email'])) {
		if (!compare_password($user_info['password'], _POST('password_old'), $user_info['salt'])) {
			$_warning .= 'Vous devez entrer votre mot de passe actuel afin de changer d\'adresse email!';
		}
	}
	
	$f = implode (', ', array_map(function($f) { return "`$f` = ?"; }, array_keys($edits)));

	if (isset($edits['group_id'])) {
		$group = get_group($edits['group_id']);
	}
	
	if (isset($group) && $user_session['group']['priority'] > $group['priority'])
	{
		return $_warning = 'Vous ne pouvez assigner un groupe plus élévé que votre groupe actuel.';
	}
	elseif (isset($edits['username']) && Db::Get('select username from {users} where username = ?', $edits['username']))
	{
		$_warning .= 'Cet utilisateur est déjà pris!';
	}
	elseif (!empty($edits) && empty($_warning) && Db::Exec('update {users} set ' . $f . ' where `id` = ' . $user_info['id'], array_values($edits)) !== false) 
	{	
		$_success = 'Profil mis à jour!';
		
		log_event($user_info['id'], 'user', 'Modification de profil: '.implode(', ', array_keys($edits)));
		
		plugins::trigger('account_updated', [$user_info, $edits]);
		
		$user_info = $edits + $user_info;
		
		if ($user_info['id'] == $user_session['id']) {
			$user_session = $user_info;
			cookie_login();
		} else
			log_event($user_info['id'], 'admin', 'Modification du profil de '.$user_info['username'].': '.implode(', ', array_keys($edits)));
	}
}




$f = [
	'username' => [
		'label' => 'Nom d\'utilisateur: ',
		'type' => 'text',
		'value' => _POST('username', $user_info['username']),
		'validation' => PREG_USERNAME,
		'required' => true,
	],
	'email' => [
		'label' => 'Votre Email: ',
		'type' => 'text',
		'value' => _POST('email', $user_info['email']),
		'validation' => PREG_EMAIL,
		'required' => true,
	],
	'country' => [
		'label' => 'Votre Pays: ',
		'type' => 'select',
		'options' => $_countries,
		'value' => _POST('country', $user_info['country']),
		'validation' => array_keys($_countries),
		'required' => true,
	],
	'timezone' => [
		'label' => 'Fuseau horaire: ',
		'type' => 'select',
		'options' => $timezones,
		'value' => _POST('timezone', $user_info['timezone']),
		'validation' => array_keys($timezones),
		'required' => true,
	],
	[
		'label' => 'Options',
		'type' => 'multiple',
		'fields' => [
			'hide_email' => [
				'label' => ' Cacher mon email des autres membres',
				'type' => 'checkbox',
				'checked' => _POST('hide_email', $user_info['hide_email']),
				'value' => 1,
				'validation' => PREG_DIGIT,
			],
			'newsletter' => [
				'label' => 'Je désire recevoir la newsletter',
				'type' => 'checkbox',
				'checked' => _POST('newsletter', $user_info['newsletter']),
				'value' => 1,
				'validation' => PREG_DIGIT,
			],
			'discuss' => [
				'label' => 'Activer le mode discussion pour messagerie interne',
				'type' => 'checkbox',
				'checked' => _POST('discuss', $user_info['discuss']),
				'value' => 1,
				'validation' => PREG_DIGIT,
			],
		],
	],
	'password' => [
		'label' => 'Mot de passe: ',
		'type' => 'multiple',
		'fields' => [
			'password' => [
				'type' => 'password',
				'value' => '',
				'attributes' => ['placeholder' => 'Nouveau mot de passe'],
				'validation' => PREG_PASSWORD,
			],
			'password_old' => [
				'type' => 'password',
				'value' => '',
				'attributes' => ['placeholder' => 'Mot de passe actuel'],
				'validation' => PREG_PASSWORD,
			],
		],
	],
	'raf' => [
		'label' => 'Parrain: ',
		'type' => 'text',
		'value' => _POST('raf', $user_info['raf']),
		'validation' => PREG_USERNAME,
		'attributes' => 'disabled',
	],
	'ingame' => [
		'label' => '<i class="fa fa-' . $_community['icon'] . ' fa-2x"></i>',
		'type' => 'text',
		'value' => _POST('ingame', $user_info['ingame']),
		'validation' => $_community['ingame_pattern'],
		'attributes' => ['placeholder' => $_community['ingame_label']],
	],
	'group_id' => [
		'label' => 'Niveau du compte',
		'type' => 'select',
		'value' => $user_info['group_id'],
		'options' => $groups,
	],
	'theme' => [
		'label' => 'Thème',
		'type' => 'select',
		'value' => _POST('theme', $user_info['theme']),
		'options' => ['' => 'Theme de l\'administrateur'] + array_map(function($a) {return new htmlSelectGroup($a);}, get_themes()),
		'validation' => PREG_FILENAME,
	],
	'avatar' => [
		'label' => 'Mon avatar: ',
		'type' => 'avatar',
		'value' => $user_info['avatar'],
		'avatar' => get_avatar($user_info, 42, true),
		'attributes' => 'disabled',
		'validation' => PREG_FILEPATH,
		'required' => false,
	],
];


?>
<form method="post" role="form" class="form-horizontal">
<?=build_form('Modification du profil de ' . $user_info['username'], $f, false)?>
</form>


<script>
	$('select.avatar_selector option[value=""]').attr('data-src-alt', "<?php echo get_avatar(['avatar' => '', 'email' => $user_info['email']], true); ?>");
	$('select.avatar_selector option[value="ingame"]').attr('data-src-alt', "<?php echo get_avatar(['avatar' => 'ingame', 'email' => $user_info['email'], 'ingame' => $user_info['ingame']], true); ?>");
	$('select.avatar_selector')
		.after('<select style="float: left;width: 300px;" class="form-control" id="cat_only_selectbox"></select>')
		.hide();
	$("select.avatar_selector > optgroup").each(function() {
		var f = $(this).children('option');
		var in_group = $(this).children('option[selected]').length;
		if (f.length != 0) {
			$('#cat_only_selectbox').append('<option value="' + f[0].value + '" ' + (in_group ? 'selected':'') + '>' + this.label + '</option>');
		}
	});
	$('#cat_only_selectbox').bind('change keyup', function(e) {
		$('select.avatar_selector').val($(this).val()).change();
	});
	$('.password-required').on('change keyup', function() {
		if ($(this).attr('data-old-value') != $(this).val()) {
			$('input[name="password_old"]').css('background-color', 'pink');
		} else {
			$('input[name="password_old"]').css('background-color', '');
		}
	});
</script>