<?php 
	has_permission('admin.page_create') || has_permission('admin.page_edit', true);

	$ppp = 10;
	$pn = isset($_REQUEST['pn']) && $_REQUEST['pn'] > 0 ? $_REQUEST['pn'] : 1;
	$ptotal = ceil(Db::Get('select count(*) from {pages}') / $ppp);
	
	if (isset($_GET['filter'])) {
		$filter = '%' . $_GET['filter'] . '%';
	} else {
		$filter = '%';
	}
	
	if (_POST('drop_cache')) {
		rrmdir(ROOT_DIR . '/cache/', true) and $_success = 'Cache vidé !';	
	}
?>
<div class="pull-right">
	<form method="post" class="form-inline">
		<input id="filter" name="filter" class="form-control" type="text" placeholder="Recherche..." style="height:auto; padding: 2px 4px;">
		<button type="submit" hidden></button>
		<?php if (Site('cache')) { ?>
			<button class="btn btn-warning" name="drop_cache" value="1" title="Vider le cache"><i class="fa fa-lg fa-refresh"></i></button>
		<?php } ?>
		<a class="btn btn-info" href="index.php?page=page_edit" title="Ajouter une nouvelle page"><i class="fa fa-lg fa-file-o"></i></a>
	</form>
</div>

<legend>Liste des pages</legend>

<form action="?page=page_edit" method="post">
	<input type="hidden" name="delete" value="1">
	<div id="content">
		<table class="table">
			<thead>
				<th style="width:50%">Page</th>
				<th style="width:0px;"></th>
				<th>Révisions</th>
				<th>Commentaires</th>
				<th>Vues</th>
				<th>Gestion</th>
			</thead>
			<tbody>
		<?php 
			//select where status <> "revision"
			$pages =  Db::QueryAll('SELECT r.*, p.* FROM {pages} as p 
									JOIN {pages_revs} as r ON r.page_id = p.page_id AND r.revision IN(p.revisions, p.pub_rev) 
									WHERE r.title LIKE ?
									ORDER BY r.status, p.page_id DESC, revision DESC LIMIT ?, ?',
									$filter, ($pn - 1) * $ppp, $ppp);
			foreach($pages as $page) {
				$a = ($page['pub_rev'] != $page['revision'] ? ['rev' => $page['revision']]:[]);
				echo '<tr'.($page['pub_rev'] != $page['revision'] ? ' class="warning"':'').'>';
					echo '<td><a href="'.create_url($page['slug']?:$page['page_id'], $a).'">'.html_encode($page['title'] ?: 'Page sans titre').'</a>';
					if ($page['pub_rev'] != $page['revision'])
						echo '<small><em> - Brouillon</em></small>';
					echo '</td>';
					echo '<td><a title="Permalink" href="'.create_url($page['page_id']).'"><small>#</small></a></td>';
					echo '<td>'.$page['revisions'].'</td>';
					echo '<td>'.$page['comments'].'</td>';
					echo '<td>'.$page['views'].'</td>';
					echo '<td class="btn-group">';
						echo '<a title="Éditer" href="index.php?page=page_edit&id='.$page['id'].'" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a>';
						echo '<button title="Supprimer" class="btn btn-danger btn-xs" name="id" value="'.$page['id'].'" onclick="return confirm(\'Sur?\');"><i class="fa fa-eraser"></i></button>';
					echo '</td>';
				echo '</tr>';
				};
		?>
			</tbody>
		</table>
		<?php echo paginator(count($pages) < $ppp ? $pn : $ptotal, $pn, 10); ?>
	</div>
</form>