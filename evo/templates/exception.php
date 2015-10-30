<!DOCTYPE html>
<html>
	<head>
		<style>
			div.sql-query {
				border: 1px solid #222;
				padding: 0px 10px 10px;
			}
		</style>
	</head>
	<body>
		<h2>Une erreur de type <?= get_class($e) ?> s'est produite!</h2>
		<pre style="white-space: pre-wrap"><?php
			echo substr($e->getFile(), strlen(ROOT_DIR)) . '#' . $e->getLine() . ': <strong>' . $e->getMessage() . "</strong>\n\n"; 
			
			if (!empty($_warning))
				echo 'warning: ' . $_warning . "\n";
			if (!empty($_notice))
				echo 'notice: '  . $_notice . "\n";
			if (!empty($_success))
				echo 'success: ' . $_success . "\n";
			
			Db::$throwException = false;
			
			if ($user_session['id']) {
				echo $e->getTraceAsString()."\n\n";
				echo "<hr>Last PHP error to occur (May or may not be relevant):\n";
				print_r(error_get_last());
				echo "\n<hr>";
				if (Db::$db && has_permission('admin.sql'))
					echo str_replace('<br>', '', widgets::print_queries(array_reverse(Db::$queries, true), true));
			} else {
				echo preg_replace('@(^|\n)(#[0-9]+ )(' . ROOT_DIR . ')?([^:]+).*@', '$1$2$4', $e->getTraceAsString());
			}
		?></pre>
	</body>
</html>
