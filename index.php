<?php
try {
	require_once 'evo/common.php';
	
	define('REQUESTED_PAGE', '/' . trim(fixpath(_GET('p', _GET('', Site('frontpage')))), '/'));
	
	try {
		$routes = plugins::routes() + [
			'/page/(?<pn>[0-9]+)' 		=> function($e) { return 'pages/home.php'; },
			'/category/(?<id>.+)' 		=> function($e) { return 'pages/pagelist.php'; },
			'(?<path>/upload/.+)' 		=> function($e) { return 'pages/getfile.php'; },
			'/(?<p>[^/]+)/(?<id>.+)' 	=> function($e) { return 'pages/'.$e['p'].'.php'; },
			'/(?<p>[^/]+)' 				=> function($e) { return 'pages/'.$e['p'].'.php'; },
			'/(?<id>.+)' 				=> function($e) { return 'pages/pageview.php'; },
			'/' 						=> function($e) { return 'pages/home.php'; },
		];
	
		foreach($routes as $route => $callback) {
			if (preg_match('#^'.$route.'$#', REQUESTED_PAGE, $m) && file_exists($_file = $callback($m))) {
				define('REQUESTED_SCRIPT', $_file);
				$_GET = $m + $_GET;
				include $_file;
				break;
			}
		}
	}
	catch(Warning $e) {
		ob_clean();
		include_template('warning.php', [
			'_title' => $e->getTitle() ?: $_title,
			'_message' => $e->getMessage(), 
		]);
	}

	if (IS_AJAX) return;

	$_title  = $_title ?: ucwords(substr(REQUESTED_PAGE, 1));
	$_title .= ($_title ? ' - ' : '') . Site('name');
	$_content = ob_get_contents(); ob_clean();
	
	if (defined('REQUESTED_SCRIPT')) {
		$_body_class .= ' page-' . basename(REQUESTED_SCRIPT, '.php');
	}
	
	include_template(
		$_template ?: 'main.php', 
		compact('_notice', '_warning', '_success', '_crumbs', '_tpl_vars', '_body_class', '_content', '_title')
	);
}
catch(PDOException $e) {
	@ob_end_clean();
	include 'templates/exception.php';
}
catch(Exception $e) {
	@ob_end_clean();
	Db::$throwException = false;
	include get_template('exception.php');
}
