<?php
/*
 * Query Builder, QueryResult, Query
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>.
 *
 * Licensed under the ISC license:
 * 	http://opensource.org/licenses/ISC
 */

 

$user = $db->users->where('id', 1)->first();

$user->username = 'NewUsername';
$user->update();
$users = $user->all() // calls all() on parent Query object;

$db->users->truncate();


$db->users->where('id', 1)->orWhere('username', 'alex')->order(['users.username' => 'desc', 'a' => 'asc'])->build();
$db->users->where('id', 1)->join('table2', 'user_id')->limit(1)->build();
$db->users->whereId(1)->join('table2', 'user_id')->limit(1)->build();

$db->users->where_id(1)->join('table2', 'user_id')->limit(1)->first();
$db->users->whereId(1)->join('table2', 'user_id')->limit(1)->first();

$db->users->whereId(1)->join('table2', 'user_id')->take(1); //sets limit to 1 then return first()


$db->create('users', array (
							'id' => 'inc',
							'group' => 'integer',
							'username' => array('type' => 'string', 'index' => 'unique'),
							'email' => array('type' => 'string', 'length' => 255),
							'about' => 'text'
							));
							
$table = new Table('users');
$table->rename('new_users');
$table->add('facebook', 'string')->after('email');
$table->string('facebook')->after('email');

$table->facebook->unique();

$table->unique('facebook');

$table->drop('facebook');


//or
	//$type(name [, $option = primary/ai/unique/index])
$db->users->ai('id')->integer('blah')->string('username')->unique('username')->text('blah', 'index')->create();



$db->users->group('email')->build();

$db->{'users as u'}->{'d.username, dicks.size'}->where('id > sum(dicks.size)')->join('dicks as d', 'dicks.col = users.b')->build();

$db->query{'select * from users'}->all();

$db->users->update('username', 'alex');
$db->users->update(['username' => 'alex']);

$db->users->where('id', 2)->update(['a' => ['COUNT(*)']]);
$db->users->select('id', 'username')->build();
$db->users->select(['id', 'username'])->build();
$db->users->select(['id' => 'id', 'username'])->build();



$db->users->where_id(1)->and_username('alex')->build();
$db->users->where("`id` = 1 and `username` = 'alex'")->build();
$db->users->where('id', [1])->where('username', 'alex')->build();
$db->users->where(['id', 1], 'and', ['username', 'alex'])->build();
$db->users->where(['id', 1])->where('username', 'alex')->build();
$db->users->where(['id' => 1])->where('username', 'alex')->build();
$db->users->where(['id' => 1, 'username' => 'alex'])->build();



 
function MyDb() {
	return call_user_func_array(MyDb::$self, func_get_args());
}

class MyDb {

	public static  $self = null;
	private $db = null;
	private $host = null;
	private $user = null;
	private $password = null;
	private $database = null;
	private $prefix = null;
	
	public $throwException = false;
	public $logging = true;
	public $exec_time = 0;
	public $queries = array();
	public $num_queries = 0;
	
	
	
