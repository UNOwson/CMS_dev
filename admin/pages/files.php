<?php
has_permission('mod.', true);

if (isset($_POST['delete'])) {
	if ($file = Db::Get('select path, thumbs from {files} where id = ?', $_POST['delete'])) {
		Db::Exec('delete from {files} where id = ?', $_POST['delete']);
		@unlink(ROOT_DIR.'/'.$file['path']);
		if ($file['thumbs']) {
			foreach (unserialize($file['thumbs']) as $thumb) {
				@unlink(ROOT_DIR.'/'.$thumb);
			}
			$_success = 'Fichier supprimé !';
		} else {
			$_warning = 'Erreur lors de la suppression.';
		}
	}
}

if (isset($_FILES['up'])) {
	$_warning = $_success = '';
	foreach(multi_upload_array() as $file) {
		if (upload_fichier($file, null, null)) {
			$_success .= 'Fichier ' . $file['name'] . ' enregistré !<br>';
		} else {
			$_warning .= 'Erreur lors de l\'upload de ' . $file['name'] . '.<br>';
		}
	}
}

$files = Db::QueryAll('select f.*, u.username from {files} as f join {users} as u on u.id = f.poster order by f.id desc', true);
?>
<a href="?page=gallery" class="btn btn-primary pull-right"><i class="fa fa-image"></i> Gallery</a>
<form method="post" enctype="multipart/form-data">
	<input type="file" id="up" name="up[]" style="display:inline;"  multiple>
	<input type="submit" value="Envoyer" class="btn btn-xs btn-primary">
</form>
<script>
$('#up').on('change', function() {
	if ($(this)[0].files[0]) $(this).parent().submit();
});
</script>
<br>
<?php if (!$files) { ?>
	<br><div style="text-align: center;" class="alert alert-warning">Il n'y a aucun fichier.</div>
<?php } else { ?>
<form method="post">
<table class="table table-lists">
	<tbody>
<?php

foreach($files as $file) {
	echo '<tr>';
		echo '<td>'.html_encode($file['id']).'</td>';
		echo '<td><a href="'.site('url').html_encode($file['path']).'">'.html_encode(short($file['name'],45)).'</a></td>';
		echo '<td>'.html_encode($file['type']).'</td>';
		echo '<td>'.html_encode($file['hits']).'</td>';
		echo '<td>'.mk_human_unit($file['size']).'</td>';
		echo '<td>'.html_encode($file['username']).'</td>';
		echo '<td><button onclick="return confirm(\'Sur?\');" name="delete" value="'.$file['id'].'" class="btn btn-xs btn-danger"><i class="fa fa-times"></i></button></td>';
	echo '</tr>';
}
?>
	</tbody>
</table>
</form>
<?php } ?>