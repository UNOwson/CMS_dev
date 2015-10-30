<?php has_permission('admin.', true);

if (isset($_POST['cat_id']))
{
	$cat_id = (int)$_POST['cat_id'];
}

if (isset($_POST['header_change'])) {
	Site('forums.name', $_POST['forums_name']);
	Site('forums.description', $_POST['forums_description']);
	$_success = 'Changement effectués!';
}

if (_POST('new_categorie', '') !== '')
{
	Db::Insert('forums_cat', array('name' => $_POST['categorie_name'], 'priority' => 0));
	$_success = 'Catégorie ajoutée !';
}
elseif (isset($_POST['move_categorie'], $cat_id))
{
	$direction = $_POST['move_categorie'];
	$categories = Db::QueryAll('SELECT id, name, priority FROM {forums_cat} ORDER BY priority ASC', true);
	
	$i = 0;
	foreach($categories as $id => $categorie)
	{
		if ($id === $cat_id)
		{
			$categorie['priority'] = $i * 10 + ($direction * 11);
		}
		else
		{
			$categorie['priority'] = $i * 10;
		}
		
		Db::Exec('update {forums_cat} set priority = ? where id = ?', $categorie['priority'], $id);
		$i++;
	}
}
elseif (isset($_POST['edit_categorie'], $_POST['categorie_name'], $cat_id))
{
	Db::Exec('update {forums_cat} set name = ? where id = ?', $_POST['categorie_name'], $cat_id);
	$_success = 'Catégorie renommée !';
	unset($_POST['edit_categorie']);
}


if (isset($_POST['add_forum'], $_POST['name']) && _POST('name') !== '')
{
	if ($_POST['add_forum'] == 0)
	{
		$q = Db::Insert('forums', array(
					   'cat'         => $_POST['cat'], 
					   'priority'    => $_POST['priority'], 
					   'name'        => $_POST['name'], 
					   'description' => $_POST['description'], 
					   'icon'        => $_POST['icon'],
					   'redirect'    => $_POST['redirect']
				));
		
		if ($q)
		{
			$_POST['add_forum'] = Db::$insert_id;
			$_success = 'Forum ajouté !';
			log_event($user_session['id'], 'forum', 'Création du forum "' . $_POST['name'] . '"');
		} 
		else
		{
			$_warning = Db::$error;
		}
	}
	elseif (
		Db::Exec('UPDATE {forums} 
				  SET cat = ?, priority =?, name = ?, description = ?, icon = ?, redirect = ? WHERE id = ?', 
				  $_POST['cat'], 
				  $_POST['priority'], 
				  $_POST['name'], 
				  $_POST['description'], 
				  $_POST['icon'], 
				  $_POST['redirect'], 
				  $_POST['add_forum']
				  )
	)
		$_success = 'Forum mis à jour !';
	elseif (Db::$errno !== 0)
		$_warning = Db::$error;

	Db::Exec('delete from {permissions} where related_id = ? and name like "forum.%"', $_POST['add_forum']);
	
	$values = $args = array();
	
	if (isset($_POST['perms']['read']))
	{
		foreach($_POST['perms']['read'] as $group)
		{
			$values[] = '("forum.read", ?, ?, 1)';
			$args[] = $_POST['add_forum'];
			$args[] = $group;
		}
	}
	
	if (isset($_POST['perms']['write']))
	{
		foreach($_POST['perms']['write'] as $group)
		{
			$values[] = '("forum.write", ?, ?, 1)';
			$args[] = $_POST['add_forum'];
			$args[] = $group;
		}
	}
	
	if (isset($_POST['perms']['moderation']))
	{
		foreach($_POST['perms']['moderation'] as $group)
		{
			$values[] = '("forum.moderation", ?, ?, 1)';
			$args[] = $_POST['add_forum'];
			$args[] = $group;
		}
	}
	
	if ($values)
	{
		Db::Exec('replace into {permissions} (name, related_id, group_id, value) VALUES '.implode(',', $values), $args);
		isset($_success) or $_success = 'Forum mis à jour !';
	}
}
elseif (isset($_POST['del_forum']))
{
	if (Db::Exec('DELETE FROM {forums} WHERE id = ?', $_POST['del_forum']))
	{
		Db::Exec('delete from {permissions} where related_id = ?', $_POST['del_forum']);
		$_success = 'Élément supprimé!';
	}
	else
	{
		$_warning = Db::$error;
	}
}
elseif (isset($_POST['reorder_forums']))
{
	foreach($_POST['reorder_forums'] as $cat => $forums)
	{
		foreach($forums as $priority => $k)
		{
			if (!$k) continue;
			Db::Exec('update {forums} set priority = ? where id = ?', $priority, $k);
		}
	}
	$_success = 'Forum enregistré!';
}

