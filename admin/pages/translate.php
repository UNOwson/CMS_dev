<?php
use Translation\Lang;
use Translation\Translator;

if (sha1($user_session['email']) !== '61ad42d42c3388168221f96735244fbcb513fbdb') {
	$_notice = 'Interface non fonctionnelle.';
	return;
}

$catalogues = lang::getAllDictionaries(true);
$locales = lang::getLocales(true);
$all_keys = [];

foreach($catalogues as $locale => $catalogue) {
	$all_keys = array_merge_recursive($all_keys, $catalogue);
}

foreach($all_keys as $catalogue => $messages) {
	echo '<h2>' . $catalogue . '</h2>';
?>
<table class="table table-list">
<thead>
	<tr>
		<th style="width:250px;">Clé</th>
		<?php 
			foreach($locales as $locale) {
				echo '<th>Traduction <select><option>' . $locale . '</option></select><img src="/assets/img/flags/us.png"></th>';
			}
		?>
	</tr>
</thead>
<?php
foreach(array_keys($messages) as $key) {
	$t= rand(0, 33) > 15 ? '<small style="color:gray">/test/test.php #'.rand(3, 17).'</small>' : '';
	
	echo '<tr>
			<td>
				<input type="text" class="form-control" value="' . $key . '" style="border: 1px solid #dedede;">
				<small style="color:gray">/test/translate.php #' . rand(3, 17) . '</small><br>
				' . $t . '
			</td>';

	foreach($catalogues as $messages_) {
		echo '<td><textarea class="form-control">'.@$messages_[$catalogue][$key].'</textarea></td>';
	}
	echo '</tr>';
}
?>
</table>
<?php } ?>
<div class="text-center">
	<button class="btn btn-primary">Trouver traductions manquantes</button> 
	<button class="btn btn-primary">+ Ajouter une traduction</button> 
	<button class="btn btn-primary">+ Ajouter une langue</button>
</div>
<br>
<div class="text-center">
	<button class="btn btn-success">Enregistrer</button> 
</div>
