<?php has_permission('mod.ban_member', true);
	
	$types = array(
		'username' => 'Username',
		'email' => 'Email',
		'ip' => 'IP',
		'country' => 'Pays',
	);
	
	if (!empty($_POST['rule']) || !empty($_POST['country'])) {
		$_POST['rule'] = $_POST['rule'] ?: $_POST['country'];
		
		Db::Insert('{banlist}', array(
			'type' => $_POST['type'],
			'rule' => str_replace(array('*', '_', '?'), array('%', '\_', '_'), $_POST['rule']),
			'reason' => $_POST['reason'],
			'created' => time(),
			'expires' => strtotime($_POST['expires'])
		));
		
		$uid = $_POST['type'] == 'username' ? (int)Db::Get('select id from {users} where username  =?', $_POST['rule']) : 0;
		log_event($uid, 'admin', 'Nouveau banissement: '.$_POST['type'].' = '.$_POST['rule']);
		
		$_success = 'Règle ajoutée !';
	} elseif (isset($_POST['delete'])) {
		$rule = Db::Get('select * from {banlist} where id = ?', $_POST['delete']);
		$uid = $rule['type'] == 'username' ? (int)Db::Get('select id from {users} where username  = ?', $rule['rule']) : 0;
		
		Db::Exec('delete from {banlist} where id = ?', $_POST['delete']);
		log_event($uid, 'admin', 'Suppression d\'une règle de banissement: ' . $rule['type'] . ' = '. $rule['rule']);
		
		$_success = 'Règle supprimmée !';
	}
	
	$banlist = Db::QueryAll('select * from {banlist}');
?>
<div class="banlist">
	<legend><a onclick="$('#banlist').toggle('slow'); return false;" href="#">Banlist</a></legend>
	<?php if (!$banlist): ?>
		<div class="text-center alert alert-warning">Aucun élément trouvé!</div>
	<?php else: ?>
			<form method="post" <?php if (isset($_GET['hide'])) echo 'hidden'; ?> id="banlist" action="index.php?page=banlist">
				<table class="table">
					<thead>
						<tr>
							<th>Règle</th>
							<th>Raison</th>
							<th>Expiration</th>
							<th style="width:90px;"> </th>
						</tr>
					</thead>
					<?php
						foreach($banlist as $ban) {
							echo '<tr ' . ($ban['rule'] == _GET('username') || $ban['rule'] == _GET('ip') ? 'class="danger"' : '') .  '>' .
								'<td>' . $types[$ban['type']] . ' = <strong>'. html_encode(str_replace(array('%', '\_'), array('*', '_'), $ban['rule'])) . '</strong></td>'.
								'<td>' . html_encode($ban['reason']) . '</td>'.
								'<td>' . today($ban['expires']) . '</td>'.
								'<td><button class="btn btn-danger btn-xs" name="delete" value="'.$ban['id'].'"><i class="fa fa-times"></i></button></td>'.
								'</tr>';
						}
					?>
				</table>
			</form>
	<?php endif; ?>

	<br>
	<form class="form-horizontal" method="post" action="index.php?page=banlist">
		<legend>Ajouter un élément</legend>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="type">Type :</label>
			<div class="col-sm-8 controls">
				<?php echo html_select('type', $types); ?>
				<small>Utilisez le ban IP avec parcimonie, n'oubliez pas qu'une IP ne représente pas forcément un utilisateur.</small>
			</div>
		</div>
		<div class="form-group ban ban-username ban-email ban-ip">
			<label class="col-sm-3 control-label" for="rule">Règle :</label>
			<div class="col-sm-8 controls">
				<input class="form-control" data-autocomplete="userlist" name="rule" id="rule" type="text" <?php if (isset($_GET['username'])) echo 'value="' . html_encode($_GET['username']) .'" style="background-color:pink;"'; ?>>
				<small>Les wildcards * et % sont acceptés, la règle n'est pas sensible à la casse. Example: *@LiVe.Ca</small>
			</div>
		</div>
		<div class="form-group ban ban-country" hidden>
			<label class="col-sm-3 control-label">Pays :</label>
			<div class="col-sm-8">
				<?php echo html_select('country', $_countries); ?>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="reason">Raison :</label>
			<div class="col-sm-8 controls">
				<input class="form-control" name="reason" type="text" value="">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="expires">Expiration :</label>
			<div class="col-sm-8 controls">
				<input class="form-control" name="expires" type="text" value="+1 week">
				<small>Une valeur de 0 indique que la règle n'expire pas. Format: <a href="http://php.net/manual/fr/function.strtotime.php">strtotime</a>.</small>
			</div>
		</div>
		<div class="text-center">
			<button class="btn btn-primary" name="add_menu" value="" type="submit">Enregistrer</button>
		</div>
	</form>
</div>
<script>
$('#type').change(function(e){
	switch(this.value) {
		case 'ip':
			$('#rule').val('<?php echo addslashes(_GET('ip'))?>').removeAttr('data-autocomplete');
			break;
		case 'username':
			$('#rule').val('<?php echo addslashes(_GET('username'))?>').attr('data-autocomplete', 'userlist');
			break;
		case 'email':
			$('#rule').val('<?php echo addslashes(_GET('email'))?>').removeAttr('data-autocomplete');
			break;
	}
	
	$('.ban').hide().val('');
	$('.ban.ban-' + this.value).show();
});
</script>