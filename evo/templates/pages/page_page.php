<div class="page">
	<?php if (!$page['hide_title']) { ?>
	<h2 class="title"><?= html_encode($page['title']) ?>
		<?php if (has_permission('admin.page_edit')) { ?>
			<a title="Éditer" href="<?= Site('url') ?>/admin?page=page_edit&id=<?= $page['id'] ?>"><i class="fa fa-pencil"></i></a>
		<?php } ?>
	</h2>
	<?php } ?>
	<?= $page['content'] ?>
</div>