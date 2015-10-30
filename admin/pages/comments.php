<?php	has_permission('mod.report_mess', true);

	if (has_permission('mod.comment_censure') && isset($_POST['com_accept'])) {
		Db::Exec('UPDATE {comments} SET state = 1 WHERE id = ?', $_POST['com_accept']) !== false  && $_success = 'Commentaire approuvé!';
		log_event($user_session['id'], 'admin', 'Commentaire accepté #'.$_POST['com_accept']);
	}
	elseif (has_permission('mod.comment_censure') && isset($_POST['com_censure'])) {
		Db::Exec('UPDATE {comments} SET state = 2 WHERE id = ?', $_POST['com_censure']) !== false  && $_success = 'Commentaire censuré!';
		log_event($user_session['id'], 'admin', 'Commentaire censuré #'.$_POST['com_censure']);
	}
	elseif (has_permission('mod.comment_delete') && isset($_POST['com_delete'])) {
		$page_id = Db::Get('select page_id from {comments} WHERE id = ?', $_POST['com_delete']);
		if ($page_id && Db::Exec('DELETE FROM {comments} WHERE id = ?', $_POST['com_delete']) !== false) {
			$_success = 'Commentaire supprimé!';
			//Db::Exec('update {pages} set comments = comments - 1 where page_id = ?', $page_id);
			Db::Exec('update {pages} as p set comments = (select count(*) from {comments} as c where c.page_id = p.page_id) where page_id = ?', $page_id);
			log_event($user_session['id'], 'admin', 'Commentaire supprimé #'.$_POST['com_delete']);
		}
	}
	
	
	$where_coms = isset($_GET['page_id']) ? 'where page_id = '.(int)$_GET['page_id'] : '';
	
	$start = isset($_REQUEST['pn']) && $_REQUEST['pn'] ? ($_REQUEST['pn']-1) * 25: 0;
	$total = Db::Get('select count(*) from {comments} ' . $where_coms);
	
	$comment_status = array( 0=> 'Ok', 1=> 'Ok', 2=> 'Censuré');
	$comments = Db::QueryAll('SELECT coms.*, acc.username FROM {comments} AS coms LEFT JOIN {users} AS acc ON coms.user_id = acc.id '.$where_coms.' ORDER BY state ASC, id DESC LIMIT '.$start.', 25');

	if (!$comments)
		return print '<br><div style="text-align: center;" class="alert alert-warning">Aucun commentaire!</div>';
?>
<legend>Commentaires</legend>
<div id="content">
	<form method="post" action="#comments">
		<table class="table">
			<thead>
				<tr>
					<th>Message</th>
					<th>Utilisateur</th>
					<th>État</th>
					<th style="width:110px;"> </th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach($comments as $comment) {
						if ($comment['state'] != 1) { //$_GET['page'] == 'comments' && 
							$seen[] = $comment['id'];
						}
						echo '<tr>';
							echo '<td>' . html_encode($comment['message']) . '</td>';
							echo '<td style="white-space:nowrap">'.($comment['username'] ?: $comment['poster_name']) . '</td>';
							echo '<td>' . $comment_status[$comment['state']] . '</td>';
							echo '<td>';
								if ($comment['state'] == 2)
									echo '<button class="btn btn-xs btn-success" name="com_accept" value="'.$comment['id'].'" title="Autoriser ce commentaire"><i class="fa fa-check"></i></button> ';
								if (has_permission('mod.comment_censure') && $comment['state'] != 2)
									echo '<button name="com_censure" value="'.$comment['id'].'" title="Censurer le contenu" class="btn btn-xs btn-danger"><i class="fa fa-ban"></i></button> ';
								if (has_permission('mod.comment_delete'))
									echo '<button name="com_delete" value="'.$comment['id'].'" title="Supprimer" class="btn btn-xs btn-danger"><i class="fa fa-eraser"></i></button> ';
								echo '<a href="'.create_url($comment['page_id'], [], '#msg'.$comment['id']).'" title="Voir la page" class="btn btn-xs btn-primary"><i class="fa fa-eye"></i></a>';
							echo '</td>';
						echo '</tr>';
					}
					
					if (isset($seen)) {
						Db::Exec('UPDATE {comments} SET state = 1 WHERE STATE = 0 AND id IN('.implode(',', $seen).')');
					}
				?>
			</tbody>
		</table>
	</form>
<?php echo paginator(ceil($total / 25), _GET('pn') ?: 1, 10, null, _GET('prevpn')); ?>
</div>