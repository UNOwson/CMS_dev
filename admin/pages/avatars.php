<?php
	$dir = ROOT_DIR . '/upload/avatar/';
	
	if (isset($_POST['categorie']) && !preg_match('#^[-a-zA-Z0-9_]+$#', $cat = $_POST['categorie']))
		{
			$_warning = 'Le nom de dossier contient des caractères interdits ou est vide '.$cat;
		}
	elseif (isset($_POST['create'])) //SI le formulaire est envoyé on éxécute le code
		{
			if (mkdir($dir . $cat, 0744, true))
				{
					@touch($dir . $cat . '/index.html');
					$_success = 'Dossier '.$cat.' est créé avec succès';
				}else{
					$_warning = 'Une erreur est survenu durant la création du dossier '.$cat;
				}
		}
	elseif (isset($_POST['delete'])) //SI le formulaire est envoyé on éxécute le code
		{	
			if (rrmdir($dir . $cat))
			{
				$_success = 'Le dossier '.$cat.' a bien été supprimé';
			}else{
				$_warning = 'Une erreur est survenu durant la suppression du dossier '.$cat;
			}
		}
	elseif(isset($_FILES['upload']))
		{
			foreach(multi_upload_array() as $file) {
				$filename = safe_filename($file['name']);
				$path = $dir . $cat . '/' . $filename;
				
				if ($filename == '') {
					$_warning .= "Le nom du fichier {$file['name']} après conversion est vide!";
				}
				elseif (!preg_match('/\.(jpg|gif|png)$/', $filename) || !in_array(@getimagesize($file['tmp_name'])[2], [1, 2, 3])) {
					$_warning .= "Le format de {$file['name']} n'est pas supporté\n";
				}
				elseif(file_exists($path)) {
					$_warning .= "Le fichier {$path} existe déjà!\n";
				}
				elseif (move_uploaded_file($file['tmp_name'], $path)) {
					$_success .= "Avatar {$file['name']} ajouté!\n";
				}
				else {
					$_warning .= "Erreur d'upload pour {$file['name']}!\n";
				}
			}
	}
?>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Créer une catégorie d'avatars</h3>
	</div>
	<div class="panel-body">
	<form class="form-horizontal" role="form" style="margin-bottom: -13px;" method="post">
	  <div class="form-group">
		<label class="col-sm-3 control-label">Nom de la catégorie</label>
		<div class="col-sm-6">
		  <input type="text" class="form-control" name="categorie">
		</div>
	  <button type="submit" class="btn btn-success" style="margin-top: 2px;" name="create" value="1">Créer la catégorie</button>
	  </div>
	</form>
	</div>
</div> 

<?php
	if ($files = glob($dir.'/*', GLOB_ONLYDIR))
	foreach($files as $cat_dir) {
		$cat = basename($cat_dir);
		
		echo '<div class="panel panel-default" style="margin-top:10px">';
		echo '<div class="panel-heading" style="height: 40px;">';
			echo '<h3 class="panel-title" style="float:left">Catégorie : '.$cat.'</h3>';
			echo '<form method="post" enctype="multipart/form-data"><input type="hidden" name="categorie" value="' . $cat . '">';
				echo '<button class="btn btn-danger" style="position:relative;top:-5px;float:right" onclick="return confirm(\'Les fichiers seront supprimés. Continuer?\');" name="delete">Supprimer</button>';
				echo '<input type="file" class="pull-right" name="upload[]" multiple>';
			echo '</form>';
		echo '</div>';
		
		
		if ($avatars = glob($cat_dir.'/*.{jpg,gif,png,bmp,svg,ico}', GLOB_BRACE)) //Si il y a des images dans le dossier on le parcours
			{
				echo '<ul class="clearfix">';
				foreach ($avatars as $avatar) {
					$url = get_asset(substr($avatar, strlen(ROOT_DIR)));
					echo '<div style="padding:10px;float:left;display:block">';
						echo '<img style="border-radius:5px;margin-right:5px;margin-top:10px" width="64" src="'.$url.'">';
					echo '</div>';
				}
				echo '</ul>';
			}
		echo '</div>';
	}
?>

<script>
$('input[type=file]').on('change', function() {
	if ($(this)[0].files[0]) $(this).parent().submit();
});
</script>