$cur_elem = array('id' => '', 'cat' => 0, 'name' => '', 'icon' => '', 'perms' => array('read' => array(), 'write' => array(), 'moderation' => array()), 'description' => '', 'priority' => 0, 'redirect' => '');

$groups = Db::QueryAll('select id, color, name from {groups} order by priority asc, id desc', true);

$forums = Db::QueryAll('SELECT * FROM {forums} ORDER BY priority ASC, id ASC', true);
$categories = Db::QueryAll('SELECT id, name, priority FROM {forums_cat} ORDER BY priority ASC', true);
$perms = Db::QueryAll('select * from {permissions} where name like "forum.%"');

if ($forums)
{
	foreach($perms as $p)
	{
		if (!isset($forums[$p['related_id']])) // Some Cleanup
		{
			Db::Exec('delete from {permissions} where related_id = '.$p['related_id'].' and name like "forum.%"');
		}
		elseif ($p['value'])
			$forums[$p['related_id']][$p['name']][] = $p['group_id'];
	}

	foreach($forums as $forum)
	{
		if (!isset($categories[$forum['cat']]['forums']))
			$categories[$forum['cat']]['forums'] = array();
		
		$categories[$forum['cat']]['forums'][] = $forum;

		if (isset($_POST['edit_forum']) && $_POST['edit_forum'] == $forum['id'])
			$cur_elem = $forum;
	}
}



if (_POST('delete_categorie', '') !== '')
{
	if (isset($categories[$cat_id]['forums']))
	{
		$_warning = 'Vous ne pouvez supprimer une catégorie contenant des forums.';
	}
	else
	{
		Db::Exec('delete from {forums_cat} where id = ?', $cat_id);
		$_success = 'Catégorie supprimée !';
		unset($categories[$cat_id]);
	}
}

?>



<legend>Éditeur de forums</legend>

<div class="panel panel-default">
	<div class="panel-heading">Entête du forum</div>
	<div class="panel-body">
	<form class="form-horizontal" role="form" method="post">
	  <div class="form-group">
		<label class="col-sm-4 control-label">Titre du forum</label>
		<div class="col-sm-5">
		  <input type="text" class="form-control" name="forums_name" placeholder="<?=html_encode(Site('name'))?>" value="<?=html_encode(_POST('forums_name', Site('forums.name')))?>">
		</div>
	  </div>
	  <div class="form-group">
		<label class="col-sm-4 control-label">Description du forum</label>
		<div class="col-sm-5">
		  <input type="text" class="form-control" name="forums_description" placeholder="<?=html_encode(Site('description'))?>" value="<?=html_encode(_POST('forums_description', Site('forums.description')))?>">
		  <small>Vous pouvez utiliser du html ici.</small>
		</div>
	  </div>
	  <div class="text-center">
		<button type="submit" name="header_change" value="1" class="btn btn-primary">Enregistrer</button>
		</div>
	</form>
	</div>
</div>

<?php if (!$categories): ?>
	<div class="text-center alert alert-warning">Aucun élément trouvé!</div>
