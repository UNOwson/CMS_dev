<?php  has_permission('admin.broadcast', true);
set_time_limit(0);

$sujet   = _POST('sujet');
$message = _POST('message');
$groups  = _POST('groups');
$cycle   = _POST('cycle');

if (!empty($_POST)) {
	if (e_empty($sujet, $message, $groups) || $cycle < 1) {
		$_warning = 'Un des champs est vide.';
	} else {
		$i = 0;
		$groups = array_map('intval', $groups);

		if ($groups === array(0)) {
			$users = Db::QueryAll('select username, email from {users} where newsletter = 1');
		} else {
			$users = Db::QueryAll('select username, email from {users} where group_id in ('.implode(',', $groups).')');
		}
		
		Db::Insert('newsletter', array(
				'author' 			=> $user_session['id'],
				'date_sent' 		=> time(),
				'groups' 			=> implode(',', $groups),
				'subject' 			=> $sujet,
				'message' 			=> $message,
		));
		
		$mail_id = Db::$insert_id;
		$mail_failed = $mail_sent = 0;
		
		log_event(0, 'admin', 'Envoi de la newsletter #'.$mail_id.': '.$sujet);
		
		foreach($users as $user) {
			//if ($i++ == $cycle) {
				//we stop and refresg the page
			//} else {
				if (sendmail($user['email'], $sujet, str_replace('<username>', $user['username'], $message))) {
					$_notice .= 'Mail envoyé à '.$user['username'].' &lt;'.$user['email'].'&gt;<br>';
					$mail_sent++;
				} else {
					$_warning .= 'Mail non envoyé à '.$user['username'].' &lt;'.$user['email'].'&gt;<br>';
					$mail_failed++;
				}
			//}
		}
		
		$_success = "Envoi completé!\nNewsletter envoyée à ".plural('membre|membres', $mail_sent, true);
		
		Db::Exec('update {newsletter} set mail_sent = ?, mail_failed = ? where id = ?', $mail_sent, $mail_failed, $mail_id);
	}
}


$news_cnt = Db::Get('select count(*) from {users} where newsletter = 1');

$groups = array(
	array(
		'id' => 0,
		'name' => 'Newsletter',
		'cnt' => $news_cnt,
	)
);

$other_groups = Db::GetAll('select g.*, count(*) as cnt from {users} join {groups} as g on g.id = group_id group by group_id order by priority asc');
$groups = array_merge($groups, $other_groups);
?>

<?php if (!isset($mail_id)) { ?>
	<legend>Envoi de mail de masse</legend>
	<form method="post">
	<input type="hidden" name="cycle" value="100">
		<div class="pull-right col-sm-4">
		<table class="table table-lists" id="rcpt_groups">
			<thead>
				<th>Groupe</th>
				<th style="width:35%">Membres</th>
				<th style="width:10%"></th>
			</thead>
			<tbody>
				<?php
					foreach($groups as $group) {
						echo '<tr style="cursor: pointer"><td>' . html_encode($group['name']) . '</td><td>' . $group['cnt'] . '</td><td><input name="groups[]" type="checkbox" value="' . $group['id'] .'" id="rcpt_group'.$group['id'].'"></td></tr>';
					}
				?>
			</tbody>
		</table>
		</div>
		<div class="form-horizontal text-center pull-right col-sm-8">
			<div class="form-group">
				<label class="col-sm-2 control-label" for="sujet">Sujet :</label>
				<div class="col-sm-10 control">
					<input id="sujet" name="sujet" class="form-control" type="text" maxlength="32" value="<?php echo $sujet; ?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="id">Message :</label>
				<div class="col-sm-10 control">
					<textarea id="message" name="message" class="form-control" style="height:200px" placeholder="Composer un message..."><?php echo $message; ?></textarea><small>Variables: &lt;username&gt;</small><br>
					
				</div>
			</div>
			<button class="btn btn-primary" type="submit">Envoyer le message</button>
		</div>
	</form>
	<div class="clearfix text-center">&nbsp;</div>
<?php } ?>

<div class="clearfix"> 
	<button id="viewhistory" class="btn btn-info pull-right">Voir l'historique</button>
	<table class="table table-lists" id="history" <?= isset($mail_id)?'':'hidden'?>>
		<thead>
			<th style="width:15%">Date</th>
			<th style="width:12%">Groupes</th>
			<th style="width:10%">Membres</th>
			<th style="width:11%">Auteur</th>
			<th style="width:55%">Message</th>
		</thead>
		<tbody>
			<?php
				$gmap = array();
				foreach($groups as $group) {
					$gmap[$group['id']] = $group['name'];
				}
				$letters = Db::GetAll('select u.username, n.* from {newsletter} as n left join {users} as u on u.id = n.author order by date_sent desc');
				foreach($letters as $letter) {
					$groups = array_intersect_key($gmap, array_flip(explode(',', $letter['groups'])));
					
					echo '<tr>
							<td>' . today($letter['date_sent'], 'H:i') . '</td>
							<td>' . html_encode(implode(', ', $groups)) . '</td>
							<td>' . $letter['mail_sent'] . ' (' . $letter['mail_failed'] . ')</td>
							<td>' . html_encode($letter['username']) . '</td>
							<td>
								<strong>' . html_encode($letter['subject']) . '</strong><br>
								' . nl2br(short(html_encode($letter['message']), 255)) . '
							</td>
						</tr>';
				}
			?>
		</tbody>
	</table>
</div>
<script>
$('.alert').removeClass('auto-dismiss');
$('#rcpt_groups > tbody tr').click(function() {
	$(this).find('input').click();
});

$('#viewhistory').click(function() {
	$('#history').toggle();
});

$('input').click(function(e) {
	if ($('#rcpt_groups input:checked').length == 0) {
		$('#rcpt_groups > tbody tr').show();
	} else if ($('#rcpt_groups input[value="0"]').prop('checked')) {
		$('#rcpt_groups > tbody tr').hide();
		$('#rcpt_groups > tbody tr:first').show();
	} else {
		$('#rcpt_groups > tbody tr').show();
		$('#rcpt_groups > tbody tr:first').hide();
	}
	e.stopPropagation();
});
$('#rcpt_groups > tbody tr:first').click();
</script>