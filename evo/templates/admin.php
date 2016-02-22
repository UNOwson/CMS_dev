<!DOCTYPE html>
<html>

<head>
	<?php include get_template('head.php'); ?>
	<link href="<?= get_asset('css/admin.css') ?>" rel="stylesheet">
</head>
	
<body>
<div id="top_bar">
     <div id="logo" class="col-xs-1">
         <div id="logo_zone">
    	        <img src="https://fkcd.ca/MWe.gif" width="45">
          </div>
     </div>
     <div id="notif_title" class="col pull-left">
          <h3>Panneau d'administration</h3>
    </div>
    <div name="notif_Zone" class="col pull-right">
         <ul id="notif_ico">
            <li><a href="<?=$update_available ?: EVO_UPDATE_URL;?>" data-toggle="tooltip" data-placement="bottom" title="Mise à jour"><i class="fa fa-lg fa-inbox <?php if ($update_available) echo 'fa-inverse'; ?>"></i> <?php if ($update_available) echo '<span class="label label-success">!</span>'; ?></a></li>
            <li><a href="index.php?page=comments" data-toggle="tooltip" data-placement="bottom" title="Commentaires"><i class="fa fa-lg fa-comment <?php if ($comments_nbr) echo 'fa-inverse'; ?>"></i> <?php if ($comments_nbr) echo '<span class="label label-primary">'.$comments_nbr.'</span>'; ?></a></li>
            <li><a href="index.php?page=reports" data-toggle="tooltip" data-placement="bottom" title="Notifications"><i class="fa fa-lg fa-bell <?php if ($reports_nbr) echo 'fa-inverse'; ?>"></i> <?php if ($reports_nbr) echo '<span class="label label-danger">'.$reports_nbr.'</span>'; ?></a></li>
        </ul>
    </div>
</div>

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

    <div name="menu" id="menu_zone" class="col-xs-1">
        <ul style="margin-bottom: 0px;">
            <li id="logo_zone"><?= get_avatar($user_session) ?></li>
        </ul>
        <?php include 'menu.php'; ?>
    </div>

<div id="container" style="margin-top:70px;">
    <div id="Wrapper_zone" class="col">

	<!-- DEBUT CONTENU -->
	<?php echo $contenu; ?>

	<!-- FIN CONTENU -->
		</div>
</div>
		
		<!-- DEBUT FOOTER -->
			<div id="footer">
				<strong>
					© <?php echo date('Y')?> <a href="" target="_blank"><?php echo html_encode(Site('name')) ?></a> Powered by <a href="http://www.evolution-network.ca" target="_blank">Evo-CMS</a>
				</strong>
				<br>
				<small style="cursor:pointer;" onclick="window.scrollTo(0, $('#debug').toggle().offset().top);"><?php echo plural('requête|requête', Db::$num_queries, true);?> en <?php echo plural('seconde|secondes', round(Db::$exec_time, 4), true); ?>.</small>
				<br>
				<div id="debug" hidden><br><?php if (has_permission('admin.sql')) widgets::print_queries(Db::$queries); ?></div>
			</div>
		<!-- FIN FOOTER -->

		<script src="<?= get_asset('js/evo-cms.js') ?>"></script>
		
		<script>
		if ((pos = window.location.href.indexOf('&')) > 1) {
			var page = window.location.href.substr(0, pos);
		} else {
			var page = null;
		}
		
		$('#admin-menu li').removeClass('active');
		
		$("#admin-menu a[href!='#']").each(function() {
		  if(this.href == window.location.href) {
				$('#admin-menu li').removeClass('active');
				$(this).parents('li').addClass('active');
				return false;
		  } else if(page && this.href.substr(0, page.length) == page) {
				$(this).parents('li').addClass('active');
		  }
		});
		</script>
</body>	
</html>