	public function __construct($host, $user = '', $password = '', $database = '', $prefix = '')
	{
		$this->db = @new mysqli($this->host = $host, $this->user = $user, $this->password = $password, $this->database = $database);
		$this->prefix = $prefix;
		self::$self = $this;
		
		if ($this->throwException && $this->db->connect_errno)
			throw new Exception('SQL: #'.$this->db->connect_errno.' '.$this->db->connect_error);
		
		$this->db->set_charset('utf8');
	}
	
	
	public function __invoke()
	{
		$args = func_get_args();
		
		if (preg_match('/^([`a-z0-9_\.]+)(\sas\s([`a-z0-9_]+))?$/i', $args[0])) {
			return new QueryBuilder($args[0], $this->prefix, $this);
		} else {
			return call_user_func_array(array($this, 'query'), $args);
		}
	}
	
	
	public function __get($prop)
	{
		return new QueryBuilder($prop, $this->prefix, $this);
	}
	
	
	public static function __callStatic($callname, $args)
	{
		$q = new QueryBuilder($callname, self::$self->prefix, self::$self);
		if (!empty($args)) $q->select($args);
		
		return $q;
	}
	
	
	public function table($table)
	{
		return new QueryBuilder($table, $this->prefix, $this);
	}
	
	
	public function query()
	{
		$params = func_get_args();
		
		if (count($params) == 0)
			return false;

		$qnb = ++$this->num_queries;

		$query = array_shift($params);
		
		$throwException = $params && is_bool(end($params)) ? array_pop($params) : $this->throwException;
		
		
		if (count($params) > 0) { //prepare query?
			$query = str_replace ('{%}', $this->prefix, $query);
		
			while(is_array(reset($params))) $params = reset($params);
		
			$count = 0;
		
			$query = preg_replace_callback('#\?([a-z]?)#', function ($m) use (&$params, &$count) {
				return $this->escape_value($params[$count++]);
			}, $query);
			
			if ($count != count($params))
				throw new Exception('Erreur SQL: Nombre de parametres invalide. '. $count. ' ? dans la requete, mais ' . count($params) . ' arguments.');
		}
		
		
		$start = microtime(true);
		
		$q = new Query($query, $this->db);

		if ($this->logging) {
			$this->queries[$qnb]['query'] = $query;
			$this->queries[$qnb]['time'] = $q->time();
			$this->queries[$qnb]['errno'] = $q->errno();
			$this->queries[$qnb]['error'] = $q->error();
			$this->queries[$qnb]['affected_rows'] = $q->affected_rows();
			$this->queries[$qnb]['num_rows'] = $q->num_rows();
			$this->queries[$qnb]['insert_id'] = $q->insert_id();
			$this->queries[$qnb]['params'] = &$params;
		
			foreach(debug_backtrace(false) as $trace) {
				if (isset($trace['file']) && $trace['file'] != __FILE__) {
					$this->queries[$qnb]['trace'] = $trace;
					break;
				}
			}
		}
		
		$this->exec_time += microtime(true) - $start;

		if ($throwException && $q->errno())
			throw new Exception('Erreur SQL: #'.$this->errno.' '.$this->error);
		
		return $q->errno() == 0 ? $q : null;
	}
	
	
	public function escape($string)
	{
		return $this->db->real_escape_string($string);
	}
	
	
	public function escape_value($value)
	{
		switch (gettype($value)) {
			case 'NULL': return 'NULL';
			case 'double':
			case 'integer': return $value;
			default: return '\''.$this->escape($value).'\'';
		}
	}
	
	
	public function error()
	{
		return $this->db->error;
	}
	
	
	public function errno()
	{
		return $this->db->errno;
	}

		
	public function affected_rows()
	{
		return $this->db->affected_rows;
	}
	
	
	public function insert_id()
	{
		return $this->db->insert_id;
	}
}










class Query implements SeekableIterator, Countable {

	private $db = null;
	private $query = null;
	
	private $fetch_mode = MYSQLI_ASSOC;
	
	private $affected_rows = 0;
	private $insert_id = 0;
	private $num_rows = 0;
	private $errno = 0;
	private $error = null;
	private $time = 0;
	
