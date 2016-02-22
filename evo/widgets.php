<?php
/*
 * Evo-CMS
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */

class widgets
{
	/**
	 *  Display a list of recent pages
	 */
	static function page_list($num_pages = 5, $truncate = 28)
	{
		$pages = get_pages($num_pages, 0, array('select' => 'r.title, p.slug, p.pub_date'));

		echo '<div class="widget pages-widget">';

		if ($pages) {
			echo '<strong>Pages récentes:</strong>';
			echo '<ul>';
			foreach($pages as $page) {
				echo '<li><a href="' . create_url($page['slug']) . '"
				title="' . html_encode($page['title']) . '">' .
				html_encode(short($page['title'], $truncate)) . '</a><br><small>' . today($page['pub_date']) . '</small></li>';
			}
			echo '</ul>';
		} else {
			echo 'Rien à afficher';
		}
		echo '</div>';
	}


	/**
	 *  Display a list of recent comments
	 */
	static function recent_comments($num_comments = 5, $truncate = 75)
	{
		$comments = Db::QueryAll('SELECT * from {comments} JOIN {pages} USING(page_id) ORDER BY id DESC LIMIT 0, ?', $num_comments);

		echo '<div class="widget comments-widget">';

		if ($comments) {
			echo '<strong>Commentaires récents:</strong>';
			echo '<ul>';
			foreach($comments as $comment) {
				echo '<li><em>' . html_encode(short($comment['message'], $truncate)) . '</em><br>
						<a href="' . create_url($comment['slug'] . '#msg' . $comment['id']) . '">' .
						html_encode(short($comment['slug'], 30)) . '</a><br><small>' . today($comment['posted']) . '</small></li>';
			}
			echo '</ul>';
		} else {
			echo 'Rien à afficher';
		}
		echo '</div>';
	}


	/**
	 *  Display a list of page categories and the number of pages in each
	 */
	static function categories()
	{
		$categories = Db::QueryAll('SELECT category, count(*) as cnt from {pages} WHERE pub_date > 0 GROUP BY category', true);

		echo '<div class="widget catlist-widget">';

		if ($categories) {
			echo '<strong>Catégories:</strong>';
			echo '<ul>';
			foreach($categories as $cat) {
				if (empty($cat['category'])) {
					echo '<li>Non classé<br><small>' . plural($cat['cnt'], 'page') . '</small></li>';
				} else {
					echo '<li><a href="' . create_url('category/' . strtolower(urlencode(str_replace(' ', '-', $cat['category'])))) . '">' .
							html_encode($cat['category']) . '</a><br><small>' . plural($cat['cnt'], 'page') . '</small></li>';
				}
			}
			echo '</ul>';
		} else {
			echo 'Rien à afficher';
		}
		echo '</div>';
	}


	/**
	 *  Returns a branch from the menu, meant to be used by menu()
	 */
	static function menu_branch($tree, $id = 0)
	{
		if (!isset($tree[$id]) || !$tree[$id]) return;

		$current_page = defined('REQUESTED_PAGE') ? trim(REQUESTED_PAGE, '/') : '';

		$r = '<ul>';

		foreach ($tree[$id] as $menu) {
			empty($menu['slug']) or $menu['link'] = $menu['slug'];
			empty($menu['redirect']) or $menu['link'] = $menu['redirect'];

			if (!empty($menu['link']) && trim($menu['link'], '/') === $current_page) {
				$r .= '<li class="active">';
			} else {
				$r .= '<li>';
			}

			if ($menu['link'] != '')
				$r .= '<a href="'.html_encode(strpos($menu['link'], '/') !== false ? $menu['link'] : create_url($menu['link'])).'">';

			if ($menu['icon'])
				$r .= '<i class="fa fa-fw fa-'.$menu['icon'].'"></i> ';

			$r .= html_encode($menu['name']);

			if ($menu['link'] != '')
				$r .= '</a>';

			if (isset($tree[$menu['id']])) {
				$r .= self::menu_branch($tree, $menu['id']);
			}

			$r .= '</li>';
		}
		$r .= '</ul>';
		return $r;
	}


