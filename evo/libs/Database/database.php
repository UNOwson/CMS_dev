<?php
/*
 * Db Singleton abstraction.
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>.
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */

abstract class Database {

	const PRIMARY = 1;
	const AI = 2;
	
	public static $db = null;
	
	public static $throwException = true;
	public static $queryLogging = true;
	public static $queries = array();
	public static $num_queries = 0;
	public static $exec_time = 0;
	public static $errno = 0;
	public static $error = null;
	public static $affected_rows = 0;
	public static $insert_id = 0;
	
	public static $host, $user, $password, $database, $prefix;
	
	abstract public static function Connect($host, $user, $password, $database = '', $prefix = '');
	
	abstract public static function CreateTable($table, $fields, $if_not_exists = false, $drop_if_exists = false);
	
	abstract public static function DropTable($table, $if_exists = true);
	
	abstract public static function AddIndex($table, $type, $fields);
	
	abstract public static function AddColumn($table_name, $col_name, $col_type, $primary = false, $auto_increment = false, $default = null);
	
	abstract public static function GetColumns($table, $names_only = false);
	
	abstract public static function TableExists($table);
	
	abstract public static function GetTables($full_schema = false);
	
	abstract public static function Truncate($table);
	
	
	public static function AvailableDrivers()
	{
		$pdo = PDO::getAvailableDrivers();
		$files = array_map('basename', glob(__DIR__.'/db.*.php'));
		$files = str_replace(['db.', '.php'], '', $files);
		
		return array_intersect($pdo, $files);
	}
	
	
	public static function DriverName()
	{
		return self::$db->getAttribute(PDO::ATTR_DRIVER_NAME);
	}
	
	
	public static function ServerVersion()
	{
		return self::$db->getAttribute(PDO::ATTR_SERVER_VERSION);
	}
	
	
	public static function GetTableName($table)
	{
		if ($table[0] === '"' || $table[0] === '`' || strpos($table, '.') !== false) {
			return $table;
		}
		$table = self::$prefix . trim($table, '{}');
		
		return $table;
	}
	
	
	public static function AddColumnIfNotExists($table_name, $col_name, $col_type, $primary = false, $auto_increment = false, $default = null)
	{
		$columns  = static::GetColumns($table_name, true);
		
		if (!in_array($col_name, $columns)) {
			return static::AddColumn($table_name, $col_name, $col_type, $primary, $auto_increment, $default);
		}
		
		return true;
	}
	
	
	public static function buildCond(array $conditions)
	{
		$parts = array();
		
		$operators = array('not', 'or', 'and');

		$prev = 0;
		
		foreach($conditions as $key => $value) {
			if ($prev && (is_array($value) || !in_array(strtolower($value), $operators))) {
				$parts[] = 'and';
			}
			if (is_int($key)) {
				if (is_array($value) && count($value) === 3) {
					$parts[] = self::escapeField($value[0]) . ' ' . $value[1] . ' ' . self::escapeValue($value[2]);
				} else {
					$parts[] = $value;
				}
			} else {
				$parts[] = self::escapeField($key) . ' = ' . self::escapeValue($value);
			}
			$prev = is_array($value) || !in_array(strtolower($value), $operators);
		}
		
		return implode(' ', $parts);
	}
	
	
	public static function escapeValue($value, $quote = true)
	{
		if (ctype_digit($value)) $value = (int) $value;
		
		switch (gettype($value)) {
			case 'NULL': return 'NULL';
			case 'float':
			case 'double':
			case 'integer': return $value;
			default: return $quote ? self::$db->quote($value) : substr(self::$db->quote($value), 1, -1);
		}
	}
	

