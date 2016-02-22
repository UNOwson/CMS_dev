<?php

class Plugins
{
	protected static $plugins = [];

	protected static $hooks = [];

	protected static $routes = [];

	protected static $dir = 'plugins/';

	/**
	 *  Define/execute plugin hook within our core code
	 *
	 *  @param string $name
	 *  @param array $args
	 *  @return void
	 */
	public final function load(array $plugins, $dir = null)
	{
		self::$dir = $dir ?: self::$dir;

		foreach($plugins as $plugin) {
			$class = 'Plugins\\'.basename($plugin, '.php');
			if (!class_exists($class, false) && file_exists(self::$dir . $plugin . '/main.php')) {
				self::$plugins[$plugin] = include self::$dir . $plugin . '/main.php';
				if (method_exists($class, 'init')) {
					$class::init();
				}
			}
		}

		return self::$plugins;
	}

	/**
	 *  Routes getter
	 */
	public static function routes()
	{
		return self::$routes;
	}

	/**
	 *  Execute hooks that were registered with Plugins::hook()
	 *
	 *  @param string $name   :  Hook/trigger/event name
	 *  @param array $args    :  Arguments to be passed to callbacks
	 *  @return void
	 */
	static final function trigger($name, array $args = [])
	{
		if (isset(self::$hooks[$name])) {
			foreach (self::$hooks[$name] as $hook) {
				call_user_func_array($hook, $args);
			}
		}
	}

	/**
	 *  Register plugin hook with a callback
	 *
	 *  @param string $name
	 *  @param callback $callback
	 *  return void;
	 */
	static final function hook($name, callable $callback)
	{
		if (!isset(self::$hooks[$name]))
			self::$hooks[$name] = [];

		self::$hooks[$name][] = $callback;
	}

	/**
	 *  Register a route to a plugin action/page
	 *  Typically your callback should return a path to a file, but it can hijack the process and die();
	 *
	 *  @param string $route
	 *  @param callback $callback
	 *  return void;
	 */
	static final function route($route, callable $callback)
	{
		// $prefix = '';

		// if ($route[0] !== '/') {
			// $prefix = '/plugin/' . strtolower(str_replace('Plugins\\', '', get_called_class()));
		// }

		self::$routes[$prefix . $route] = $callback;
	}


	/**
	 *  Return the plugin's settings table
	 *
	 *  return mixed;
	 */
	static final function settings()
	{
		if (!isset(static::$settings)) {
			return [];
		}

		$prefix = 'plugins.' . strtolower(str_replace('Plugins\\', '', get_called_class())) . '.';
		foreach(static::$settings as $k => $v) {
			$settings[$prefix.$k] = $v;
		}
		return $settings;
	}

	static final protected function config()
	{
		return call_user_func_array(
			'Site',
			array_merge(['plugins.' . get_class() . '.' . $name], func_get_args())
		);
	}
}