	private $primary_key = array();
	private $fields = array();
	private $rows = array();
	private $current = null;
	private $position = 0;
	
	
	public function __construct($query, $db)
	{
		$this->db = $db;
		$start = microtime();
		
		if ($this->query = $db->query($query)) {
			$this->errno = $this->db->errno;
			$this->error = $this->db->error;
			$this->insert_id = $this->db->insert_id;
			$this->affected_rows = $this->db->affected_rows;
			$this->num_rows = $this->query->num_rows;
			$this->time = microtime(true) - $start;
			
			if ($this->num_rows) {
				foreach($this->query->fetch_fields() as $i => $field) {
					 // 2: primary key 4: unique  512: auto increment. Favor primary, fallback on unique.
					if ($field->flags & 2 || (!isset($this->primary_key[$field->orgtable]) && $field->flags & 4))
					{
						$this->primary_key[$field->orgtable] = (object) array('key' => $field->orgname, 
																								'index' => $field->name,
																								'pos' => $i,
																								'table' => $field->orgtable);
					}
					
					$this->fields[$field->name] = (object) array(
																'table' => $field->orgtable,
																'alias' => $field->name,
																'name' => $field->orgname,
																'pos' => $i,
																'primary' => &$this->primary_key[$field->orgtable]
																);
				}
				
				if ($i+1 != count($this->fields)) {
					$this->fetch_mode = MYSQLI_BOTH;
					foreach($this->primary_key as &$key) $key->index = $key->pos;
				}
			}
		}
	}
	
	
	public function fields()
	{
		return $this->fields;
	}	
	
	
	public function keys()
	{
		return $this->primary_key;
	}
	
	
	public function one($col = null) // Return first $col or first col of current record. 
	{											// if $col === true, the whole row is returned
		if (!$this->current && !$this->seek(0))
			return null;
		
		if ($col === true)
			return $this->current;
		elseif ($col)
			return isset($this->current[$col]) ? $this->current[$col] : null;
		else 
			return reset($this->current);
	}
	
	
	public function column($column = null, $key = null)
	{
		if (!$column)
			$column = reset($this->fields)->name;
		
		if (!isset($this->fields[$column]))
			return null;
		
		$set = array();
		
		foreach($this->toArray() as $row) {
				$set[] = $row[$column];
		}
		
		return $set;
	}
	
	 
	public function count($mode = COUNT_NORMAL)
	{
		return $this->num_rows;
	}
	
	
	public function valid()
	{
		return $this->position < $this->num_rows && $this->position >= 0;
	}
	
	
	public function first()
	{
		return $this->seek(0);
	}
	
	
	public function last()
	{
		return $this->seek($this->num_rows - 1);
	}
	
	
	public function rewind()
	{
		return $this->seek(0);
	}	
	
	
	public function current()
	{
		return $this->seek($this->position);
	}
	
	
	public function row()
	{
		return $this->seek($this->position);
	}
	
	
	public function next()
	{
		return $this->seek(++$this->position);
	}
	
	
	public function key()
	{
		return $this->position; // could be nice to return primary key instead !
	}
	
	
	public function seek($row_id = null)
	{
		if (is_null($row_id)) $row_id = $this->position;
		
		if (!isset($rows[$row_id]) && $row_id < $this->num_rows) {
			$this->query->data_seek($row_id);
			$this->rows[$row_id] = $this->query->fetch_array($this->fetch_mode);
		}
		
		
		if (!isset($this->rows[$row_id])) return null;
		
		
		$this->position = $row_id;
		$this->current = &$this->rows[$row_id];
		
		return new QueryResult($this->current, $this->position, $this);
	}
	
	
	public function get($row_id = null)
	{
		if (is_integer($row_id))
			return $this->seek($row_id);
		elseif(is_null($row_id))
			return $this->all();
		else
			return $this->column($row_id);
	}
	
	
	public function all($col_as_key = null)
	{
		$this->toArray($col_as_key);
		$set = array();
		for ($i = 0; $i < $this->num_rows; $i++) {
			$set[] = $this->seek($i);
		}
		
		return $set;
	}

	
	public function toArray($col_as_key = null)
	{
		if (count($this->rows) == $this->num_rows) {
			return $this->rows;
		}
		
		for ($i = 0; $i < $this->num_rows; $i++) {
			$this->seek($i);
		}
		
		return $this->rows;
	}
	
	
	public function lists($column)
	{
		return $this->column($column);
	}
	
	
	public function affected_rows()
	{
		return $this->affected_rows;
	}	
		
	
	public function num_rows()
	{
		return $this->num_rows;
	}	
	
	
	public function insert_id()
	{
		return $this->insert_id;
	}
	
	
	public function error()
	{
		return $this->error;
	}
	
	
	public function errno()
	{
		return $this->errno;
	}	
	
	
	public function time()
	{
		return $this->time;
	}
}




/*
	Query will run when we try to iterate over the querybuilder, when it is casted to array, 
	or with selected methods such as one() first() all() update() delete() truncate() drop() 
	insert() insertGetId() replace() run()
*/

class QueryBuilder {

	private $db = null;

	private $table = '';
	private $alias = '';
	private $prefix = '';
	
	private $command = 'SELECT';
	
	private $tables = array();
	private $updates = array();
	private $inserts = array();
	private $select = array();
	private $join = array();
	private $where = array();
	private $group = array();
	private $order = array();
	private $limit = '';
	
	
	
