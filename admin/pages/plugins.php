<?php has_permission('admin.change_website', true);

if (_POST('pluginlist')) {
	Site('plugins', isset($_POST['plugins']) ? $_POST['plugins']: array());
	$_success = 'Changement enregistrés';
}

$enabled = Site('plugins');
$plugins = array();
$current_plugin = null;

foreach(glob(ROOT_DIR . '/plugins/*/main.php') as $plugin) {
	$plugin_name = basename(dirname($plugin));
	$class = 'Plugins\\'.$plugin_name;
	include_once $plugin;
	if (!class_exists($class, false)) continue;
	$plugins[$plugin_name] = $class;
}

$p =_GET('plugin');

if (isset($plugins[$p]) && $plugins[$p]::settings()) {
	$current_plugin = $plugins[_GET('plugin')];
	if ($_POST) {
		if (settings_save($current_plugin::settings(), $_POST)) {
			$_success = 'Configuration mise à jour!';
		}
	}
}

?>
<legend>Modules additionnels <?php if ($current_plugin) echo ':: Configuration de '.html_encode($current_plugin::NAME) ?></legend>

<?php 
if ($current_plugin) { 
	echo settings_form($current_plugin::settings(), true, false);
	return;
}
?>
<form method="post">
	<input type="hidden" name="pluginlist" value="1">
	<table class="table table-striped">
		<thead>
			<tr>
				<th></th>
				<th>Nom du plugin</th>
				<th>Description</th>
				<th>Auteur</th>
				<th>Version</th>
				<th>Option</th>
			</tr>
		</thead>
		<tbody>
		<?php
			foreach($plugins as $plugin => $class) {
				echo '<tr><td><input type="checkbox" name="plugins[]" value="' . html_encode($plugin) . '"' .
						(in_array($plugin, $enabled) ? 'checked' : '').'></td>
						<td>' . html_encode($class::NAME) . '</td>
						<td>' . html_encode($class::DESCRIPTION) . '</td>
						<td>' . html_encode($class::AUTHOR) . '</td>
						<td>' . html_encode($class::VERSION) . '</td>
						<td>';
						
						if ($class::settings()) {
							echo '<a class="btn btn-default btn-sm" href="?page=plugins&plugin='.$plugin.'"><i class="fa fa-cog"></i> Settings</a>';
						} else {
							echo '';
						}
						echo '</td></tr>';
			}
		?>
		</tbody>
	</table>
	<br>
	<div class="text-center">
		<input type="submit" value="Activer les modules sélectionnées" class="btn btn-success ">
	</div>
</form>