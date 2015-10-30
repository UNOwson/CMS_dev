<?php
has_permission('admin.change_group', true);

if (isset($_POST['update_group'])) {
	if (false !== Db::Exec('update {groups} set `name` = ?, `color` = ? where id = ?', $_POST['group_name'], $_POST['color'], $_POST['update_group'])) {
		$values = array();
		$args = array();
		foreach($_privileges as $group => $perms) {
			foreach($perms as $k => $v) {
				if (is_array($v)) {
					foreach($v as $perm => $tag) {
						$values[] = isset($_POST['perms'][$group.'.'.$perm]) ? '(?,?,1)' : '(?,?,0)';
						$args[] = $group.'.'.$perm;
						$args[] = $_POST['update_group'];
					}
				}
			}
		}
		Db::Exec('replace into {permissions} (name, group_id, value) VALUES '.implode(', ', $values), $args);
		$_success = 'Modifications enregistrés!';
	}
	log_event(0, 'admin', 'Modification des permissions du groupe '.$_POST['group_name'].'.');
}
elseif (!empty($_POST['new_group_name'])) {
	Db::Insert('groups', array('name' => $_POST['new_group_name'])) && $_success = 'Groupe ajouté!';
	log_event(0, 'admin', 'Création d\'un groupe '.$_POST['new_group_name'].'.');
}
elseif (!empty($_POST['delete_group'])) {
	if ($_POST['delete_group'] == $user_session['group_id']) {
		$_warning = 'Vous ne pouvez pas supprimer un groupe auquel vous appartenez !';
	}
	elseif (Db::Get('select id from {groups} where id = ? AND internal is not null', $_POST['delete_group'])) {
		$_warning = 'Vous ne pouvez pas supprimer un groupe de base, sinon le CMS risque de mal fonctionner !';
	}
	elseif (Db::Exec('delete from {groups} where id = ? AND internal is null', $_POST['delete_group'])) {
		$new_group = Db::Get('select id from {groups} where id = ?', Site('default_user_group'));
		Db::Exec('update {users} set group_id = ? where group_id = ?', $new_group ?: 2, $_POST['delete_group']);
		Db::Exec('delete from {permissions} where group_id = ?', $_POST['delete_group']);
		$_success = 'Groupe supprimé!';
		log_event($user_session['id'], 'admin', 'Suppression d\'un groupe '.$_POST['group_name'].'.');
	} else 
		$_warning = 'Erreur lors de la suppression';
}
elseif (isset($_POST['reorder'])) {
	foreach($_POST['reorder'] as $priority => $k) {
		Db::Exec('update {groups} set priority = ? where id = ?', $priority, $k);
	}
	$_success = 'Menu enregistré!';
}

foreach(Db::QueryAll('select * from {groups} order by priority asc') as $group) {
	$groups[$group['id']] = $group;
	$groups[$group['id']]['permissions'] = group_permissions($group['id']);
}

$cur_id = isset($_GET['id'], $groups[$_GET['id']]) ? $_GET['id'] : key($groups);

?>
<div class="panel panel-default" style="margin-top:10px">
	<div class="panel-heading">
		<h3 class="panel-title">Créer un groupe</h3>
	</div>
	<div class="panel-body">
	<form class="form-horizontal" role="form" style="margin-bottom: -13px;" method="post">
	  <div class="form-group">
		<label class="col-sm-3 control-label">Nom du groupe</label>
		<div class="col-sm-6">
		  <input type="text" class="form-control" name="new_group_name">
		</div>
	  <button type="submit" class="btn btn-success" style="margin-top: 2px;">Créer le groupe</button>
	  </div>
	</form>
	</div>
