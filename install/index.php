<?php
/* 
 * Evo-CMS Installer
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */
define('EVO', 1);
define('ROOT_DIR', realpath(__DIR__.'/..'));

error_reporting(E_ALL & ~E_STRICT);

date_default_timezone_set('UTC');

require_once '../evo/definitions.php';
require_once '../evo/libs/Database/database.php';

function post_e($key, $default = null) {
	if (isset($_POST[$key])) {
		return htmlentities($_POST[$key]);
	}
	return $default;
}

$cur_step = isset($_POST['step']) ? (int)$_POST['step'] : 0;
$steps = array('Bienvenue', 'License', 'Vérifications', 'Informations SQL', 'Configuration', 'Terminé');
$next_step = $cur_step;
$payload = isset($_POST['payload']) ? $_POST['payload'] : '';
$db_types = array();
$warning = $failed = '';

$drivers = Database::AvailableDrivers();
$db_types = array_combine($drivers, $drivers);

isset($db_types['mysql']) and $db_types['mysql'] = 'MySQL';
isset($db_types['sqlite']) and $db_types['sqlite'] = 'SQLite3';

	

if (file_exists('../config.php')) {
	$warning = 'Le fichier config.php est déjà présent, l\'application a déjà été installée !';
	$hide_nav = true;
	$cur_step = -1;
}

