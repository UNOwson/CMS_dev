<?php
defined('EVO') or die('Ce fichier ne peut être appellé directement.');

// Pour forcer dé-commenter les deux lignes suivantes:
//Db::$throwException = false;
//Site('database.version', 0, true);

if (Site('database.version') >= DATABASE_VERSION) {
	die('Base de données déjà à jour.');
}

// Ne pas oublier de modifier evo/version.php!

switch((int)Site('database.version')) {
	case 0:
	case 1:
		Db::AddColumnIfNotExists('pages_revs', 'attached_files', 'text');
		Db::AddColumnIfNotExists('forums_posts', 'attached_files', 'text');
		Db::AddColumnIfNotExists('users', 'timezone', 'string');
		Db::AddColumnIfNotExists('users', 'last_user_agent', 'string');
		Db::CreateTable('files_rel', array(
						'file_id' 	=> 'int',
						'rel_id' 	=> 'int',
						'rel_type' 	=> 'string',
		), true);
		Db::AddIndex('files_rel', 'unique', array('file_id', 'rel_id', 'rel_type'));

		Db::AddColumnIfNotExists('servers', 'query_host', 'string');
		Db::AddColumnIfNotExists('servers', 'query_port', 'integer');
		Db::AddColumnIfNotExists('servers', 'query_password', 'string');
		Db::AddColumnIfNotExists('servers', 'query_extra', 'string');
		Db::AddColumnIfNotExists('servers', 'additional_settings', 'text');
		Db::AddColumnIfNotExists('pages', 'hide_title', 'integer');
	case 2:
		Db::AddColumnIfNotExists('users', 'raf_token', 'string');
	case 3:
		Db::AddColumnIfNotExists('users', 'locked', 'integer');
	case 4:
}

Site('database.version', DATABASE_VERSION);

echo 'Termin&eacute;. Nous vous sugg&eacute;rons de supprimer ou renommer le dossier install/ du cms!';

exit;