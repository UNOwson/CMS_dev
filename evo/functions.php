<?php
/*
 * Evo-CMS
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */

defined('EVO') or die('Que fais-tu là?');

/**
 *  get user from id
 */
function get_user($id)
{
	$user = Db::Get('select a.*, g.name as gname, g.color as color
					 from {users} as a
					 left join {groups} as g ON a.group_id = g.id
					 where a.id = ?',
					 $id);

	if ($user) {
		$user['group'] = get_group($user['group_id']);
	}

	return $user;
}


/**
 *  get group from id
 */
function get_group($id)
{
	$group = Db::Get('select * from {groups} where id = ?', $id);
	if ($group) {
		$group['permissions'] = group_permissions($id);
	}
	return $group;
}


/**
 *  Core login function.
 *
 *  @param string $user_id User id you wish to login with
 *  return boolean
 */
function cookie_login($user_id = null, $expire = 0)
{
	global $user_session, $cookie_name, $cookie_domain, $_timezones;

	session_name($cookie_name);
	session_set_cookie_params($expire > time() ? $expire - time() : $expire, '/', $cookie_domain);

	if (!session_id() && !session_start()) return false;

	if (!$user_id && !isset($_SESSION['user_id']) && isset($_COOKIE[$cookie_name . '_login'])) {
		if (count($p = explode('|', $_COOKIE[$cookie_name . '_login'])) == 2) {
			$user = Db::Get('select * from {users} where id = ?', $p[0]);
			if ($p[1] == sha1($user['username'].$user['password'].$user['salt'])) {
				$user_id = $user['id'];
				log_event($user['id'], 'user', 'Connexion sur le site depuis un cookie.');
			}
		}
	}

	if ($user_id || isset($_SESSION['user_id'])) {
		$user_session = get_user($user_id ?: $_SESSION['user_id']);
	}

	if ($user_session && $user_session['id']) {
		if ($user_session['locked']) {
			cookie_destroy();
			return false;
		}
		$_SESSION['user_id'] = $user_session['id'];

		if (!empty($_timezones[$user_session['timezone']])) {
			$_SESSION['timezone'] = $_timezones[$user_session['timezone']];
			date_default_timezone_set($_timezones[$user_session['timezone']]);
		}

		if (!isset($_SESSION['last_visit']))
			$_SESSION['last_visit'] = $user_session['activity'];

		if (!isset($_COOKIE[$cookie_name.'_login']))
			setcookie($cookie_name.'_login', $user_session['id'].'|'.sha1($user_session['username'].$user_session['password'].$user_session['salt']), $expire, '/');

		if ($user_session['activity'] < time() - 90) {
			$set[] = 'activity = ?';
			$set_vars[] = time();
			if ($_SERVER['REMOTE_ADDR'] !== $user_session['last_ip']) {
				$set[] = 'last_ip = ?';
				$set_vars[] = $_SERVER['REMOTE_ADDR'];
			}
			if ($_SERVER['HTTP_USER_AGENT'] !== $user_session['last_user_agent']) {
				$set[] = 'last_user_agent = ?';
				$set_vars[] = $_SERVER['HTTP_USER_AGENT'];
			}
			$set_vars[] = $user_session['id'];
			Db::Exec('update {users} set '.implode(', ', $set).' where id = ?', $set_vars);
		}
		plugins::trigger('cookie_login', [$user_session, $expire]);
		return true;
	} else {
		cookie_destroy();
	}
	return false;
}


/**
 *  Destroy current cookie
 *
 *  @return void
 */
function cookie_destroy()
{
	global $cookie_name, $cookie_domain, $user_session;

	plugins::trigger('cookie_destroy');

	$_SESSION['user_id'] = 0;
	$user_session = ['id' => 0, 'group_id' => 4, 'username' => 'Invité', 'email' => null];

	if (isset($_COOKIE[$cookie_name.'_login'])) {
		setcookie($cookie_name.'_login', null, 10, '/');
	}
}


/**
 *  Check if current visitor matches one of our ban rules
 *
 *  @return void
 */
function check_banlist($visitor = null)
{
	global $user_session;
	$visitor = $visitor ?: $user_session;

	if (!isset($_SESSION['country'])) {
		$_SESSION['country'] = geoip_country_code($_SERVER['REMOTE_ADDR']);
	}

	if (rand(0, 3) === 1) {
		foreach(Db::QueryAll('select * from {banlist} where expires <> 0 and expires < '. time()) as $ban) {
			log_event(0, 'admin', 'Expiration d\'une règle de banissement: ' . $ban['type'] . ' = ' . $ban['rule']);
			Db::Exec('delete from {banlist} where id = ?', $ban['id']);
		}
	}
	
	$visitor = $visitor + ['group_id' => 4, 'email' => '', 'username' => ''];
	
	if ($visitor['group_id'] == 1) {
		return null;
	}
	
	$banlist = Db::Get('select *
						from {banlist}
						where (? like rule and type = "email")
						   or (? like rule and type = "username")
						   or (? like rule and type = "ip")
						   or (? like rule and type = "country")',
						$visitor['email'],
						$visitor['username'],
						$_SERVER['REMOTE_ADDR'],
						$_SESSION['country']);
	return $banlist;
}


/**
 *  Compare password hashes testing mybb and punbb compatible hashes.
 *
 *  @param string $reference the hash to check against
 *  @param string $password
 *  @param string $salt
 *  @return boolean
 */
function compare_password($reference, $password, $salt)
{
	switch(strlen($reference)) {
		case 32: //md5 hash, assume mybb style
			return md5(md5($salt).md5($password)) === $reference;
		case 40: //sha1 hash, assume punbb style or plain sha1. Our default format is punbb style
			return sha1($salt.sha1($password)) === $reference || sha1($password) === $reference;
		default:
			return false;
	}
}


function hash_password($password, $salt = null)
{
	if ($salt === null) {
		$salt = salt_password(15);
		return [sha1($salt.sha1($password)), $salt];
	}

	return sha1($salt.sha1($password));
}


function salt_password($rounds = 3)
{
	$string = '!@#$%^&*()_+}{qwertyuiopasdfghjklzxcvbnm1234567890-=!@#$%^&*()_+POIUYTREWQASDFGHJKLMNBVCXZ;./';
	for(;$rounds > 0; --$rounds) {
		$string = str_shuffle($string);
	}
	return substr($string, 0, 12);
}


/**
 *  Returns an array of permissions for $group_id
 *
 *  @param int #group_id
 *  @return array
 */
function group_permissions($group_id)
{
	$q = Db::QueryAll('select * from {permissions} where group_id = ? and value <> 0', $group_id);
	$permissions = [];
	foreach($q as $p) {
		if ($p['related_id']) {
			$permissions[$p['name']][$p['related_id']] = $p['value'];
		} else {
			$permissions[$p['name']] = $p['value'];
		}
	}
	return $permissions;
}


/**
 *  Verifiy if a group is granted a permission.
 *
 *  @param int $group_id
 *  @param string $name
 *  @param null|int $rel_id
 *  @return boolean|integer
 */
function group_has_permission($group_id, $name, $rel_id = null)
{
	static $permissions;
	static $permission_groups;

	if (!isset($permissions[$group_id])) {
		$permissions[$group_id] = group_permissions($group_id);
		foreach($permissions[$group_id] as $perm => $value) {
			if ( (is_array($value) && in_array(1, $value)) || $value > 0 )
				$permission_groups[$group_id][strstr($perm, '.', true)] = $value;
		}
	}

	if ($rel_id && isset($permissions[$group_id][$name][$rel_id]) && $permissions[$group_id][$name][$rel_id])
		return $permissions[$group_id][$name][$rel_id];

	if (!$rel_id && isset($permissions[$group_id][$name]) && $permissions[$group_id][$name])
		return $permissions[$group_id][$name];

	if (substr($name, -1) == '.' && isset($permission_groups[$group_id][substr($name, 0, -1)]))
		return true;

	return false;
}