<?php else: ?>
<div class="panel-group" id="accordion">
	<?php
	$collapse = $cur_elem['id'] ? '' : 'in';

	if (!_POST('edit_categorie'))
	foreach($categories as $id => $c)
	{
		$cat_select[$c['id']] = $c['name'];

		echo '<form method="post">
				<input type="hidden" name="cat_id" value="'.$id.'">
				<div class="panel panel-default" style="margin-top:10px">
					<div class="panel-heading"><a data-toggle="collapse" data-parent="#accordion" href="#cat'.$id.'">' . $c['name'] . '</a>
						<div class="btn-group pull-right">
							<button name="move_categorie" value="-1" class="btn btn-xs btn-info">↑</button>
							<button name="move_categorie" value="1" class="btn btn-xs btn-info">↓</button>
							<button name="edit_categorie" value="1" class="btn btn-xs btn-info">Renommer</button>
							<button name="delete_categorie" value="1" class="btn btn-xs btn-danger">Supprimer</button>
						</div>
					</div>
					<div class="panel-collapse collapse '.$collapse.'" id="cat'.$id.'">
						<div class="panel-body">
						<table class="table sortable" id="reorder_forums['.$id.']" style="width:100%">
							<tbody>';
		
		if (isset($c['forums']) && $c['forums'])
		foreach ($c['forums'] as $forum)
		{
			echo '<tr id="' . $forum['id'] . '">';
				echo '<td><a href="'.create_url('forums', $forum['id']).'">'. html_encode($forum['name']) . '</a><br>'.
					  ($forum['redirect'] ? '<em>Redirection: <strong>'.$forum['redirect'].'</strong></em><br>':'').'
					  <small>'. $forum['description'] .'</small><br>
					  </td>';
				echo '<td><i class="fa fa-'.$forum['icon'].'"></i></td><td></td>';
				echo '<td style="min-width:40%"><small>Lecture: ';

				if (!isset($forum['forum.read']))
					echo '<strong>Personne</strong>';
				elseif (!array_diff(array_keys($groups), $forum['forum.read']))
					echo '<strong>Tout le monde</strong>';
				else foreach($forum['forum.read'] as $group)
					if (isset($groups[$group]))
						echo '<i><span style="color:' . $groups[$group]['color'] . '">' . $groups[$group]['name'] . '</span></i> ';
						
				echo '<br>Écriture: ';

				if (!isset($forum['forum.write']))
					echo '<strong>Personne</strong>';
				elseif (!array_diff(array_keys($groups), $forum['forum.write']))
					echo '<strong>Tout le monde</strong>';
				else foreach($forum['forum.write'] as $group)
					if (isset($groups[$group]))
						echo '<i><span style="color:' . $groups[$group]['color'] . '">' . $groups[$group]['name'] . '</span></i> ';
						
				echo '<br>Modération: ';

				if (!isset($forum['forum.moderation']))
					echo '<strong>Modérateur globaux seulement</strong>';
				elseif (!array_diff(array_keys($groups), $forum['forum.moderation']))
					echo '<strong>Tout le monde</strong>';
				else foreach($forum['forum.moderation'] as $group)
					if (isset($groups[$group]))
						echo '<i><span style="color:' . $groups[$group]['color'] . '">' . $groups[$group]['name'] . '</span></i> ';
						
				echo '</small></td>';
				echo '<td style="width:75px;">'.
						'<button name="edit_forum" value="'.$forum['id'].'" class="btn btn-xs btn-primary" title="Éditer cet élément"><i class="fa fa-pencil"></i></button> '.
						'<button name="del_forum" value="'.$forum['id'].'" class="btn btn-xs btn-danger" title="Supprimer cet élément" onclick="return confirm(\'Sur?\');"><i class="fa fa-eraser"></i></button>'.
					 '</td>';

			echo '</tr>';
		}
		echo '</tbody></table></div></div></div></form>';

	}
	?>
	<?php endif; ?>
	<br <?php if (!isset($_success) && !isset($_warning)) echo 'id="edit"'; ?>>
	<?php if ($categories && !_POST('edit_categorie')): ?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<?php
				if ($cur_elem['id'])
					echo 'Modifier le forum #'.$cur_elem['id'];
				else
					echo 'Ajouter un forum';
			?>
		</div>
		<div class="panel-body">
		
		
		
	<form class="form-horizontal" method="post" action="#">
		<div class="form-group">
			<label class="col-sm-3 control-label" for="name">Nom :</label>
			<div class="col-sm-8 controls">
				<input class="form-control" name="name" type="text" value="<?php echo html_encode($cur_elem['name'])?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="description">Description :</label>
			<div class="col-sm-8 controls">
				<input class="form-control" name="description" type="text" value="<?php echo html_encode($cur_elem['description'])?>">
				<small>Vous pouvez utiliser du html ici.</small>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="redirect">Redirection :</label>
			<div class="col-sm-8 controls">
				<input class="form-control" name="redirect" type="text" placeholder="Exemple: https://google.ca" value="<?php echo html_encode($cur_elem['redirect'])?>">
				<small>Afficher un lien externe dans la liste de forums. <strong>Le forum ne sera plus accessible</strong>.</small>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="permission">Accès :</label>
			<div class="col-sm-2">
				<strong>Lecture</strong>
				<?php
					echo '<select class="form-control" size="'.count($groups).'" name="perms[read][]" multiple>';
					foreach($groups as $group) {
						echo '<option style="color:'.$group['color'].';" value="'.$group['id'].'" '.(in_array($group['id'], $cur_elem['forum.read']) || ($group['id'] != 0 && !$cur_elem['id']) ?'selected="selected"':'').'>'.
								html_encode($group['name']).'</option>';
					}
					echo '</select>';
				?>	
			</div>
			<div class="col-sm-2">
				<strong>Écriture</strong>
				<?php
					echo '<select class="form-control" size="'.count($groups).'" name="perms[write][]" multiple>';
					foreach($groups as $group) {
						echo '<option style="color:'.$group['color'].';" value="'.$group['id'].'" '.(in_array($group['id'], $cur_elem['forum.write']) || ($group['id'] != 4 && !$cur_elem['id']) ?'selected="selected"':'').'>'.
								html_encode($group['name']).'</option>';
					}
					echo '</select>';
				?>	
			</div>
			<div class="col-sm-2">
				<strong>Modération</strong>
				<?php
					echo '<select class="form-control" size="'.count($groups).'" name="perms[moderation][]" multiple>';
					foreach($groups as $group) {
						echo '<option style="color:'.$group['color'].';" value="'.$group['id'].'" '.(in_array($group['id'], $cur_elem['forum.moderation']) ?'selected="selected"':'').'>'.
								html_encode($group['name']).'</option>';
					}
					echo '</select>';
				?>	
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="icon">Icône :</label>
			<div class="col-sm-8 controls fa2">
			<?php
			if (
				preg_match_all('#\.fa-(?<label>[-a-z]+):before\s?+{\s?+content:\s?+"\\\(?<icon>[0-9a-z]+)"#msU', 
							   file_get_contents(ROOT_DIR.'/assets/css/font-awesome.min.css'), 
							   $m)
			) {
				$m['icon'][] = '';
				$m['label'][] = '';
				echo html_select('icon', array_combine($m['label'], array_map(function($e, $l) {return $e ? $l . ' &#x' . $e . ';' : $l;}, $m['icon'], $m['label'])), $cur_elem['icon'], false); 
			}
			?>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="account">Catégorie :</label>
			<div class="col-sm-8 controls"><span class="fa2">
				<?php echo html_select('cat', $cat_select, $cur_elem['cat'], false); ?>
			</span></div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="account">Ordre :</label>
			<div class="col-sm-8 controls">
				<?php echo html_select('priority', array_keys(array_fill(0, 100, '')), $cur_elem['priority']); ?>
			</div>
		</div>
		<div class="text-center">
			<button class="btn btn-medium btn-primary" name="add_forum" value="<?php echo $cur_elem['id']?>" type="submit">Enregistrer le forum</button>
			<button class="btn btn-danger">Annuler</button>
		</div>
	</form>

		</div>
	</div>
