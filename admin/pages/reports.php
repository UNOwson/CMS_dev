<?php has_permission('admin.', true);

$types = Db::QueryAll('select distinct type from {reports}', true);
$selected_types = _GP('types', array_keys($types));

if (_POST('dismiss')) {
	if ($r = Db::Get('select * from {reports} where id = ?', _POST('dismiss'))) {
		Db::Exec('update {reports} set deleted = UNIX_TIMESTAMP() where id = ?', _POST('dismiss'));
		log_event($user_session['id'], 'admin', "Suppression d'une alerte de type {$r['type']}#{$r['rel_id']}: {$r['reason']}");
	}
}

if ($selected_types) {
	$reports = Db::QueryAll('select r.*, u.username , if(p.message is null, if(c.message is null, up.username, c.message), p.message) as message, c.page_id
									 from {reports} as r 
									 left join {users} as u on u.id = r.user_id 
									 left join {users} as up on up.id = rel_id and type="profile"
									 left join {forums_posts} as p on p.id = rel_id and type = "forum" 
									 left join {comments} as c on c.id = rel_id and type = "comment" 
									 where deleted = 0 and `type` in (' . implode(', ', array_fill(0, count($selected_types), '?')) . ')
									 order by reported desc', $selected_types);
} else {
	$reports = array();
}
$ptotal = 0;
?>
<?php if (!$reports) { ?>
	<br><div style="text-align: center;" class="alert alert-warning">Il n'y a aucun signalement.</div>
<?php } else { ?>
	<legend>Signalements</legend>
	<form method="post" id="content">
		<div class="pull-right">
		<?php
			foreach($types as $t) {
				if (empty ($selected_types) || in_array($t['type'], $selected_types)) 
					echo '<label><input name="types[]" type="checkbox" value="'.$t['type'].'" checked> '.html_encode($t['type']).'</label>&nbsp;&nbsp;&nbsp;';
				else
					echo '<label><input name="types[]" type="checkbox" value="'.$t['type'].'"> '.html_encode($t['type']).'</label>&nbsp;&nbsp;&nbsp;';
			}
		?>
		</div>
		<table class="table table-lists">
			<thead>
				<th>Membre</th>
				<th>Résumé</th>
				<th>Raison</th>
				<th></th>
			</thead>
			<tbody>
			<?php
				foreach($reports as $r) {
					switch($r['type']){
						case 'forum': $link = create_url('forums', ['pid'=>$r['rel_id']], 'alert'.$r['rel_id']); break;
						case 'comment': $link = create_url('pageview', $r['page_id'], 'alert'.$r['rel_id']); break;
						case 'profile': $link = create_url('user', $r['rel_id']); break;
					}
					echo '<tr><td>' . html_encode($r['username'] ?: $r['user_ip']) . '</td>';
					echo '<td><small>' . html_encode(short(strip_tags($r['message']),60)) . '</small></td>';
					echo '<td>' . html_encode($r['reason']) . '</td>';
					echo '<td style="text-align:right;width:auto;"><a class="badge" style="color:white" href="' . $link . '">' . $r['type'] . '</a>&nbsp;&nbsp;<button name="dismiss" value="' . $r['id'] . '" class="btn btn-xs btn-warning" >Ignorer</button></td></tr>';
				}
				?>
			</tbody>
		</table>
		<script>
			$('input[type=checkbox]').on('change', function () {
				$('form').submit();
			});
		</script>
		<?php echo paginator($ptotal , _GET('pn', 1), 10); ?>
	</form>
<?php } ?>