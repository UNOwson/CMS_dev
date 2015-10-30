<!DOCTYPE html>
<html>

<head>
	<?php include get_template('head.php'); ?>
</head>
	
<body class="<?= $_body_class ?>">
	<div id="header">
		<div class="logo">
			<a href="<?= Site('url') ?>">
			<?= '<img src="'.get_asset(Site('theme.logo') ?: '/img/logo.png'). '" alt="logo">'; ?>
			</a>
		</div>
		<div class="links">
			<a href="<?= Site('theme.facebook'); ?>"><img src="<?= get_asset('/img/social/facebook.png') ?>" width="38" alt="facebook" title="Notre page Facebook"></a>
			<a href="<?= Site('theme.twitter'); ?>"><img src="<?= get_asset('/img/social/twitter.png') ?>" width="38" alt="twitter" title="Notre compte twitter"></a>
			<a href="<?= Site('theme.youtube'); ?>"><img src="<?= get_asset('/img/social/youtube.png') ?>" width="38" alt="youtube" title="Notre chaîne Youtube"></a>
			<a href="<?= create_url('feed')?>"><img src="<?= get_asset('/img/social/rss.png') ?>" width="38" alt="rss" title="Lisez notre flux !"></a>
		</div>
	</div>
	<div class="clearfix"></div>
	<div id="wrapper">
<!-- DEBUT SIDEBAR -->
		<div id="sidebar">
			<div id="zone_login">
			<?php if (has_permission()) { ?>
				<div class="avatar_container"><?= get_avatar($user_session) ?></div>
				<div class="welcome">Bienvenue <?= $user_session['username'] ?></div>
				<?php include get_template('userdropdown.php'); ?>
			<?php } else { ?>
				<div class="text-center">
					<div class="btn-group">
						<a href="<?= create_url('login') ?>" class="btn btn-primary"><i class="fa fa-user"></i>  Me Connecter</a> 
						<a href="<?= create_url('register') ?>" class="btn btn-success"><i class="fa fa-pencil"></i>  Créer un compte</a>
					</div>
				</div>
			<?php } ?>
			</div>
			<div id="menu">
				<?php widgets::menu(); ?>
			</div>
			<div id="notifications"></div>
		</div>
<!-- FIN SIDEBAR -->
	
	
<!-- DEBUT CONTENU -->
		<div id="page">
			<?php
				if (!empty($_success)) {
					echo '<div class="alert alert-success alert-dismissable auto-dismiss"><button type="button" class="close" 
					data-dismiss="alert" aria-hidden="true">&times;</button>'.$_success.'</div>';
				}
				if(!empty($_warning)) {
					echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" 
					data-dismiss="alert" aria-hidden="true">&times;</button>'.$_warning.'</div>';
				}
				if(!empty($_notice)) {
					echo '<div class="alert alert-warning alert-dismissable"><button type="button" class="close" 
					data-dismiss="alert" aria-hidden="true">&times;</button>'.$_notice.'</div>';
				}
			?>
			<?= $_content ?>
		</div>
<!-- FIN CONTENU -->
	
	
	<!-- DEBUT FOOTER -->
		<div id="footer">
			<strong>
				© <?= date('Y') ?> <a href="" target="_blank"><?= html_encode(Site('name')) ?></a> Powered by <a href="http://www.evolution-network.ca" target="_blank">Evo-CMS</a>
			</strong>
			<br>
			<small style="cursor:pointer;" onclick="window.scrollTo(0, $('#debug').toggle().offset().top);"><?= plural('%count% requête|%count% requêtes', Db::$num_queries);?> en <?= plural('%count% seconde|%count% secondes', round(Db::$exec_time, 4)); ?>.</small>
			<br>&nbsp;
			<br>
			<div id="debug" style="display:none"><?php if (has_permission('admin.sql')) widgets::print_queries(Db::$queries); ?></div>
		</div>
	<!-- FIN FOOTER -->
	</div>
	<script src="<?= get_asset('js/evo-cms.js') ?>"></script>
</body>	
</html>