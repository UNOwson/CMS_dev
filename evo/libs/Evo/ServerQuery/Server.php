<?php

namespace Evo\ServerQuery;
use \Exception;

class Server {
	
	public static function __callStatic($call, $args)
	{
		$args[] = $call;
		return call_user_func_array('self::query', $args);
	}
	
	
	public static function query($host, $port, $type)
	{
		if (method_exists('Evo\ServerQuery\GameServerQuery', 'query'.$type)) {
			return GameServerQuery::{'query'.$type}($host, $port);
		} elseif (method_exists('Evo\ServerQuery\StreamingQuery', 'query'.$type)) {
			return StreamingQuery::{'query'.$type}($host, $port);
		} else {
			throw new exception('Server type unsupported!');
		}
	}
	
	
	public static function isOnline($host, $port, $type)
	{
		if ($type === 'minecraft' || !method_exists('Evo\ServerQuery\GameServerQuery', 'query'.$type)) { // No need for the full ping
			return @fclose (@fsockopen ($host, $port, $err, $errstr, 2));
		}
		
		return self::query($host, $port, $type);
	}
	
}