switch($cur_step) {
	case 0:
		$next_step = 1;
		break;
	case 1:
		$next_step = 2;
		break;
	case 2:
		$checks[] = array('Version de PHP minimale de 5.4',				$ok[] =  version_compare(PHP_VERSION, '5.4.0', '>='));
		$checks[] = array('Variable PHP register_globals à off',		$ok[] =  !ini_get('register_globals'));
		$checks[] = array('Droit d\'écriture sur le dossier racine',	$ok[] =  is_writable('../'));
		$checks[] = array('Droit d\'écriture sur le dossier upload', 	$ok[] =  is_writable('../upload/'));
		$checks[] = array('PDO MySQL ou PDO SQLite3 installé', 			$ok[] =  !empty($db_types));
		$checks[] = array('Support des Sessions', 						$ok[] =  session_start());
		
		/* Le cms peut fonctionner de façon limitée sans ces conditions: */
		$checks[] = array('Extension GD', 						 	function_exists('imagecreatetruecolor'));
		$checks[] = array('Extension Zip', 						 	class_exists('ZipArchive'));
		
		if (!in_array(false, $ok, false)) $next_step = 3; else $next_step = 1000;
		break;
	case 3:
		if (!isset($_POST['db_type']) || !isset($db_types[$_POST['db_type']])) break;
		
		$payload = array($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name'], $_POST['db_prefix'], $_POST['db_type']);
		
		try {
			require '../evo/libs/Database/db.'.strtolower($_POST['db_type']).'.php';

			Db::Connect($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name'], $_POST['db_prefix']);
			
			if (Db::TableExists('users')) {
				$warning = 'Une installation utilisant ce préfixe est déjà présente dans cette base. Elle sera détruite si vous continuez !';
			}
			$next_step = 4;
			$cur_step++;
		} catch (Exception $e) {
			$warning = $e->getMessage();
		}
		break;
	case 4:
		if (isset($_POST['email'], $_POST['admin'], $_POST['admin_pass'], $_POST['url'], $_POST['name'], $_POST['payload'])) {
			if (!preg_match('#http://.+#', $_POST['url']))
				$warning .= 'Le format de votre url est mauvais.<br>';
			if (!preg_match('#^.+@.+\..+$#', $_POST['email']))
				$warning .= 'Le format de votre email est mauvais.<br>';
			if (empty($_POST['admin']))
				$warning .= 'Vous devez saisir un nom d\'utilisateur.<br>';
			if (empty($_POST['admin_pass']) || empty($_POST['admin_pass_confirm']))
				$warning .= 'Vous devez saisir un mot de passe.<br>';
			elseif ($_POST['admin_pass_confirm'] !== $_POST['admin_pass'])
				$warning .= 'Les deux mots de passe ne sont pas identiques.<br>';
			
			if ($warning) break;
			
			$db = unserialize(base64_decode($_POST['payload']));
			$_POST['url'] = trim($_POST['url'], '/');
			try {
				require '../evo/libs/Database/db.'.strtolower($db[5]).'.php';
				Db::Connect($db[0], $db[1], $db[2], $db[3], $db[4]);
				
				$cur_step++;
				$hide_nav = true;
				
				$db_version = 1;
				
				Db::CreateTable('banlist', array(
								'id' 				=> 'increment',
								'type' 				=> 'string|16',
								'rule' 				=> 'string|64',
								'reason' 			=> 'string',
								'created'			=> 'integer',
								'expires'			=> array('integer', 0),
				), false, true);
				Db::AddIndex('banlist', 'index', array('type', 'rule'));
				Db::AddIndex('banlist', 'index', array('expires'));

				
				
				Db::CreateTable('comments', array(
								'id' 				=> 'increment',
								'page_id' 			=> 'integer',
								'user_id' 			=> 'integer',
								'message' 			=> 'text',
								'posted' 			=> 'integer',
								'poster_ip' 		=> 'string',
								'poster_name' 		=> array('string', null),
								'poster_email' 		=> array('string', null),
								'state' 			=> array('integer', 0),
				), false, true);

				
				
				Db::CreateTable('files', array(
								'id' 				=> 'increment',
								'name' 				=> 'string',
								'caption' 			=> 'string',
								'path' 				=> 'string',
								'thumbs' 			=> array('text', null),
								'type' 				=> 'string',
								'mime_type' 		=> 'string',
								'size' 				=> 'integer',
								'md5' 				=> 'string',
								'poster' 			=> 'integer',
								'posted' 			=> 'integer',
								'origin' 			=> array('string', null),
								'hits' 				=> array('integer', 0),
				), false, true);
				Db::AddIndex('files', 'index', array('md5'));
								
				
				Db::CreateTable('files_rel', array(
								'file_id' 			=> 'int',
								'rel_id' 			=> 'int',
								'rel_type' 			=> 'string',
				), false, true);
				Db::AddIndex('files_rel', 'unique', array('file_id', 'rel_id', 'rel_type'));
				

				
				Db::CreateTable('forums', array(
								'id' 				=> 'increment',
								'cat' 				=> 'integer',
								'priority' 			=> 'integer',
								'name' 				=> 'string',
								'description' 		=> 'string',
								'icon' 				=> 'string',
								'num_topics' 		=> array('integer', 0),
								'num_posts' 		=> array('integer', 0),
								'last_topic_id' 	=> array('integer', 0),
								'redirect' 			=> array('string', null),
				), false, true);
						
				
				
				Db::CreateTable('forums_cat', array(
								'id' 				=> 'increment',
								'name' 				=> 'string',
								'priority' 			=> 'integer',
				), false, true);
				
				
				
				Db::CreateTable('forums_posts', array(
								'id' 				=> 'increment',
								'topic_id' 			=> 'integer',
								'poster_id' 		=> 'integer',
								'poster' 			=> 'string',
								'poster_ip' 		=> 'string',
								'message' 			=> 'longtext',
								'posted' 			=> 'integer',
								'edited' 			=> array('integer', 0),
								'user_agent' 		=> 'string',
								'attached_files'	=> 'text',
				), false, true);
				Db::AddIndex('forums_posts', 'index', array('topic_id'));
				
				
				
				Db::CreateTable('forums_topics', array(
								'id' 				=> 'increment',
								'forum_id' 			=> 'integer',
								'poster_id' 		=> 'integer',
								'poster' 			=> 'string',
								'subject' 			=> 'string',
								'first_post_id' 	=> 'integer',
								'first_post' 		=> 'integer',
								'last_post_id' 		=> 'integer',
								'last_post' 		=> 'integer',
								'last_poster' 		=> 'string',
								'num_posts' 		=> array('integer', 0),
								'num_views' 		=> array('integer', 0),
								'sticky' 			=> array('integer', 0),
								'closed' 			=> array('integer', 0),
								'redirect' 			=> array('string', null),
				), false, true);
				Db::AddIndex('forums_topics', 'index', array('forum_id'));
			
				
				
				Db::CreateTable('friends', array(
								'id' 				=> 'increment',
								'u_id' 				=> 'integer',
								'f_id' 				=> 'integer',
								'state' 			=> array('integer', 0)
				), false, true);
				Db::AddIndex('friends', 'unique', array('u_id', 'f_id'));
				
				
				
				Db::CreateTable('groups', array(
								'id' 				=> 'increment',
								'name' 				=> 'string',
								'internal'	 		=> array('string', null),
								'color' 			=> 'string',
								'priority' 			=> array('integer', 100)
				), false, true);
				
				
				
				Db::CreateTable('history', array(
								'id' 				=> 'increment',
								'e_uid' 			=> 'integer',
								'a_uid' 			=> 'integer',
								'ip' 				=> 'string',
								'type' 				=> 'string',
								'timestamp'		 	=> 'integer',
								'event' 			=> 'string',
				), false, true);
				
				
				
				Db::CreateTable('mailbox', array(
								'id' 				=> 'increment',
								'reply' 			=> 'integer',
								's_id' 				=> 'integer',
								'r_id' 				=> 'integer',
								'type' 				=> 'tinyint',
								'sujet' 			=> 'string',
								'message' 			=> 'text',
								'posted' 			=> 'integer',
								'viewed' 			=> array('integer', null),
								'deleted_rcv' 		=> array('integer', 0),
								'deleted_snd' 		=> array('integer', 0),
				), false, true);
				
				
				
				Db::CreateTable('menu', array(
								'id' 				=> 'increment',
								'parent' 			=> 'integer',
								'priority' 			=> 'integer',
								'name' 				=> 'string',
								'icon' 				=> 'string',
								'link' 				=> 'string'
				), false, true);


				
				Db::CreateTable('newsletter', array(
								'id' 				=> 'increment',
								'author' 			=> 'int',
								'groups' 			=> 'string',
								'subject' 			=> 'string',
								'message' 			=> 'text',
								'date_sent'			=> 'int',
								'mail_sent'			=> 'integer',
								'mail_failed'		=> 'integer',
				), false, true);

				

				Db::CreateTable('pages', array(
								'page_id' 			=> 'increment',
								'type' 				=> 'string',
								'slug' 				=> 'string',
								'redirect'			=> array('string', ''),
								'category'			=> array('string', ''),
								'pub_date' 			=> 'integer',
								'pub_rev' 			=> 'integer',
								'display_toc' 		=> 'tinyint',
								'allow_comments' 	=> 'tinyint',
								'revisions' 		=> 'integer',
								'comments' 			=> array('integer', 0),
								'views' 			=> array('integer', 0),
								'sticky' 			=> array('integer', 0),
								'hide_title'		=> array('integer', 0),
				), false, true);
				Db::AddIndex('pages', 'index', array('type'));
				Db::AddIndex('pages', 'index', array('category'));
				Db::AddIndex('pages', 'index', array('sticky'));
				
				
				
				Db::CreateTable('pages_revs', array(
								'id' 				=> 'increment',
								'page_id' 			=> 'integer',
								'revision' 			=> 'integer',
								'posted' 			=> 'integer',
								'author' 			=> 'integer',
								'status' 			=> 'string',
								'title' 			=> 'string',
								'slug' 				=> 'string',
								'content'	 		=> 'text',
								'format'			=> array('string', 'html'),
								'metas'				=> 'text',
								'attached_files'	=> 'text',
				), false, true);
				Db::AddIndex('pages_revs', 'index', array('page_id', 'revision'));
				Db::AddIndex('pages_revs', 'index', array('slug'));
				
				
				
				Db::CreateTable('permissions', array(
								'name' 				=> 'string',
								'group_id' 			=> 'integer',
								'related_id' 		=> array('integer', null),
								'value' 			=> 'integer',
				), false, true);
				Db::AddIndex('permissions', 'primary key', array('name', 'group_id', 'related_id'));
				Db::AddIndex('permissions', 'index', array('group_id'));
				
				
				
				Db::CreateTable('polls', array(
								'poll_id'			=> 'increment',
								'name' 				=> 'string',
								'description'		=> 'text',
								'choices' 			=> 'text',
								'end_date'			=> 'integer',
				), false, true);
				
				
				
				Db::CreateTable('polls_votes', array(
								'id' 				=> 'increment',
								'user_id' 			=> 'integer',
								'poll_id' 			=> 'integer',
								'choice'			=> 'integer',
								'date' 				=> 'integer',
				), false, true);
				Db::AddIndex('polls_votes', 'unique', array('user_id', 'poll_id'));
				
				
				
				Db::CreateTable('reports', array(
								'id' 				=> 'increment',
								'user_id' 			=> 'integer',
								'type' 				=> 'string',
								'rel_id' 			=> 'integer',
								'reason' 			=> 'text',
								'reported' 			=> 'integer',
								'deleted' 			=> array('integer', 0),
								'user_ip' 			=> 'string',
				), false, true);		
				
				
				
				Db::CreateTable('servers', array(
								'id' 				=> 'increment',
								'type' 				=> 'string',
								'name' 				=> 'string',
								'host' 				=> 'string',
								'port' 				=> 'integer',
								'rcon_port' 		=> 'integer',
								'rcon_password' 	=> 'string',
								'query_host' 		=> 'string',
								'query_port' 		=> 'integer',
								'query_password' 	=> 'string',
								'query_extra'		=> 'string',
								'additional_settings'=>'text',
				), false, true);
				Db::AddIndex('servers', 'unique', array('host', 'port'));

				
				
				Db::CreateTable('settings', array(
								'name' 				=> array('string', null, Db::PRIMARY),
								'value' 			=> array('text', null)
				), false, true);
				

				
				Db::CreateTable('subscriptions', array(
								'user_id' 			=> 'integer',
								'type' 				=> 'string',
								'rel_id' 			=> 'integer',
								'email' 			=> 'string',
				), false, true);
				Db::AddIndex('subscriptions', 'primary key', array('user_id', 'type', 'rel_id'));
				
				
				
				Db::CreateTable('users', array(
								'id' 				=> 'increment',
								'group_id' 			=> 'integer',
								'username' 			=> 'string',
								'email' 			=> 'string',
								'password' 			=> 'string',
								'salt' 				=> 'string',
								'locked' 			=> array('integer', 0),
								'hide_email' 		=> array('tinyint', 1),
								'newsletter' 		=> array('tinyint', 0),
								'discuss' 			=> array('tinyint', 0),
								'registered' 		=> 'integer',
								'activity' 			=> array('integer', 0),
								'timezone' 			=> array('string', null),
								'reset_key' 		=> array('string', null),
								'raf' 				=> array('string', null),
								'raf_token' 		=> array('string', null),
								'theme' 	 		=> array('string', null),
								'registration_ip'	=> array('string', null),
								'last_ip'			=> array('string', null),
								'last_user_agent'	=> array('string', null),
								'country' 			=> array('string', null),
								'avatar' 			=> array('string', null),
								'ingame' 			=> array('string', null),
								'facebook' 			=> array('string', null),
								'twitter' 			=> array('string', null),
								'skype' 			=> array('string', null),
								'twitch' 			=> array('string', null),
								'youtube' 			=> array('string', null),
								'website' 			=> array('string', null),
								'about' 			=> array('text'  , null),
								'num_posts' 		=> array('integer', 0  )
				), false, true);
				Db::AddIndex('users', 'unique', array('username'));
				Db::AddIndex('users', 'unique', array('email'));

				
				
				
				Db::Exec('insert into {settings} (name, value) values ("name", ?), ("email", ?), ("url", ?), ("theme", "default"), ("database.version", ?), ("salt", ?), ("language", ?)',
							$_POST['name'], $_POST['email'], $_POST['url'], DATABASE_VERSION, uniqid(sha1(rand()), true), 'french');
				
				
				Db::Exec('insert into {menu} (`id`, `parent`, `priority`, `name`, `icon`, `link`) values 
								(1, 0, 0, "Navigation", "", ""),
								(2, 1, 0, "Accueil", "home", "home"),
								(3, 1, 0, "Forum", "list-ul", "forums"),
								(4, 1, 0, "Membres", "users", "users"),
								(5, 1, 0, "Sondages", "pie-chart", "polls"),
								(6, 1, 0, "Contact", "envelope-o", "contact")
								');


				Db::Exec('INSERT INTO {groups} (`id`, `name`, `internal`, `color`, `priority`) VALUES
								(1, "Administrateur", "Administrator", "#00aa00", 1),
								(2, "Modérateur", "Moderator", "#5555ff", 2),
								(3, "Membre", "Member", "#000", 3),
								(4, "Invité", "Guest", "#aaaaaa", 4)
								');

				$groups = [
					'admin' => array('id' => 1),
					'mod'   => array('id' => 2),
					'user'  => array('id' => 3, 'ignore' => array('user.staff')),
					'guest' => array('id' => 4, 'force' => array('comment_send')),
				];
				
				foreach($_privileges as $group => $sections) {
					foreach(array_filter($sections, 'is_array') as $section) {
						foreach(array_keys($section) as $priv) {
							$key = $group.'.'.$priv;
							foreach($groups as $g) {
								if ($g['id'] <= $groups[$group]['id'] && (empty($g['ignore']) || !in_array($key, $g['ignore']))) {
									$inserts[] = "('$key', {$g['id']}, 1)";
								}
							}
						}
					}
				}
				
				foreach($groups as $g) {
					if (!empty($g['force'])) {
						foreach($g['force'] as $perm) {
							$inserts[] = "('$perm', {$g['id']}, 1)";
						}
					}
				}
				
				if ($inserts) {
					Db::Exec('INSERT INTO `{permissions}` (`name`, `group_id`, `value`) VALUES ' . implode(',', $inserts));
				}
				
				$salt = substr(str_shuffle('!@#$%^&*()_+}{POIUytreqqsdfgjkZXCVBNM<LKJHGHGFFSASDFGHJKL;./'), 0, 12);
				$password = sha1($salt.sha1($_POST['admin_pass']));

				// ("Invité", 0, "", "", "email", UNIX_TIMESTAMP(), ""), 
				Db::Exec('INSERT INTO {users} (username, group_id, password, salt, email, registered) 
							VALUES (?, 1, ?, ?, ?, '.time().')',
							$_POST['admin'], 
							$password,
							$salt, 
							$_POST['email']);
				
				
				foreach(glob('updates/*.php') as $migration) { // Applying incremental updates
					if ((include $migration) === false) {
						throw new exception('Migration ' . $migration . ' failed');
					}
				}
				
				$db = array_map('addslashes', $db);
				$cookie_name = 'evocms_'.dechex(rand(0,65000));
				
				$config = "<?php\n".
							"\$db_host = '{$db[0]}'; \n".
							"\$db_user = '{$db[1]}'; \n".
							"\$db_pass = '{$db[2]}'; \n".
							"\$db_name = '{$db[3]}'; \n".
							"\$db_prefix = '{$db[4]}'; \n".
							"\$db_type = '{$db[5]}'; \n".
							"\$cookie_name = '$cookie_name';\n".
							"\$cookie_domain = '';\n";
							
				file_put_contents('../config.php', $config);
				
				include '../evo/plugins.php';
				include '../evo/functions.php';
				
				cookie_login(1);
				
				$done = true;
			} catch (Exception $e) {
				$failed  = 'Erreur SQL: ' . $e->getMessage() . '<br>';
				$failed .= 'Requete: '. end(Db::$queries)['query'];
			}
			
			if (isset($_POST['report'])) {
				$status = isset($done) ? 'Réussie' : 'Échouée:';
				$report = "Rapport d'installation du " . date('Y-m-d H:i:s') . ":\n\n".
						  "Status:      $status $failed\n".
						  "Database:    ". Db::DriverName() . ' ' . Db::ServerVersion() . "\n" .
						  "Version CMS: " . EVO_VERSION . " #" . EVO_REVISION . " - " . EVO_BUILD . "\n" .
						  "Version PHP: " . PHP_VERSION . "\n" .
						  "Serveur Web: " . $_SERVER['SERVER_SOFTWARE'] . "\n" .
						  "\n" .
						  "URL du CMS:  " . $_POST['url'] . "\n" .
						  "Email admin: " . $_POST['email'] . "\n" .
						  "User Agent:  " . $_SERVER['HTTP_USER_AGENT'];
				
				@mail('cms+stats@rb5.ca', 'Rapport d\'installation', utf8_decode($report), 'From: cms+stats@rb5.ca');
			}
		}
		break;
}
	
?>
<!doctype html>
<html>
	<head>
	<meta charset="utf-8">
	<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<script src="../assets/js/components.js"></script>
	<style type="text/css">
		html { height: 100%; }
		body { font-family: 'Century Gothic', sans-serif; background: #6fa5d0; background-image: url(img/bg.jpg);  background-size:100%; }
		#content { width:800px; padding:10px; margin:0 auto; border: 1px solid rgb(223, 223, 223); border-radius: 5px; margin-top: 10px; background-image: url('data:image/png;base64,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');}
		.logo { width:800px; margin: 0 auto; margin-top: 20px; }
		.logo img { width:225px;}
		.main-titre { text-align: right; font-size: 22pt; margin-top: 10px; font-weight: 100; }
		.rights { text-align: right; font-size: 11px; color: gray; }
		.version { text-align: left; font-size: 11px; color: gray; }
		.jumbotron { margin-bottom: 10px; border: 1px solid rgb(223, 223, 223); padding: 30px; color: inherit; background-color: #ffffff; }
		.avancement { height: 500px; border-right: 1px solid rgb(223, 223, 223); padding-top: 30px; }
		.avancement p { font-size: 10pt; font-weight: 400 !important; }
		.avancement .actif { font-weight: 600 !important; font-size: 11pt; }
		.avancement .pass { color: rgb(189, 189, 189); }
		.requis { margin-bottom: 15px; margin-top: 15px; }
		.requis_align { margin: 0 auto; width: 400px; }
		.requis .ok { color: green; }
		.requis .error{ color: red; }
		.requis .warning{ color: orange; }
		.bs-callout { margin: 10px 0; padding: 10px; border-left: 3px solid #eee; }
		.bs-callout h4{ margin-top: 0; }
		.bs-callout-danger h4{ color: #B94A48; }
		.bs-callout-success h4 { color: #3C763D; }
		.bs-callout p:last-child { margin-bottom: 0; }
		.bs-callout-danger { background-color: #fdf7f7; border-color: #d9534f; }
		.bs-callout-success { background-color: #fdf7f7; border-color: #67B823; }
        .bs-callout-info { border-left-color: #5bc0de; }
        .bs-callout-info h4 { color: #5bc0de; }
	</style>
	</head>
	<script>
	$(function() {
		$('[title]').tooltip({placement: 'bottom'});
	});
	</script>
	<body>
		<div class="row logo">
			<div class="col-md-7">
				<a href="">	
					<img alt="logo" src="data:image/png;base64,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"/>
				</a>
			</div>
			<div class="col-md-5">
				<div class="main-titre">Installation</div>
			</div>
		</div>
		<div id="content">
			<div class="jumbotron">
				<div class="row">
					<div class="col-md-3 avancement">
					<?php
						foreach($steps as $step => $tag) {
							if ($cur_step == $step)
								echo '<p class="actif">' . $tag . '</p>';
							elseif($cur_step > $step)
								echo '<p class="pass">' . $tag . '</p>';
							else
								echo '<p>' . $tag . '</p>';
						}
					?>
					</div>
					<div class="col-md-9">
						<?php
							if(isset($warning) && !empty($warning)) {
								echo '<div class="alert alert-danger">'.$warning.'</div>';
							}
						?>
						<form class="form-horizontal" method="post" autocomplete="off" id="form-content">
<?php if ($cur_step == 0): ?>
            </br>
			<legend>Veuillez choisir votre langue</legend>
			<div class="form-group">
				<div class="col-sm-12">
					<select class="form-control" id="language" name="language">
						<option value="francais" data-image="../asset/img/flags/ca.png">Français</option>
					</select>
				</div>
			</div>
            </br>
            <div class="bs-callout bs-callout-info">
              <h4>Bon à savoir !</h4> Le choix de votre langue d'affichage pourra être changé au besoin via la panneau d'administration du CMS.
            </div>
<?php elseif ($cur_step == 1): ?>
			<legend>License d'utilisation</legend>
				<strong>Evo-CMS : A Small Content Management System</strong><br>
				<br>
				Copyright (c) 2014, Alex Duchesne &lt;alex@alexou.net&gt;, Yan Bourgeois &lt;dev@evolution-network.ca&gt;<br>
				<br>
				Permission is hereby granted, free of charge, to any person obtaining<br>
				a copy of this software and associated documentation files (the<br>
				"Software"), to deal in the Software without restriction, including<br>
				without limitation the rights to use, copy, modify, merge, publish,<br>
				distribute, sublicense, and/or sell copies of the Software, and to<br>
				permit persons to whom the Software is furnished to do so, subject to<br>
				the following conditions:<br>
				<br>
				The above copyright notice and this permission notice shall be<br>
				included in all copies or substantial portions of the Software.<br>
				<br>
				<small>
				THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,<br>
				EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF<br>
				MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND<br>
				NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE<br>
				LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION<br>
				OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION<br>
				WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.<br>
				</small>
<?php elseif ($cur_step == 2): ?>
			<legend>Processus d'installation de Evo-CMS!</legend>
			<h5>Evo-CMS a besoin de certains composants pour fonctionner correctement. Nous allons maintenant vérifier si tout est en ordre après quoi vous pourrez débuter l'installation !</h5>
			<legend><small>Vérification des éléments requis</small></legend>
			<?php
			echo '<div class="requis_align">';
			foreach ($checks as $check) {
				echo '<div class="row requis">'.
					'<div class="col-md-9 info">' . htmlentities($check[0], ENT_COMPAT, 'UTF-8') . '</div>';		
					if (!$check[1]) {
						echo '<div class="col-md-3 error"><i class="fa fa-exclamation"></i> Erreur</div>';
					} else {
						echo '<div class="col-md-3 ok"><i class="fa fa-check"></i> OK</div>';
					}
				echo '</div>';
			}
			echo '</div>'; 
			?>
<?php elseif ($cur_step == 3): ?>
			<legend>Informations SQL</legend>
			<h5>Entrez ci-dessous les détails de connexion à votre base de données. Si vous ne les connaissez pas avec certiture, contactez votre hébergeur.</h5>
                </br>
				<div class="sqlite form-group bs-callout bs-callout-danger">
					Le fichier sqlite3 ne doit pas être accessible publiquement. Si vous utiliser un serveur de type apache, un nom commençant par .ht. devrait faire l'affaire. Autrement référéz vous à votre serveur ou placer la base en dehors de votre webroot (chemin absolu).
				</div>
				<div class="sqlite mysql form-group" data-toggle="tooltip">
					<label for="type" class="col-sm-4 control-label">Type</label>
					<div class="col-sm-6">
						<select class="form-control" id="type" name="db_type">
						<?php
							foreach ($db_types as $type => $label) {
									echo '<option value="' . $type . '"'  . 
											($type == @$_POST['db_type'] ? ' selected="selected"':'') . '>' . $label . '</option>';
							}
						?>
						</select>
					</div>
					<script>
						$(function() {$('#type').bind('click change blur keyup', function () {
							$('.form-group').hide(); 
							$('.'+$(this).val()).show();
							if ($(this).val() == 'sqlite') {
								$('#dbname').val('.ht.sqlite');
							}
							}).click();
						});
					</script>
				</div>
				<div class="mysql form-group" data-toggle="tooltip" title="Host ou se trouve la DB.">
					<label for="host" class="col-sm-4 control-label">Hôte</label>
                    <div class="col-sm-5 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-server"></i></div>
                        <input type="text" class="form-control" id="host" name="db_host" value="<?= post_e('db_host', 'localhost') ?>">
                    </div>
				</div>
				<div class="sqlite mysql form-group" data-toggle="tooltip" title="Nom de fichier sqlite3 ou nom de base mysql.">
					<label for="dbname" class="col-sm-4 control-label" >Nom de la base</label>
                    <div class="col-sm-5 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-database"></i></div>
                        <input type="text" class="form-control" id="dbname" name="db_name" value="<?= post_e('db_name') ?>">
                    </div>
				</div>
				<div class="mysql form-group" data-toggle="tooltip" title="Votre identifiant MySQL.">
					<label for="username" class="col-sm-4 control-label">Nom d'utilisateur</label>
                    <div class="col-sm-5 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-user"></i></div>
                        <input type="text" class="form-control" id="username" name="db_user" value="<?= post_e('db_user') ?>">
                    </div>
				</div>
				<div class="mysql form-group" data-toggle="tooltip" title="Votre mot de passe MySQL.">
					<label for="password" class="col-sm-4 control-label">Mot de passe</label>
                    <div class="col-sm-5 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-key"></i></div>
                        <input type="password" class="form-control" id="password" name="db_pass" value="<?= post_e('db_pass') ?>">
                    </div>
				</div>
			<div class="sqlite mysql form-group" data-toggle="tooltip" title="Si vous voulez installer plusieurs blogs Evo-CMS dans une même base de données, modifiez ce champ.">
				<label for="inputPassword3" class="col-sm-4 control-label">Prefixe</label>
				<div class="col-sm-6">
					<input type="text" class="form-control" id="prefixe" name="db_prefix" value="<?= post_e('db_prefix', 'evo_') ?>">
				</div>
			</div>
<?php elseif ($cur_step == 4): ?>
			<?php
			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
				$url = 'https://'.$_SERVER['HTTP_HOST'];
			else
				$url = 'http://'.$_SERVER['HTTP_HOST'];

			$dir = rtrim(strstr($_SERVER['REQUEST_URI'].'?', '?', true), '/'); 

			$url .= substr($dir, 0, strrpos($dir, '/'));
			?>
				<legend>Configuration</legend>
				<h5>Merci de fournir les informations suivantes. Ne vous inquiétez pas, vous pourrez les modifier plus tard.</h5><br/><br/>
				<div class="form-group" data-toggle="tooltip" title="Quel nom possèdera votre site ?">
					<label for="sitename" class="col-sm-4 control-label">Nom du site</label>
                    <div class="col-sm-6 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-font"></i></div>
                        <input type="text" class="form-control" id="sitename" name="name" value="<?= post_e('name', 'Evo-CMS '.EVO_VERSION) ?>">
                    </div>
				</div>
				<div class="form-group" data-toggle="tooltip" title="Quel est l'url de votre site ?">
					<label for="siteurl" class="col-sm-4 control-label">URL du site</label>
					<div class="col-sm-6 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-globe"></i></div>
						<input type="text" class="form-control" id="siteurl" name="url" value="<?= post_e('url', $url) ?>">
					</div>
				</div>
				<div class="form-group" data-toggle="tooltip" title="Quel est votre adresse courriel ?">
					<label for="sitemail" class="col-sm-4 control-label">Courriel de l'admin</label>
					<div class="col-sm-6 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-envelope"></i></div>
						<input type="text" class="form-control" id="sitemail" name="email" placeholder="exemple@domain.com" value="<?= post_e('email') ?>">
					</div>
				</div>
				<div class="form-group" data-toggle="tooltip" title="Nom d'utilisateur de l'administrateur">
					<label for="sitelogin" class="col-sm-4 control-label">Nom d'utilisateur</label>
					<div class="col-sm-6 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-user"></i></div>
						<input type="text" class="form-control" id="sitelogin" name="admin" value="admin" value="<?= post_e('admin') ?>">
					</div>
				</div>
				<div class="form-group"  data-toggle="tooltip" title="Mot de passe pour l'administrateur">
					<label for="sitepass" class="col-sm-4 control-label">Mot de passe</label>
					<div class="col-sm-6 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-lock"></i></div>
						<input type="password" class="form-control" id="sitepass" name="admin_pass" value="<?= post_e('admin_pass') ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="sitepass" class="col-sm-4 control-label"></label>
					<div class="col-sm-6 input-group" style="padding-left: 15px;">
                        <div class="input-group-addon"><i class="fa fa-clipboard"></i></div>
						<input type="password" class="form-control" id="sitepass2" name="admin_pass_confirm" placeholder="Confirmation" value="<?= post_e('admin_pass_confirm') ?>">
					</div>
				</div>
				<div class="form-group"  data-toggle="tooltip" title="Envoyer certaines informations d'installation afin de contribuer
																	  au développement et faciliter la résolution de bug. Aucune 
																		  information de connexion n'est envoyé cependant votre email est 
																		  incluse au besoin">
				    <label class="col-sm-3 control-label"></label>
				    <div class="col-sm-9">
				        <input type="checkbox" name="report" id="report" value="1" checked> <label for="report">Envoyer un rapport d'installation</label>
				    </div>
				</div>
<?php elseif ($cur_step == 5): ?>
				<legend>Installation en cours...</legend>
				<h5>L'installation est présentement en cours. Si vous rencontrez des bugs, merci de nous le faire savoir en allant sur <a href="http://blog.evolution-network.ca/">notre site</a>.</h5>
                </br>
			<?php if ($failed) { ?>
				<div class="bs-callout bs-callout-danger">
				<h4>L'installation a échouée !</h4>
				<p><?= $failed ?></p>
				</div>
			<?php } elseif ($done) { ?>
				<div class="bs-callout bs-callout-success">
					<h4>Félicitation, c'est terminé !</h4>
					<h5>Votre CMS est maintenant prêt à être utilisé.</h5>
					<h5>Merci d'utiliser Evo-CMS. Nous espérons que cette expérience vous plaira !</h5>
					<h5 style="color:#B94A48;">Nous vous suggérons de supprimer le dossier "install" pour plus de sécurité.</h5>
				</div>
                </br>
				<div class="form-group" style="margin-bottom: 0px">
					<label for="host" class="col-sm-5 control-label">Adresse d'accès</label>
                    <div class="col-sm-7 input-group" style="padding-left: 15px;">
                        <p class="form-control-static" style="font-size: 100%"><?= $_POST['url'] ?></p>
                    </div>
				</div>
                <div class="form-group" style="margin-bottom: 0px">
					<label for="host" class="col-sm-5 control-label">Panneau de gestion</label>
                    <div class="col-sm-7 input-group" style="padding-left: 15px;">
                        <p class="form-control-static" style="font-size: 100%"><?= $_POST['url'] ?>/admin</p>
                    </div>
				</div>
                <div class="form-group" style="margin-bottom: 0px">
					<label for="host" class="col-sm-5 control-label">Nom d'utilisateur</label>
                    <div class="col-sm-7 input-group" style="padding-left: 15px;">
                        <p class="form-control-static" style="font-size: 100%"><?= $_POST['admin'] ?></p>
                    </div>
				</div>
                <div class="form-group" style="margin-bottom: 0px">
					<label for="host" class="col-sm-5 control-label">Mot de passe</label>
                    <div class="col-sm-7 input-group" style="padding-left: 15px;">
                        <p class="form-control-static" style="font-size: 100%"><?= $_POST['admin_pass'] ?></p>
                    </div>
				</div>
                </br>
		    <div class="text-center">
			    <a href="<?= htmlentities($_POST['url'].'/admin', ENT_COMPAT, 'UTF-8') ?>" class="btn btn-success">Terminer</a>
			</div>

			<?php } ?>
<?php endif; ?>
							<br>
							<p class="navbtn text-center">
							<?php
								if (!isset($hide_nav)) {
									if ($cur_step > 0)
										echo '<a onclick="$(\'#step\').val(',($cur_step-1).').click();" class="btn btn-primary btn-md" role="submit">Précédent</a> ';
									if ($next_step < count($steps))
										echo '<button id="step" type="submit" name="step" value="' . $next_step . '"  class="btn btn-primary btn-md" onclick="setTimeout(function() {$(\'#form-content,#progressbar\').toggle();}, 300);; " role="submit">Suivant</button>';
								}
							?>
							</p>
							<input type="hidden" name="payload" value="<?= is_array($payload) ? base64_encode(serialize($payload)) : $payload ?>">
						</form>
						<div id="progressbar" style="display:none;">
							<legend>Veuillez Patienter</legend>
							<div class=" progress progress-striped active">
							  <div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
								 <span class="sr-only">Endless progressbar</span>
							  </div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-8 version">Evo-CMS <?=EVO_VERSION?></div>
				<div class="col-md-4 rights">© Evolution-Network</div>
			</div>
		</div>
	</body>
</html>