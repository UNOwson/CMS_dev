<?php
##
##
##        Mod title:  User agent
##
##      Mod version:  1.3
##  Works on FluxBB:  1.5.0, 1.5-beta, 1.4.9, 1.4.8, 1.4.7
##     Release date:  2012-08-04
##      Review date:  2012-08-04
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Adds a browser and system icon into each new post
##
##   Repository URL:  http://fluxbb.org/resources/mods/user-agent/
##
##   Affected files:  viewtopic.php
##                    post.php
##
##       Affects DB:  Yes
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at
##                    your own risk. Backup your forum database and any and
##                    all applicable files before proceeding.
##
##

function ua_get_filename($name, $folder)
{
	$name = strtolower($name);
	$name = preg_replace('/[^a-z0-9_]/', '', $name); // remove special characters
	$name = get_asset('/img/user_agent/'.$folder.'/'.$name.'.png');
	return $name;
}

function ua_search_for_item($items, $useragent)
{
	foreach ($items as $item)
	{
		if (strpos($useragent, strtolower($item)) !== false)
			return $item;
	}
}

function get_useragent_names($useragent)
{
	if ($useragent == '')
	{
		$result = array(
			'system'			=> 'Unknown',
			'browser_img'		=> 'Unknown',
			'browser_version'	=> 'Unknown',
			'browser_name'		=> 'Unknown'
		);
		return $result;
	}

	$browser_img = '';
	$browser_version = '';

	$useragent = strtolower($useragent);

	// Browser detection
	$browsers = array('Arora', 'AWeb', 'Camino', 'Epiphany', 'Galeon', 'HotJava', 'iCab', 'MSIE', 'Maxthon', 'Chrome', 'Safari', 'Konqueror', 'Flock', 'Iceweasel', 'SeaMonkey', 'Firebird', 'Netscape', 'Firefox', 'Mozilla', 'Opera', 'PhaseOut', 'SlimBrowser');

	$browser = ua_search_for_item($browsers, $useragent);

	preg_match('#'.preg_quote(strtolower(($browser == 'Opera' ? 'Version' : $browser))).'[\s/]*([\.0-9]*)#', $useragent, $matches);
	$browser_version = $matches[1];

	if ($browser == 'MSIE')
	{
		if (intval($browser_version) >= 9)
			$browser_img = 'Internet Explorer 9';
		else if (intval($browser_version) >= 7)
			$browser_img = 'Internet Explorer 7';

		$browser = 'Internet Explorer';
	}

	// System detection
	$systems = array('Amiga', 'BeOS', 'FreeBSD', 'HP-UX', 'Linux', 'NetBSD', 'OS/2', 'SunOS', 'Symbian', 'Unix', 'Windows', 'Sun', 'Macintosh', 'Mac');

	$system = ua_search_for_item($systems, $useragent);

	if ($system == 'Linux')
	{
		$systems = array('CentOS', 'Debian', 'Fedora', 'Freespire', 'Gentoo', 'Katonix', 'KateOS', 'Knoppix', 'Kubuntu', 'Linspire', 'Mandriva', 'Mandrake', 'RedHat', 'Slackware', 'Slax', 'Suse', 'Xubuntu', 'Ubuntu', 'Xandros', 'Arch', 'Ark');

		$system = ua_search_for_item($systems, $useragent);
		if ($system == '')
			$system = 'Linux';

		if ($system == 'Mandrake')
			$system = 'Mandriva';
	}
	elseif ($system == 'Windows')
	{
		$version = (float)substr($useragent, strpos($useragent, 'windows nt ') + 11);
		if ($version === 5.0 || $version === 5.1 || $version === 5.2)
			$system = 'Windows XP';
		else if ($version === 6.0)
			$system = 'Windows Vista';
		else if ($version === 6.1)
			$system = 'Windows 7';
		else if ($version === 6.2 || $version == 6.3)
			$system = 'Windows 8';
		else if ($version === 10.0)
			$system = 'Windows 10';
	}
	elseif ($system == 'Mac')
		$system = 'Macintosh';

	if (!$system)
		$system = 'Unknown';
	if (!$browser)
		$browser = 'Unknown';

	if (!$browser_img)
		$browser_img = $browser;

	$result = array(
		'system'			=> $system,
		'browser_img'		=> $browser_img,
		'browser_version'	=> $browser_version,
		'browser_name'		=> ($browser != 'Unknown') ? $browser.' '.$browser_version : $browser
	);

	return $result;
}

function get_useragent_icons($useragent)
{
	static $user_agent_cache;

	if ($useragent == '')
		return '';

	if (!isset($user_agent_cache))
		$user_agent_cache = array();

	if (isset($user_agent_cache[$useragent]))
		return $user_agent_cache[$useragent];

	$agent = get_useragent_names($useragent);

	$result = '<img src="'.ua_get_filename($agent['system'], 'system').'" title="'.html_encode($agent['system']).'" alt="'.html_encode($agent['system']).'" style="margin-right: 1px">';
	$result .= '<img src="'.ua_get_filename($agent['browser_img'], 'browser').'" title="'.html_encode($agent['browser_name']).'" alt="'.html_encode($agent['browser_name']).'" style="margin-left: 1px">';

	$desc = has_permission('mod.') ? ' style="cursor: pointer" onclick="alert(\''.html_encode(addslashes($useragent).'\n\nSystem:\t'.addslashes($agent['system']).'\nBrowser:\t'.addslashes($agent['browser_name'])).'\')"' : '';

	$result = ' <span class="user-agent"'.$desc.'>'.$result.'</span>'."\n";
	$user_agent_cache[$useragent] = $result;
	return $result;
}