	/**
	 *  Creates a new QueryBuilder
	 *  
	 *  @param string $table
	 *  @param string $prefix
	 *  @param MyDb $db	 
	 *  @return void
	 */
	public function __construct($table, $prefix = '', &$db = null)
	{
		$this->table = $table;
		$this->tables[] = strstr($this->table . ' ', ' ', true);
		
		$this->prefix = $prefix;
		$this->db = $db;
	}
	
	
	/**
	 *  
	 */
	public function __call($callname, $args)
	{
		//exec query if Query::callname exists eg $q = new QueryBuilder('table'); $q->where('id', 1)->first(); 
		if (method_exists('Query', $callname)) {
			$query = $this->run();
			return $query ? call_user_func_array(array($query, $callname), $args) : null;
		} else {
			$p = explode('_', $callname);
			switch ($p[0]) {
				case 'where':
				case 'and':
					array_unshift($args, $p[1]);
					return $this->where($args);
				case 'or':
					array_unshift($args, $p[1]);
					return $this->orwhere($args);
			}
		}
	}

	
	/**
	 *  It sets select if not set, otherwise it runs select and fetch column
	 *  
	 *  @param string $select
	 *  @return QueryBuilder|Query
	 */
	public function __get($select) // select
	{
		if (!$this->select)
			return $this->select($select);
		else
			return $this->run()->column($select);
	}
	
	
	/**
	 *  Select WHERE. 
	 *  Valid syntaxes: where('id', 1) where('id', '>', 1)
	 *  Valid syntaxes: where('id', [1, 2, 3]) // IN 
	 *  where(['id' => 1, 'name' => 'alex']) // AND
	 *  where(['id', 1], ['name', 'alex']) // OR
	 *  where(['id', 1], 'and', ['name', 'alex'])
	 *  
	 *  @param mixed $where_clause...
	 *  @return QueryBuilder
	 */
	public function where()
	{
		if ($this->where) $this->where[] = 'and';
		$this->where[] = func_get_args();
		return $this;
	}
	
	
	/**
	 *  See WHERE
	 *  
	 *  @param mixed $where_clause...
	 *  @return QueryBuilder
	 */
	public function orwhere()
	{
		if ($this->where) $this->where[] = 'or';
		$this->where[] = func_get_args();
		return $this;
	}
	
	
	/**
	 *  WHERE alias
	 *  
	 *  @param mixed $where_clause...
	 *  @return QueryBuilder
	 */
	public function andwhere()
	{
		return call_user_func_array(array($this, 'where'), func_get_args());
	}
	
	
	/**
	 *  SQL JOIN
	 *  
	 *  join('table2 as t2')
	 *  join('table2', 'page_id') // USING
	 *  join('table2', 'table.id = table2.id') // ON
	 *  
	 *  @param string $table
	 *  @param string $on
	 *  @param string $type
	 *  @return QueryBuilder
	 */
	public function join($table, $on = '', $type = '')
	{
		$join = array('table' => $table, 'alias' => '', 'on' => $on, 'type' => $type);
		
		if (preg_match('/^(?<t>[a-z0-9_\.]+)\s+as\s+(?<a>[a-z0-9_]+)$/i', func_get_arg(0), $m)) {
			$join['table'] = $m['t'];
			$join['alias'] = $m['a'];
			$this->tables[] = $m['t'];
		}
		
		$this->join[] = $join;
		
		return $this;
	}
	
	
	/**
	 *  SQL LEFT JOIN
	 *  
	 *  leftjoin('table2 as t2')
	 *  leftjoin('table2', 'page_id') // USING
	 *  leftjoin('table2', 'table.id = table2.id') // ON
	 *  
	 *  @param string $table
	 *  @param string $on
	 *  @return QueryBuilder
	 */
	public function leftjoin($table, $on = '')
	{
		return $this->join($table, $on, 'LEFT');
	}
	
	
	/**
	 *  ORDER BY
	 *  'column'
	 *  'column', 'desc'
	 *  ['column' => 'desc', 'column2' => 'asc']
	 *  
	 *  @param mixed $column
	 *  @param string $direction
	 *  @return QueryBuilder
	 */
	public function order($column, $direction = 'ASC')
	{
		if (is_array($column)) {
			foreach($column as $k => $v) $this->order($k, $v);
			return $this;
		}
		
		$direction = strtoupper($direction) == 'DESC' ? 'DESC' : 'ASC';
		$this->order[] = $column . ' ' . $direction;
		
		return $this;
	}
	

	/**
	 *  GROUP BY
	 *  
	 *  $column
	 *  [$column, $column, $column]
	 *  'expression'
	 *  
	 *  @param mixed $column
	 *  @return QueryBuilder
	 */
	public function group($column)
	{
		if (is_array($column))
			array_map(array($this, 'group'), $column);
		else
			$this->group[] = $column;
		
		return $this;
	}
	

