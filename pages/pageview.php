<?php defined('EVO') or die(__('403.msg'));

$page = null;

if ($id = _GET('id')) {
	$sql = 'SELECT o.*, p.*, a.username
			FROM {pages} AS p
			JOIN {pages_revs} as o ON o.page_id = p.page_id AND o.revision = ' . ((int)_GP('rev') ?: 'p.pub_rev') . '
			LEFT JOIN {users} as a ON a.id = o.author
			WHERE '. (ctype_digit($id) ? 'p.page_id = ? ' : 'p.slug = ?');
	
	$page = Db::Get($sql, $id);
	
	if (!$page) {
		$slug = Db::QuerySingle('select p.slug, max(r.id) from {pages} as p JOIN {pages_revs} as r USING(page_id) where r.slug = ? or r.slug = ?', $id, basename($id));
		if ($slug && $slug !== $id) {
			header('Location: ' . Site('url') . '/' . $slug);
			exit;
		}
	}
}


if (!$page) { /* Page not found */
	if ((include 'pagelist.php') === true) return;
	@header('HTTP/1.1 404 Not Found');
	throw new Warning('Cette page n\'existe pas', 'Veuillez vérifier votre url');
}

define('CACHE_PAGE', $page['slug']);

$_title = $page['title'];
$article = &$page;
$home = false;

$article['page_link'] = create_url($article['slug'] ?: $article['page_id']);

/* Hit counter */
isset($_SESSION['pageview'][$page['page_id']]) or Db::Exec('update {pages} set views = views + 1 where page_id = ?', $page['page_id']);
@$_SESSION['pageview'][$page['page_id']] = 1;




/* Nouveau commentaire */
if (isset($_POST['new_comment'], $_POST['commentaire']) && _POST('commentaire') !== '' && has_permission('comment_send')) {
	if (preg_match('#^[^@\n<>]+$#', _POST('name'))) {
		$user_session['username'] = _POST('name');
	}
	
	if (_POST('email')) {
		if (preg_match(PREG_EMAIL, _POST('email'))) {
			$user_session['email'] = _POST('email');
		} else {
			$_warning = 'Email invalide';
		}
	}
	
	if ($_warning) {
		
	} elseif (!has_permission() && (empty($_POST['verif']) || (int)$_POST['verif'] !== ($page['id'] * 5))) {
		$_warning = 'Verification code mismatch!';
	} else {
		Db::Insert('comments', array(
			'page_id'     => $page['page_id'],
			'user_id'     => $user_session['id'],
			'message'     => $_POST['commentaire'],
			'posted'      => time(),
			'poster_name' => $user_session['username'],
			'poster_email'=> $user_session['email'],
			'poster_ip'   => $_SERVER['REMOTE_ADDR'],
			'state'       => 0
		));
		Db::Exec('update {pages} set comments = comments + 1 where page_id = ?', $page['page_id']);
		$_success = 'Commentaire enregistré!';
		log_event($user_session['id'], 'user', 'Commentaire sur la page #'.$page['page_id']. ': '.substr($_POST['commentaire'], 0, 32).'.');
	}
}



if (_POST('report')) {
	Db::Insert('reports', array(
		'user_id' => $user_session['id'], 
		'type'    => 'comment',
		'rel_id'  => _POST('pid'),
		'reason'  => _POST('report'), 
		'reported'=>  time(),
		'user_ip' => $_SERVER['REMOTE_ADDR'],
	));
	log_event($user_session['id'], 'user', 'Commentaire flaggé sur la page: '.$page['title']);
	exit;
}


/* comments */
$comments = Db::QueryAll ('SELECT coms.*, g.name as gname, g.color as gcolor, acc.username, acc.avatar, acc.ingame, acc.email FROM {comments} AS coms LEFT JOIN {users} AS acc ON acc.id = coms.user_id LEFT JOIN {groups} AS g ON acc.group_id = g.id WHERE coms.page_id = ? ORDER BY coms.posted ASC', $page['page_id']);
$page['comments'] = count($comments);



/* Page format */

switch($page['format']) {
	case 'markdown':
		$md = new \Parsedown\ParsedownExtra;
		$page['content'] = $md->text($page['content']);
		break;
		
	case 'bbcode':
		$page['content'] = bbcode2html($page['content']);
		break;
		
	case 'text':
		$page['content'] = nl2br(emoticons($page['content']));
		break;
		
	case 'html':
	default:
}


/* links */
$page['content'] = rewrite_links($page['content'], true);
$page['author_link'] = create_url('user', $page['username']);

/* page break */
$page['abstract'] = '';


/* table of contents */
if ($page['display_toc'] && preg_match_all('#<h[1-3][^>]*>(.+)</h[1-3]>#miU', str_replace('&nbsp;', '', $page['content']), $m)) {
	$toc = '<div id="table-of-contents"><p>Contenu</p><ul>';
	$i = 0;
	
	foreach($m[1] as $j => $h) {
		$h = trim(strip_tags($h));
		if (empty($h)) continue;
		$id = preg_replace('#[^a-zA-Z0-9]#', '', $h);
		$search[] = $m[0][$j];
		$replacement[] = '<a name="' . $id . '"></a>' . $m[0][$j];
		$toc .= '<li><a href="#' . $id . '">' . ++$i . '. ' . $h . '</a></li>';
	}
	
	$toc .= '</ul></div>';
	
	if ($i > 0) {
		$page['content'] = $toc . str_replace($search, $replacement, $page['content']);
	}
}



/* Gallery */



/* bread brumbs */
if ($page['category'])
	$_crumbs[] = '<a href="'.create_url('category/'.preg_replace('/[^a-z0-9-]/i', '-', strtolower($page['category']))). '">' . $page['category'] . '</a>';
else
	$_crumbs[] = '<a>' . ucfirst($page['type']) . '</a>';
$_crumbs[] = '<a href="' . create_url($page['slug'] ?: $page['page_id']). '">' . html_encode($page['title']) . '</a>';


plugins::trigger('page_display', array(&$page));


/* page content display */
switch($page['type']) {
	case 'article':
		include_template('pages/page_article.php', ['article' => $page, 'home' => $home]);
		break;
	case 'page-raw':
		include_template('pages/page_raw.php', ['page' => $page, 'home' => $home]);
		exit;
	case 'page-wide':
	case 'page':
	default:
		include_template('pages/page_page.php', ['page' => $page, 'home' => $home]);
		break;
}

$_body_class = 'page-type-' . preg_replace('/^page-/', '', $page['type']);


/* display comments */
if ($page['allow_comments']) {
	$can_post_comment = has_permission('comment_send') && $page['allow_comments'] != 2;
	$captcha_code = str_pad($page['id'] * 5, 4, '0', STR_PAD_LEFT);
	include_template('pages/page_comments.php', compact('comments', 'can_post_comment', 'page', 'captcha_code'));
}
