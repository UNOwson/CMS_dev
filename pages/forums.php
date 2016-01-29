<?php
/*
 * Evo-CMS: Simple forum.
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>.
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */

 /*
 TODO:
	- Proper word search instead of string search
	- Subscriptions (maybe something general for the whole CMS?)
	- As expected this file grew out of hand, it will need to be broken down
	  or at least documented!
 */

defined('EVO') or die(__('403.msg'));

$_body_class = 'page-wide';

$post = $forum = $topic = $sub = $mode = null;
$message = $subject = '';
$forum_moderator = false;

$topics_per_page = 25;
$posts_per_page = 10;

$pn = ceil(_GET('pn', 1)) ?: 1;
$ptotal = 0;

$topic_read = &$_SESSION['forum_new_posts'];
$last_visit = isset($_SESSION['last_visit']) ? $_SESSION['last_visit'] : 0;

if (_GET('edit')) {
	$post = Db::Get('select * from {forums_posts} where id = ? ', _GET('edit'));
	if (!$post) return $_notice = ''.__('forums.edit_post').'';

	$topic = Db::Get('select * from {forums_topics} where id = ? ', $post['topic_id']);
	if (!$topic) return $_notice = ''.__('forums.edit_disc').'';

	$permission = has_permission('forum.moderation', $topic['forum_id']) || has_permission('mod.forum_post_edit');

	if (!has_permission() || (!$permission && $post['poster_id'] != $user_session['id']))
		return $_warning = ''.__('forums.edit_right').'';

	if ($topic['closed'] == 1 && !$permission)
		return $_warning = ''.__('forums.edit_closed').'';

	$message = $post['message'];
	$subject = $topic['subject'];

	$mode = ''.__('forums.edit_mode').'';
}
elseif (_GP('pid')) {
	$post = Db::Get('select * from {forums_posts} where id = ? ', _GP('quote', _GP('pid')));
	if (!$post) return $_notice = ''.__('forums.post_post').'';

	$topic = Db::Get('select * from {forums_topics} where id = ? ', $post['topic_id']);
	if (!$topic) return $_notice = ''.__('forums.post_disc').'';

	$pn = ceil (Db::Get('select count(*) from {forums_posts} where topic_id = ? and id <= ?', $post['topic_id'], $post['id']) / $posts_per_page);
	$ptotal = ceil($topic['num_posts'] / $posts_per_page);

	$mode = ''.__('forums.post_mode').'';
}
elseif (_GP('topic')) {
	$topic = Db::Get('select * from {forums_topics} where id = ? ', _GP('topic'));
	if (!$topic) return $_notice = 'Cette discussion n\'existe pas.';

	$ptotal = ceil($topic['num_posts'] / $posts_per_page);
	$mode = ''.__('forums.post_mode').'';
}
elseif (_GET('id')) {
	$forum = Db::Get('select * from {forums} where id = ? ', _GET('id'));
	if (!$forum) return $_notice = 'Ce forum n\'existe pas.';

	$ptotal = ceil($forum['num_topics'] / $topics_per_page);
	$mode = ''.__('forums.post_disc_new').'';
}

if (isset($forum)) {
	has_permission('forum.read', $forum['id'], true);
	$forum_moderator = has_permission('forum.moderation', $topic['forum_id']);
}


if (_GET('quote') && $post = Db::Get('select * from {forums_posts} where id = ?', _GET('quote'))) {
	$message = '[quote][url=?p=forums&pid=' . $post['id'] . '#msg' . $post['id'] . '][b]' . $post['poster'] . "[/b] a dit[/url]:\n"
				. $post['message'] . "[/quote]\n\n";
}


$edit_mode = isset($mode) && _GET('compose', _GET('edit', _GET('quote')));


if (isset($topic)) {
	$forum = Db::Get('select * from {forums} where id = ? ', $topic['forum_id']);
	if (!$forum) return $_notice = ''.__('forums.forum_disc').'';

	$sub = Db::Get('select count(*) from {subscriptions} where type = "forum" and user_id = ? and rel_id = ?', $user_session['id'], $topic['id']);

	if (!isset($topic_read[$topic['id']])) {
		Db::Exec('update {forums_topics} set num_views = num_views + 1 where id = ?', $topic['id']);
	}
}


if (!empty($topic['redirect'])) {
	if (!_GET('force') && !_GET('edit')) {
		http_redirect($topic['redirect']);
		exit;
	} else {
		$_notice = 'Redirigé vers: ' . $topic['redirect'];
	}
}


if (isset($post, $_POST['report'])) {
	Db::Insert('reports', array(
		'user_id' => $user_session['id'],
		'type' => 'forum',
		'rel_id' => $post['id'],
		'reason' => _POST('report'),
		'reported' => time(),
		'user_ip' => $_SERVER['REMOTE_ADDR']
	));
	log_event($user_session['id'], 'forum', ''.__('forums.report').''.$topic['subject']);
}