	public static function escapeField($value)
	{
		return '`' . str_replace('`', '``', $value) . '`';
	}	

	
	public static function escape($string)
	{
		return substr(self::$db->quote($string), 1, -1);
	}
	
	
	public static function Query() // $query, $params = array()..., $throwExceptionOnError = true
	{
		$params = func_get_args();
		
		if (count($params) == 0)
			return false;

		$query = preg_replace('!([^a-z0-9])\{([_a-z0-9]+)\}([^a-z0-9]|$)!i', '$1' . self::$prefix . '$2$3', array_shift($params));

		$throwException = $params && is_bool(end($params)) ?  array_pop($params) : self::$throwException;
		self::$db->setAttribute(PDO::ATTR_ERRMODE, $throwException ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT);
		
		while(is_array(reset($params))) $params = reset($params);
		
		$count = 0;
		$start = microtime(true);			
		try {
			if ($q = Db::$db->prepare($query)) {
				foreach($params as $param) {
					if (ctype_digit((string)$param))
						$q->bindValue(++$count, (int)$param, PDO::PARAM_INT);
					elseif($param === null)
						$q->bindValue(++$count, $param, PDO::PARAM_NULL);
					else
						$q->bindValue(++$count, $param);
				}
				
				$q->execute();
			}
		} catch (PDOException $exception) {
			$q = false;
		}
		
		// self::$errno = $q ? $q->errorInfo()[1] : self::$db->errorInfo()[1];
		// self::$error = $q ? $q->errorInfo()[2] : self::$db->errorInfo()[2];
		
		$error = $q ? $q->errorInfo() : self::$db->errorInfo();
		
		self::$errno = $error[1];
		self::$error = $error[2];
		self::$affected_rows = $q ? $q->rowCount() : 0;
		self::$insert_id = $q ? self::$db->lastInsertId() : 0;
		self::$exec_time += microtime(true) - $start;
		self::$num_queries++;
		
		if (self::$queryLogging) {
			self::$queries[self::$num_queries] = array(
				'query' => $query,
				'params' => &$params,
				'time' => microtime(true) - $start,
				'errno' => self::$errno,
				'error' => self::$error,
				'affected_rows' => self::$affected_rows,
				'fetch' => 0,
				'insert_id' => self::$insert_id,
			);
			
			foreach(debug_backtrace(false) as $trace) {
				if (isset($trace['file']) && $trace['file'] != __FILE__) {
					self::$queries[self::$num_queries]['trace'] = $trace;
					break;
				}
			}
		}
		
		if ($throwException && isset($exception)) {
			throw $exception;
		}

		return $q;
	}
	
	
	public static function QueryObj()
	{
		if (func_num_args() == 0) return;
	
		if ($q = call_user_func_array('self::Query', func_get_args())) {
			return new DbResult($q);
		} else {
			return null;
		}
	}
	
	public static function QuerySingle() // $query, $params = array()..., $entire_row = false
	{
		if (count($args = func_get_args()) == 0) return;
		$entire_row = is_bool(end($args)) ?  array_pop($args) : false;
		
		if ($q = call_user_func_array('self::Query', $args)) {
			if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
				if (self::$queryLogging) self::$queries[self::$num_queries]['fetch'] = 1;
				return $entire_row ? $row: reset($row);
			} else {
				return null;
			}
		}
		return false;
	}
	
	
	public static function QueryAll() // $query, $params = array()..., $use_first_col_as_key = false
	{
		if (count($args = func_get_args()) == 0) return;
		$use_first_col_as_key = is_bool(end($args)) ?  array_pop($args) : false;

		$r = array();
		
		if ($q = call_user_func_array('self::Query', $args)) {
			if ($use_first_col_as_key) { //return FETCH_GROUP
				while($row = $q->fetch(PDO::FETCH_ASSOC)) $r[reset($row)] = $row;
			} else {
				$r = $q->fetchAll(PDO::FETCH_ASSOC);
			}
			if (self::$queryLogging) self::$queries[self::$num_queries]['fetch'] = count($r);
			
			return $r;
		}
		return false;
	}
	
	
	/**
	 *  The function Fetches one result.
	 *  It will return a single value if only one column is specified. Otherwise it returns the whole row.
	 */
	public static function Get()
	{
		if (count($args = func_get_args()) == 0) 
			return false;
		
		if ($q = call_user_func_array('self::Query', $args)) {
			if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
				if (self::$queryLogging) self::$queries[self::$num_queries]['fetch'] = 1;
				return count($row) > 1 ? $row: reset($row);
			} else {
				return null;
			}
		}
		return false;
	}
	
	
	public static function GetRow()
	{
		$args = func_get_args();
		$args[] = true;
		return call_user_func_array('self::QuerySingle', $args);
	}
	
	
	public static function GetAll()
	{
		return call_user_func_array('self::QueryAll', func_get_args());
	}
	
	
	public static function Exec() // $query, $params = array()..., $throwExceptionOnError = true
	{
		if (($r = call_user_func_array('self::Query', func_get_args())) && self::$errno == 0) {
			return self::$affected_rows;
		}
		return false;
	}
	
	
	public static function Insert($table, array $rows, $replace = false)
	{
		if (empty($rows))
			return false;
		
		if (!is_array(reset($rows)))
			$rows = array($rows);
		
		$head = array_keys(current($rows));
		sort($head);
		
		$fields = array_map('self::escapeField', $head);
		
		$values = array();
		
		foreach($rows as $i => $row) {
			ksort($row); // Let's not be too strict, as long as all columns are there
			if (array_keys($row) !== $head) { // We need to make sure all rows contain the same columns
				if (self::$throwException)
					throw new Exception("INSERT ERROR: Unmatching columns on row $i, make sure each row contains the same columns");
				else
					return false;
			}
			$inserts[] = '(' . rtrim(str_repeat('?,', count($row)), ',') . ')';
			$values = array_merge($values, array_values($row));
			// $inserts[] = '(' . implode(',', array_map('self::escapeValue', $row)) . ')';
		}
		
		$command = $replace ? 'REPLACE INTO ' : 'INSERT INTO ';
		$command .= self::GetTableName($table);
		$command .= ' (' . implode(',', $fields) . ') VALUES ';
		$command .= implode(',', $inserts);
		
		return self::Exec($command, $values);
	}
}
