<?php defined('EVO') or die('Que fais-tu là?');
has_permission(null, true);
 
	$_title = 'Messagerie';
	
	$reply = $to = $mails = $highlight = 0;
	$can_send = has_permission('send_pmess_friend') || has_permission('send_pmess_nofriend');
	
	$tab_mail = isset($_GET['id']);
	
	/* Supprimer un message */
	if (isset($_POST['del_email'])) {
		if (Db::Exec('update {mailbox} set deleted_snd = 1 where id = ? AND s_id = ?', $_POST['del_email'], $user_session['id']) +
			 Db::Exec('update {mailbox} set deleted_rcv = 1 where id = ? AND r_id = ?', $_POST['del_email'], $user_session['id']))
			$_success = 'Message supprimé!';
		else
			$_warning = 'Aucun message supprimé!';
	}
	
	/* Restorer un message */
	if (isset($_POST['restore_email'])) {
		if (Db::Exec('update {mailbox} set deleted_snd = 0 where id = ? AND s_id = ?', $_POST['restore_email'], $user_session['id']) +
			 Db::Exec('update {mailbox} set deleted_rcv = 0 where id = ? AND r_id = ?', $_POST['restore_email'], $user_session['id']))
			$_success = 'Message restauré!';
		else
			$_warning = 'Aucun message restauré!';
	}

	/* Envoyer un message */
	if ($can_send && !empty($_POST['message'])) {
		if (isset($_POST['id']) && $_POST['id'] > 0 && $mail = Db::Get('select * from {mailbox} where id = ?', $_POST['id'])) {
			$reply = $mail['reply'] ?: $mail['id'];
			$subject = strncasecmp($mails[0]['sujet'], 're :', 4) ? 'Re :'.$mail['sujet'] : $mail['sujet'];
			$to = $mail['s_id'] == $user_session['id'] ? $mail['r_id'] : $mail['s_id'];
		} else {
			$subject = _POST('sujet');
			$to = _POST('username');
		}
		
		if ($highlight = SendMessage($to, $subject, $_POST['message'], $reply)) {
			$_success = 'Votre message a été envoyé avec succès !';
			$_POST = array();
			$_GET['id'] = 0;
		} else {
			$_warning = 'Utilisateur introuvable !';
			$tab_mail = true;
		}
	}
	
	/* Ouvrir un message/discussion */
	if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
		if ($user_session['discuss']) {
			$reply = Db::Get('select if(reply > 0, reply, id) from {mailbox} where id = ?', $_GET['id']);
		}
		
		if ($reply == 0) {
			$reply = -1;
		}
		
		$mails = Db::QueryAll('SELECT mb.*, a.username, b.username as rcpt, a.avatar, a.ingame, a.email, g.color, g.name as gname 
							   FROM {mailbox} AS mb 
							   LEFT JOIN {users} AS a ON s_id = a.id 
							   LEFT JOIN {users} AS b ON r_id = b.id 
							   LEFT JOIN {groups} as g ON g.id = a.group_id 
							   WHERE (mb.id = ? or ? IN(mb.reply, mb.id)) AND ((mb.r_id = ? AND deleted_rcv =0) OR (mb.s_id = ? AND deleted_snd =0))
							   ORDER BY mb.id ASC', $_GET['id'], $reply, $user_session['id'], $user_session['id']);

		Db::Exec('UPDATE {mailbox} SET viewed = ? WHERE (id = ? or reply = ?) and r_id = ? and (viewed = 0 or viewed is null)', time(), $_GET['id'], $reply, $user_session['id']);
		
		if ($mails) {
			$_title = 'Message de ' . $mails[0]['username'];
		}
	} elseif (!empty($_GET['id'])) {
		echo '<script>$(function() { window.location.hash = "mail"; });</script>';
	}	

	if (rand(0, 10) == 5) {
		Db::Exec('DELETE FROM {mailbox} WHERE (deleted_rcv = 1 AND deleted_snd = 1 AND posted < ?) OR (deleted_rcv = 1 AND s_id = ?)', time() - 15* 24 * 3600, $user_session['id']);
	}
	
	$mail_inbox  = Db::QueryAll('SELECT m.sujet, m.posted, m.id, m.viewed, m.type, a.username FROM {mailbox} as m LEFT JOIN {users} as a ON m.s_id = a.id WHERE deleted_rcv = 0  AND r_id = ? ORDER by id desc', $user_session['id']);
	$mail_outbox = Db::QueryAll('SELECT m.sujet, m.posted, m.id, m.viewed, a.username FROM {mailbox} as m LEFT JOIN {users} as a ON m.r_id = a.id WHERE deleted_snd = 0 AND s_id = ? ORDER by id desc', $user_session['id']);
	$mail_trash  = Db::QueryAll('SELECT m.sujet, m.posted, m.id, m.viewed, a.username FROM {mailbox} as m LEFT JOIN {users} as a ON m.r_id = a.id WHERE (deleted_rcv = 1 AND r_id = ?) OR (deleted_snd = 1 AND s_id = ? ) ORDER BY id desc', $user_session['id'], $user_session['id']);
	
	if ($mails) {
		$participants = [];
		
		foreach($mails as $mail) {
			$participants[] = $mail['username'];
		}
		
		$reply = $mails[0]['reply'] ?: $mails[0]['id'];
		$highlight = count($mails) > 1 ? ($highlight ?: $_GET['id']) : 0;
		$action = count($mails) > 1 ? create_url('mail#mail') : create_url('mail');
	}
	
include_template('pages/mail.php', compact(
	'_message_type',
	'tab_mail',
	'participants', 
	'reply', 
	'highlight', 
	'mail_inbox',
	'mail_outbox',
	'mail_trash',
	'mails',
	'action',
	'can_send'
));