	/**
	 *  LIMIT
	 *  
	 *  @param int $count
	 *  @param int $from
	 *  @return QueryBuilder
	 */
	public function limit($count, $from = null)
	{
		$this->limit = (string)$count;
		if ($from) 
			$this->limit .= ', ' . $from;
		return $this;
	}
	
	
	/**
	 *  Runs select query
	 *  
	 *  
	 *  @param mixed $columns | integer $row_id
	 *  @return Query|QueryResult|null
	 */
	public function get()
	{
		$this->command = 'SELECT';
		
		$args = func_get_args();
		
		if (count($args) == 1 && is_integer($args[0])) {
			$q = $this->run();
			return $q ? $q->seek($args[0]) : null;
		} elseif(!empty($args)) {
			$this->select = array();
			call_user_func_array(array($this, 'select'), func_get_args());
		}
		
		$q = $this->run();
		
		return $q ? $q->all() : null;
	}
	
	
	/**
	 *  Add select fields
	 *  
	 *  'field', 'field', 'field as alias', 'table.field'
	 *  'field as alias, field, field, table.field'
	 *  ['alias' => 'count(*)', 'alias' => 'field', 'field']
	 *  
	 *  @param mixed $fields
	 *  @return QueryBuilder
	 */
	public function select()  //select can be a string or an array
	{
		$this->command = 'SELECT';
		
		if (func_num_args())
			$this->select[] = func_get_args();
		
		return $this;
	}
	
	
	/**
	 *  Sets command to SELECT DISTINCT
	 *  
	 *  @return QueryBuilder
	 */
	public function distinct()
	{
		$this->command = 'SELECT DISTINCT';
		return $this; // or execute now ?
	}
	
	
	/**
	 *  SQL Update
	 *  
	 *  $field, $newvalue
	 *  array($field => $newvalue, $field2 => [$expression])
	 *  
	 *  @param string $field
	 *  @param string $newvalue
	 *  @return null|int $affected_rows
	 */
	public function update() //Returns affected rows
	{
		$this->command = 'UPDATE';
		
		$updates = func_get_args();
		
		if (count($updates) == 2) {
			$this->updates[] = $this->prefix_fields($updates[0]) . ' = ' . $this->db->escape_value($updates[1]);
		} elseif(is_array($updates[0])) {
			foreach($updates[0] as $key => $value) {
				$this->updates[] = $this->prefix_fields($key) . ' = ' . (is_array($value) ? array_pop($value) : $this->db->escape_value($value));
			}
		}
		
		if ($q = $this->run())
			return $affected_rows ? $q->affected_rows() : $q;
		
		return null;
	}
	
	
	/**
	 *  SQL Insert
	 *  
	 *  array($field => $value, $field2 => $value, $field3 => [$non_escaped_expression])
	 *  array(array($field => $value, $field2 => $value), array($field => $value, $field2 => $value))
	 *  
	 *  @param array $inserts
	 *  @param boolean $affected_rows
	 *  @return null|int $affected_rows
	 */
	public function insert(array $inserts, $affected_rows = true) //Returns affected rows
	{
		$this->command = 'INSERT INTO';
		
		if ($q = $this->run())
			return $affected_rows ? $q->affected_rows() : $q;
			
		return null;
	}

	

