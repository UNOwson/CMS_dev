<?php defined('EVO') or die('Que fais-tu là?');
/**
 *  If the url rewriting is setup correctly this page should not be called on every hit.
 *  Only once per thumb size (we should probably limit as it's a security issue/DoS possibility).
 */

if (_GET('path')) {
	$sql = 'select * from {files} where path = ?';
	$param = fixpath(_GET('path'));
} elseif (count($param = explode('/', _GET('id'), 2)) === 2) {
	$sql = 'select * from {files} where id = ? and name = ?';
} elseif (_GET('id', 1000) < 100) {
	$sql = 'select * from {files} where id = ?';
} else {
	return $_warning = __('getfile.badlink');
}

# Url ideas:
# http://site.tld/upload/user/uploader/image/name.png
# http://site.tld/user/uploader/image/name.png
# http://site.tld/getfile/ID/name.png
# http://site.tld/dl/ID/name.png

if ($file = Db::Get($sql, $param)) {

	if (!file_exists(ROOT_DIR . $file['path'])) { // Better double check!
		return $_warning = __('getfile.notfound');
	}
	
	$file['thumbs'] = @unserialize($file['thumbs']) ?: array();
	$serve = $file['path'];
	$serve_size = $file['size'];
	
	if ($size = _GET('size', _GET('thumb'))) {
		if($file['type'] != 'image' && strpos($file['mime_type'], 'image') !== 0) {
			header('Location: ' . get_asset('img/file-' . $file['type'] . '.png'));
			exit;
		} 
		
		if (ctype_digit($size) && $size > 10) {
			$m = array($size, $size, $size);
		} elseif ($size == 1 || !preg_match('#^([0-9]+)x([0-9]+)$#', $size, $m)) {
			$m = array('150x150', 150, 150);
		}
		
		if (isset($file['thumbs'][$m[0]]) && $file['thumbs'][$m[0]] && file_exists(ROOT_DIR . $file['thumbs'][$m[0]])) {
			$serve = $file['thumbs'][$m[0]];
		} elseif ($path = thumbnail(ROOT_DIR . '/' . $file['path'], array($m[1], $m[2]), strpos($size, 'x'))) {
			$file['thumbs'][$m[0]] = $serve = substr($path, strlen(ROOT_DIR));
			Db::Exec('update {files} set thumbs = ? where id = ?', serialize($file['thumbs']), $file['id']);
		}
		$serve_size = filesize(ROOT_DIR . $serve);
	}
	
	Db::Exec('update {files} set hits = hits + 1 where id = ?', $file['id']);
	
	header('Cache-Control: max-age=7200');
	header('Content-Type: ' . $file['mime_type']);
	header('Content-Length: ' . $serve_size);
	header('Content-Disposition: inline; filename="' . $file['name'] . '"');
	
	if (!Site('x-sendfile') && $serve_size < 10*1024*1024) { // If the file is small we serve it directly, the request is already open...
		ob_end_clean();
		readfile(ROOT_DIR . $serve);
	} else {
		header('Location: '.Site('url') . $serve);
		header('X-Accel-Redirect: ' . Site('base').$serve); // Let's save a client redirection if we use nginx :)
	}
	exit;
} else {
	$_warning = __('getfile.notfound');
}