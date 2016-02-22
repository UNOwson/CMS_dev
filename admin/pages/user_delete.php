<?php has_permission('admin.del_member', true);

$user_info = Db::Get('select a.*, g.name as gname, g.color as color from {users} as a LEFT JOIN {groups} as g ON a.group_id = g.id WHERE a.id = ?', $_REQUEST['id']);

if (!$user_info) {
	return $_warning = 'Utilisateur inexistant!';
}

echo '<legend>Supprimer le compte de '.html_encode($user_info['username']).' (<small style="color:'.$user_info['color'].';">'.$user_info['gname'].'</small>) ?</legend>';

if (!empty($_POST) && empty($_POST['del_reason'])) {
	$_warning = 'Vous devez donner une raison!';
}
elseif (isset($_POST['del_confirmation'])) {
	
	echo '<div class="bs-callout bs-callout-success"><h4>Félicitation</h4><p>Le compte a été supprimé. Voici ce qui a été détruit:<ul>';

	if ($c = Db::Exec('delete from {users} where id = ?', $user_info['id']))
		echo '<li>Profil supprimé</li>';
	
	Db::Exec('delete from {subscriptions} where user_id = ?', $user_info['id']);
	
	if ($c = Db::Exec('delete from {friends} where u_id = ? or f_id = ?', $user_info['id'], $user_info['id']))
		echo '<li>'.$c.' ami(s) supprimé(s)</li>';
	
	if ($c = Db::Exec('delete from {mailbox} where r_id = ?', $user_info['id']))
		echo '<li>'.$c.' message(s) supprimé(s)</li>';
	
	if (isset($_POST['del_comments']) && $c = Db::Exec('delete from {comments} where user_id = ?', $user_info['id']))
		echo '<li>'.$c.' commentaire(s) supprimé(s)</li>';
	
	if (isset($_POST['del_forum_topics'])) {
		$c = Db::Exec('
					  delete from {forums_posts} 
					  where topic_id in (
										select id from {forums_topics} where poster_id = ?
										)
					',
					$user_info['id']
		);
		
		$topics = Db::Exec('delete from {forums_topics} where poster_id = ?', $user_info['id']);
		
		echo '<li>'.$topics.' disccusion(s) supprimée(s) contenant '.$c.' posts(s) aussi supprimé(s)</li>';
	}
	
	if (isset($_POST['del_forum_posts']) && $c = $np = Db::Exec('delete from {forums_posts} where poster_id = ?', $user_info['id']))
		echo '<li>'.$c.' posts(s) supprimé(s)</li>';
	
	if (!empty($topics) || !empty($np)) {
		Db::Exec('update {forums} as f set num_topics = (select count(*) from {forums_topics} as t where f.id = t.forum_id)');
		Db::Exec('
				 update {forums} as f 
				 set num_posts = (
									select count(*) 
									from {forums_posts} as p
									join {forums_topics} as t on t.id = p.topic_id 
									where f.id = t.forum_id
								),
					last_topic_id = (
									 select max(id) from {forums_topics} as t where t.forum_id = f.id
									)
				');
	}
	
	plugins::trigger('account_deleted', array($user_info));
	
	log_event($user_info['id'], 'admin', 'Suppression du compte de '.$user_info['username'].': '.$_POST['del_reason']);
	
	echo '</ul></p></div>';
	
	return;
}
?>
<form method="post">
	<label><input name="del_comments" value="1" type="checkbox" checked> Supprimer ses commentaires</label><br>
	<label><input name="del_forum_posts" value="1" type="checkbox" checked> Supprimer les messages sur le forum</label><br>
	<label><input name="del_forum_topics" value="1" type="checkbox" checked> Supprimer les discussions sur le forum</label><br><br>
	<label><input name="del_files" value="1" type="checkbox" disabled> Supprimer ses fichiers uploadés</label><br><br>
	<label>Raison de la suppression: </label><input name="del_reason" type="input" class="form-control">
	<br>
	<input class="btn btn-medium btn-danger" type="submit" name="del_confirmation" onclick="return confirm('Sur?');" value="Supprimer">
</form>