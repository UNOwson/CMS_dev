<?php
require '../evo/common.php';

switch (_GP('action')) {
	case 'preview':
		switch(_GP('format')) {
			case 'markdown':
				$md = new \Parsedown\ParsedownExtra;
				die($md->text(_GP('text')));
				break;

			case 'bbcode':
				die(bbcode2html(_GP('text')));
				break;

			case 'text':
				$page['content'] = nl2br(emoticons($page['content']));
				break;

			case 'html':
			default:
				die(_GP('text'));
		}

		break;
	case 'servers':
		if ($servers = Db::QueryAll('SELECT * FROM {servers} ORDER BY name ASC'))
		{
			echo '<div>État des serveurs:</div>';
			echo '<table class="jeux">';
			foreach($servers as $server)
			{
				if (\Evo\ServerQuery\Server::isOnline($server['host'], $server['port'], $server['type'])) {
					echo '<tr><td style="width: 190px;"><a href="'.create_url('server/'.$server['id']) . '">' . $server['name'] . '</a></td><td style="color: green; font-weight: bold;">'. $server['host'] .':'. $server['port'] .'</font></td></tr>';
				} else {
					echo '<tr><td style="width: 190px;">' . $server['name'] . '</td><td><font style="color: red; font-weight: bold;">' . $server['host'] . ':' . $server['port'] . '</font></td></tr>';
				}
			}
			echo '</table>';
		}
	case 'poke':
		if (has_permission())
		{
			$content = '';
			$mbox = Db::QueryAll('SELECT type, count(*) as c FROM {mailbox} WHERE deleted_rcv = 0 AND viewed is null AND r_id = ? group by `type`', $user_session['id'], true);
			$nbr_friends = Db::Get('SELECT COUNT(*) as c FROM {friends} WHERE state = 0 AND f_id = ?', $user_session['id']);

			foreach($mbox as $type => $count) {
				$t = strip_tags($_message_type[$type][0]) ?: 'message';
				$content .= '<div class="alert alert-'.$_message_type[$type][4].'"><a href="'.create_url('mail').'"><i class="fa fa-lg '.$_message_type[$type][2].'"></i> Vous avez ' . plural("%count% $t|%count% {$t}s", $count['c']) . ' ! </a></div>';
			}

			// erf...
			$content = str_replace(array(' 1 message', ' 1 notification'), array(' un message', ' une notification'), $content);

			if ($nbr_friends == 1)
				$content .= '<div class="alert alert-info"><a href="'.create_url('friends').'">Vous avez une demande d\'amitié !</a></div>';
			if ($nbr_friends > 1)
				$content .= '<div class="alert alert-info"><a href="'.create_url('friends').'">Vous avez '.$nbr_friends.' demandes d\'amitié !</a></div>';

			if ($content)
				echo '<script>poptart("'.addslashes($content).'", ' . (!empty($_SESSION['poptart']) && $_SESSION['poptart'] == $content ? 'false' : 'true') . ');</script>';

			$_SESSION['poptart'] = $content;
		}
		break;

	case 'userlist':
		if (!has_permission('user.view_uprofile')) break;

		$json = array();
		$req = Db::QueryAll('SELECT a.username, a.activity, a.avatar, a.email, g.color, g.name as gname
							 FROM {users} as a
							 JOIN {groups} as g ON g.id = a.group_id
							 LEFT JOIN {friends} as f ON f.u_id = ? AND f.f_id = a.id
							 WHERE username LIKE ? OR email = ?
							 ORDER BY LOCATE(username, ?) ASC, f.id is null, a.activity DESC
							 LIMIT 10',
							 $user_session['id'], '%'._GP('query').'%', _GP('query'), _GP('query'));

		foreach($req as $row) {
			$online = $row['activity'] > time() - 120 ? '<span style="font-size:x-small;">* (En ligne)</span>&nbsp;' : '';
			$json[] = array($row['username'], $row['username']. '&nbsp;&nbsp;&nbsp;<span style="font-size:x-small;color:' . $row['color'] . '">('. $row['gname'] . ')</span>&nbsp;&nbsp;' . $online, get_avatar($row, true));
		}

		echo json_encode($json);
		break;

	case 'categorylist':
		if (!has_permission('admin.page_edit')) break;

		$q = '%'._GP('query').'%';

		echo json_encode(Db::QueryAll('SELECT DISTINCT category from {pages} WHERE category LIKE ? and category <> ""', $q, true));
		break;

	default:
		plugins::trigger('ajax', array('action' => _GP('action')));
}