	/**
	 *  SQL Insert and returns insert_id
	 *  
	 *  array($field => $value, $field2 => $value, $field3 => [$non_escaped_expression])
	 *  array(array($field => $value, $field2 => $value), array($field => $value, $field2 => $value))
	 *  
	 *  @param array $inserts
	 *  @return null|int $insert_id
	 */
	public function insertGetId(array $inserts) //runs insert() then returns last insert ID
	{
		$q = $this->insert($inserts, false);
		return $q ? $q->insert_id() : null;
	}
	
	
	/**
	 *  SQL Delete
	 *  
	 *  @return null|int $affected_rows
	 */
	public function delete()
	{
		$this->command = 'DELETE FROM';
		return $q = $this->run() ? $q->affected_rows() : null;
	}
	
	
	/**
	 *  SQL Replace into
	 *  
	 *  array($field => $value, $field2 => $value, $field3 => [$non_escaped_expression])
	 *  array(array($field => $value, $field2 => $value), array($field => $value, $field2 => $value))
	 *  
	 *  @param array $inserts
	 *  @param boolean $affected_rows
	 *  @return null|int $affected_rows
	 */
	public function replace(array $inserts)
	{
		$this->command = 'REPLACE INTO';
		return $q = $this->run() ? $q->affected_rows() : null;
	}
		
	
	/**
	 *  SQL Truncate
	 *  
	 *  @return boolean
	 */
	public function truncate()
	{
		$this->command = 'TRUNCATE TABLE';
		return $this->run();
	}
	
	
	/**
	 *  SQL Drop
	 *  
	 *  @return boolean
	 */
	public function drop()
	{
		$this->command = 'DROP TABLE';
		return $this->run();
	}
	
	
	/**
	 *  Builds SQL and runs the query
	 *  
	 *  @return null|Query
	 */
	public function run()
	{
		return $this->db->Query($this->build());
	}
	
	
	/**
	 *  This function adds the table prefix to known table names.
	 *  
	 *  @param string $expr
	 *  @return string
	 */
	private function prefix_fields($expr)
	{
		if ($this->tables && strpos($expr, '"') === false && strpos($expr, "'") === false) {
			return preg_replace('/(^|[\s,\(])(' . implode('|', $this->tables) . ')\./i', '$1' . $this->prefix . '$2.', $expr);
		}
		return $expr;
	}
	
	
	/**
	 *  Parses a select chunk (see this->select())
	 *  
	 *  @param mixed $select
	 *  @return string finished_list
	 */
	private function parse_select($select)
	{
		$selects = array();
		
		if (is_string($select))
			$select = array($select);
			
		foreach($select as $alias => $expr) {
			if (is_array($expr)) {
				$selects[] = $this->parse_select($expr);
			} elseif (is_int($alias)) {
				if (preg_match('/^([a-z0-9_\.,\s]+)$/i', $expr)) {
					foreach(array_map('trim', explode(',', $expr)) as $expr) {
						$selects[] =  $this->prefix_fields($expr);
					}
				} else {
					$selects[] =  $expr;
				}
			} else {
				$selects[] = $this->prefix_fields($expr) . ' as `' . $alias . '`';
			}
		}
		
		return implode(', ', $selects);
	}
	
	
	/**
	 *  Parses a where clause (see this->where())
	 *  
	 *  @param mixed $where
	 *  @return string finished_clause
	 */
	private function parse_where($where)
	{
		if (!is_array($where)) { // format: raw where
			$where = array($where);
		}
		$where_parts = array();
		
		if (isset($where[0])) {
			if (is_array($where[0])) { // format ['id', (op), 'value'], (op), ['id', (op), 'value']
				for ($i = 0, $count = count($where); $i < $count; $i++) {
					if (is_array($where[$i])) {
						$where_parts[] = $this->parse_where($where[$i]);
						if ($count != $i+1 && (!isset($where[$i+1]) || is_array($where[$i+1]))) {
							$where_parts[] = ' or ';
						}
					} else {
						$where_parts[] = ' ' . $where[$i] . ' ';
					}
				}
			} else { //format: id, (op), value
				switch(count($where)){
					case 3:
						$where_parts[] = $this->prefix_fields($where[0]) . ' ' . $where[1] . ' ' . $this->db->escape_value($where[2]);
						break;
					case 2:
						if (is_array($where[1])) {
							array_walk($where[1], array($this->db, 'escape_value'));
							$where_parts[] = $this->prefix_fields($where[0]) . " IN (" . implode(',', $where[1]) . ")";
						} else {
							$where_parts[] = $this->prefix_fields($where[0]) . ' = ' . $this->db->escape_value($where[1]);
						}
						break;
					default:
						$where_parts[] = $this->prefix_fields($where[0]);
				}
			}
		} else {
			foreach($where as $key => $value) {
				$where_parts[] = $this->prefix_fields($key) . ' = ' . $this->db->escape_value($value);
			}
			$where_parts = array(implode(' and ', $where_parts));
		}
		
		return count($where_parts) > 1 ? '(' . implode($where_parts) . ')' : implode($where_parts);
	}
	
	
	/**
	 *  This is the actual SQL generator
	 *  
	 *  @return string query
	 */
	public function build() //Create the actual SQL
	{
		// build where string
		$query = $this->command.' ';
		
		switch($this->command) {
			case 'SELECT':
			case 'SELECT DISTINCT':
				$query .= $this->parse_select($this->select) ?: '*';
				$query .= ' FROM ' . $this->prefix.$this->table;
				
				if ($this->alias)
					$query .= ' AS ' . $this->alias;
					
				foreach ($this->join as $join) {
					if ($join['type'])
						$query .= ' ' . $join['type'];
					
					$query .= ' JOIN ' . $this->prefix.$join['table'];
					
					if ($join['alias'])
						$query .= ' AS ' . $join['alias'];
						
					if (preg_match('/^([a-z0-9_\.]+)$/i', $join['on']))
						$query .= ' USING(' . $this->prefix_fields($join['on']) .')';
					
					elseif ($join['on'])
						$query .= ' ON ' . $this->prefix_fields($join['on']);
				}
				
				if ($this->where)
					$query .= ' WHERE ' . implode(' ', array_map(array($this, 'parse_where'), $this->where));
				
				if ($this->group)
					$query .= ' GROUP BY ' . implode(', ', array_map(array($this, 'prefix_fields'), $this->group));
				
				if ($this->order)
					$query .= ' ORDER BY ' . implode(', ', array_map(array($this, 'prefix_fields'), $this->order));
				
				if ($this->limit)
					$query .= ' LIMIT ' . $this->limit;
				break;
			
			
			case 'INSERT INTO':
			case 'REPLACE INTO':
				$query .= $this->prefix.$this->table;
				break;
				
				
			case 'UPDATE':
				$query .= $this->prefix.$this->table;
				$query .= ' SET ' . implode (', ', $this->updates);
				if ($this->where)
						$query .= ' WHERE ' . implode(' ', array_map(array($this, 'parse_where'), $this->where));
				break;
				
				
			case 'DELETE FROM':
				$query .= $this->prefix.$this->table;
				if ($this->where)
						$query .= ' WHERE ' . implode(' ', array_map(array($this, 'parse_where'), $this->where));
				break;
			
			
			case 'TRUNCATE TABLE':
			case 'DROP TABLE':
				$query .= $this->prefix.$this->table;
				break;
		}
		
		return $query;
	}
}











