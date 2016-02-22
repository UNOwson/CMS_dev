<?php defined('EVO') or die('Que fais-tu là?');
has_permission('view_friendlist', true); 

if (!empty($_POST['new_friend'])) {
	$friend = Db::Get('select id, username, email from {users} where username = ? or email = ?', $_POST['new_friend'], $_POST['new_friend']);
	if ($friend) {
		if (Db::Get('select id from {friends} where u_id = ? and f_id = ?', $user_session['id'], $friend['id'])) {
			$_warning = ''.__('friends.already').'';
		} else {
			Db::Insert('friends', ['u_id' => $user_session['id'], 'f_id' => $friend['id'], 'state' => 0]);
			sendmail($friend['email'], ucfirst($user_session['username']).' souhaiterait être votre ami!', "Bonjour {$friend['username']},\n\nVous avez reçu une demande d'amitié sur sur " . site('name') . "  de la part de {$user_session['username']}!\n\nConnectez-vous pour accepter ou refuser.");
			$_success = ''.__('friends.sent').'';
		}
	} else {
		$_warning = __('friends.not');
	}
}
elseif (isset($_POST['del_request'])) {
	$req = Db::Exec("delete from {friends} where (u_id = {$user_session['id']} and f_id = ?) or (f_id = {$user_session['id']} and u_id = ?)", $_POST['del_request'], $_POST['del_request']);
	if ($req >= 1) {
		$_success = __('friends.delete');
	} else {
		$_warning = __('friends.error');
	}
}
elseif (isset($_POST['accept_request'])) {
	$req = Db::Exec('update {friends} set state = 1 where id = ? and f_id = ?', $_POST['accept_request'], $user_session['id']);
	if ($req >= 1) {
		if ($u_id = Db::Get('select u_id from {friends} where id = ?', $_POST['accept_request'])) {
			Db::Insert('friends', array('u_id'  => $user_session['id'], 'f_id'  => $u_id, 'state' => 1), true);
		}
		$_success = __('friends.accept');
	} else {
		$_warning = __('friends.error');
	}	
}

$request_out = $friends = array();

$request_in = Db::QueryAll('SELECT f.id as fid, f.state as fstate, acc.activity, acc.username, acc.email, acc.hide_email, acc.id, g.name as gname, g.color as gcolor
							FROM {friends} AS f JOIN {users} as acc ON f.u_id = acc.id 
							LEFT JOIN {groups} as g ON g.id = acc.group_id
							WHERE f.state <> 1 AND f.f_id = ?', $user_session['id'], true);

$requests = Db::QueryAll('SELECT f.id as fid, f.state as fstate, acc.activity, acc.username, acc.email, acc.hide_email, acc.id , g.name as gname, g.color as gcolor
						  FROM {friends} AS f JOIN {users} as acc ON f.f_id = acc.id 
						  LEFT JOIN {groups} as g ON g.id = acc.group_id
						  WHERE f.u_id = ?', $user_session['id']);

foreach($requests as $row)
{
	if ($row['fstate'] == 1) {
		$friends[$row['fid']] = $row;
	} else {
		$request_out[$row['fid']] = $row;
	}
}

$can_be_friend = has_permission('be_friend');

include_template('pages/friends.php', compact(
	'friends', 
	'request_in', 
	'request_out', 
	'can_be_friend'
));
