<?php
/*
 * Evo-CMS
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>.
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 *
 *
 * This page is responsible to include all essential scripts and establish user session
 * This page should be included by third parties to verify auth instead of common.php.
 */

define('EVO', 1);
define('ROOT_DIR', realpath(__DIR__.'/..'));
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

set_include_path(ROOT_DIR . '/evo/libs' .  PATH_SEPARATOR .  ROOT_DIR . '/evo');

if (file_exists(ROOT_DIR.'/config.php')) {
	require ROOT_DIR.'/config.php';
} else {
	die ('Le fichier de configuration n\'a pas &eacute;t&eacute; trouv&eacute; ! D&eacute;sirez-vous ex&eacute;cuter le script d\'<a href="install/">installation</a> ?');
}

error_reporting(defined('DEBUG') ? E_ALL : E_ERROR | E_PARSE);

require_once 'definitions.php';
require_once 'version.php';
require_once 'exceptions.php';
require_once 'functions.php';
require_once 'widgets.php';
require_once 'Database/database.php';
require_once 'Database/db.'.$db_type.'.php';

spl_autoload_register(function ($className)
{
    if (strpos($className, '\\', 1) === false) {
		if (!stream_resolve_include_path($className . '.php')) {
			if (stream_resolve_include_path(strtolower($className) . '.php')) { // We should probably not do that
				return require strtolower($className) . '.php';
			}
			if (stream_resolve_include_path($className . '/' . $className . '.php')) { // We should certainly not do that
				return require $className . '/' . $className . '.php';
			}
		}
	}

	$path = str_replace(array('\\','_'), DIRECTORY_SEPARATOR, $className) . '.php';
	require $path;
});

class_alias ('Translation\Lang', 'lang');

try {
	Db::Connect($db_host, $db_user, $db_pass, $db_name, $db_prefix);
} catch (PDOException $e) {
	die('Erreur connexion SQL: ' . $e->getMessage());
}

if (Site('database.version') < DATABASE_VERSION) {
	if (_GET('upgrade')) {
		require_once ROOT_DIR . '/install/upgrade.php';
	} else {
		die('Votre base de donn&eacute;es doit &ecirc;tre mise &agrave; jour, <a href="?upgrade=1">cliquez ici</a> pour proc&eacute;der.');
	}
}

if (empty($_timezones[Site('timezone')])) { // Needs to be done here, otherwise we'd override cookie_login
	Site('timezone', 'UTC', true);
}
date_default_timezone_set($_timezones[Site('timezone')]);

Lang::setTranslator(
	new Translation\Translator(Site('language') ?: 'french', ['english'], ROOT_DIR . '/evo/languages')
);

if (Site('plugins')) {
	Plugins::load(Site('plugins'))[j];
}

cookie_login();
has_permission() && error_reporting(E_ALL);

if ($ban = check_banlist()) {
	cookie_destroy();
	throw new Warning(
		'Vous avez été banni du '.date('Y-m-d à H:i', $ban['created']).' jusqu\'au '.($ban['expires'] ? date('Y-m-d à H:i', $ban['expires']) : 'jour du jugement dernier').'!',
		html_encode($ban['reason']) . 
		'<p><strong>Vous pouvez nous <a href="'.create_url('contact').'">contacter</a> s\'il s\'agit d\'une erreur.</strong></p>'
	);
}

Db::$queryLogging = has_permission('admin.sql');
