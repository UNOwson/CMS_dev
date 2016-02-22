<?php
/*
 * Db Singleton abstraction.
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>.
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */

class Db extends Database {
	
	public static function Connect($host, $user, $password, $database = '', $prefix = '')
	{
		self::$host = $host;
		self::$user = $user;
		self::$password = $password;
		self::$database = $database;
		self::$prefix = $prefix;

		self::$db = new PDO('mysql:host='.$host.';charset=utf8;dbname='.$database, $user, $password);
	}

	
	public static function CreateTable($table, $fields, $if_not_exists = false, $drop_if_exists = false)
	{
		if ($drop_if_exists)
			self::DropTable($table, true);
		
		$table = self::GetTableName($table);
		$rows = [];
		
		foreach($fields as $name => $field) {
			is_array($field) or $field = array($field);
			
			$type = self::ParseColType($field[0]);
			
			if ($field[0] == 'increment') {
				$field[2] = Database::AI; 
				$field[1] = null;
			}
			
			$row = '`' . $name . '` ' . $type . ' ';
			
			if (isset($field[2])) {
				if ($field[2] == Database::PRIMARY)
					$row .=  'PRIMARY KEY';
				elseif($field[2] == Database::AI)
					$row .= 'PRIMARY KEY AUTO_INCREMENT';
			}
			
			if (isset($field[1])) {
				$row .= ' NOT NULL ';
				$row .= ' DEFAULT ' . self::escapeValue($field[1]) . ' ';
			} elseif (count($field) == 1) {
				$row .= ' NOT NULL';
			}
			
			$rows[] = $row;
		}
		
		$create  = $if_not_exists ? 'CREATE TABLE IF NOT EXISTS ' : 'CREATE TABLE ';
		$create .= '`' . $table . '` (' . implode(',', $rows) . ')';
		
		return self::Exec($create);
	}
	
	
	public static function DropTable($table, $if_exists = true)
	{
		$table = self::GetTableName($table);
		$drop = $if_exists ? 'DROP TABLE IF EXISTS ': 'DROP TABLE ';
		return self::Exec($drop . '`' . $table . '`');
	}
	
	
	public static function AddIndex($table, $type, $fields)
	{
		$type = strtoupper($type);
//		if ($type !== 'UNIQUE' && $type !== 'PRIMARY' && $type !== 'PRIMARY')
//			$type= 'INDEX';
		
		if (count($fields) < 1)
			return false;
		
		foreach ($fields as &$field) $field = '`' . $field . '`';
		
		return self::Exec('ALTER TABLE `' . self::GetTableName($table) . '` ADD ' . $type . '(' . implode(',', $fields) . ')');
	}
	
	
	public static function AddColumn($table, $col_name, $col_type, $primary = false, $auto_increment = false, $default = null)
	{
		$add = 'ALTER TABLE `' . self::GetTableName($table) . '` ADD COLUMN `' . $col_name . '` ' . self::ParseColType($col_type) . ' ';
		
		if ($primary)
			$add .= ' PRIMARY KEY';
	
		if ($auto_increment)
			$add .= ' AUTO_INCREMENT';
			
		if ($default)
			$add .= ' DEFAULT "'.self::escape($default) . '" ';
		
		return Db::Exec($add);
	}
	
	
	public static function GetColumns($table, $names_only = false)
	{
		if ($r = self::QueryAll('SHOW COLUMNS FROM `'.self::GetTableName($table).'`', true))
			return $names_only ? array_keys($r) : $r;
		else	
			return false;
	}	
	
	
	public static function TableExists($table)
	{
		return self::QueryAll('SHOW TABLES LIKE "' . self::GetTableName($table) . '"');
	}	
	
	
	public static function GetTables($full_schema = false)
	{
		return self::QueryAll('SHOW TABLES');
	}
	
	
	public static function Truncate($table)
	{
		return self::Exec("TRUNCATE {$table}");
	}
	
	
	public static function ParseColType($type)
	{
		list($type, $length) = explode('|', $type.'|255');
		
		switch($type) {
			case 'string':
				$type = 'varchar('.$length.')'; break;
			case 'text':
				$type = 'text'; break;
			case 'int':
			case 'integer':
				$type = 'int(11)'; break;
			case 'tinyint':
				$type = 'tinyint'; break;
			case 'float':
			case 'double':
				$type = 'float'; break;
			case 'increment':
				$type = 'int(11)'; break;
			default:
		}
		
		return $type;
	}

}