/**
 *  Verifiy if current user is granted a permission
 *  If $name is empty, the function will return true if the user is logged in, false otherwise.
 *
 *  @param string $name
 *  @param integer|null $rel_id
 *  @param boolean $redirect redirect to 403 on failure
 *  @return boolean
 */
function has_permission($name = '', $rel_id = null, $redirect = false)  // Si $name est vide alors on test si logged in.
{
	global $user_session;

	if (empty($name) && isset($user_session['id']) && $user_session['id'])
		return true;

	if ($name && strpos($name, '.') == false) $name = 'user.'.$name;// temp fix
	if (is_bool($rel_id)) {$redirect = $rel_id; $rel_id = null ;} // temp fix

	if ($r = group_has_permission($user_session['group_id'], $name, $rel_id))
		return $r;

	if ($redirect == true) {
		header('Location: ' . create_url('403', ['redir'=>$_SERVER['REQUEST_URI']]));
		die('Permission refusee');
	} else {
		return false;
	}
}


/**
 *  Simple pagination script
 *
 *  @param integer $total total pages
 *  @param integer $page current page
 *  @param integer $display how many pages to display at once
 *  @param string $link link format
 *  @param integer $prev previous page (to decide if the uses is moving forward or backward)
 *  @return string html
 */
function paginator($total, $page, $display = 10, $link = null, $prev = 0)
{
	$r = '<div class="text-center"><ul class="pagination paginator">';

	$total   = ceil($total);
	$display = ceil($display);
	$page    = (int)$page;
	$prev    = (int)$prev;

	if (!$link) {
		$args = $_GET;
		unset($args['pn']);
		$args['pn'] = '';
		if ($prev) $args['prevpn'] = $prev;
		$link = '?'.http_build_query($args);
	}

	if ($page <= 1)
	  $r .= '<li class="disabled"><a>Prev</a></li>';
	else
	  $r .= '<li><a href="'.$link.($page-1).'">Prev</a></li>';

	$range = paginator_range($total, $page, $display, $prev);

	foreach($range as $i => $l)
		if ($i == $page)
			$r .= '<li class="active"><span>'.$i.' <span class="sr-only">(current)</span></span></li>';
		else
			$r .= '<li><a href="'.$link.$i.'">'.$l.'</a></li>';

	if ($page >= $total)
	  $r .= '<li class="disabled"><a>Next</a></li>';
	else
	  $r .= '<li><a href="'.$link.($page+1).'">Next</a></li>';

	$r .= '</ul></div>';
	return $r;
}


function paginator_range($total, $page, $display = 10, $prev = 0)
{
	$page = (int)$page;
	$display = $display - 1;
	$range = [];

	if ($total > 0 && $display > 0) {
		$end = ceil($page / $display) * $display +1;
		$start = (int)$end - $display;

		if ($page == $start && $prev > $page && $page != 1) {
			$end = ceil(($page-1) / $display) * $display +1;
			$start = $end - $display;
		}

		$first = $start ?: 1;
		$last = $end > $total ? $total : $end;

		$first_tip = $tip = ceil($first / 2);
		$last_tip = $last + round(($total - $last) / 2);

		$range = [$first_tip => '...', $last_tip => '...', $total => $total, 1 => 1];

		if ($page == $first && $page !== 1) {
			$first--;
			$last--;
		}

		if ($page == $last && $page != $last) {
			$first++;
			$last++;
		}

		if ($first_tip != 1) {
			if ($page-1 > $first) {
				$first++;
			}
			elseif ($page+1 < $last) {
				$last--;
			}
		}

		if ($last_tip != $total) {
			if ($page-1 > $first) {
				$first++;
			}
			elseif ($page+1 < $last) {
				$last--;
			}
		}

		$range = $range + array_combine(range($first, $last), range($first, $last));

	}

	ksort($range);

	return $range;
}


/**
 *  GET POST getter
 *
 *  @param string $k GET key
 *  @param mixed $default return if key is not set.
 *  @return mixed
 */
function _GP($k, $default = null)
{
	return _GET($k, _POST($k, $default));
}


/**
 *  GET getter
 *  if key is empty AND the first GET element has no value (?user&id=1) then "user" is returned
 *
 *  @param string $k GET key
 *  @param mixed $default return if key is not set.
 *  @return mixed
 */
function _GET($k, $default = null)
{
	if (isset($_GET[$k]))
		return $_GET[$k];
	elseif ($k === '' && reset($_GET) === '') {
		if ($_SERVER['REQUEST_URI']) { // We try that first because PHP replaces . by _ in $_GET keys
			$first_get_param = explode('?', $_SERVER['REQUEST_URI'])[1];
			$first_get_param = explode('&', $first_get_param)[0];
			return $first_get_param;
		}
		return key($_GET);
	}
	return $default;
}


/**
 *  POST getter
 *
 *  @param string $k GET key
 *  @param mixed $default return if key is not set.
 *  @return mixed
 */
function _POST($k, $default = null)
{
	if (isset($_POST[$k]))
		return $_POST[$k];
	else
		return $default;

	// $args = array_filter(array_slice(func_get_args(), 1), function($v){return !e_empty($v);});
	// return array_shift($args);
}


/**
 *  enhanced empty: accepts many variables at once and do a trim check too
 *
 *  @param mixed $variable...
 *  @return boolean
 */
function e_empty()
{
	foreach(func_get_args() as $arg) {
		if (is_null($arg) || $arg == [] || is_string($arg) && trim($arg) === '') return true;
	}
	return false;
}


/**
 *  Return avatar URL (gravatar or local)
 *
 *  @param string $u array containing avatar email and/or email
 *  @param integer $size the size to return. Optional
 *  @param string $url_only return url instead of img tag
 *  @return string
 */
function get_avatar(array $u, $size = 85, $url_only = false)
{
	global $_community;

	if (!empty($u['avatar'])) {
		if (is_file(ROOT_DIR . $u['avatar']))
			$url = get_asset($u['avatar']);
		elseif ($u['avatar'] === 'ingame' && isset($_community['avatar']) && !empty($u['ingame']))
			$url = $_community['avatar']($u['ingame']);
	}
	elseif(!empty($u['email']))
		$url = '//www.gravatar.com/avatar/' . md5(trim($u['email'])) . '?s=' .
				(is_int($size) ? $size : 85); //. '&d=' . urlencode(get_asset('img/avatar.png', 1));


	if (empty($url))
		$url = get_asset('/img/avatar.png');

	if ($url_only || $size === true)
		return $url;
	else
		return '<img src="' . $url . '" alt="avatar" class="avatar" height="'.$size.'" width="'.$size.'">';
}


/**
 *  Get a list of installed themes
 *
 *  @return array
 */
function get_themes()
{
	$themes = [];

	foreach (glob(ROOT_DIR . '/themes/*', GLOB_ONLYDIR) as $dirname) {
		if (file_exists($dirname.'/index.php')) {
			$theme = include $dirname.'/index.php';
			isset($theme['name']) or $theme['name'] = basename($dirname);
			isset($theme['categorie']) or $theme['categorie'] = '';
		} else {
			$theme = ['name' => basename($dirname), 'categorie' => ''];
		}

		$themes[$theme['categorie']][basename($dirname)] = $theme['name'];
	}

	return $themes;
}


/**
 *  Find an asset file. It will look in the current theme, then in the default theme, then in the ROOT_DIR.
 *
 *  @param string $filename
 *  @param boolean $absolute_url
 *  @return bool|string
 */
function get_asset($filename, $absolute_url = true)
{
	if (file_exists(ROOT_DIR . '/themes/' .Site('theme') . '/' . $filename))
		$filename = 'themes/' . Site('theme') . '/' . trim($filename, '/');
	elseif (file_exists(ROOT_DIR . '/assets/' . $filename))
		$filename = 'assets/' . $filename;
	elseif (!file_exists(ROOT_DIR .  '/' . $filename))
		return false;

	if  (!is_dir(ROOT_DIR . '/' . $filename)) {
		 $filename .=  '?' . EVO_REVISION;
	}

	$filename = Site($absolute_url ? 'url' : 'base')  . '/' . $filename;

	return $filename;
}


/**
 *  Find an template file. It will look in the current theme, then in the default templates.
 *
 *  @param string $filename
 *  @return string
 */
