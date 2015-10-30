<?php	$mois = array('', 'Janv.', 'F&eacute;v.', 'Mars', 'Avril', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'D&eacute;c.'); ?>
<div class="article">
	<div class="snippet">
		<div class="date"><?php echo date('d', $article['pub_date']); ?></div>
		<div class="mois"><?php echo $mois[(int)date('m', $article['pub_date'])]; ?></div>
	</div>
	<?php if (!$article['hide_title']) { ?>
		<div class="title"><a href="<?=$article['page_link'];?>"><?php echo html_encode($article['title']); ?></a></div>
	<?php } ?>
	<div class="sender">Publié par : <a href="<?=$article['author_link'];?>"><?php echo html_encode($article['username']); ?></a></div>
	<br>
	<div class="message">
		<?php echo $article['abstract'] ?: $article['content']; ?>
	</div>
	<div class="a-btn clearfix">
		<div style="padding-top: 3px;float:left;">Nombre de visites : <?php echo $article['views']; ?></div>
		<div class="pull-right">
		<?php if ($home) { ?>
			<a href="<?=$article['page_link'];?>" class="btn btn-primary">En savoir plus</a>
		<?php } ?>
		</div>
	</div>
</div>