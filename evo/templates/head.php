<meta charset="utf-8">
<meta name="description" content="<?= html_encode(Site('description')) ?>">
<meta name="title" content="<?= html_encode(Site('name')) ?>">
<meta name="identifier-url" content="<?= html_encode(Site('url')) ?>">
<meta name="revisit-after" content="1">
<meta name="language" content="<?=Site('language')?>">
<meta name="robots" content="All">

<title><?= html_encode($_title ?: Site('name'))?></title>

<link href="<?= get_asset('css/vendor.css') ?>" rel="stylesheet">
<link href="<?= get_asset('css/style.css') ?>" rel="stylesheet">
<link href="<?= get_asset('favicon.ico') ?>" rel="shortcut icon">
<link rel="alternate" type="application/rss+xml" href="<?=create_url('feed')?>">

<script src="<?= get_asset('js/components.js') ?>"></script>
<script>
	var site_url = '<?= Site('url') ?>';
	var enable_poll = <?= (int) ($user_session['id'] || Db::Get('select count(*) from {servers}')) ?>;
	var logged_in = <?= (int) $user_session['id'] ?>;
	var csrf = '<?= isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '' ?>';
</script>

<?php plugins::trigger('head'); ?>

<?php
$css = array();
if (!empty($theme_settings['settings'])) {
	foreach($theme_settings['settings'] as $name => $setting) {
		if (isset($setting['css']) && Site($name)) {
			$css[] = sprintf($setting['css'], Site($name));
		}
	}
	if (!empty($css))
		echo '<style>'.implode($css).'</style>';
}
?>