</div>
<?php endif; ?>



<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title"><?php echo $e = _POST('edit_categorie') ? 'Renommer la catégorie' : 'Créer une catégorie' ?></h3>
	</div>
	<div class="panel-body">
	<form class="form-horizontal" role="form" style="margin-bottom: -13px;" method="post">
	  <div class="form-group">
		<label class="col-sm-4 control-label">Nom de la catégorie</label>
		<div class="col-sm-5">
		  <input type="text" class="form-control" name="categorie_name" value="<?php if (_POST('edit_categorie')) echo html_encode($categories[$cat_id]['name']); ?>">
		  <small>Vous pouvez utiliser du html ici.</small>
		</div>
	<?php if (_POST('edit_categorie')): ?>
		<input type="hidden" value="<?=$cat_id?>" name="cat_id">
		<button type="submit" name="edit_categorie" value="<?php echo _POST('edit_categorie'); ?>" class="btn btn-success" style="margin-top: 2px;">Renommer la catégorie</button>
		<button type="submit" name="cancel" value="" class="btn btn-danger" style="margin-top: 2px;">Annuler</button>
	<?php else: ?>
		<button type="submit" name="new_categorie" value="1" class="btn btn-success" style="margin-top: 2px;">Créer la catégorie</button>
	<?php endif; ?>
	  </div>
	</form>
	</div>
</div>