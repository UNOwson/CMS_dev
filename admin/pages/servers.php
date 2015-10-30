<?php has_permission('admin.change_serv', true);

$fields = ['id', 'type', 'name', 'host', 'port', 'rcon_port', 'rcon_password'];

$server_types = [
	'minecraft' => 'Minecraft',
	'diablo3' => 'Diablo 3',
	'wow' => 'World Of Warcraft',
	'trackmania' => 'Trackmania Nation',
	'source' => 'Source Engine',
	'quake3' => 'Quake 3',
	'' => '--------',
	'shoutcast' => 'SHOUTcast',
];


$cur_serv = array_fill_keys($fields, '');
$inserts = array_intersect_key($_POST, $cur_serv);

if (count($inserts) === count($fields) && isset($server_types[$_POST['type']])) {
	$match = Db::Get('select id from {servers} where host = ? and port = ?', $_POST['host'], $_POST['port']);
	
	if (!$inserts['id']) {
		unset($inserts['id']);
	}
	
	if ($_POST['rcon_port'] > 65535 || $_POST['port'] > 65535 || (int)$_POST['rcon_port'] < 0 || (int)$_POST['port'] < 0)
		$_warning = 'Votre port est invalide!';
	elseif ($match && $match != $inserts['id'])
		$_warning = 'Attention, un autre serveur utilise cette combinaison Hôte:Port !';
	elseif (!isset($inserts['id']) && Db::Insert('servers', $inserts) == 1)
		$_success = 'Serveur ajouté!';
	elseif (Db::Insert('servers', $inserts, true) !== false)
		$_success = 'Serveur mis à jour!';
	else
		$_warning = Db::$error;
}
elseif (isset($_POST['del_serv'])) {
	if (Db::Exec('DELETE FROM {servers} WHERE id = ?', $_POST['del_serv'])) {
		$_success = 'Serveur supprimé!';
	} else {
		$_warning = 'Aucun serveur supprimé!';
	}
}
$servers = Db::QueryAll('select * FROM {servers} ORDER BY name ASC', true);

if (isset($servers[_POST('edit_serv', _POST('id'))])) {
	$cur_serv = $servers[_POST('edit_serv', _POST('id'))];
}
?>
<legend>Liste des serveurs</legend>
<form method="post" action="#edit">
<?php if (!$servers): ?>
	<div style="text-align: center;" class="alert alert-warning">Aucun serveur trouvé!</div>
<?php else: ?>
	<table class="table">
		<thead>
			<tr>
				<th></th>
				<th>Nom</th>
				<th>Type</th>
				<th>Ip</th>
				<th>Port</th>
				<th>RCON Port</th>
				<th>RCON Password</th>
				<th> </th>
			</tr>
		</thead>
		<tbody>
		<?php
			foreach ($servers as $serv)
			{
				echo "<tr>";
					echo '<td><img src="'. get_asset('/img/servers/'.$serv['type'].'.png'). '" width="28" title="'.$server_types[$serv['type']].'" /></td>';
					echo '<td>'.$serv['name'].'</td>';
					echo '<td>'.$server_types[$serv['type']].'</td>';
					echo '<td>'.$serv['host'].'</td>';
					echo '<td>'.$serv['port'].'</td>';
					echo '<td>'.$serv['rcon_port'].'</td>';
					echo '<td>'.$serv['rcon_password'].'</td>';
					echo '<td>
						<a href="?page=rcon&server='.$serv['id'].'" class="btn btn-xs btn-info" title="RCon"><i class="fa fa-terminal"></i></a>
						<button name="edit_serv" value="'.$serv['id'].'" class="btn btn-xs btn-primary" title="Éditer ce serveur"><i class="fa fa-pencil"></i></button>
						<button name="del_serv" value="'.$serv['id'].'" class="btn btn-xs btn-danger" title="Supprimer ce serveur" onclick="return confirm(\'Sur?\');"><i class="fa fa-eraser"></i>
						</button>';
					echo '</td>';
				echo "</tr>";
			}
		?>
		</tbody>
	</table>
<?php endif; ?>
</form>
</br>
<form class="form-horizontal" method="post" id="edit" action="#">
<?php
	if ($cur_serv['id'])
		echo '<legend>Modifier le serveur #'.$cur_serv['id'].'</legend>';
	else
		echo '<legend>Ajouter un serveur</legend>';
?>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Nom :</label>
		<div class="col-sm-8 controls">
				<input class="form-control" name="name" type="text" value="<?php echo html_encode($cur_serv['name'])?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Type :</label>
		<div class="col-sm-8 controls">
			<?php echo html_select('type', $server_types, $cur_serv['type']); ?>  
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Adresse IP :</label>
		<div class="col-sm-8 controls">
			<input class="form-control" name="host" type="text" value="<?php echo html_encode($cur_serv['host'])?>">
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-3 control-label" for="port">Port :</label>
		<div class="col-sm-8 controls">
			<input class="form-control" name="port" type="text" value="<?php echo $cur_serv['port']?>">
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-3 control-label">RCON Port :</label>
		<div class="col-sm-8 controls">
			<input class="form-control" name="rcon_port" type="text" autocomplete="off" value="<?php echo $cur_serv['rcon_port']?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">RCON Password:</label>
		<div class="col-sm-8 controls">
			<input class="form-control" name="rcon_password" type="text" autocomplete="off" value="<?php echo $cur_serv['rcon_password']?>">
		</div>
	</div>
	<div class="text-center">
		<button class="btn btn-medium btn-primary" name="id" value="<?php echo $cur_serv ? $cur_serv['id'] : 0?>" type="submit">Enregistrer le serveur</button> <button class="btn btn-danger">Annuler</button>
	</div>
</form>
