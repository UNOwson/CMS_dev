<?php has_permission('admin.change_website', true);
	if (isset($_FILES['theme_file']) && is_uploaded_file($_FILES['theme_file']['tmp_name'])) { /* Importation de theme */
		if (!class_exists('ZipArchive')) {
			$_warning = 'Votre PHP n\'a pas l\'extension zip !';
		} else {
			$zip = new ZipArchive;
			if ($zip->open($_FILES['theme_file']['tmp_name']) === true) {
				$tmpdir = sys_get_temp_dir() . '/' . md5(rand(0, time()));
				$zip->extractTo($tmpdir);
				$zip->close();
				
				$glob = glob($tmpdir . '/*',  GLOB_ONLYDIR);
				
				if (!$glob || count($glob) != 1) {
					if (file_exists($tmpdir . '/index.php') && $theme = include $tmpdir . '/index.php') {
						$dir = ROOT_DIR . '/' . $theme['name'];
						$source = $tmpdir;
					}
				} else {
					$source = array_pop($glob);
					$dir = ROOT_DIR . '/themes/' . basename($source);
				}
				
				if (!isset($dir)) {
					$_warning = 'Ce theme est invalide, référez vous à la documentation ou importer le manuellement via ftp.';
				} else {
					rename($source, $dir);
					$_success = 'Theme importé. Vous pouvez maintenant l\'activer.';
				}
				
				rrmdir($tmpdir);
			} else {
				$_warning = 'Zip invalide !';
			}
		}
	}
	
	
	if ($values = $_POST) {
		
		$fields = array ('articles_per_page', 'theme', 'theme_changer', 'change_theme');
		if (isset($theme_settings['settings'])) {
			$fields = array_merge($fields, str_replace('theme_', 'theme.', array_keys($theme_settings['settings'])));
		}
		
		foreach($_FILES as $field => $file) {
			$field = str_replace('||', '.', $field);
			if (in_array($field, $fields)) {
				if ($upload = upload_fichier($file, null, null, true, 'settings/'.$field)) {
					$values[$field] = $upload[3];
				}
			}
		}
		foreach ($values as $field => $value) {
			$field = str_replace('theme||', 'theme.', $field);
			if (in_array($field, $fields) && $value != Site($field)) {
				if ($field === 'theme') { /* If we change theme we reset css settings */
					foreach($theme_settings['settings'] as $key => $reset) {
						if (isset($reset['css'])) {
							Site($key, null);
							unset($values[$key]);
						}
					}
				}
				Site($field, $value);
				Db::$affected_rows and log_event($user_session['id'], 'admin', 'Modification du paramètre: '.$field.'.') and $_success = 'Configuration mise à jour!';
			}
		}
		rrmdir(ROOT_DIR . '/cache/', true);
	}
	$theme_settings = include ROOT_DIR . '/themes/' . Site('theme') .  '/index.php';
?>
<form method="post" class="form-horizontal" enctype="multipart/form-data">
<legend>Préférences d'affichage</legend>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="theme">Thème :</label>
		<div class="col-sm-5">
				<?php
					echo html_select('theme', array_map(function($a) {return new htmlSelectGroup($a);}, get_themes()), Site('theme'));
				?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="theme"></label><input name="change_theme" value="0" type="hidden">
		<div class="col-sm-6"><label><input name="change_theme" value="1" type="checkbox"<?php if (Site('change_theme')) echo ' checked';?>> Permettre aux membres de changer de theme.</label>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="theme">Importer un thème :</label>
		<div class="col-sm-8">
			<input style="margin-top:7px" type="file" name="theme_file">
		</div>
	</div>
	<br>
	<div class="form-group">
		<label class="col-sm-4 control-label" for="articles_per_page">Articles par page :</label>
		<div class="col-sm-2">
			<input class="form-control" name="articles_per_page" id="articles_per_page" type="text" value="<?php echo Site('articles_per_page')?>">
		</div>
	</div>
<br>
<legend>Préférences du thème</legend>
<?php
if (isset($theme_settings['settings'])) {
	echo settings_form($theme_settings['settings'], true);
}
?>
</form>
<script>
$(function() {
	$('.image_selector').each(function() {
		$.fn.image_selector($(this));
	}).hide();
	$('input, select, textarea').change(function(e) {
		var el = $('[name=' + this.name.replace('\.', '\\.') + ']').not(this);
		if ($(this).attr('type') == 'checkbox') {
			// el.attr('disabled', this.checked);
			if (this.checked) el.val('').keyup();
		} else if(this.value != '') {
			el.attr('checked', false);
		}
	});
});
</script>