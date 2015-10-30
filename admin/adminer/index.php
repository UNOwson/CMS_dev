<?php 
/* Adminer bootstrap */

require '../../evo/boot.php';
has_permission('admin.sql', true);

function adminer_object() {
	global $db_user, $db_name;
	// if ($db_type === 'sqlite') {
		// define('DB', $db_name[0] == '/' ? $db_name : ROOT_DIR . '/' . $db_name);
	// } else {
		// define('DB', $db_name);
	// }
	// $_GET['username'] = '';
	class AdminerSoftware extends Adminer {
		public function name() {
			return '<a href="' . \Site('url') . '/admin/"><small>' . \Site('name') . '</small></a>';
		}
		public function database() { // Doesn't work for some reason
			return Db::$database;
		}
		public function loginForm() {
			$type = Db::DriverName();
			$db = ($type !== 'sqlite') ? Db::$database : (Db::$database[0] == '/' ? Db::$database : ROOT_DIR . '/' . Db::$database);
			echo '<input type="hidden" name="auth[db]" value="'.$db.'">';
			echo '<button name="auth[driver]" type="submit" value="'.$type.'">Login</button>';
		}
		public function credentials() {
			global $db_host, $db_user, $db_pass;
			return [$db_host, $db_user, $db_pass];
		}
	}
	return new AdminerSoftware;
}

require 'adminer.php';