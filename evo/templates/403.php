<?php if (!has_permission()) { ?>
	<?php include_template('pages/login.php', ['action' => 'login', 'login' => '', 'password' => '']); ?>
	<div class="bs-callout bs-callout-danger">
		<h4><strong><?= __('403.attention'); ?></strong></h4>
		<p><?= __('403.login'); ?></p>
	</div>
<?php } else { ?>
	<div class="bs-callout bs-callout-danger">
		<h4><?= __('403.attention'); ?></h4>
		<p><?= __('403.access'); ?></p>
		<p><a href="<?= html_encode(_GET('redir')) ?>" onclick="window.history.back(); return false;">Revenir en arrière</a>.</p>
	</div>
<?php } ?>