</div>
<style>
legend {
	font-size: 17px;
	margin-bottom: 10px;
	margin-top: 10px;
}
</style>
<div class="panel panel-default" style="margin-top:10px">
	<div class="panel-heading">
		<span class="pull-right"><strong><?php echo $groups[$cur_id]['name']; ?></strong></span>
		<h3 class="panel-title">Gestion des permissions</h3>
	</div>
	<div class="panel-body">
	<form style="margin-bottom: 0px;" method="post">
		<div class="row">
		  <div class="col-md-9 col-md-push-3"  style="border-left:1px solid #ddd;">
			<ul class="nav nav-tabs">
			  <li class="active"><a href="#general" data-toggle="tab">Général</a></li>
				<?php
				foreach($_privileges as $id => $perms) {
					echo '<li class=""><a href="#perms-'.$id.'" data-toggle="tab">'.$perms['label'].'</a></li>';
				}
				?>
			</ul>
			<div class="tab-content">
				<div class="tab-pane fade active in" id="general">
					<legend>Configuration du groupe </legend>
					<div class="form-group" style="height: 30px;">
						<label class="col-sm-5 col_gm control-label">Nom du groupe  &nbsp; <small title="Nom interne"><?php echo $groups[$cur_id]['internal'] ? '('.$groups[$cur_id]['internal'].')': '' ?></small></label>
						<div class="col-sm-6">
							<input type="text" class="form-control" name="group_name" value="<?php echo $groups[$cur_id]['name']?>">
						</div>
					</div>
					<div class="form-group" style="height: 30px;">
						<label for="color" class="col-sm-5 col_gm control-label" >Couleur du groupe</label>
						<div class="col-sm-6" style="margin-top:4px">
							<select class="form-control" name="color" style="background-color:<?php echo $groups[$cur_id]['color']?>;color:white;" onkeyup="$(this).css('background-color', $(this).val());" onclick="$(this).css('background-color', $(this).val());">
							<?php
								foreach($_couleurs as $couleur => $hex) {
									echo '<option '. ($hex == $groups[$cur_id]['color'] ? 'selected="selected"' : '').
										  ' value="'.$hex.'" style="color:white; background-color:'.$hex.';">'.$couleur.'</option>';
								}
								?>
							</select>
						</div>
					</div>
					<br>
					
					<input type="submit" name="update_group" value="<?php echo $cur_id?>" hidden>
					
					<legend>Suppression du groupe</legend>
					<?php if ($groups[$cur_id]['internal']) { ?>
						<em>Ceci est un groupe système, il ne peut être supprimé.</em>
					<?php } else { ?>
					<div class="form-group clearfix">
						<label class="col-sm-5 col_gm control-label">Supprimer ce groupe</label>
						<div class="col-sm-4">
							<button type="submit" name="delete_group" class="btn btn-danger" onclick="return confirm('Sur?');" value="<?php echo $cur_id?>">Supprimer</button>
						</div>
					</div>
					<?php } ?>
				</div>
				<?php
					foreach($_privileges as $id => $perms) {
						echo '<div class="tab-pane fade" id="perms-'.$id.'">';
						echo '<label class="pull-right">Cocher tout <input type="checkbox" class="check-all" data-group="'.$id.'"></label>';
								foreach($perms as $title => $permissions) {
								if (is_array($permissions)) {
									echo '<legend>'.$title.'</legend>';
									foreach($permissions as $pname => $ptag) {
										echo '<div class="checkbox">
													<label><input type="checkbox" data-group="'.$id.'" autocomplete="off" name="perms['.$id.'.'.$pname.']" '.(!empty($groups[$cur_id]['permissions'][$id.'.'.$pname]) ? 'checked="checked"' : '').' value="1">'.$ptag.'</label>
												</div>';
									}
								}
							}
						echo '</div>';
					}	
				?>
				<script>
					$('.check-all').click(function() {
						var g = $(this).attr('data-group');
						$('[data-group='+g+']').prop('checked', this.checked);
					})
					.prop('indeterminate', true);
				</script>
				
				<div class="form-group">
					<div class="col-sm-offset-4 col-sm-0">
						<button type="submit" name="update_group" value="<?php echo $cur_id?>" class="btn btn-success">Enregistrer les modifications</button>
					</div>
				</div>  
			</div>
			</div>
			
			<div class="col-md-3 col-md-pull-9">
			<table id="reorder" class="sortable" cellspacing="0" cellpadding="2" style="width:100%;">
			<?php
				foreach($groups as $id => $group) {
					if ($cur_id == $id) {
							echo '<tr id="'.$group['id'].'"><td style="color:'.$group['color'].'"><strong>'.$group['name'].'</strong></td><td></td><td></td></tr>';
					} else {
							echo '<tr id="'.$group['id'].'"><td><a href="index.php?page=groups&id='.$id.'" style="';
							if ($group['internal'] == 0) echo 'font-style: italic;';
							echo 'color:'.$group['color'].';">'.$group['name'].'</a></td><td></td><td></td></tr>';
					}
				}
			?>
			</table>
			<br>
			<br>
			<br>
			<em><small>Les groupes doivent être triés par grade (du plus puissant au moins puissant)</small></em>
		  </div>
		</div>
	</form>
	</div>
</div>