class QueryResult extends ArrayObject {
	
	private $staged_changes = array();
	private $primary_key = array();
	private $fields = array();
	private $row = array();
	private $queryObj = null;
	private $current = 0;
	
	public function __construct($row, $current, $queryObj)
	{
		$this->row = $row;
		$this->queryObj = $queryObj;
		$this->fields = $queryObj->fields();
		$this->primary_key = $queryObj->keys();
		$this->current = $current;
		
		foreach($this->primary_key as &$key) {
			$key->value = $this->row[$key->index];
		}
		
		foreach($this->fields as &$field) {
			$field->value = &$this->row[$field->name];
		}
		
		parent::__construct($row);
	}

	public function __call($callname, $args)
	{
		if (method_exists('Query', $callname)) {
			$this->queryObj->get($this->current);
			return call_user_func_array(array($this->queryObj, $callname), $args);
		}
	}

	
	public function __get($offset)
	{
		return $this->offsetGet($offset);
	}
	 
	 
	public function __set($offset, $value)
	{
		$this->offsetSet($offset, $value);
	}
	
	
	public function offsetSet($offset, $value)
	{
		if (!is_null($offset)) {
			$this->staged_changes[$offset] = $value;
		}

		parent::offsetSet($offset, $value); // Maybe should be commented and applied only after successful update() ?
	}
	 
	 
/*
		Will update the fields from $this->staged_changes if the related table has a primary key
		We might want to accept an alternate where condition too.
		
		otherwise return false
	*/
	
	public function update()
	{
		foreach($this->staged_changes as $column => $value) {
			if (isset($this->fields[$column]) && $this->fields[$column]->primary) {
				echo "Updating table {$this->fields[$column]->table} setting {$this->fields[$column]->name} (alias {$column}) 
						value to '$value' using primary key {$this->fields[$column]->primary->key} = {$this->fields[$column]->primary->value} <br>";
				//parent::offsetSet($column, $value);
			}
		}
	}
	
	
	
	/*
		Will delete the result based on its first primary key and table
		You can also specify an alternate column (it should be primary otherwise nasty things could happen)
		
		if column is null we use the first table listed if it has a primary key. 
		
		otherwise return false
	*/
	
	public function delete($column = null)
	{
		if (!$column && $delete = reset($this->primary_key)) {
			$delete->name = $delete->key;
			$this->{$delete->key} = $delete->value;
		} elseif (isset($this->fields[$column])) {
			$delete = $this->fields[$column];
		} else {
			return false;
		}
		
		echo "Deleting from {$delete->table} using {$delete->name} = {$this->row[$delete->name]} <br>";
	}
}