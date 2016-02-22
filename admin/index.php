<?php
try {
	define('EVO_ADMIN', 1);
	require_once '../evo/common.php';
	has_permission('admin.') || has_permission('mod.', true);
	
	if (!empty($_GET['page'])) {
		$_script = basename($_GET['page']); //basename should be enough validation
	} elseif (isset($_GET['p'])) { // This is needed to load images in WYSIWYG editor. We could store absolute url instead but we'd have to REPLACE forums,comments,pages,polls,etc if the url changes.
		header('Location: '. Site('url') . str_replace('/admin/', '/', $_SERVER['REQUEST_URI']));
		exit;
	} else {
		$_script = 'accueil';
	}
	
	if (file_exists('./pages/'.$_script.'.php')) {
		include './pages/'.$_script.'.php';
	} else {
		$_script = '404';
		echo 'Page introuvable!';
	}
	
	if ( ! IS_AJAX) {
		$contenu = ob_get_contents();
		ob_clean();
		
		$reports_nbr = Db::Get('select count(*) from {reports} where deleted = 0 or deleted is null');
		$comments_nbr = Db::Get('select count(*) from {comments} where state = 0');
		$update_available = false;
		
		if (empty($_SESSION['update_check']) || $_SESSION['update_check'] < time()) {
			$_SESSION['update_check'] = time() + 3600;
			if ($buffer = @file_get_contents(EVO_UPDATE_URL)) {
				list($rev, $url) = explode(' ', $buffer . ' ');
				if ($rev > EVO_REVISION) {
					$update_available = $url;
				}
			}
		}
		
		include get_template('admin.php');
	}
	
} catch(PDOException $e) {
	@ob_end_clean();
	include get_template('exception.php');
} catch(Exception $e) {
	@ob_end_clean();
	include get_template('exception.php');
}