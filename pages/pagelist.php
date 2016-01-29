<?php defined('EVO') or die(__('403.msg'));

$pages = Db::QueryAll('select r.*, p.* from {pages} as p
					   join {pages_revs} as r on r.page_id = p.page_id and status="published"
					   where pub_date > 0 and category LIKE ?
					   order by p.pub_date DESC
					   ', str_replace('-', '_', basename(REQUESTED_PAGE)));

if ($pages) {
	$categorie = $pages[0]['category'];
	$_title = ucwords($pages[0]['category']);
	$_body_class = 'page-pagelist';
	foreach($pages as &$page) {
		$page['link'] = $page['redirect'] ?: create_url($page['slug']);
		if (preg_match('/<img[^>]+src="([^">]+)"/', $page['content'], $m)) {
			$page['image'] = $m[1];
		} else {
			$page['image'] = '';
		}
	}
	
	include_template('pages/pagelist.php', compact('pages', 'categorie', 'page'));
	return true;
}