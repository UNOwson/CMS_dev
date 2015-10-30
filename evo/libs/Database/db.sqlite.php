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
		if ($database[0] != '/')
			$database = ROOT_DIR . '/' . basename($database);
		
		self::$database = $database;
		self::$prefix = $prefix;
		
		self::$db = new PDO('sqlite:' . $database);
		self::$db->sqliteCreateFunction ('UNIX_TIMESTAMP', 'time', 0);
		self::$db->sqliteCreateFunction ('LOCATE', 'strpos', 2);
		self::$db->sqliteCreateFunction ('IF', function($a, $b, $c) {return $a ? $b : $c;}, 3);
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
				$row .=  'PRIMARY KEY';
			}
			
			if (isset($field[1])) {
				$row .= ' NOT NULL ';
				$row .= ' DEFAULT ' . self::escapeValue($field[1]) . ' ';
			} elseif (count($field) == 1) {
				$row .= ' NOT NULL ';
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
		$table = self::GetTableName($table);
		$type = strtoupper($type);
		
		if (count($fields) < 1)
			return false;
		
		switch($type) {
			case 'PRIMARY KEY':
			case 'UNIQUE':
				$type = 'UNIQUE INDEX'; break;
		}
		
		$index_name = $table.'_'.implode('_', $fields);
		
		foreach ($fields as &$field) $field = '`' . $field . '`';
		
		return self::Exec('CREATE '.$type.' '. $index_name .' ON `' . $table . '` (' . implode(',', $fields) . ')');
	}
	
	
	public static function AddColumn($table, $col_name, $col_type, $primary = false, $auto_increment = false, $default = null)
	{
		$add = 'ALTER TABLE `' . self::GetTableName($table) . '` ADD COLUMN `' . $col_name . '` ' . $col_type . ' ';
		
		if ($primary)
			$add .= ' PRIMARY KEY';
	
		if ($auto_increment)
			$add .= ' AUTO_INCREMENT';
			
		if ($default)
			$add .= ' DEFAULT "'.self::escape($default) . '" ';
		
		return self::Exec($add);
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
		return self::QueryAll('SELECT name FROM sqlite_master WHERE type="table" and name = ' . self::escapeValue(self::GetTableName($table)));
	}
	
	
	public static function GetTables($full_schema = false)
	{
		$tables = self::QueryAll('SELECT name, * FROM sqlite_master WHERE type="table"', true);
		return $full_schema ? $tables : array_keys($tables);
	}
	
	
	public static function Truncate($table)
	{
		return  self::Exec("delete from {$table}") +
				self::Exec("delete from sqlite_sequence where name={$table}");
	}
	
	
	public static function ParseColType($type)
	{
		list($type, $length) = explode('|', $type.'|255');
		
		switch($type) {
			case 'string':
			case 'text':
				$type = 'text'; break;
			case 'int':
			case 'integer':
			case 'tinyint':
				$type = 'integer'; break;
			case 'float':
			case 'double':
				$type = 'numeric'; break;
			case 'increment':
				$type = 'integer'; break;
		}

		return $type;
	}

}