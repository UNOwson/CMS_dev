<?php defined('EVO') or die('Que fais-tu là?');

require ROOT_DIR . '/evo/misc/parse_minecraft_string.php';

$server = queryServer(array('id' => _GET('id')));
if ($server->query) {
	foreach($server->query as $key => $value) {
		if ($key == 'favicon' || $key == 'albumArt') {
			echo $value = str_replace("\n", '', $value);
		} elseif (!is_array($value)) {
			$value = parse_minecraft_string(str_replace(array('<', '>'), array('&lt;', '&gt;'), $value));
		}
	}
}

include_template('pages/server.php', compact('server'));