function get_template($filename, $stock_theme = false)
{
	if (!$stock_theme && file_exists(ROOT_DIR . '/themes/' .Site('theme') . '/templates/' . $filename))
		return ROOT_DIR . '/themes/' . Site('theme') . '/templates/' . trim($filename, '/');
	elseif (file_exists(ROOT_DIR . '/evo/templates/' . $filename))
		return ROOT_DIR . '/evo/templates/' . $filename;
	else
		return false;
}

/**
 *  @param string $filename
 *  @param array $variables
 *  @return void
 */
function include_template($filename, array $variables = [], $buffer_output = false)
{
	global $user_session, $theme_settings;

	extract($variables);

	if ($buffer_output) {
		ob_start();
	}

	if ($tpl_file = get_template($filename)) {
		$return = include $tpl_file;
	}

	if ($buffer_output) {
		return ob_get_clean();
	}

	return $return;
}


/**
 *  Find an template file and replace variables
 *
 *  @param string $filename
 *  @param array $variables
 *  @return string
 */
function parse_template($filename, array $variables)
{
	$variables['sitename'] = Site('name');
	$variables['siteurl'] = Site('url');

	foreach($variables as $k => $v) {
		$search['{{'.$k.'}}'] = $v;
	}

	if ($filename = get_template($filename)) {
		return str_ireplace(array_keys($search), array_values($search), file_get_contents($filename));
	} else {
		return false;
	}
}


/**
 *  Recursive remove directory. Similar to rm -rf
 *
 *  @param string $dir
 *  @param boolean $empty_only wether to delete the dir or only its contents.
 *  @return bool
 */
function rrmdir($dir, $empty_only = false)
{
	$files = glob($dir . '/*') ?: [];
	foreach($files as $file) {
		is_dir($file) ? rrmdir($file) : unlink($file);
	}
	return $empty_only ?: @rmdir($dir);
}


/**
 *  Format a size unit into B/KB/MB/GB
 *
 *  @param integer $size
 *  @param string $format optional format for sprintf
 *  @return string
 */
function mk_human_unit($size, $format = '%1.2f %s')
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unit = 0;
    while ($size > 1024) {$unit++; $size /= 1024;}
    return sprintf($format, $size, $units[$unit]);
}


/**
 *  BBCode parser
 *
 *  @param string $bbcode
 *  @param boolean $encode_html wether to allow or encode html tags
 *  @return string
 */
