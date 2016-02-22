<?php 
has_permission('admin.download_bkp_web', true);

log_event($user_session['id'], 'admin', 'Téléchargement d\'une sauvegarde web.');

$filename = 'backup_web-'.date('Y-m-d_Hi').'.zip';
$filter = null;

if (_GET('dist')) {
	$filter = '#^' . ROOT_DIR . '(' . implode('|', array(
			'/upload/.+/.+',
			'/\.git(ignore)?',
			'/cache/(.*)',
			'/themes/(?!default|index.html)([^/]+)',
			'/config\.php',
		)) . ')(/.+)?$#';
	$filename = 'evocms-'.EVO_VERSION.'.r'.EVO_REVISION.'-'.date('Ymd').'.zip';
}

ignore_user_abort(true);
set_time_limit(0);
ob_end_clean();

header('Content-Type: application/zip');
header('Content-Transfer-Encoding: Binary');
header('Content-disposition: attachment; filename="' . $filename . '"');


if (class_exists('ZipArchive'))
{
	class BetterZip extends ZipArchive
	{
		public function addDir($path, $base = '')
		{
			 global $filter;
			
			 if (!empty($base) && strpos($path, $base) === 0) {
				$startpos = strlen($base) + 1;
			 } else {
				$startpos = 0;
			 }
			 
			 if ($filter && preg_match($filter, $path))
			   return;
			
			 $this->addEmptyDir(substr($path, $startpos));
			 foreach (glob($path . '/{.??*,*}', GLOB_BRACE) as $node) {
				if ($filter && preg_match($filter, $node)) {
				   continue;
		      } elseif (is_dir($node)) {
					$this->addDir($node, $base);
				} else if (is_file($node))  {
					$this->addFile($node, substr($node, $startpos));
				}
			 }
		}		
	}
	
	$zip = new BetterZip;
	
	$tmp_file = tempnam(sys_get_temp_dir(), 'evo-backup');
	
	$zip->open($tmp_file, ZipArchive::CREATE);
	$zip->addDir(ROOT_DIR, ROOT_DIR);

	$zip->close();
	
	readfile($tmp_file);
	unlink($tmp_file);
	
} else {
	
	chdir(ROOT_DIR) or die;
	
	$fd = popen('zip -r - .', 'r');
	
	while ($line = fread($fd, 8096)) {
		echo $line;
		flush();
	}
}
die;