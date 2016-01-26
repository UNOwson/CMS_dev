<?php defined('EVO') or die('Que fais-tu là ?');
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

foreach($my_files as $file) {
	// getimagesize();
	$avatars[$label_my_files][$file['path']] = basename($file['path']);
}

// ['' => ' - Heure du CMS: ' . date_in_tz('Y-m-d H:i', Site('timezone')) . ' - '] + 
$timezones = generate_tz_list();

$groups = Db::QueryAll('select * from {groups} order by priority asc');


$fields = [ // regex/enum validation, is_required, filter
			'username' 	   => [PREG_USERNAME, true],
			'password' 	   => ['/^.{4,512}$/', false],
			'email'        => [PREG_EMAIL, true], 
			'country' 	   => [array_keys($_countries), false],
			'timezone' 	   => [array_keys($timezones), false],
			'avatar' 	   => [PREG_FILEPATH, false],
			'hide_email'   => [[0, 1], true],
			'newsletter'   => [[0, 1], true],
			'discuss' 	   => [[0, 1], true],
			
			'ingame' 	   => [@$_community['ingame_pattern'], false],
			
			'skype' 	   => [PREG_USERNAME, false],
			'facebook'    	=> [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(facebook\\.com/)?#i'],
			'twitter' 	   => [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(twitter\\.com/)?#i'],
			'twitch' 	   => [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(twitch\\.tv/)?#i'],
			'youtube' 	   => [PREG_USERNAME, false, '#^(https?://)?(www\\.)?(yotu\\.be/user/|youtube\\.com/user/)?#i'],
			
			'website' 	   => [PREG_URL, false],
			'about' 	   => ['/^.{0,1024}$/m', false],
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
?>
<legend><?= __('profile.title') ?> <?php echo $user_info['username']?></legend>
<form method="post" role="form" class="form-horizontal" autocomplete="off">
	<div class="form-group">
		<label class="col-sm-4 control-label" for="username"><?= __('profile.username') ?> :</label>
		<div class="col-sm-6">
			<input class="form-control" name="username" type="text" value="<?php echo $user_info['username']?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="mail"><?= __('profile.email') ?> :</label>
		<div class="col-sm-6">
			<input class="form-control password-required" name="email" type="text" data-old-value="<?php echo html_encode($user_info['email'])?>" value="<?php echo html_encode($user_info['email'])?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="mail"><?= __('profile.country') ?> :</label>
		<div class="col-sm-6">
			<?php echo html_select('country', $_countries, $user_info['country']); ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="mail"><?= __('profile.tzone') ?> :</label>
		<div class="col-sm-6">
			<?php echo html_select('timezone', $timezones, $user_session['timezone'] ?: Site('timezone'), false); ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="newsletter"><?= __('profile.options') ?> :</label>
		<div class="col-sm-8">
			<input id="hide_email" name="hide_email" type="checkbox" value="1" <?php if ($user_info['hide_email'] == 1) echo 'checked';?>>
			<label for="hide_email" class="normal"><?= __('profile.opt1') ?></label><br>
			<input id="newsletter" name="newsletter" type="checkbox" value="1" <?php if (@$user_info['newsletter'] == 1) echo 'checked';?>>
			<label for="newsletter" class="normal"><?= __('profile.opt2') ?></label><br>
			<input id="discuss" name="discuss" type="checkbox" value="1" <?php if ($user_info['discuss'] == 1) echo 'checked';?>>
			<label for="discuss" class="normal"><?= __('profile.opt3') ?></label><br>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="password"><?= __('profile.password') ?> :</label>
		<div class="col-sm-6">
			<input name="password" type="password" hidden><!-- that's to stop chrome's autocomplete -->
			<input name="password" type="password" data-old-value="" class="form-control password-required" placeholder="<?= __('profile.password_old') ?>">
			<br>
			<input name="password_old" type="password" class="form-control" placeholder="<?= __('profile.password_new') ?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="parrain"><?= __('profile.recruiter') ?> :</label>
		<div class="col-sm-4">
			<input class="form-control" data-autocomplete="userlist" name="raf" id="parrain" type="text" value="<?= html_encode($user_info['raf'])?>" <?php if (!isset($fields['raf'])) echo 'disabled'; ?>>
		</div>
	</div>
	<?php if ($_community) { ?>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="ingame" title="<?= $_community['label'] ?>"><i class="fa fa-<?= $_community['icon'] ?> fa-2x"></i></label>
		<div class="col-sm-4">
			<input class="form-control" id="ingame" name="ingame" type="text" value="<?= html_encode($user_info['ingame'])?>" placeholder="<?= $_community['ingame_label'] ?>">
		</div>
	</div>
	<?php } ?>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="permission"><?= __('profile.acc_lvl') ?> :</label>
		<div class="col-sm-6">
			<?php
				$groups = Db::QueryAll('select * from {groups} order by priority asc', true);
				$options = [];
				foreach($groups as $group) {
					$options[] = [
								  $group['id'], 
								  $group['name'], 
								  ['style' => 'color:'.$group['color']]
								 ];
				}
				if (isset($fields['group_id']))
					echo html_select('group_id', $options, $user_info['group_id']);
				else
					echo '<label class="col-sm-4 control-label" style="color: '.$user_info['color'].';">'.html_encode($user_info['gname']).'</label>';
			?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="theme"><?= __('profile.theme') ?> :</label>
		<div class="col-sm-6">
				<?php
					if (Site('change_theme'))
						echo html_select('theme', ['' => 'Theme de l\'administrateur'] + array_map(function($a) {return new htmlSelectGroup($a);}, get_themes()), $user_session['theme']);
					else
						echo '<label class="col-sm-4 control-label">' . Site('theme') . '</label>';
				?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="avatar"><?= __('profile.avatar') ?> :</label>
		<div class="col-sm-5">
			<?php echo html_select('avatar', $avatars, $user_info['avatar'], true, ['class' => 'avatar_selector form-control']); ?>
			<span style="margin-left: 10px;position: relative;top: -4px;"><img id="avatar_selector_preview" title="<?= __('profile.curravatar') ?>" width="42" height="42" src="<?php echo get_avatar($user_info, 42, true)?>"></span>
		</div>
	</div>
	
	<div id="avatar_selector_box" class="well"></div>
	
	<br><br>
	
	<legend><?= __('profile.sn_title') ?></legend>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="facebook" title="Facebook"><i class="fa fa-facebook fa-2x"></i></label>
		<div class="col-sm-6">
			<input class="form-control" id="facebook" name="facebook" type="text" value="<?php echo html_encode($user_info['facebook'])?>" placeholder="<?= __('profile.sn_fb') ?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="twitter" title="Twitter"><i class="fa fa-twitter fa-2x"></i></label>
		<div class="col-sm-6">
			<input class="form-control" id="twitter" name="twitter" type="text" value="<?php echo html_encode($user_info['twitter'])?>" placeholder="<?= __('profile.sn_tweeter') ?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="skype" title="Skype"><i class="fa fa-skype fa-2x"></i></label>
		<div class="col-sm-6">
			<input class="form-control" id="skype" name="skype" type="text" value="<?php echo html_encode($user_info['skype'])?>" placeholder="<?= __('profile.sn_skype') ?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="twitch" title="Twitch"><i class="fa fa-twitch fa-2x"></i></label>
		<div class="col-sm-6">
			<input class="form-control" id="twitch" name="twitch" type="text" value="<?php echo html_encode($user_info['twitch'])?>" placeholder="<?= __('profile.sn_twitch') ?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="youtube" title="Youtube"><i class="fa fa-youtube fa-2x"></i></label>
		<div class="col-sm-6">
			<input class="form-control" id="youtube" name="youtube" type="text" value="<?php echo html_encode($user_info['youtube'])?>" placeholder="<?= __('profile.sn_youtube') ?>">
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-4 control-label" for="website" title="Site web"><i class="fa fa-globe fa-2x"></i></label>
		<div class="col-sm-6">
			<input class="form-control" id="website" name="website" type="text" value="<?php echo html_encode($user_info['website'])?>" placeholder="<?= __('profile.sn_website') ?>">
		</div>
	</div>

	<legend><?= __('profile.prez_title') ?></legend>
	<div class="form-group">
		<div class="col-md-10 col-md-offset-1">
			<textarea class="form-control" name="about" placeholder="<?= __('profile.prez_textarea') ?>"><?php echo html_encode($user_info['about'])?></textarea>
		</div>
	</div>
	
	<div class="text-center">
		<input class="btn btn-medium btn-primary" type="submit" value="Enregistrer les modifications">
	</div>
</form>
<script>
	$('select.avatar_selector option[value=""]').attr('data-src-alt', "<?php echo get_avatar(['avatar' => '', 'email' => $user_info['email']], true); ?>");
	$('select.avatar_selector option[value="ingame"]').attr('data-src-alt', "<?php echo get_avatar(['avatar' => 'ingame', 'email' => $user_info['email'], 'ingame' => $user_info['ingame']], true); ?>");
	$('select.avatar_selector')
		.after('<select style="float: left;width: 200px;" class="form-control" id="cat_only_selectbox"></select>')
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