function bbcode2html($bbcode, $encode_html = true)
{
	$bbcodes = [
			'b' => '<strong>$1</strong>',
			'u' => '<span style="text-decoration:underline;">$1</span>',
			's' => '<span style="text-decoration:line-through;">$1</span>',
			'i' => '<i>$1</i>',
			'h' => '<h4>$1</h4>',

			'sub' => '<sub>$1</sub>',
			'sub' => '<sup>$1</sup>',

			'font=([-a-z\s,\']+)' => '<span style="font-family:$1">$2</span>',
			'color=(#?[a-z0-9]+)' => '<span style="color:$1">$2</span>',
			'size=([0-9]+)' => '<font size="$1">$2</font>', //We should map font size to span sizes instead small/xxlarge,etc

			'justify' => '<div style="text-align:justify;">$1</div>',
			'center' => '<div style="text-align:center;">$1</div>',
			'left' => '<div style="text-align:left;">$1</div>',
			'right' => '<div style="text-align:right;">$1</div>',

			'youtube' => '<iframe src="http://www.youtube.com/embed/$1" width="600px" height="360px"></iframe>',

			'spoiler' => '<div class="spoiler"><a style="cursor:pointer">Afficher le Spoiler</a><div>$1</div></div>',
			'spoiler=([^\]]+)?' => '<div class="spoiler"><a style="cursor:pointer">$1</a><div>$2</div></div>',

			'quote' => '<blockquote>$1</blockquote>',
			'quote=([-a-z0-9_]+)' => '<blockquote>$1 a dit:<br>$2</blockquote>',
			"quote='([-a-z0-9_]+)' pid='([0-9]+)' dateline='([0-9]+)'" => '<blockquote><a href="'.create_url('forums', ['pid'=>'$2']).'">$1 a dit</a>:<br>$4</blockquote>',

			'code' => '<pre><code>$1</code></pre>',

			'img' => '<img src="$1">',
			'img=([0-9]+)x([0-9]+)' => '<img width="$1" height="$2" src="$3">',

			'poll' => '<div id="poll"></div><script>$.get("'.create_url('poll', '$1').'", function(data) { $("#poll").html(data); })</script>',

			'url=((https?://|irc://|\?|\/)[^"\'\]]+)' => '<a href="$1">$3</a>',
			'url' => '<a href="$1">$1</a>',

			'list=\*' => '<ul>$1</ul>',
			'list' => '<ol>$1</ol>',

			'\*' => '<li>',
			'/\*' => '</li>',

			'ul' => '<ul>$1</ul>',
			'ol' => '<ol>$1</ol>',
			'li' => '<li>$1</li>',

			'table' => '<table>$1</table>',
			'tr' => '<tr>$1</tr>',
			'td' => '<td>$1</td>',

			'hr' => '<hr>',
			];

	$filters = [
			'url' => '(https?://|irc://|\?|\/)',
			];

	$block = 'right|left|center|justify|h|youtube|spoiler|quote|hr|\*';
	$notext = 'list|ul|ol|li|table|tr|td|code';

	foreach($bbcodes as $bb => $html) {
		$code = explode('=', $bb, 2);
		$codes[] = $code[0];
		if (strpos($html, '$') !== false) {
			$regexes[] = '!\['.$bb.'\]('.(isset($filters[$bb]) ? $filters[$bb] : '').'[^\]]*)\[/'.$code[0].'\]!msui';
		} else {
			$regexes[] = '!\['.$bb.'\]!msui';
		}
		$replacements[] = $html;
	}

	if ($encode_html)
		$bbcode = html_encode($bbcode);

	$bbcode = preg_replace('@\[(/?(' . $block . ")(=[^\]]+)?)\][\r ]*\n@musi", '[$1]', $bbcode);
	$bbcode = preg_replace('@\s*\[(/?(' . $notext . ")(=[^\]]+)?)\]\s*@mui", '[$1]', $bbcode);
	$bbcode = preg_replace('@(?!\[/?('.implode('|', $codes).')(=[^\]]+)?\])(\[([^\[\]]+)\])@msiu', '&#91;$4&#93;', $bbcode);
	$bbcode = preg_replace('@(?!\[/?('.implode('|', $codes).')(=[^\]]+)?\])(\[([^\[\]]+)\])@msiu', '&#91;$4&#93;', $bbcode);

	do {
		$bbcode = preg_replace($regexes, $replacements, $bbcode, -1, $count);
	} while($count != 0);


	$blocks = preg_split('!(</?pre>)!m', $bbcode, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	foreach($blocks as &$block) {
		if (strpos($block, '<code>') === false) {
			$block = emoticons(nl2br($block, false));
		}
	}

	return implode($blocks);
}


/**
 *  Replaces emoticons with images. It also transforms urls into links
 *
 *  @param string $html
 *  @return string
 */
function emoticons($html)
{
	global $_emoticons;
	$regexes = ['@(^|[^"])(https?://[^\s<]+)@musi'];
	$replacements = ['$1<a href="$2">$2</a>'];

	foreach($_emoticons as $emoticon => $file) {
		$regexes[] = '!([^a-z]|^)' . preg_quote(html_encode($emoticon)) . '([^a-z]|$)!i';
		$replacements[] = '$1<img class="emoticon" src="' . get_asset('emoticons/' . $file) . '" alt="' . $emoticon . '">$2';
	}

	return preg_replace($regexes, $replacements, $html);
}


/**
 *
 */
function remove_accents($string)
{
	$a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
	$b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
	$string = strtr(utf8_decode($string), utf8_decode($a), $b);
	return utf8_encode($string);
}


/**
 *
 */
function format_slug($title)
{
	$title = remove_accents($title);
	$title = trim(strtolower($title));
	$title = preg_replace('#[^a-z0-9\\-/]#i', '-', $title);
	return trim(preg_replace('/-+/', '-', $title), '-/');
}

/**
 *
 */
function safe_filename($filename)
{
	$filename = remove_accents($filename);
	$filename = preg_replace('@[^-a-z0-9_/\.]@i', '_', $filename);
	$filename = preg_replace('@/\.+/@', '/', $filename);
	$filename = preg_replace('/([-\._\/])\\1+/', '$1', $filename);
	return trim($filename, '-_');
}



function user_tags($content, $message)
{
	global $_emoticons, $user_session;

	$matches = [];

	if (preg_match_all('/([^a-z]|^)(?<tag>@[-a-z0-9_\.\\x{202F}]+)/imu', $content, $tags)) {
		foreach($tags['tag'] as $tag) {
			$user = substr(preg_replace('/\\x{202F}/u', ' ', $tag), 1);
			if (!isset($matches[$tag]) && SendMessage($user, 'Quelqu\'un parle de vous!', $message, $user_session['id'], 1)) {
				$matches[$tag] = $user;
			}
		}
	}

	return $matches;
}


/**
 *  Plural
 *
 *  @param array|string $choices
 *  @param integer $count
 *  @param boolean $prepend
 *  @return string
 */
function plural($choices, $count, $prepend = false)
{
	if (!is_array($choices)) {
		$choices = explode('|', $choices);
	}

	if (count($choices) === 2) {
		array_unshift($choices, $choices[0]);
	}

	$sel = (int) ($count > 1 ? 2 : $count);

	if ($prepend) { //Legacy
		return $count . ' ' . $choices[$sel];
	}

	return str_replace('%count%', $count, $choices[$sel]);
}


/**
 *  Shorten a string to length-3 and add an ellipsis if it is too long.
 *
 *  @param string $string
 *  @param integer $length
 *  @return string
 */
function short($string, $length)
{
	if (function_exists('mb_substr') && mb_strlen($string) > $length) {
		$string = mb_substr($string, 0, $length - 3, 'UTF-8') . '...';
	} elseif (strlen($string) > $length) {
		$string = substr($string, 0, $length - 3) . '...';
	}

	return $string;
}


/**
 *  Human readable date. Eg "Aujourd'hui, hier, jamais". Appends time if showtime = true
 *
 *  @param integer $time
 *  @param boolean $showtime
 *  @return string
 */
function today($time, $showtime = false)
{
	if (date('Ymd') === date('Ymd', $time))
		$r = 'Aujourd\'hui';
	elseif (date('Ymd', time()-24*3600) === date('Ymd', $time))
		$r = 'Hier';
	elseif ($time == 0)
		$r = 'Jamais';
	else
		$r = date('Y-m-d', $time);

	return $r . ($showtime ? ' à '. date('H:i', $time) : '');
}


function base64url_encode($data)
{
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}


function base64url_decode($data)
{
	return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}


/**
 *  Creates a thumbnails of a picture. It can respect ratio or crop to fit the requested size
 *
 *  @param string $orig_file original image path
 *  @param string $dest_file thumbnail path
 *  @param array|int $thumb_size X/Y
 *  @param boolean $crop
 *  @return boolean
 */
function thumbnail($filename, $thumb_size = [150, 150], $crop = true)
{
	if (!file_exists($filename))
		return false;

	if (is_int($thumb_size))
		$thumb_size = [$thumb_size, $thumb_size];

	if ($thumb_size[0] < 1 || $thumb_size[1]  < 1)
		return false;


	$types = [1 => 'gif', 2 => 'jpeg', 3 => 'png'];

	list($fichier_larg, $fichier_haut, $fichier_type, $fichier_attr) = @getimagesize($filename);

	if (!array_key_exists($fichier_type, $types)) {
		return false;
	}

	if ($crop)
		$thumb_path = substr($filename, 0, -4) . '-' . $thumb_size[0] . 'x' . $thumb_size[1] . substr($filename, -4);
	else
		$thumb_path =  substr($filename, 0, -4) . '-' . max($thumb_size) . 'px' . substr($filename, -4);

	if ($fichier_larg <= $thumb_size[0] || $fichier_haut <= $thumb_size[1]) {
		copy($filename, $thumb_path); // We can't return $filrname directly as some script can delete thumbs, thus deleting original file
		chmod($thumb_path, 0744);
		return $thumb_path;
	}

	if ($fichier_larg > $fichier_haut) {
		if ($crop) {
			$new_width = $thumb_size[0] * ($fichier_larg / $fichier_haut);
			$new_height = $thumb_size[1];
		} else {
			if ($thumb_size[1] < floor($thumb_size[0] * ($fichier_haut / $fichier_larg))) {
				sort($thumb_size);
			}

			$thumb_size[0] = $new_width = $thumb_size[0];
			$thumb_size[1] = $new_height = floor($thumb_size[0] * ($fichier_haut / $fichier_larg));
		}
	} else {
		if ($crop) {
			$new_width = $thumb_size[0];
			$new_height = $thumb_size[1] * ($fichier_haut / $fichier_larg);
		} else {
			if ($thumb_size[0] > floor($thumb_size[1] * ($fichier_larg / $fichier_haut))) {
				rsort($thumb_size);
			}
			$thumb_size[0] = $new_width = floor($thumb_size[1] * ($fichier_larg / $fichier_haut));
			$thumb_size[1] = $new_height = $thumb_size[1];
		}
	}


	$fichier_source = call_user_func('imagecreatefrom'.$types[$fichier_type], $filename);
	imagealphablending($fichier_source, true);

	if (!$fichier_source) {
		return false;
	}

	$fichier_reduit = imagecreatetruecolor($thumb_size[0], $thumb_size[1]);
	imagealphablending($fichier_reduit, false);
	imagesavealpha($fichier_reduit, true);

	imagecopyresampled($fichier_reduit,
		   $fichier_source,
		   0 - ($new_width - $thumb_size[0]) / 2, // Center the image horizontally
		   0 - ($new_height - $thumb_size[1]) / 2, // Center the image vertically
		   0, 0,
		   $new_width, $new_height,
		   $fichier_larg, $fichier_haut);

	if ($symlink) @symlink($thumb_path, $link_path);

	if (call_user_func('image'.$types[$fichier_type], $fichier_reduit, $thumb_path) && chmod ($thumb_path, 0744))
		return $thumb_path;
	else
		return false;
}


/**
 *  Transforms html5 multi upload format into traditional $_FILES format.
 *
 *  @param void
 *  @return array
 */
function multi_upload_array()
{
	$files = [];
	if (is_array($_FILES) && count($_FILES) > 0) {
		foreach($_FILES as $id => $file) {
			if (is_array($file['name'])) {
				foreach($file['name'] as $nid => $filename) {
					if (!empty($filename)) {
						$tmp = [];
						foreach($file as $argument => $value) {
							$tmp[$argument] = $value[$nid];
						}
						$files[] = $tmp;
					}
				}
			} elseif (!empty($file['name'])) {
				$files[] = $file;
			}
		}
	}
	return $files;
}


/**
 *  Takes care of uploaded file and insert it inside the database.
 *
 *  @param string|array $fileinput Can be key in $_FILES['key'] or $_FILES['key'] itself
 *  @param array|string $prepend prepend/append string to the filename/path
 *  @param string $type makes sure the $type is what we want
 *  @param boolean $crop Crop the thumbnails if it is an image
 *  @return attach_file()
 */
function upload_fichier($fileinput, $prepend = null, $type = null, $allow_dup = false, $origin = null)
{
	global $_warning;

	if (!is_array($fileinput) && isset($_FILES[$fileinput])) {
		$fileinput = $_FILES[$fileinput];
	}

	if (!is_uploaded_file($fileinput['tmp_name'])) {
		return false;
	}

	$fileinput['name'] = $prepend . basename($fileinput['name']);

	try {
		if ($r = attach_file($fileinput['tmp_name'], $fileinput['name'], $type, '', $allow_dup, $origin)) {
			return $r;
		}
	} catch (Exception $e) {
		$_warning = $e->getMessage();
	}

	@unlink($fileinput['tmp_name']);
	return false;
}


/**
 *
 */
function attach_file($filename, $new_name = null, $type = null, $caption = '', $allow_dup = false, $origin = null)
{
	global $user_session;

	$path = '/upload/';
	$thumb = null;
	$banned_exts = ['php', 'php5']; // NOTE: Users are allowed to upload ONLY configured exts, this banlist is for ADMINS
	$known_exts = [];
	$allowed_exts = [];

	if (!file_exists($filename)) {
		return false;
	}

	$mime_types = parse_mime_types_file();
	$attachment_type = parse_config_kv(Site('upload_groups'));
	$vip = has_permission('admin.upload_anything');

	foreach($attachment_type as $t => $exts) {
		foreach($exts as $k => $ext) {
			if (strpos($ext, '/')) { // Si il y a un mime dans la liste on le remplace par les extensions qu'il représente
				$exts = array_merge($exts, array_keys($mime_types, $ext));
				unset($exts[$k]);
			}
		}
		$known_exts = array_merge($known_exts, $exts);
	}

	$ext = $ext_ = pathinfo($new_name ?: $filename, PATHINFO_EXTENSION);
	$mime = mime_content_type($filename);

	if ($type) {
		$allowed_exts = $attachment_type[$type];
	} else {
		$allowed_exts = $known_exts;
	}

	// If we're not VIP we confirm that the extension matches the mime-type. If not we set the correct extension
	if (!$vip && (!isset($mime_types[$ext]) || $mime_types[$ext] !== $mime)) {
		$exts = array_intersect(array_keys($mime_types, $mime), $allowed_exts);
		$ext = $exts ? reset($exts) : false;
	}

	if (empty($ext) || in_array($ext, $banned_exts) || (!$vip && !in_array($ext, $allowed_exts))) {
		throw new Exception("Erreur d'upload: Format $mime $ext n'est pas accepté !\n$type ; $ext_ => $ext");
	}

	if (!$type) {
		foreach($attachment_type as $t => $exts) {
			if (in_array($ext, $exts)) {
				$type = $t;
				break;
			}
		}
		$type = $type ?: 'file';
	}

	if ($origin) {
		$path .= $origin . '/';
	}

	$path .= $type . '/';

	$new_name = safe_filename($new_name ?: basename($filename));
	$new_name = $tmp_name = preg_replace('/\.[^\.]+$/', '', $new_name);


	if ($new_name == '') {
		throw new Exception('Erreur d\'upload: le nom du fichier est vide!');
	}

	$hash = md5_file($filename);

	if (!$allow_dup && $file = Db::Get('select * from {files} where md5 = ? and poster = ?', $hash, $user_session['id'])) {
		if (file_exists(ROOT_DIR.'/'.$file['path'])) {
			@unlink($filename);
			return [basename($file['path']), $file['type'], $file['id'], $file['path']];
		} else {
			$update_broken_dups = true;
		}
	}

	$i = 0;
	while(file_exists(ROOT_DIR . $path . $new_name . '.' . $ext))
		$new_name = $tmp_name.'_'.++$i;

	$new_name .= '.'.$ext;
	$path .= $new_name;

	@mkdir(dirname(ROOT_DIR . $path), 0755, true);
	@touch(dirname(ROOT_DIR . $path) . '/index.html');

	if (!@move_uploaded_file($filename, ROOT_DIR . $path) && !@rename($filename, ROOT_DIR . $path)) {
		throw new Exception('Erreur d\'upload: Impossible de déplacer le fichier');
	}

	chmod(ROOT_DIR . $path, 0755);

	$thumb = [];

	$path = substr(realpath(ROOT_DIR.$path), strlen(ROOT_DIR));
	$path = fixpath($path);

	Db::Insert('files', [
		'name'      => basename($new_name),
		'path'      => $path,
		'thumbs'    => serialize($thumb),
		'type'      => $type,
		'mime_type' => $mime,
		'size'      => filesize(ROOT_DIR.$path),
		// 'img_size'  => getimagesize(),
		'md5'       => $hash,
		'poster'    => $user_session['id'],
		'posted'    => time(),
		'caption'   => $caption,
		'origin'    => $origin,
	]);

	$id = Db::$insert_id;

	if (isset($update_broken_dups))
		Db::Exec('update {files} set path = ?, thumbs = ? where md5= ?', $path, serialize($thumb), $hash);

	log_event($user_session['id'], 'user', "Upload du fichier $path ($origin.$type)");

	return [$new_name, $type, $id, $path];
}


/**
 *  $file can be a path(string) or an id (int)
 */
function delete_file($file)
{
	$column = is_string($file) ? 'path' : 'id';
	if ($file = Db::Get('select id, path, thumbs, md5 from {files} where '.$column.' = ?', $file)) {
		$duplicates = (int)Db::Get('select count(*) from {files} where path = ?', $file['path']);
		Db::Exec('delete from {files} where id = ?', $file['id']);
		if ($duplicates  === 1) {
			@unlink(ROOT_DIR.'/'.$file['path']);
		}
		if ($file['thumbs']) {
			foreach (unserialize($file['thumbs']) as $thumb) {
				@unlink(ROOT_DIR.'/'.$thumb);
			}
		}
		return true;
	}
	return false;
}


function parse_attached_files($text, $rel_type = null, $rel_id = null)
{
	if (preg_match_all('![="\(]\?p=(%2Fupload%2F.*?|/upload/.*?)["&\\) \]]!S', $text, $m)) {
		$files = array_unique(array_map('urldecode', $m[1]));
	} else {
		$files = [];
	}

	// We probably should check if the user owns the files OR is a moderator.
	// We could implement the check only in the detach function, where it can be a real issue (files get deleted)...

	if ($rel_type === null) {
		return $files;
	}

	Db::Exec('DELETE FROM {files_rel} WHERE rel_type = ? AND rel_id = ?', $rel_type, $rel_id);

	foreach($files as $file) {
		if ($file = Db::Get('SELECT id FROM {files} WHERE path = ?', $file)) {
			Db::Insert('files_rel', ['rel_type' => $rel_type, 'rel_id' => $rel_id, 'file_id' => $file]);
		}
	}

	return $files;
}


function mime_type_extension($ext)
{
	$ext = strtolower($ext);
	$mime_types = parse_mime_types_file();
	if (isset($mime_types[$ext])) {
		return $mime_types[$ext];
	} else {
		return false;
	}
}


function fixpath($path)
{
	return str_replace('\\', '/', $path);
}


function parse_mime_types_file($file = null, $by_ext = true)
{
	$file = $file ?: __DIR__.'/mime.types';

	if (!file_exists($file)) {
		return false;
	}

	$mimes = parse_config_kv(file_get_contents($file));

	if ($by_ext) {
		foreach($mimes as $mime => $exts) {
			foreach($exts as $ext) {
				$mimes[$ext] = $mime;
			}
			unset($mimes[$mime]);
		}
	}

	return $mimes;
}


function parse_config_kv($string)
{

	$rows = [];

	$lines = explode("\n", str_replace("\r", "", $string));
	$lines = array_map(function($v) {return trim(explode('#', $v)[0]);}, $lines);
	$lines = array_filter($lines);
	$lines = preg_replace('/\s+/ms', ' ', $lines);

	foreach($lines as $line) {
		$parts = explode(' ', $line);
		$key = array_shift($parts);
		$rows[$key] = $parts;
	}

	return $rows;
}


function html_encode($string)
{
	if (is_array($string))
		return array_map('html_encode', $string);
	else
		return htmlspecialchars($string, ENT_COMPAT, 'utf-8');
}


class htmlSelectGroup extends ArrayObject {

}


function html_select($name, array $options, $default = null, $escape = true, $attributes = 'class="form-control"')
{
	if (count($options) > 0) {
		$attributes = html_attributes($attributes);
		$r = '<select name="'.$name.'" id="'.$name.'" ' . $attributes . '>';
		foreach($options as $value => $opts) {
			$r .= html_select_opt([$value, $opts], $default, $escape);
		}
		$r .= '</select>';
		return $r;
	} else
		return 'Aucun choix!';
}


function html_select_opt(array $option, $default = null, $escape = true)
{
	$option += ['', '', ''];

	if ($option[1] instanceOf htmlSelectGroup) { // optgroup
		$option[2] = html_attributes($option[2]);
		$r = '<optgroup label="' . html_encode($option[0]) . '" ' . $option[2] . '>';
		foreach($option[1] as $value => $label) {
			$attributes = '';
			if (is_integer($value) && is_array($label)) {
				list($value, $label, $attributes) = $label + ['', '', ''];
			}
			$r .= html_select_opt([$value, $label, $attributes], $default, $escape);
		}
		$r .= '</optgroup>';
	} else {
		if (is_array($option[1])) {
			$option = (array)$option[1];
		}

		list($value, $label, $attributes) = $option + ['', '', ''];
		$attributes = html_attributes($attributes);

		$r = '<option' . ($default == $value ? ' selected' : '') . ' ' . $attributes . ' value="' . html_encode($value) . '">' . ($escape ? html_encode($label) : $label) . '</option>';
	}

	return $r;
}


function html_attributes($attributes)
{
	if (is_array($attributes)) {
		$r = '';
		foreach($attributes as $attr => $value) {
			if (is_int($attr))
				$r .= $value . ' ';
			else
				$r .= $attr . '="' . html_encode($value) . '" ';
		}
		return $r;
	}

	return $attributes;
}


function log_event($uid, $type, $event = '')
{
	global $user_session;
	return Db::Insert('history', [
		'e_uid'     => $user_session['id'] ?: 0,
		'a_uid'     => $uid,
		'type'      => $type,
		'timestamp' => time(),
		'ip'        => $_SERVER['REMOTE_ADDR'],
		'event'     => $event
	]);
}


function SendMessage($to, $subject, $message, $reply_to = 0, $type = 0)
{
	global $user_session, $_warning;

	$from = ($type == 0) ? $user_session : ['id' => 0, 'username' => 'Système', 'group_id' => 1];

	if (ctype_digit($to)) {
		$to = Db::Get('select id, username, email, group_id from {users} where id = ?', $to);
	} else {
		$to = Db::Get('select id, username, email, group_id from {users} where username = ?', $to);
	}

	if (!$to) {
		return false;
	}

	// if (group_has_permission('' )
	Db::Insert('mailbox', [
		'reply'   => $reply_to,
		's_id'    => $from['id'],
		'r_id'    => $to['id'],
		'sujet'   => $subject,
		'message' => $message,
		'posted'  => time(),
		'type'    => $type,
	]);

	sendmail($to['email'], 'Vous avez un message', parse_template('mail/new_message_'.$type.'.tpl', ['username' => $to['username'], 'mailfrom' => $from['username'], 'message' => $message]));

	return Db::$insert_id;
}


function sendmail($to, $subject, $message, $reply_to = '')
{
	$headers  = 'From: '.Site('name').' <'.Site('email').'>' . "\r\n";
	if ($reply_to)
		$headers  .= 'Reply-To: <'.$reply_to.'>' . "\r\n";
	$headers .= 'Content-Type: text/plain;charset=UTF-8';

	$message .= "\n\n" . Site('name') . "\n" . Site('url');

	return @mail($to, $subject, $message, $headers);
}


function send_activation_email($username)
{
	if ($r = Db::Get('SELECT id,username,locked,activity,email FROM {users} where locked = 2 and username = ?', $username)) {
		$hash    = sha1(sprintf('%d/%s/%d/%d/s', $r['id'], $r['username'], $r['locked'], $r['activity'], Site('salt')));
		$url     = create_url('login', ['action' => 'activate','key' => $hash,'username' => $r['username']]);
		$message = parse_template('mail/activate_account.tpl', ['username' => $r['username'], 'activation_url' => $url]);
		
		if (sendmail($r['email'], 'Activation de votre compte sur ' . Site('name'), $message)) {
			log_event('user', 'Mail d\'activation envoyé pour ' . $_POST['username'] . '.');
			return true;
		}
	}
	return false;
}


/**
 *  Load and edit site settings.
 *
 *  @param string $name setting name (url/name/description/etc).
 *  @param mixed $value set value and save it in database.
 *  @param bool $temp set value for current session only.
 *  @return attach_file()
 */
function Site($name, $value = null, $temp = false)
{
	global $theme_settings;
	static $site_settings;

	static $defaults = [];

	$validation = [];

	if (!$defaults) { // It needs to be done that way for now
		global $_default_settings;
		$defaults = $_default_settings;
	}

	if (!isset($site_settings)) {
		foreach(Db::QueryAll('select name, value from {settings}') as $setting) {
			$site_settings[$setting['name']] = ($tval = @unserialize($setting['value'])) !== false ? $tval: $setting['value'];
		}
		if (isset($site_settings['url'])) {
			$site_settings['base'] = preg_replace('#^(https?://)?([^/]*)#', '', $site_settings['url']);
		}
	}
	if (!isset($theme_settings)) {
		if (!file_exists(ROOT_DIR . '/themes/' . $site_settings['theme'] .  '/index.php')) {
			$site_settings['theme'] = 'default';
		}
		$theme_settings = include ROOT_DIR . '/themes/' . $site_settings['theme'] .  '/index.php';
	}

	$name = strtolower($name); // From now on settings should be normalized lowercase

	if (func_num_args() > 1) { // $value !== null
		if ($value === null) {
			Db::Exec('delete from {settings} where name = ?', $name);
		} elseif (!$temp) {
			if (!isset($site_settings[$name]) || $value !== $site_settings[$name]) {
				Db::Exec('replace into {settings} values (?, ?)', $name, is_array($value) ? serialize($value) : $value);
			}
		}
		return $site_settings[$name] = $value;
	}

	if (substr($name, -1) === '*' && $name = substr($name, 0, -1)) {
		$return = [];
		foreach($site_settings as $key => $value) {
			if (strpos($key, $name) === 0) $return[$key] = $value;
		}
		return $return;
	}

	if (isset($site_settings[$name]))
		return $site_settings[$name];
	elseif (isset($defaults[$name]))
		return $defaults[$name];
	else
		return null;
}


/**
 *  Creates an html form from the settings table.
 *
 *  Each entry should contain those elements:
 *  - type: image textarea boolean enum text color
 *  - label: Setting's description
 *  - css: CSS linked to the setting. %s inside the CSS code represent the setting's value
 *  - choices: Only for enum, an array of $value => $label
 *  - default: default value if unset
 *
 */
function settings_form(array $settings, $allow_reset = false, $no_form_tag = false)
{
	global $_default_settings;

	$form = $no_form_tag ? '<div class="form-horizontal">': '<form method="post" class="form-horizontal" enctype="multipart/form-data">';

	foreach($settings as $name => $description)
	{
		$hname = str_replace('.', '||', $name); // PHP will eat the . in POST

		$form .= '<div class="form-group"><label class="col-sm-4 control-label">' . $description['label'];

		if (isset($description['help'])) {
			$form .= ' <i class="fa fa-question-circle" title="' . html_encode($description['help']) . '"></i>';
		}

		$form .= '</label><div class="col-sm-6">';

		switch($description['type']) {
			case 'image':
				if (is_file(ROOT_DIR . '/' . Site($name))) {
					$form .= '<img src="'.get_asset(Site($name)).'" alt="Image actuelle" title="Image actuelle" height="128"><br>';
				}

				$files = Db::QueryAll('select path, name from {files} where origin = ?', 'settings/'.$name);

				if ($files) {
					$form .= '<select name="' . $hname . '" class="image_selector">'.
							   '<option value="">Valeur par défaut</option>';
					foreach($files as $image)
						$form .= '<option value="'. $image['path'] .'"'.($image['path'] == Site($name) ? ' selected':'').'>'.$image['name'].'</option> ';
					$form .= '</select> ou ';
				}
				$form .= '<input name="' . $hname . '" type="file" style="display:inline"><br>';
				break;

			case 'textarea':
				$form .= '<textarea class="form-control" name="' . $hname . '">' . html_encode(Site($name)). '</textarea>';
				break;

			case 'bool':
			case 'boolean':
				$form .= html_select($hname, [0 => 'Non', 1 => 'Oui'], Site($name));
				break;

			case 'enum':
				$form .= html_select($hname, $description['choices'], Site($name));
				break;

			default:
				$form .= '<input class="form-control" name="' . $hname . '" type="'.$description['type'].'" value="' . html_encode(Site($name)). '">';
		}

		if (isset($_default_settings[$name]) && !isset($description['default'])) {
			$description['default'] = $_default_settings[$name];
		}

		if ($allow_reset || isset($description['allow_reset'])) {
			$form .= '<label class="normal"><input class="" name="' . $hname . '" type="checkbox" '.(Site($name) == @$description['default'] ? 'checked' : '').' value="'.html_encode(addslashes(@$description['default'])).'"> Valeur par défaut</label>';
		}

		$form .= '</div></div>';
	}

	$form .= '<div class="text-center">
					<input class="btn btn-medium btn-primary" type="submit" value="Enregistrer les modifications">
				</div>';

	$form .= '<script>
		$("textarea,input[type!=checkbox],select").bind("change keyup", function() {
			var c = $("[name=\""+  this.name + "\"][type=checkbox]");
			c[0].checked = this.value == c[0].value;
		});
		$("[type=checkbox]").click(function() {
			var c = $("[name=\""+  this.name + "\"][type!=checkbox]");
			if (this.checked) {
				c.hide();
			} else {
				c.show();
			}
		});
	</script>';

	$form .= $no_form_tag ? '</div>' : '</form>';

	return $form;
}


function settings_save(array $settings, array $values)
{
	global $user_session;

	$changes = [];

	foreach ($values as $field => $value)
	{
		$field = str_replace('||', '.', $field); // PHP will eat the . in POST

		if (array_key_exists($field, $settings) && $value != Site($field)) {
			if ($settings[$field]['type'] === 'enum' && !in_array($value, $settings[$field]['choices']) && !isset($settings[$field]['choices'][$value])) {
				log_event($user_session['id'], 'admin', 'Tentative modification du paramètre: '.$field.' avec valeure incorrecte.');
				continue;
			}
			elseif ($settings[$field]['type'] === 'bool' && !in_array($value, [0, 1])) {
				log_event($user_session['id'], 'admin', 'Tentative modification du paramètre: '.$field.' avec valeure incorrecte.');
				continue;
			}

			if ($field === 'url') { rtrim($value, '/'); }
			Site($field, $value);
			Db::$affected_rows and log_event($user_session['id'], 'admin', 'Modification du paramètre: '.$field.'.') and $changes[] = $field;
		}
	}
	return $changes;
}


/**
 *  This should probably be merged with settings_form...
 */

function build_form($title, array $fields, $form_tag = true)
{
	$buffer = '<legend>' . html_encode($title) . '</legend>';

	if ($form_tag) {
		$buffer .= '<form method="post" role="form" class="form-horizontal">';
	}

	foreach($fields as $name => $props)
	{
		$buffer .= '<div class="form-group">'.
			 '<label class="col-sm-4 control-label" for="' . $name . '">' . $props['label'] . '</label>'. // html_encode
			 '<div class="col-sm-6">';

		if ($props['type'] === 'multiple') {
			$loop = $props['fields'];
		} else {
			$loop = [$name => $props];
		}

		$base_attributes = ['class' => 'form-control'];

		foreach($loop as $name => $field) {
			$field += ['value' => '', 'attributes' => '', 'placeholder' => ''];
			$attributes = html_attributes((array)$field['attributes'] + $base_attributes);

			switch($field['type']) {
				case 'text':
					$buffer .= '<input id="' . $name . '" name="' . $name . '" type="text" value="' . html_encode($field['value']) . '" ' .$attributes . '>';
					break;

				case 'textarea':
					$buffer .= '<textarea id="' . $name . '" name="' . $name . '" ' .$attributes . '>' . html_encode($field['value']) . '</textarea>';
					break;

				case 'checkbox':
					$buffer .= '<input id="' . $name . '" name="' . $name . '" type="checkbox" value="' . html_encode($field['value']) . '" ' . (empty($field['checked']) ? '' : 'checked') . ' class="" ' .$attributes . '>'.
						       '<label for="' . $name . '" class="normal"> ' . html_encode($field['label']) . '</label><br>';
					break;

				case 'select':
					$buffer .= html_select($name, $field['options'], $field['value']);
					break;

				case 'password':
					$buffer .= '<input id="' . $name . '" name="' . $name . '" type="password" value="' . html_encode($field['value']) . '" ' .$attributes . '>';
					break;

				case 'avatar':
					$avatars['Base'] = new htmlSelectGroup([
						'/assets/img/avatar.png' => 'Inconnu',
						'/assets/img/gravatar.jpg' => 'Gravatar'
					]);

					foreach(glob(ROOT_DIR.'/upload/avatar/*/') as $cat_dir) {
						$cat = ucfirst(basename($cat_dir));
						$avatars[$cat] = new htmlSelectGroup();
						if ($pictures = glob($cat_dir . '*.{jpg,png,gif}', GLOB_BRACE)) {
							foreach($pictures as $avatar_path) {
								$avatar = preg_replace('#\.[^.]+$#', '', ucfirst(basename($avatar_path)));
								$avatar_path = str_replace(ROOT_DIR, '', $avatar_path);
								$avatars[$cat][$avatar_path] = $avatar;
							}
						}
					}
					$buffer .= html_select($name, $avatars, $field['value'] , true, ['class' => 'avatar_selector form-control']);

					if (!empty($field['avatar'])) {
						$buffer .= '<span style="margin-left: 10px;position: relative;top: -4px;"><img id="avatar_selector_preview" title="Current value" width="42" height="42" src="' . $field['avatar']. '"></span>';
					}

					$buffer .= '<div id="avatar_selector_box" class="well"></div>';
					break;
			}
		}

		$buffer .= '</div></div>';
	}
	$buffer .= '<div class="text-center"><input class="btn btn-medium btn-primary" type="submit" value="Inscription"></div>';

	if ($form_tag) {
		$buffer .= '</form>';
	}

	return $buffer;
}



function rewrite_links($html, $absolute_url = true)
{
	$root = $absolute_url ? Site('url') : Site('base');
	return preg_replace_callback('!\s(href|src|action)="(/?\?p=.*?|\?/.*?|/[^/].*?)"!S', function (&$m) use ($root) {

		list($url, $hash) = explode('#', $m[2].'#');
		list($link, $query) = explode('?', ltrim($url, '/').'?');

		if ($link === 'index.php') $link = '';

		parse_str(html_entity_decode($query), $arr);

		if (Site('url_rewriting') && $link === '' && isset($arr['p']) && !defined('EVO_ADMIN')) {
			$url = $root . '/' . ltrim($arr['p'], '/');
			if (isset($arr['id']))
				$url .= '/' . $arr['id'];
			unset($arr['p'], $arr['id']);
		} else {
			$url = $root . '/' . $link;
		}

		if ($arr) {
			$url .= '?' . http_build_query($arr);
		}

		if ($hash) {
			$url .= '#' . $hash;
		}

		return ' ' . $m[1] . '="' . $url . '"';
	}, $html);
}


function create_url($page, $arguments = [], $hash = '')
{
	/* Assume id */
	$args = is_array($arguments) ? $arguments : ['id' => $arguments];
	$url  = rtrim(Site('url'), '/') . '/';

	if ((!$hash || $hash[0] !== '#') && strpos($page, '#') === false) {
		$hash = '#' . $hash;
	}

	$page = ltrim($page, '/');

	list($page, $hash) = explode('#', $page.$hash, 2);
	list($url, $query) = explode('?', $url.'?');

	if ($query) {
		parse_str($query, $args);
		$args = $args + $arr;
	}

	$sep = '?';
	
	if ($page !== '') {
		if (!Site('url_rewriting')) {
			$url .= '?p='; // '?/'
			$sep = '&';
		}

		$url .= $page;

		if (isset($args['id']))
			$url .= '/' . $args['id'];
	}
	
	unset($args['id'], $args['p']);

	if ($args) {
		$url .= $sep . http_build_query($args);
	}

	if ($hash) {
		$url .= '#' . $hash;
	}

	return $url;
}


function get_menu_tree($extended = false, &$items = null)
{
	$tree = [];

	if ($extended)
		$items = Db::QueryAll('SELECT m.*, r.title as page_name, r.slug, p.redirect
							   FROM {menu} as m
							   LEFT JOIN {pages} as p ON p.page_id = m.link
							   LEFT JOIN {pages_revs} as r ON r.page_id = p.page_id AND r.revision = p.revisions
							   ORDER BY priority, m.id ASC', true);
	else
		$items = Db::QueryAll('select m.*, p.slug, p.redirect from {menu} as m
							   left join {pages} as p on p.page_id = m.link
							   order by priority, id asc', true);

	foreach($items as $item) {
		if (!isset($items[$item['parent']]) || $item['parent'] == $item['id'])
			$item['parent'] = 0;

		$tree[$item['parent']][$item['id']] = $item;
	}

	return $tree;
}


function get_pages($total, $from = 0, array $options = [])
{
	$options = array_merge([
		'select' => 'r.*, p.*, a.username',
		'type' => 'article',
		'order_by' => ['sticky>0' => 'desc', 'sticky' => 'asc', 'pub_date' => 'desc'],
	], $options);

	$orderby = [];

	if (!is_array($options['order_by'])) {
		$options['order_by'] = [$options['order_by'] => 'desc'];
	}
	foreach($options['order_by'] as $column => $order) {
		$orderby[] = $column.' '.$order;
	}

	$sql = 'SELECT ' . $options['select'] . '
			FROM {pages} AS p
			JOIN {pages_revs} as r ON r.page_id = p.page_id AND p.pub_rev = r.revision
			JOIN {users} as a ON a.id = r.author
			WHERE p.pub_date <= UNIX_TIMESTAMP() AND p.`type` = ?
			ORDER BY '.implode(',', $orderby).' LIMIT ?, ?';

	return Db::QueryAll($sql, $options['type'], (int)$from, (int)$total);
}


function get_scripts()
{
	$scripts = [];
	foreach(glob(ROOT_DIR.'/pages/*.php') as $page)
		$scripts[basename($page, '.php')] = basename($page);

	return $scripts;
}


function gzip($contents, $filename = 'file', $output = 'php://output')
{
	$fp = fopen($output, 'wb');

	if ($fp == false) {
		return false;
	}

	$header  = "\x1f\x8b";				// header (ID1 + ID2)
	$header .= pack('C', 0x08); 		// compression method (CM)
	$header .= pack('C', 0 | 0x08); 	// flags (FLG) (filename = on)
	$header .= "\x9c\x54\xf4\x50";		// modification time (MTIME)
	$header .= pack('C', 0);			// extra flags (XFL)
	$header .= "\xff";					// operating system (OS)

	if ($filename) {
		$header .= $filename;
		$header .= "\x00";
	}

	fwrite($fp, $header);
	fwrite($fp, substr(gzcompress($contents), 2, -4));  //Stripping extra
	fwrite($fp, pack('V', crc32($contents)));
	fwrite($fp, pack('V', strlen($contents)));
	fclose($fp);
}


function queryServer(array $conditions = [], $cache = true) {
	static $cache = [];


	$begin = microtime(true);

	$where = ['where 1'];

	foreach($conditions as $k => $v) {
		$where[] = Db::escapeField($k) . ' = ?';
	}

	$where_str = implode(' and ', $where);

	if ($cache && isset($cache[$where_str])) {
		return $cache[$where_str];
	}

	$server = Db::Get('select * from {servers} ' . $where_str, $conditions);

	if (!$server) {
		throw new Warning('Ce serveur n\'existe pas !');
	}

	$server['query_time'] = number_format(microtime(true) - $begin, 4);
	// $server['query'] = ServerQuery::{$server['type']} ($server['host'], $server['port']);
	$server['query'] = call_user_func(['\Evo\ServerQuery\Server', $server['type']], $server['host'], $server['port']);
	return $cache[$where_str] = (object)$server;
}


if (!function_exists('array_column')) {
	function array_column(array $array, $column_key, $index_key = null)
	{
		$values = array_map(function($e) use ($column_key) {return $e[$column_key];}, $array);
		if ($index_key !== null) {
			$keys = array_map(function($e) use ($index_key) {return $e[$index_key];}, $array);
			return array_combine($keys, $values);
		}
		return $values;
	}
}


function array_try_keys(array $array, array $keys, $return_value = false)
{
	foreach($keys as $key) {
		if (isset($array[$key])) {
			return $return_value ? $array[$key] : $key;
		}
	}
	return false;
}


function getTimezoneByOffset($offset = null, $tz_name = false)
{
	global $_timezones;

	if ($offset === null) {
		$offset = (new DateTime('now'))->getOffset();
	}

	foreach($_timezones as $name => $tz) {
		$dt = new DateTime('now', new DateTimeZone($tz));
		if ($offset == $dt->getOffset()) {
			return $tz_name ? $tz : $name;
		}
	}
	return false;
}


function generate_tz_list()
{
	global $_timezones;

	$zero = [];
	$plus = [];
	$neg = [];

	foreach($_timezones as $name => $tz) {
		$dt = new DateTime('now', $tz ? new DateTimeZone($tz) : null);
		$offset = $dt->getOffset();
		$sign = $offset >= 0 ? '+' : '-';

		$gmt = $sign . gmdate('H:i', abs($offset));
		$desc = '(GMT' . $gmt . ') ' . $name . ' [' . $dt->format('H:i') . ']';

		if ($gmt > 0) {
			$plus[$name] = $desc;
		} elseif($gmt < 0) {
			$neg[$name] = $desc;
		} else {
			$zero[$name] = $desc;
		}
	}

	return array_merge($neg, $zero, $plus);
}


function date_in_tz($format, $tz)
{
	global $_timezones;

	$tz = $_timezones[$tz];

	$dt = new DateTime('now', $tz ? new DateTimeZone($tz) : null);

	return $dt->format($format);
}


function __($string, array $parameters = [], $locale = null)
{
	return Translation\Lang::get($string, $parameters, $locale);
}


function http_redirect($url)
{
	if (substr($url, 0, 4) !== 'http')
	{
		$url = Site('url') . '/' . rtrim($url, '/');
	}
	header('Location: ' . $url);
	die('Redirected to: ' . $url);
}


function build_search_query($query, $columns = ['a-z0-9_-\.'])
{
	$where = [];
	$args  = [];
	$link  = ' or ';
	$joined_cols = implode('|', array_merge(preg_replace('/^[a-z0-9_-]+\./', '', $columns), $columns));

	$filter = preg_replace_callback(
		'/('.$joined_cols.'):\s*([^\s]+)/ims',
		function($m) use (&$where, &$args, &$link) {
			$operator = strpos($m[2], '*') !== false || strpos($m[2], '%') !== false ? 'LIKE' : '=';
			$link = ' and ';
			$where[] = "a.{$m[1]} $operator ?";
			$args[] = str_replace('*', '%', $m[2]);
			return '';
		},
		$query
	);

	if ($filter = trim($filter)) {
		foreach($columns as $column) {
			$args[] = '%' . $filter . '%';
			$where[] = $column . ' like ? ';
		}
	}

	return ['where' => implode($link, $where), 'args' => $args];
}


if (function_exists('geoip_country_code_by_name')) {
	function geoip_country_code($hostname)
	{
		return geoip_country_code_by_name($hostname);
	}
} else {
	function geoip_country_code($hostname)
	{
		static $gi;
		require_once __DIR__. '/libs/GeoIP/GeoIP.php';
		if ($gi || $gi = geoip_open(__DIR__. '/libs/GeoIP/GeoIP.dat', GEOIP_COUNTRY_EDITION)) {
			return geoip_country_code_by_addr($gi, $hostname);
		}
		return null;	
	}
}
