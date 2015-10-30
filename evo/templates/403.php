<?php if (!has_permission()) { ?>
	<?php include_template('pages/login.php', ['action' => 'login', 'login' => '', 'password' => '']); ?>
	<div class="bs-callout bs-callout-danger">
		<h4>Attention !</h4>
		<p>Vous devez être connecter pour avoir accès à cette page !</p>
	</div>
<?php } else { ?>
	<div class="bs-callout bs-callout-danger">
		<h4>Attention !</h4>
		<p>Vous n'avez pas accès à cette page ou section du site !</p>
		<p><a href="<?= html_encode(_GET('redir')) ?>" onclick="window.history.back(); return false;">Revenir en arrière</a>.</p>
	</div>
<?php } ?>