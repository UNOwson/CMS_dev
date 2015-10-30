<?php 
has_permission('admin.backup_sql', true);
log_event($user_session['id'], 'admin', 'Téléchargement d\'une sauvegarde SQL.');

header('Content-Type: application/x-gzip');
header('Content-Transfer-Encoding: Binary');
header('Content-disposition: attachment; filename="backup_sql-'.date('Y-m-d_Hi').'.sql.gz"');

set_time_limit(0);
ob_end_clean();

if (Db::DriverName() === 'sqlite') {
	gzip(file_get_contents(Db::$database), 'backup-'.date('Y-m-d_Hi').'.sqlite');
	die;
}

$dumpSettings = array(
	'compress' => 'Buffer',
	'no-data' => false,
	'add-drop-database' => false,
	'add-drop-table' => false,
	'single-transaction' => false,
	'lock-tables' => false,
	'add-locks' => true,
	'extended-insert' => true,
	'disable-foreign-keys-check' => false
);

$dump = new Clouddueling\Mysqldump\Mysqldump(Db::$database, Db::$user, Db::$password, Db::$host, 'mysql', $dumpSettings);
gzip($dump->start(), 'backup-'.date('Y-m-d_Hi').'.sql');

// elseif(function_exists('popen')) {
	// $fd = popen("mysqldump --user=$db_user --password=$db_pass $db_name | gzip", 'r');

	// while ($line = fread($fd, 8096)) {
		// echo $line;
		// flush();
	// }

// }
// else {
	// header('Content-Type: text/plain');
	// die ('Ni PDO ni popen n\'est disponible !');
// }
die;