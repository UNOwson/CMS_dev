<?php
/*
 * Evo-CMS
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>.
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 *
 *
 * This file includes csrf protection and post-processing such as cache and link rewriting.
 *
 */

require_once 'boot.php';

$_warning = $_notice = $_success = '';
$_title = $_body_class = $_content = null;
$_tpl_vars = $_crumbs = [];
$_template = 'main.php';

//We can't trust cstf if cache is active and user logged out
if ($_POST && (has_permission() || !Site('cache')) && (!isset($_POST['csrf'], $_SESSION['csrf']) || $_POST['csrf'] !== $_SESSION['csrf'])) {
	throw new Warning('CSRF code mismatch!', 'Veuillez retourner en arrière et réessayer votre opération.');
}


if (!empty($_POST['_bot_name'])) { // honeypot for spam bots
	throw new Warning('Yeah, no.', 'Call me maybe.');
}

if (isset($_GET['_theme']) && has_permission('mod.')) {
	Site('theme', basename($_GET['_theme']), true);
} elseif (!empty($user_session['theme']) && Site('change_theme')) {
	Site('theme', $user_session['theme'], true);
}

if (!empty($_communities[Site('community_type')])) {
	$_community = $_communities[Site('community_type')];
} else {
	$_community = false;
}

ob_start(function ($b)
{
	if (!isset($_SESSION['csrf'])) {
		$_SESSION['csrf'] = sha1(time() / rand(0, 50));
	}

	$b = str_replace('</form>', '<input type="text" name="_bot_name" hidden><input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'"></form>', $b);

	if (Site('cache') && defined('CACHE_PAGE')/* && strpos($_SERVER['REQUEST_URI'], '?') === false */) {
		if ($_POST) {
			@unlink(ROOT_DIR . '/cache/' . CACHE_PAGE . '/index.html');
			rrmdir(ROOT_DIR . '/cache/_min/', true);
		} elseif (!has_permission()) {
			@mkdir(ROOT_DIR . '/cache/' . CACHE_PAGE, 0755, true);
			file_put_contents(ROOT_DIR . '/cache/' . CACHE_PAGE . '/index.html', preg_replace('!>\s+<!m', '> <', $b).'<!-- CACHE GENERATED ' . date('Y-m-d H:i:s') . '-->');
		}
	}

	return $b;
});

array_walk_recursive($_GET,  'trim');
array_walk_recursive($_POST, 'trim');
