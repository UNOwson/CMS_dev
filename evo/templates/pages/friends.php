<?php if ($can_be_friend): ?>
	<legend>Ajouter un ami</legend>
	<form role="form" class="form-horizontal" method="post" style="margin: 20px;">
		<div class="form-group">
			<label class="col-sm-3 control-label" for="query">Pseudo ou email :</label>
			<div class="col-sm-8 control">
				<input id="query" name="new_friend" class="form-control" type="text" data-autocomplete="userlist">
			</div>
		</div>
		<div class="text-center">
			<button class="btn btn-medium btn-primary" type="submit">Envoyer la demande d'ami</button>
		</div>
	</form>
<?php endif; ?>

<?php if ($request_in): ?>
	<legend>Demandes reçues</legend>
	<table class="table friend_table">
		<form method='post'>
			<?php
				foreach($request_in as $id => $friend) {
					echo  '<tr>'.
							'<td><a class="ico-' . ($friend['activity'] > time() - 120 ? 'online" title="En Ligne"' : 'offline" title="Hors Ligne"') . '></a></td>'.
							'<td style="min-width: 25%;"><a href="' . create_url('user', $friend['id']) . '">' . $friend['username'] . '</a></td>'.
							'<td style="min-width: 30%;">'.
							($friend['hide_email'] > 0 ? 'Email privé' : $friend['email']).
							'</td>'.
							'<td style="min-width:25%;color:' . $friend['gcolor'] . '">' . $friend['gname'] . '</td>'.
							'<td class="text-right">'.
								"<button name='accept_request' value='{$id}' title='Accepter la demander' class='btn btn-success btn-xs'><i class='fa fa-check'></i></button> ".
								"<button name='del_request' value='{$friend['id']}' title='Refuser la demande' class='btn btn-danger btn-xs'><i class='fa fa-times'></i></button ".
							'</td>'.
						'</tr>';
				}
			?>
		</form>
	</table>
<?php endif; ?>

<?php if ($friends): ?>
	<legend>Ma liste d'amis</legend>
	<table class="table friend_table">
		<form method='post'>
			<?php
				foreach($friends as $id => $friend) {
					$friend['username'] = html_encode($friend['username']);
					echo  '<tr>'.
							'<td><a class="ico-' . ($friend['activity'] > time() - 120 ? 'online" title="En Ligne"' : 'offline" title="Hors Ligne"') . '></a></td>'.
							'<td style="min-width: 25%;"><a href="' . create_url('user', $friend['id']) . '">' . $friend['username'] . '</a></td>'.
								'<td style="min-width: 30%;">'.
							($friend['hide_email'] ? 'Email privé' : $friend['email']).
							'</td>'.
							'<td style="min-width:25%;color:' . $friend['gcolor'] . '">' . $friend['gname'] . '</td>'.
							'<td class="text-right">'.
								'<a href="' . create_url('mail', $friend['username']) . '" title="Envoyer un message" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a> '.
								'<button name="del_request" value="' . $friend["id"] .'" title="Retirer de mes amis" class="btn btn-danger btn-xs"><i class="fa fa-eraser"></i></button>'.
							'</td>'.
						'</tr>';
				}
			?>
		</form>
	</table>
<?php else: ?>
	<div class="alert alert-info">Vous n'avez aucun ami.</div>
<?php endif; ?>


<?php if ($request_out): ?>
	<legend>Demandes envoyées</legend>
	<table class="table friend_table">
		<form method='post'>
			<?php
				foreach($request_out as $id => $friend) {
					echo  "<tr>".
							'<td style="min-width: 25%;"><a href="' . create_url('user', $friend['id']) . '">' . $friend['username'] . '</a></td>'.
							'<td style="min-width:30%">'.
							($friend['hide_email'] ? 'Email privé' : $friend['email']).
							'</td>'.
							'<td style="min-width:25%;color:' . $friend['gcolor'] . '">' . $friend['gname'] . '</td>'.
							'<td class="text-right">'.
								'<button name="del_request" value="' . $friend['id'] . ' title="Refuser" class="btn btn-danger btn-xs"><i class="fa fa-times"></i></button>'.
							'</td>'.
						'</tr>';
				}
			?>
		</form>
	</table>
<?php else: ?>
	<div class="alert alert-info">Vous n'avez aucune demande en attente.</div>
<?php endif; ?>