if (isset($forum) && has_permission('forum.write', $forum['id']))
{
	if (isset($_FILES['ajaxup']) && has_permission('user.upload'))
	{
		$type = array_try_keys(parse_config_kv(Site('upload_groups')), ['forum:'.$forum['id'], 'forums', 'forum']) ?: null;
		if ($file = upload_fichier('ajaxup', null, $type, false, 'forums')) {
			ob_end_clean();
			die(json_encode(array('?p=' . $file[3], $file[0], $file[1], filesize(ROOT_DIR.$file[3]))));
		}
		die($_warning);
	}
	elseif (_POST('message') === '')
	{
		$_warning = ''.__('forums.write.msg').'';
	}
	elseif (_POST('subject') === '')
	{
		$_warning = ''.__('forums.write.subject').'';
	}
	elseif ($post && _GET('edit') && _POST('message')) //Edit
	{
		$files = parse_attached_files(_POST('message'), 'forum-post', $post['id']);

		Db::Exec('update {forums_posts}
				  set message = ?, edited = ?, attached_files = ?
				  where id = ?',
				  _POST('message'), time(), serialize($files), $post['id']);

		if ($topic['first_post_id'] == $post['id'] && !empty($_POST['subject'])) {
			Db::Exec('update {forums_topics} set subject = ? where first_post_id = ?', $_POST['subject'], $post['id']);
		}

		$can_redirect = $forum_moderator || has_permission('mod.forum_topic_redirect');

		if ($topic['first_post_id'] == $post['id'] && $can_redirect) {
			Db::Exec('update {forums_topics} set redirect = ? where first_post_id = ?', $_POST['redirect'], $post['id']);
		}

		$pn = ceil (Db::Get('select count(*) from {forums_posts} where topic_id = ? and id <= ?', $post['topic_id'], $post['id']) / $posts_per_page);

		topic_subscribe($topic['id'], isset($_POST['subscribe']));

		http_redirect(create_url('forums', ['topic'=>$topic['id'], 'pn'=>$pn], '#msg'.$post['id']));
		$_success = ''.__('forums.write_success').'';
	}
	elseif ($topic && _POST('message')) //Reply
	{
		if ($topic['closed'] && !($forum_moderator || has_permission('mod.forum_topic_close')))
			return $_warning = ''.__('forums.reply_disc').'';

		$files = parse_attached_files(_POST('message'));

		Db::Insert('forums_posts', array(
			'topic_id' => $topic['id'],
			'poster' => $user_session['username'],
			'poster_id' => $user_session['id'],
			'poster_ip' => $_SERVER['REMOTE_ADDR'],
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'message' => _POST('message'),
			'posted' => time(),
			'attached_files' => serialize($files),
			));

		$pid = Db::$insert_id;

		Db::Exec('update {forums_topics} set num_posts = num_posts + 1, last_poster = ?, last_post = ?, last_post_id = ? where id = ?', $user_session['username'], time(), $pid, $topic['id']);
		Db::Exec('update {forums} set num_posts = num_posts + 1, last_topic_id = ? where id = ?', $topic['id'], $topic['forum_id']);
		Db::Exec('update {users} set num_posts = num_posts + 1 where id = ?', $user_session['id']);

		parse_attached_files(_POST('message'), 'forum-post', $pid);
		message_parse_usertags(_POST('message'), $pid);
		topic_subscribe($topic['id'], isset($_POST['subscribe']));
		plugins::trigger('forum_new_post', array());

		http_redirect(create_url('forums', ['topic'=>$topic['id'],'pn'=>ceil(($topic['num_posts']+1)/$posts_per_page)],'#msg'.$pid));  // compute last page!!
		$_success = ''.__('forums.write_success').'';
	}
	elseif ($forum && _POST('message')) //Topic
	{
		$can_redirect = ($forum_moderator || has_permission('mod.forum_topic_redirect'));
		$redirect = $can_redirect ? $_POST['redirect'] : '';
		$files = parse_attached_files(_POST('message'));

		Db::Insert('forums_topics', array(
			'forum_id'     => $forum['id'],
			'poster_id'    => $user_session['id'],
			'poster'       => $user_session['username'],
			'subject'      => $_POST['subject'],
			'first_post'   => time(),
			'last_post'    => time(),
			'last_poster'  => $user_session['username'],
			'num_posts'    => 1,
			'first_post_id'=> 0,
			'last_post_id' => 0,
			'redirect'     => $redirect
		));
		$tid = Db::$insert_id;

		// if ($redirect) {
			// Db::Exec('update {forums} set num_topics = num_topics + 1 where id = ?', $tid, $forum['id']);
			// header('Location: ' . Site('url') . '/?p=forums&id='.$forum['id']);
		// } else {
			Db::Insert('forums_posts', array(
				'topic_id' => $tid,
				'poster' => $user_session['username'],
				'poster_id' => $user_session['id'],
				'poster_ip' => $_SERVER['REMOTE_ADDR'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT'],
				'message' => _POST('message'),
				'posted' => time(),
				'attached_files' => serialize($files),
			));
			$pid = Db::$insert_id;

			Db::Exec('update {forums_topics} set first_post_id = ?, last_post_id = ? where id = ?', $pid, $pid, $tid);
			Db::Exec('update {forums} set num_posts = num_posts + 1, num_topics = num_topics + 1, last_topic_id = ? where id = ?', $tid, $forum['id']);
			Db::Exec('update {users} set num_posts = num_posts + 1 where id = ?', $user_session['id']);

			parse_attached_files(_POST('message'), 'forum-post', $pid);
			message_parse_usertags(_POST('message'), $pid);
			topic_subscribe($tid, isset($_POST['subscribe']));
			plugins::trigger('forum_new_post', array());
			http_redirect(create_url('forums', ['topic'=>$tid]));
		// }q

		$_success = ''.__('forums.write_success').'';
	}

	if (isset($topic))
	{
		if (_POST('move-topic'))
		{
			if (!$permission && !has_permission('mod.forum_topic_move'))
				return $_warning = ''.__('forums.move_perm').'';

			if ($topic['forum_id'] == $_POST['move-topic']) {
				$_notice = ''.__('forums.move_already').' <strong>' . $forum['name'] . '</strong>.';
			} elseif (Db::Exec('update {forums_topics} set forum_id = ? where id = ?', $_POST['move-topic'], $topic['id'])) {
				$_success = ''.__('forums.move_success').'';
				forum_refresh($_POST['move-topic']);
				forum_refresh($topic['forum_id']);
				$forum = Db::Get('select * from {forums} where id = ?', $_POST['move-topic']);
				log_event($user_session['id'], 'forum', 'Discussion "' . $topic['subject'] . '" '.__('forums.move_to').' '. $forum['name']);
			} else {
				$_warning = ''.__('forums.move_error').'';
			}
		}
		elseif (_POST('delete-topic'))
		{
			if (!($forum_moderator || has_permission('mod.forum_topic_delete')))
				return $_warning = ''.__('forums.topic_delete_perm').'';

			if (!Db::Exec('delete from {forums_topics} where id = ?', $topic['id']))
				return $_warning = ''.__('forums.topic').'';

			Db::Exec('delete from {subscriptions} where type = "forum" and rel_id = ?', $topic['id']);
			$u = DB::QueryAll('select poster_id, count(*) from {forums_posts} where topic_id = ? group by poster_id', $topic['id']);
			if ($u) {
				foreach($u as $k => $v) $p[end($v)][] = reset($v);
				foreach($p as $c => $u) {
					DB::Exec('update {users} set num_posts = num_posts - ? where id IN (' . implode(',', $u) . ')', $c);
				}
			}
			Db::Exec('delete from {forums_posts} where topic_id = ?', $topic['id']);
			Db::Exec('update {forums} set num_topics = num_topics - 1, num_posts = num_posts - ? where id = ?', $topic['num_posts'], $topic['forum_id']);

			log_event($user_session['id'], 'forum', 'Discussion "' . $topic['subject'] . '" '.__('forums.topic_delete_from').' '. $forum['name']);

			// forum_refresh($topic['forum_id']);
			http_redirect(create_url('forums', $topic['forum_id']));
			exit;
		}
		elseif (_GET('delete-post'))
		{
			$post = Db::Get('select * from {forums_posts} where id = ? and topic_id = ?', $_GET['delete-post'], $topic['id']);

			if (!$post || !has_permission() || (!($forum_moderator || has_permission('mod.forum_post_delete')) && $post['poster_id'] != $user_session['id']))
				return $_warning = ''.__('forums.post_delete_perm').'';

			DB::Exec('update {users} set num_posts = num_posts -1 where id = ?', $post['poster_id']);
			Db::Exec('delete from {forums_posts} where id = ?', $_GET['delete-post']);
			Db::Exec('delete from {subscriptions} where type = "forum" and rel_id = ? and user_id = ?', $topic['id'], $post['poster_id']);

			if (Db::Get('select count(*) from {forums_posts} where topic_id = ?', $topic['id']) == 0) {
				Db::Exec('delete from {forums_topics} where id = ?', $topic['id']) &&
				Db::Exec('delete from {subscriptions} where type = "forum" and rel_id = ?', $topic['id']);
				Db::Exec('update {forums} set num_topics = num_topics - 1 where id = ?', $topic['forum_id']);
				// forum_refresh($topic['forum_id']);
				http_redirect(create_url('forums', $topic['forum_id']));
				exit;
			}
			else {
				topic_refresh($topic['id']);
				Db::Exec('update {forums} set num_posts = num_posts - 1 where id = ?', $topic['forum_id']);
				Db::Exec('update {forums_topics} set num_posts = num_posts - 1 where id = ?', $topic['id']);
				// forum_refresh($topic['forum_id']);
				log_event($user_session['id'], 'forum', 'Discussion "' . $topic['subject'] . '" '.__('forums.topic_delete_from').' "'. $forum['name'].'"'.__('forums.post_delete_reason').'');
				$_success = ''.__('forums.post_delete').'';
			}
		}
		elseif (isset($_POST['sticky']) && ($forum_moderator || has_permission('mod.forum_topic_stick'))) {
			$sticky = (int)$_POST['sticky'];
			$topic['sticky'] = $sticky === 0 ? 0 : $topic['sticky'] + $sticky;
			
			Db::Exec('update {forums_topics} set sticky = ? where id = ?', $topic['sticky'], $topic['id']);
			
			$_success = ''.__('forums.post_sticky').'';
		}
		elseif (_POST('closed') && ($forum_moderator || has_permission('mod.forum_topic_close'))) {
			Db::Exec('update {forums_topics} set closed = ? where id = ?', $_POST['closed'], $_REQUEST['topic']);
			$topic['closed'] = $_POST['closed'];
			$_success = ''.__('forums.post_close').'';
		}
		
		if (!_GET('topic') && !_GP('pid') && !_GP('edit')) {
			unset($topic);
		}
	}
}





function forums_list() {
	global $user_session;

	$forums = Db::QueryAll('select f.id, f.name, f.*, t.last_post, t.subject, t.last_poster, t.last_post_id, t.redirect as tredirect
							from {forums} as f
							join {permissions} as p on p.name = "forum.read" and p.related_id = f.id and p.group_id = ? and p.value = 1
							left join {forums_topics} as t on t.id = f.last_topic_id
							order by f.priority, f.id asc', $user_session['group_id'], true);

	$categories = Db::QueryAll('select * from {forums}_cat order by priority,id asc', true);

	foreach($forums as $forum) {
		$categories[$forum['cat']]['forums'][] = $forum;
	}

	foreach($categories as $id => $cat) {
		if (empty($cat['forums'])) unset($categories[$id]);
	}

	return $categories;
}

function forum_refresh($id) {
	Db::Exec('update {forums} as f set num_topics = (select count(*) from {forums_topics} where forum_id = f.id) where id = ?', $id);
	Db::Exec('update {forums} as f set num_posts = (select sum(num_posts) from {forums_topics} where forum_id = f.id) where id = ?', $id);
	Db::Exec('update {forums} as f set last_topic_id = (select id from {forums_topics} where forum_id = f.id order by last_post desc limit 1) where id = ?', $id);
}

function topic_refresh($id) {
	if ($last_post = Db::Get('select *, max(posted) from {forums_posts} where topic_id = ?', $id)) {
		Db::Exec(
			'update {forums_topics}
			    set num_posts = (select count(*) from {forums_posts} where topic_id = ?),
				    last_post_id = ?, last_post = ?, last_poster = ?
			    where id = ?',
			$id, $last_post['id'], $last_post['posted'], $last_post['poster'], $id
		);
	}
}

function topic_subscribe($id, $sub = true) {
	global $user_session;
	if ($sub && has_permission())
		Db::Exec('replace into {subscriptions} (type, user_id, rel_id, email) values ("forum", ?, ?, ?)', $user_session['id'], $id, $user_session['email']);
	else
		Db::Exec('delete from {subscriptions} where type = "forum" and user_id = ? and rel_id = ?', $user_session['id'], $id);
}

function message_parse_usertags($message, $pid) {
	$tags = user_tags($message, Site('url') . '/forums?pid=' . $pid . '#msg' . $pid);
	if ($tags) {
		$search = $replace = array();
		foreach($tags as $tag => $user) {
			$search[] = '/' . preg_quote($tag) . '([^a-z\.]|$)/iu';
			$replace[] = '[url=?p=user&id=' . $user . ']@' . $user . '[/url]$1';
		}
		Db::Exec('update {forums_posts} set message = ? where id = ?', preg_replace($search, $replace, _POST('message')), $pid);
	}
}



echo '<div class="forum-wrapper">';

echo '<div id="content">';

echo '<ol class="breadcrumb forum-navbar">';
echo '<li><strong><a href="'.Site('url').'">'.html_encode($_crumbs[] = Site('name')).'</a></strong></li>';
echo '<li><a href="'.create_url('forums').'">Forums</a></li>';
if (isset($forum)) echo '<li><a href="'.create_url('forums', $forum['id']).'">'.  html_encode($_crumbs[] = $forum['name']) .'</a></li>';
if (isset($topic)) echo '<li><a href="'.create_url('forums', ['topic'=>$topic['id']]).'"><strong><big>'.  html_encode($_crumbs[] = $topic['subject']) .'</big></strong></a></li>';
if (isset($post))  echo '<li><a href="'.create_url('forums', ['pid'=>$post['id']]).'">Post #'.  $post['id'] .'</a></li>';
if ($edit_mode)    echo '<li class="active">'. $mode . '</li>';
elseif (_GET('search') == 'recent')  echo  '<li class="active">'.__('forums.recent').'</li>';
elseif (_GET('search') == 'noreply') echo  '<li class="active">'.__('forums.noreply').'</li>';
elseif (_GET('search'))              echo  '<li class="active">'.__('forums.search').'</li>';


echo '<div class="pull-right">';
	echo '&nbsp;&nbsp;<a href="'.create_url('forums', ['id'=>@$forum['id'],'search'=>'recent']).'" title="'.__('forums.recent').'"><i class="fa fa-comment-o"></i></a>';
	echo '&nbsp;&nbsp;<a href="'.create_url('forums', ['id'=>@$forum['id'],'search'=>'noreply']).'" title="'.__('forums.noreply').'"><i class="fa fa-meh-o"></i></a>';
	echo '&nbsp;&nbsp;<a href="'.create_url('forums', ['id'=>@$forum['id'],'search'=>'1']).'" title="'.__('forums.search').'"><i class="fa fa-search"></i></a>';
	if (!has_permission())
		echo '&nbsp;&nbsp;<a href="'.create_url('login', ['redir'=>$_SERVER['REQUEST_URI']]).'" title="'.__('forums.connect').'"><i class="fa fa-user"></i></a>';
echo '</div>';


Site('name', Site('forums.name') ?: Site('name'), true);
Site('description', Site('forums.description') ?: Site('description'), true);


$_title = implode(' / ', array_reverse($_crumbs));

if ($ptotal > 1) {
	echo '<span style="float:right;padding-right:10px;">Pages: ';
	foreach(paginator_range($ptotal, $pn, 8) as $i => $l) {
		if ($pn == $i)
			echo ' <strong>' . $i . '</strong>';
		else
			echo ' <a href="'.create_url('forums', @$forum['id']). (isset($topic) ? '&topic='.$topic['id'] : '') . '&pn='.$i.'">'.$l.'</a> ';
	}
	echo '</span>';
}

echo '</ol>';

echo '<div class="forum-main">';

if ($edit_mode) {
	if (!has_permission('forum.write', $forum['id'])) {
		$_warning = ''.__('forums.reply_perm').'';
		if (!has_permission()) {
			$_warning .= ''.__('forums.notice_login1').' <a href="'.create_url('login', ['redir'=>$_SERVER['REQUEST_URI']]).'">'.__('forums.notice_login2').'</a> ?';
		}
	} elseif(isset($topic) && $topic['closed']) {
		$_warning = ''.__('forums.topic_close').'';
	}
}
elseif (isset($_GET['search'])) {
	echo '<div class="panel panel-default">
			<div class="panel-heading">'.__('forums.search').'</div>
			<form method="get">
			<input type="hidden" name="p" value="forums">
			<div class="panel-body">
				<div class="col-sm-8 control-label">'.__('forums.search_sort_keywords').' :<br>
					  <input type="text" class="form-control" name="text" value="' . html_encode(_GET('text')) . '">';

	if ($forum) echo '<label><input type="checkbox" name="forum_only" value="'.$forum['id'].'" ' . (_GET('forum_only') ? 'checked':'') . '> Seulement dans <i>' . $forum['name'] . '</i></label>';

	echo			'<br>
					  <button type="submit" name="search" value="1" class="btn btn-sm btn-primary">'.__('forums.search_sort_btn').'</button>
					</div>
					<div class="col-sm-4 control-label">'.__('forums.search_sort_author').' :<br>
					  <input type="text" class="form-control" data-autocomplete="userlist" name="poster" value="' . html_encode(_GET('poster')) . '">
					</div>
				</div>
			  </form>
		</div>';

	//Todo: build a word list instead of doing full text search...
	$text = trim(_GET('text'));
	$poster = trim(_GET('poster'));

	$forums = Db::QueryAll('select * from {forums}');

	$query = array(1);

	if (_GET('forum_only'))
		$query[] = 'forum_id = '. (int) _GET('forum_only');

	if ($poster)
		$query[] = 'username = "' . Db::Escape($poster) . '"';

	if (_GET('search') == 'recent')
		$query[] = 'p.posted >= ' . (time()-48*3600);

	if (_GET('search') == 'noreply')
		$query[] = 't.num_posts = 1';

	if (count($query) > 1 || $text) {

		$posts = Db::QueryAll('select p.*, t.subject, a.username, f.name as fname
							   from {forums_posts} as p
							   left join {forums_topics} as t on t.id = p.topic_id
							   left join {users} as a on a.id = p.poster_id
							   join {forums} as f on f.id = forum_id
							   join {permissions} as perm on perm.name = "forum.read" and perm.related_id = f.id and perm.group_id = ? and perm.value = 1
							   where message like ? and '.str_replace('?', '', implode(' and ', $query)).'
							   order by p.id desc LIMIT ?, ?', $user_session['group_id'], '%'.str_replace(' ', '%', $text).'%', $posts_per_page * ($pn-1), $posts_per_page, true);

		$topics = Db::QueryAll('select p.*, t.subject, a.username, f.name as fname
								from {forums_topics} as t
								left join {forums_posts} as p on t.first_post_id = p.id
								left join {users} as a on a.id = p.poster_id
								join {forums} as f on f.id = forum_id
								join {permissions} as perm on perm.name = "forum.read" and perm.related_id = f.id and perm.group_id = ? and perm.value = 1
								where subject like ? and '.str_replace('?', '', implode(' and ', $query)).'
								order by p.id desc LIMIT ?, ?', $user_session['group_id'], '%'.$text.'%', $posts_per_page * ($pn-1), $posts_per_page, true);

		$search = $posts + $topics;

		if ($search) {
			echo '<ul class="list-group forum">';

			foreach($search as $post) {
				if ($text) {
					$post['message'] = preg_replace('#'.str_replace(' ', '.*', preg_replace('![^a-z0-9_ 	]!i', '\\\\$0', $text)).'#mUi', '<span style="background-color:yellow">$0</span>', html_encode($post['message']));
					$post['subject'] = str_ireplace($text, '<span style="background-color:yellow">' . html_encode($text). '</span>', short($post['subject'], 40));
				}

				$r = '<span class="badge">posté ' . today($post['posted']) . ' par '. ($poster ? '<span style="color:yellow">'.html_encode($post['poster']).'</span>' : html_encode($post['poster'])) . '</span>';

				$r .= '<legend><small>'.$post['fname'].'</small> → <a href="'.create_url('forums', ['pid'=>$post['id']], '#msg'.$post['id']) . '">' . $post['subject'] . '</a></legend>';
				$r .=  bbcode2html($post['message'], false).'<br>';
				echo '<li class="list-group-item">' . $r . '</li>';
			}

			echo '</ul>';
			if (count($posts) == $posts_per_page || count($topics) == $posts_per_page)
				$pptotal = $pn + 1;
			else
				$pptotal = $pn;

			if ($pn >= 1) {
				unset($_GET['pn']);
				echo paginator($pptotal , $pn, 10, '/?'.implode('&', array_map(function (&$v, $k) { return $v = $k.'='.urlencode($v);}, $_GET, array_keys($_GET))).'&pn=');
			}
		} else {
			$_notice = ''.__('forums.search_nothing').'';
		}
	}
}
elseif (isset($topic)) {

	$topic_read[$topic['id']] = $topic['last_post'];

	if ($topic['closed'])
		echo '<div class="alert alert-warning">'.__('forums.topic_close').'.</div>';


	if ($forum_moderator || has_permission('mod.forum_topic_move')) {
		echo '<div id="move-topic-container" class="well" hidden><div class="row"><form method="post">';
			echo '<div class="col-md-9">';
				foreach(forums_list() as $c) {
					if ($c['forums'])
						foreach($c['forums'] as $f) {
							$cats[$c['name']][$f['id']] = $f['name'];
						}
				}
				echo html_select('move-topic', $cats, $topic['forum_id']);
			echo '</div>';
			echo '<div class="col-md-3"><button class="btn btn-default" name="topic" value="' . $topic['id'] . '">'.__('forums.topic_move').'</button></div>';
		echo '</form></div></div>';
	}

	echo '<div class="panel panel-default forum">';
	echo '	<div class="panel-heading">';

	echo '<div class="pull-right"><form method="post"> ';
		if ((!$topic['closed'] || $forum_moderator || has_permission('mod.forum_topic_close')) && has_permission('forum.write', $forum['id']))
			echo	'<a href="'.create_url('forums', ['topic'=>$topic['id'],'compose'=>1]).'">Répondre</a> ';

		if ($forum_moderator || has_permission('mod.forum_topic_close')) {
			if ($topic['closed']) echo '<button class="btn btn-primary btn-xs" name="closed" value="0" title="'.__('forums.topic_btn_unlock').'"><i class="fa fa-unlock"></i></button> ';
			else echo '<button class="btn btn-primary btn-xs" name="closed" value="1" title="'.__('forums.topic_btn_lock').'"><i class="fa fa-lock"></i></button> ';
		}

		if ($forum_moderator || has_permission('mod.forum_topic_stick')) {
			if ($topic['sticky']) echo '<div class="btn-group"><button class="btn btn-info btn-xs active" name="sticky" value="0" title="'.__('forums.topic_btn_unpin').'"><i class="fa fa-thumb-tack"></i></button></div> ';
			else echo '<button class="btn btn-info btn-xs" name="sticky" value="1" title="'.__('forums.topic_btn_pin').'"><i class="fa fa-thumb-tack"></i></button> ';
		}

		if ($forum_moderator || has_permission('mod.forum_topic_redirect')) {
			echo '<a class="btn btn-warning btn-xs" name="redirect-topic" href="'.create_url('forums', ['edit'=>$topic['first_post_id']]).'" title="'.__('forums.topic_btn_shortcut').'">'.
				 '<i class="fa fa-external-link"></i></a> ';
		}

		if ($forum_moderator || has_permission('mod.forum_topic_move')) {
			echo '<button type="button" class="btn btn-warning btn-xs" name="move-topic" value="'.$topic['id'].'" title="'.__('forums.topic_btn_move').'" onclick="$(\'#move-topic-container\').toggle();">'.
				 '<i class="fa fa-location-arrow "></i></button> ';
		}

		if ($forum_moderator || has_permission('mod.forum_topic_delete')) {
			echo '<button class="btn btn-danger btn-xs" name="delete-topic" value="'.$topic['id'].'" title="'.__('forums.topic_btn_delete').'" onclick="return confirm(\'Supprimer la discussion et ses messages?\');">'.
				 '<i class="fa fa-times"></i></button> ';
		}
	echo '		</form></div>';
	
	if ($topic['redirect'])
		echo'<i class="fa fa-location-arrow" title="'.__('forums.state_external').'"></i> ';
	
	if ($topic['closed'])
		echo '<i class="fa fa-lock" title="'.__('forums.state_closed').'"></i> ';
	
	if ($topic['sticky'])
		echo'<i class="fa fa-thumb-tack" title="'.__('forums.state_pinned').'"></i> ';

	echo '		' . html_encode($topic['subject']);
	echo '	</div>';

	$posts = Db::QueryAll('select p.*, a.avatar, a.username, a.ingame, g.name as gname, g.color, a.registered, a.num_posts, a.country, a.email, b.reason as ban_reason
						   from {forums_posts} as p
						   left join {users} as a on a.id = p.poster_id
						   left join {groups} as g on g.id = a.group_id
						   left join {banlist} as b on a.username like b.rule and b.type = "username"
						   where topic_id = ? order by id asc LIMIT ?, ?', $topic['id'], $posts_per_page * ($pn-1), $posts_per_page, true);

	if ($posts) {
		plugins::trigger('forum_before_posts_loop', array(&$posts));
		require_once ROOT_DIR . '/evo/misc/user_agent.php';

		echo '<table class="table table-lists forum-topic"><tbody>';
		foreach($posts as $post) {
			echo '<tr id="msg'.$post['id'].'" class="topic-message">';
				echo '<td><a href="'.create_url('user', ['id'=>$post['poster_id']]).'"><strong style="color:'.$post['color'].'">' . html_encode($post['username'] ?: $post['poster']). '</strong><br>';
				echo get_avatar($post). '</a>';
				echo '<p>';
				echo '<span class="label label-primary label-usergroup">' . $post['gname'] . '</span>';

				if ($post['ban_reason']) {
					$reason = has_permission('mod.') ? html_encode($post['ban_reason']) : ''.__('forums.state_member_banned').'';
					echo '​<span class="label label-danger" title="'.$reason.'">'.__('forums.member_banned').'</span>';
				}

				echo '</p>';
				echo '<dd class="user-meta">';

				if($post['country'])
					echo ''.__('forums.member_country').': <span>'. $_countries[$post['country']].' &nbsp;<img style="position:relative;top:-1px;" src="' . get_asset('img/flags/'.strtolower($post['country']).'.png') . '"> '. '</span><br>';

				if(isset($post['username'])) {
					echo ''.__('forums.member_register').' : <span>' . date('Y-m-d', $post['registered']). '</span><br>';
					echo ''.__('forums.member_numpost').' : <span>' . $post['num_posts']. '</span><br>';
				} else {
					if ($post['poster_id']) {
						echo ''.__('forums.member_deleted').'';
					} else {
						echo ''.__('forums.member_guest').'<br>';
					}
				}
				echo '</dd>';

				echo '<dd class="usercontacts">';
				echo get_useragent_icons($post['user_agent']);
				echo '</dd>';

				echo '</td>';
				echo '<td>';
					echo 	'<div class="header">&nbsp;<a href="'.create_url('forums', ['pid'=>$post['id']], 'msg'.$post['id']).'">' . today($post['posted'], true).'</a>';
					if ($post['edited']) echo '<small> <i>(Édité '.today($post['edited'], true).')</i></small>';
					echo '<span class="pull-right">';

					if ( (has_permission() && $user_session['id'] == $post['poster_id']) || has_permission('mod.') ) {
						echo '&nbsp;<a onclick="return confirm(\'Sur?\');" href="'.create_url('forums', ['topic'=>$post['topic_id'],'delete-post'=>$post['id']]).'" style="color:red" title="Supprimer"><i class="fa fa-trash-o"></i></a> ';
						echo '&nbsp;<a href="'.create_url('forums', ['edit'=>$post['id']]).'" title="Editer"><i class="fa fa-pencil"></i></a> ';
					}

					echo '&nbsp;<a href="" onclick="return report('.$post['id'].');" title="Signaler"><i class="fa fa-flag-o"></i></a> ';

					echo '&nbsp;<a href="'.create_url('forums', ['topic'=>$post['topic_id'],'quote'=>$post['id']]).'" title="Citer"><i class="fa fa-quote-right"></i></a>';

					echo '&nbsp;</span></div>';
					echo '<div class="comment">' . rewrite_links(bbcode2html($post['message']), true) . '</div>';
					plugins::trigger('forum_display_post_signature', array($post));
				echo '</td>';
			echo '</tr>';
		}
		$post = null;
		echo '</tbody></table>';
	} else {
		echo 'Cette discussion est vide.';
	}
	echo '</div>';
}
elseif (isset($forum)) {

	echo '<div class="panel panel-default">';
	echo '	<div class="panel-heading">';
	echo '		<div class="pull-right"><a href="'.create_url('forums',['id'=>$forum['id'],'compose'=>1]).'">Nouvelle Discussion</a></div>';
	echo 			 html_encode($forum['name']);
	echo '	</div>';
	if ($forum['description'])
		echo '	<div class="panel-body">'.$forum['description'] . '</div>';

	$topics = Db::QueryAll('select * from {forums_topics} where forum_id = ? order by sticky desc, last_post desc LIMIT ?, ?', $forum['id'], $topics_per_page * ($pn-1), $topics_per_page, true);
	
	if ($topics) {
		plugins::trigger('forum_before_topics_loop', array(&$topics));

		$can_redirect = ($forum_moderator || has_permission('mod.forum_topic_redirect'));

		echo '<table class="table table-lists forum-topics">';
		echo '<thead><tr><td colspan="2">Discussions</td><td>Messages</td><td>Vues</td><td>Dernier message</td></tr></thead>';
		echo '<tbody>';

		foreach($topics as $topic) {
			$class = 'topic ';
			
			if ($topic['last_post'] > $last_visit && (!isset($topic_read[$topic['id']]) || $topic_read[$topic['id']] != $topic['last_post']))
				$class .= 'new ';

			if ($topic['sticky'])
				$class .= 'sticky ';

			if ($topic['closed'])
				$class .= 'closed ';

			echo '<tr class="'.$class.'">';

			echo '<td class="topic-icon">';
				$icons = 0;
				if ($topic['redirect']) {
					$icons++;
					echo'<i class="fa fa-location-arrow secondary" title="Lien externe"></i> ';
				}
				if ($topic['closed']) {
					$icons++; 
					echo '<i class="fa fa-lock secondary" title="Discussion close"></i> ';
				}
				if ($topic['sticky']) {
					$icons++;
					echo'<i class="fa fa-thumb-tack secondary" title="Discussion épingler"></i> ';
				}
				
				if (!$icons) echo '<i class="fa fa-angle-right primary"></i> ';
			echo '</td>';

			echo '<td>';

			if ($topic['redirect'] && $can_redirect) {
				echo '<a href="'.create_url('forums', ['pid'=>$topic['first_post_id'],'force'=>1]).'" title="Ignorer la redirection"><i class="fa fa-eye" style="color:red;font-size:100%;"></i></a> ';
			}

			if ($topic['sticky'] && ($forum_moderator || has_permission('mod.forum_topic_stick'))) {
				echo '<div style="display:inline-block"><form method="post"><div class="btn-group" style="display:inline-block" title="Modifier l\'ordre d\'affichage">'.
						'<button class="btn btn-xs" name="sticky" value="+1"><i class="fa fa-arrow-up"></i></button>'.
						'<button class="btn btn-xs" name="sticky" value="-1"><i class="fa fa-arrow-down"></i></button>'.
						'<input type="hidden" name="topic" value="' . $topic['id'] . '">'.
					  '</div></form></div>';
			}
		
			$prefix = $topic['redirect'] ? '<em>Lien: </em>' : '';
			echo $prefix.' <a href="'.create_url('forums', ['id' => $topic['forum_id'], 'topic'=>$topic['id']]) . '">' . html_encode($topic['subject']). '</a>';

			echo '<br><small>par <a href="'.create_url('user', ['id'=>$topic['poster_id']]) . '">' . html_encode($topic['poster']) .'</a></small></td>';

			echo '<td class="num-posts">' . $topic['num_posts'] . '</td>';
			echo '<td class="num-views">' . $topic['num_views']. '</td>';

			echo '<td class="last-post">';

			if (!$topic['redirect']) {
				echo '<a href="'.create_url('forums', ['topic'=>$topic['id'],'pn'=>ceil($topic['num_posts']/$posts_per_page)], '#msg'.$topic['last_post_id']).'">' . today($topic['last_post'], true) . '</a><br>';
				echo '<small>par <a href="'.create_url('user', ['id' => $topic['last_poster']]) . '">' . html_encode($topic['last_poster']) .'</a></small>';
			} else {
				echo '---';
			}

			echo '</td></tr>';
		}
		$topic = null;
		echo '</tbody></table>';
	} else {
		echo 'Ce forum est vide.';
	}

	echo '</div>';
}
else {
	if ($forums = forums_list()) {
		$can_redirect = ($forum_moderator || has_permission('mod.forum_topic_redirect') || has_permission('admin.'));
		plugins::trigger('forum_before_forums_loop', array(&$forums));
		foreach ($forums as $c)
		{
			echo '<div class="panel panel-default">';
			echo '	<div class="panel-heading">' . $c['name'] . '</div>';
			echo '<table class="table table-lists forum-topics forum-forums">';
			echo '<thead><tr><td colspan="2">Forums</td><td>Sujets</td><td>Messages</td><td>Dernier message</td></tr></thead>';
			echo '<tbody>';
			foreach ($c['forums'] as $forum)
			{
				if ($forum['last_post'] > $last_visit && (!isset($topic_read[$forum['last_topic_id']]) || $topic_read[$forum['last_topic_id']] != $forum['last_post']))
					echo '<tr class="new">';
				else
					echo '<tr>';

				echo '<td class="forum-icon"><i class="fa fa-2x fa-'.$forum['icon'].'"></i></td>';

				if ($forum['redirect']) {
					echo '<td class="forum-name"><em>Lien: </em>';
					echo '<a href="'.$forum['redirect'].'">' . html_encode($forum['name']) . '</a>';
					if ($can_redirect) {
						echo ' <a href="'.create_url('forums', ['id'=>$forum['id'], 'force'=>'1']).'" title="Ignorer la redirection"><i class="fa fa-eye"></i></a>';
					}
					echo '<br><span class="forum-description">' . $forum['description'] . '</span></td>';
					echo '<td class="num-posts">-</td>';
					echo '<td class="num-posts">-</td>';
					echo '<td class="last-post">---</td>';
				} else {
					echo '<td class="forum-name">';
					echo '<a href="'.create_url('forums', $forum['id']).'">' . html_encode($forum['name']) . '</a>';
					echo '<br><span class="forum-description">' . $forum['description'] . '</span></td>';
					echo '<td class="num-posts">' . $forum['num_topics'] . '</td>';
					echo '<td class="num-posts">' . $forum['num_posts']  . '</td>';

					echo '<td class="last-post">';

					if ($forum['tredirect'])
						echo '<em>Lien: </em><a href="'.create_url('forums', ['topic'=>$forum['last_topic_id']]).'">' . short($forum['subject'], 22) . '</a><br>'.
								'<small>'. today($forum['last_post'], true) .' par <a href="'.create_url('user', ['id'=>$forum['last_poster']]).'">' . html_encode($forum['last_poster']) .'</a></small>';
					elseif ($forum['subject'] !== null)
						echo '<a href="'.create_url('forums', ['pid'=>$forum['last_post_id']], '#msg'.$forum['last_post_id']).'">' . short($forum['subject'], 28) . '</a><br>'.
								'<small>'. today($forum['last_post'], true) .' par <a href="'.create_url('user', ['id'=>$forum['last_poster']]).'">' . html_encode($forum['last_poster']) .'</a></small>';
					else
						echo 'Ce forum est vide.';

					echo '</td>';
				}
			}
			echo '</tbody></table>';
			echo '</div>';
		}
	} else {
		echo '<div class="alert alert-warning">Il n\'y a aucun forum.</div>';
	}
}



if ($ptotal > 1 && !$edit_mode) {
	$params = ['id' => $forum['id']];
	if (isset($topic)) {
		$params['topic'] = $topic['id'];
	}
	if ($ptotal > 1)
		echo paginator($ptotal, $pn, 10, create_url('forums', $params + ['pn' => '']));
	else
		echo '<br>';
}


echo '</div>'; //<div class="forum-main">
echo '</div>'; //<div id="content">


if (isset($forum) && has_permission('forum.write', $forum['id']) && ($edit_mode || (isset($topic) && !$topic['closed'])) ) {

	$can_redirect = (!isset($topic) || $topic['first_post_id'] == $post['id'])
					&& ($forum_moderator || has_permission('mod.forum_topic_redirect'));

	echo '<form method="post">';
	echo '<div class="forum-editbox panel panel-default" id="message">
			<div class="panel-heading">' . ucfirst($mode) . '</div>
			<div class="panel-body form-horizontal">';

	if (!isset($topic) || !isset($_GET['quote']) && isset($post) && $topic['first_post_id'] == $post['id']) {
		echo '<div class="form-group">
				<label class="col-sm-2 control-label" for="subject">Sujet :</label>
				<div class="col-sm-10 control">
					<input name="subject" class="form-control" type="text" maxlength="60" value="'.html_encode(_POST('subject', $subject)).'">
				</div>
			  </div>';
	}

	echo '<textarea class="form-control" name="message" style="width:100%; height:'.($post || _GET('compose') ? '325px' : '250px').';" placeholder="Composer un message...">'.html_encode(_POST('message', $message)).'</textarea>';

	echo '<div style="padding:5px">
				<label><input type="checkbox" name="subscribe" value="1" '.($sub?'checked':'').'> S\'abonner à cette discussion.</label>
		  </div>';

	echo '<div class="text-center">
			<button class="btn btn-primary" type="submit" name="compose" value="1">Envoyer le message</button>';
	echo '</div>';

	if ($can_redirect) {
		echo '
		<div class="text-right">
			<button class="btn btn-default" type="button"
				  onclick="$(this).button(\'toggle\');$(\'#redirectForm\').toggle().find(\'input\').val(\'\');"
				  >Redirection...</button>
		</div>
		<div class="form-group" id="redirectForm" hidden>
			<label class="col-sm-2 control-label" for="redirect">Redirection:</label>
			<div class="col-sm-10 control">
				<input name="redirect" class="form-control" placeholder="Exemple: https://google.ca" type="text" maxlength="255" value="'.html_encode(_POST('redirect', $topic['redirect'])).'">
				Lorsqu\'un visiteur cliquera sur votre topic il sera redirigé sans voir votre message.
			</div>
		</div>
		';
	}


	echo '</div></div>';
	echo '</form>';
	echo '<script src="' . get_asset('/scripts/editors.php') . '"></script>';
	echo '<script>display_sceditor();</script>';
}

echo '</div>'; //<div class="forum-wrapper">
?>
<script>
$(function() {setTimeout(function() {
	var userlisttags = function(e) {
		var range = $('textarea').sceditor('instance').getRangeHelper().selectedRange();

		if (!range.endContainer.data) {
			autocomplete();
			return;
		}

		var text = range.endContainer.data.substr(0, range.endOffset);
		var tag = text.match(/(^|[^-a-z0-9_])@[-a-z0-9_\u202F]*$/i);

		if (!tag) {
			autocomplete();
			return;
		}

		tag = tag[0].substr(tag[0].indexOf('@'));

		var outerCoord = $('.sceditor-container > iframe').offset();
		var innerCoord = range.getClientRects()[0];

		if (!innerCoord) {
			var span = document.createElement("span");
			span.appendChild( document.createTextNode("\u200b") );
			range.insertNode(span);
			innerCoord = span.getClientRects()[0] || {top:0, left:0};
			span.parentNode.removeChild(span);
		}

		autocomplete(function(user) {
				var start = text.length - tag.length;
				var trail = range.endContainer.data && range.endContainer.data
										.substr(range.endOffset).match(/.*?(([^-a-z0-9_\u202F])|$)/);
				var end = range.endOffset + trail[0].length;

				if (end <= 0) end = range.endOffset;

				user = user.replace(' ', '\u202F');

				range.endContainer.data = text.substr(0, start + 1) + user + (trail[2] || '\u00A0') +
													range.endContainer.data.substr(end);

				if ($.sceditor.ie) {
					$('textarea').sceditor('instance').focus();
				} else {
					var newrange = document.createRange();
					newrange.setEnd(range.endContainer, start + user.length + 2);
					$('textarea').sceditor('instance').getRangeHelper().selectRange(newrange);
					$('textarea').sceditor('instance').focus();
				}
			},
			{query: tag.substr(1)},
			{ top: outerCoord.top + innerCoord.top + (innerCoord.height || 16),
			  left: outerCoord.left + innerCoord.left + innerCoord.width }
		);
	};

	$('textarea').sceditor('instance').getContentAreaContainer().contents().click(userlisttags);
	$('textarea').sceditor('instance').keyDown(function(e) {
		switch(e.keyCode) {
			case 9: //Tab
			case 13: //Enter
				autocomplete.select() && e.preventDefault();
				break;
			case 38: //up
				autocomplete.prev() && e.preventDefault();
				break;
			case 40: //down
				autocomplete.next() && e.preventDefault();
				break;
			default:
				console.log(e);
				setTimeout(userlisttags, 50);
		}
	});
}, 600)});
</script>