	/**
	 *  Display the main menu
	 */
	static function menu()
	{
		echo '<div id="menu">' . self::menu_branch(get_menu_tree(), 0) . '</div>';
	}


	/**
	 *  Print SQL queries in a fancy way for debug purposes
	 */
	static function print_queries(array $queries, $return = false)
	{
		$r = '';
		foreach($queries as $i => $query) {
			$q = preg_replace('#\s+#mu', ' ', $query['query']);
			$q = preg_replace_callback(
			array(
				'#(?<string>("[^"]*"|\'[^\']*\'))#mui',
				'#(?<symbol>\s[-()<>=\*+\?\s]+)\s#mui',
				'#(?<name>\s?`?[_a-z0-9]+`?\.|`[_a-z0-9]+`)#mui',
				'#(^|[^a-z0-9])(?<function>[_a-z]+)\(#mui',
				// '#(^|[^a-z0-9])(?<newline>SELECT|INSERT|REPLACE|UPDATE|UNION|RIGHT\sJOIN|LEFT\sJOIN|JOIN|FROM|ORDER\sBY|LIMIT|VALUES|WHERE|GROUP\sBY)([^a-z0-9])#mui',
				'#(?<newline>SELECT|SELECT\s*DISTINCT|INSERT\s*INTO|REPLACE\s*INTO|UPDATE|UNION|RIGHT\s*JOIN|LEFT\s*JOIN|JOIN|FROM|ORDER\sBY|LIMIT|VALUES|WHERE|GROUP\sBY)#mui',
				'#(^|[^a-z0-9])(?<inline>ON|AS|IS NULL|IS|IN|CASE\s*WHEN|THEN|ELSE|END\s*AS|END|LIKE|NULL|AND|OR|SET|DESC|ASC)([^a-z0-9]|$)#mui',
			),

			function($m) {
				if (isset($m['newline'])) {
					return "\n" . '<span style="color:#708">' . strtoupper($m['newline']) . ' </span> ';
				} elseif(isset($m['inline'])) {
					return $m[1] . '<span style="color:#708">' . strtoupper($m['inline']) . '</span>' . $m[3];
				} elseif(isset($m['symbol'])) {
					return ' <span style="color:#FF00FF">' . trim($m['symbol']) . ' </span> ';
				}elseif(isset($m['function'])) {
					return ' <span style="color:#FF794C"> ' . strtoupper(trim($m['function'])) . '</span>(';
				}elseif(isset($m['name'])) {
					return '<span style="color:#05A">' . $m['name'] . '</span>';
				}elseif(isset($m['string'])) {
					return '<span style="color:#D90000"> ' . html_encode($m['string']) . '</span> ';
				}
			}, $q.' ');

			$r .= '<div class="panel panel-'. ($query['errno'] ? 'danger' : 'default')  .' text-left sql-query">';
			$r .= '  <div class="panel-heading">';

			if ($query['errno'])
				$r .= '  	<div class="panel-title">' . $query['errno'] . ' ' . $query['error'] . '</div>';

			$r .= $i.'. ' . str_replace(ROOT_DIR, '', $query['trace']['file']) . ' #' . $query['trace']['line'];

			$r .= '  </div>';
			$r .= '  <div class="panel-body">' . nl2br(trim($q), false) . '</div>';
			$r .= '  <div class="panel-footer clearfix">';
			$r .= '		<div style="float:left; width:50%;">Params: ' . implode(' , ', $query['params']) . '</div>
							<div style="float:left; width:16%;">Affected Rows: ' . $query['affected_rows'] . '</div>
							<div style="float:left; width:9%;">Fetch: ' . $query['fetch'] . '</div>
							<div style="float:left; width:11%;">Insert id: ' . $query['insert_id'] . '</div>
							<div style="float:left; width:14%;">Time: ' . round($query['time'], 6) . '</div>';
			$r .= '</div>';
			$r .= '</div>';
		}
		if ($return) return $r; else echo $r;
	}
}
