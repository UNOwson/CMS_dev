<?php
has_permission('admin.page_create') || has_permission('admin.page_edit', true);

//We should add a new lesser permission to allow team members to create blog posts that will be
//sanitized with sanitize_html() to avoid potential security issues with iframes.

$page = array(
			'id' => 0,
			'page_id' => 0,
			'title' => '',
			'slug' => '',
			'category' => '',
			'redirect' => '',
			'content' => '',
			'metas' => array(),
			'type' => false, 
			'allow_comments' => 1, 
			'display_toc' => 0,
			'revision' => 0,
			'revisions' => 0,
			'pub_rev' => 0,
			'pub_date' => 0,
			'views' => 0,
			'comments' => 0,
			'format' => Site('editor'),
			'sticky' => 0,
			'attached_files' => array(),
			'status' => '',
			'hide_title' => 0,
		);

$types = array(
			'article' => 'Article', 
			'page' => 'Page', 
			'page-wide' => 'Page pleine largeur', 
			'page-raw' => 'Page sans template', 
			);

if (_GP('page_id')) {
	$_GET['id'] = Db::Get('select id from {pages_revs} where (status="published" or status="draft") and page_id = ? order by status limit 1', _GP('page_id'));
}
 
if (_POST('id', _GET('id')) > 0) {
	$revision = Db::Get('SELECT r.*, p.*
						 FROM {pages} AS p
						 JOIN {pages_revs} as r
						 ON r.page_id = p.page_id
						 WHERE r.id = ?', _POST('id', _GET('id')));
	if ($revision) {
		$page = (array)$revision;
		$page['metas'] = @unserialize($page['metas']);
	} else {
		$_warning = 'La page que vous tentez d\'éditer n\'existe pas encore!';
	}
}

if (isset($_FILES['ajaxup'])) {
	if ($file = upload_fichier('ajaxup', null, null, false)) {
		ob_end_clean();
		die(json_encode(array($file[3], $file[0], '?p='.$file[1], filesize(ROOT_DIR.$file[3]))));
	}
}

if (isset($_POST['delete']) && has_permission('admin.page_delete', true)) {
	if ($page['revision'] == $page['pub_rev']) {
		$r = Db::Exec('UPDATE {pages} SET pub_rev = 0 WHERE page_id = ?', $page['page_id']);
	} else {
		$r = Db::Exec('DELETE FROM {pages_revs} WHERE id = ?', $page['id']);
		$r = Db::Exec('UPDATE {pages} SET revisions = (select max(revision) from {pages_revs} as r where r.page_id = {pages}.page_id) WHERE {pages}.page_id = ?', $page['page_id']);
	}

	if ($page['revision'] == $page['revisions'] && ($page['revision'] == $page['pub_rev'] || $page['pub_rev'] == 0)) {
		$r = Db::Exec('DELETE FROM {pages_revs} WHERE page_id = ?', $page['page_id']) +
			 Db::Exec('DELETE FROM {comments} WHERE page_id = ?', $page['page_id']) +
			 Db::Exec('DELETE FROM {pages} WHERE page_id = ?', $page['page_id']);
	}
	
	if ($r > 0) {
		log_event($user_session['id'], 'admin', 'Suppression de la page #'.$page['page_id'].': '.$page['title'].'.');
		return header('Location: ' . Site('url') . '/admin/index.php?page=pages');
	} else {
		$_warning = Db::$error;
	}
}
elseif (!isset($_POST['compare']) && isset($_POST['title'], $_POST['slug'], $_POST['content'])) {

	if (has_permission('admin.page_create') || $page['page_id'] != 0 && has_permission('admin.page_edit')) {
		
		if (empty($_POST['slug']) && $_POST['type'] === 'article') {
			$_POST['slug'] = date('Y/m/') . trim($_POST['title']);
		}
		
		$page['slug'] = format_slug($_POST['slug'] ?: $_POST['title']);
		
		rrmdir(ROOT_DIR . '/cache', true);
		
		/* A slug can't be an existing script name, a number, or be already attributed to another article */
		if (ctype_digit($page['slug']) || file_exists(ROOT_DIR . '/pages/' . $page['slug'] . '.php') || Db::Get('select slug from {pages} where slug = ? and page_id <> ?', $page['slug'], $page['page_id'])) {
			$i = 1;
			while (Db::Get('select slug from {pages} where slug = ? and page_id <> ?', $page['slug'] . '-' . $i, $page['page_id'])) {
				$i++;
			}
			$page['slug'] .= '-' . $i;
		}
		
		
		if (!isset($_POST['draft'])) {
			if ($_POST['status'] == 'published') {
				$page['pub_date'] = $page['pub_date'] ?: time();
				$page['pub_rev'] = &$page['revision'];
			} elseif ($page['pub_rev'] == $page['revision']) {
				$page['pub_rev'] = 0;
			}
		} else {
			$_POST['status'] = 'draft';
			$_POST['id'] = 0;
		}
		
		if (isset($_POST['draft']) || $page['content'] != $_POST['content'] || $page['title'] != $_POST['title'] || $page['slug'] != $_POST['slug'] || $page['revision'] < $page['revisions'])
		{
			$page['revision'] = ++$page['revisions'];
			$new_rev = true;
		}
		
		Db::Insert('pages', [
				'page_id'        => $page['page_id'] ?: null,
				'revisions'      => $page['revisions'],
				'slug'           => $page['slug'],
				'pub_date'       => strtotime($_POST['pub_date_text']) ?: $page['pub_date'],
				'pub_rev'        => $page['pub_rev'],
				'type'           => $_POST['type'],
				'display_toc'    => $_POST['display_toc'],
				'allow_comments' => $_POST['allow_comments'],
				'views'          => $page['views'],
				'comments'       => $page['comments'],
				'category'       => $_POST['category'],
				'redirect'       => $_POST['redirect'],
				'sticky'         => $_POST['sticky'],
				'hide_title'     => $_POST['hide_title'],
		], true);
			
		
		$page['page_id'] = $page['page_id'] ?: Db::$insert_id;
		
		log_event($user_session['id'], 'admin', 'Mise à jour de la page #'.$page['page_id'].': '.$_POST['title'].'.');
		
		if (!isset($_POST['autosave'])) {
			Db::Exec('UPDATE {pages_revs} SET status = "revision" WHERE id = ? OR (status = ? and id <> ? and page_id = ?)', $_POST['id'], $_POST['status'], $page['id'], $page['page_id']);
		}
		
		if (isset($new_rev)) {
			$page['attached_files'] = parse_attached_files($_POST['content'], 'page', $page['page_id']);
			Db::Insert('pages_revs', [
				'posted'          => time(), 
				'page_id'         => $page['page_id'],
				'revision'        => $page['revisions'], 
				'author'          => $user_session['id'], 
				'slug'            => $page['slug'], 
				'title'           => $_POST['title'], 
				'content'         =>  $_POST['content'],
				'attached_files'  => serialize($page['attached_files']), 
				'status'          => isset($_POST['autosave']) ? 'autosave' : $_POST['status'],
				'format'          => $_POST['format']
			]);
			
			$page['id'] = Db::$insert_id;
			
			log_event($user_session['id'], 'admin', 'Nouvelle révision de la page #'.$page['page_id'].': '.$_POST['title'].'.');
		} else {
			Db::Exec('UPDATE {pages_revs} SET status = ? WHERE id = ?', $_POST['status'], $_POST['id']);
		}
		
		$page = Db::Get('SELECT r.*,p.* FROM {pages} as p JOIN {pages_revs} as r USING(page_id)
						 WHERE r.id = ?', $page['id']);
										  
		$_success = 'Page enregistrée!';
	}
	else {
		$_warning = 'Vous n\'avez pas la permission d\'enregistrer!';
	}
}

if ($_warning)
	$page = array_merge($page, $_POST);

if ($page['revision'] < $page['revisions']) {
	$_notice = 'Vous êtes en train d\'éditer une révision antérieure. Révision ouverte: ' . $page['revision'] . '. Révision publiée: ' . $page['pub_rev'] . '. Dernière révision: ' . $page['revisions'] . '.';
} elseif ($page['revisions'] > $page['pub_rev'] && $page['pub_rev'] != 0) {
	$_notice = 'Vous êtes en train d\'éditer une révision plus récente que celle publiée. Révision ouverte: ' . $page['revision'] . '. Révision publiée: ' . $page['pub_rev'] . '.';
} elseif ($page['status'] === 'autosave') {
	$last_manual = Db::Get('select max(id) from {pages_revs} where page_id = ? and status <> "autosave"', $page['page_id']);
	$_notice = 'Ceci est une sauvegarde automatique, pour retourner au dernier enregistrement manuel <a href="?page=page_edit&id='.$last_manual.'">cliquez ici</a>.';
}

if ($page['page_id'])
	echo '<legend>'.html_encode(trim($page['title']) ?: 'Sans titre') . '</legend>';
else
	echo '<legend>Nouvelle page</legend>';

	

if (_gp('rev1') && _gp('rev2')) {
	echo '<style>#diffbox ins, .ins {color:green;background:#dfd;text-decoration:none}#diffbox del, .del {color:red;background:#fdd;text-decoration:none}
		#diffbox  > p {margin:0;border:1px solid #bcd;border-bottom:none;padding:1px 3px;background:#def;font:14px sans-serif}#diffbox > p + div {margin:0;padding:2px 0 2px 2px;border:1px solid #bcd;border-top:none}
		#diffbox .pane {margin:0;padding:0;border:0;width:100%;min-height:30em;overflow:auto;font:12px monospace}#diffbox .diff {color:gray}</style>';
	
	$rev = Db::QueryAll('SELECT revision, content, posted FROM {pages_revs} WHERE page_id = ? AND (revision = ? OR revision = ?)', _gp('page_id'), _gp('rev1'), _gp('rev2'), true);
	
	if (count($rev) != 2) {
		$_warning = 'Révisions invalides ou identiques.';
	} else {
		
		$start_time = gettimeofday(true);
		$diff = new FineDiff($rev[_gp('rev2')]['content'], $rev[_gp('rev1')]['content'], FineDiff::$wordGranularity);
		$exec_time = gettimeofday(true) - $start_time;
		$rendered_diff = $diff->renderDiffToHTML();
		$rendering_time = gettimeofday(true) - $start_time;
		$diff_len = strlen($diff->getOpcodes());
		
		$d1 = '<strong><small>' . today($rev[_gp('rev1')]['posted'], 'H:i') . '</small></strong>';
		$d2 = '<strong><small>' . today($rev[_gp('rev2')]['posted'], 'H:i') . '</small></strong>';
		
		echo '<div id="diffbox" style="width:99%">';
		
		echo '<ins>Vert</ins>: Contenu présent dans ' . _gp('rev1') . ' (' . $d1 . ') mais pas dans ' . _gp('rev2') . ' (' . $d2 . ')<br>';
		echo '<del>Rouge</del>: Contenu présent dans ' . _gp('rev2') . ' (' . $d2 . ') mais pas dans ' . _gp('rev1') . ' (' . $d1 . ')<br>';
		
		echo '<p>Diff <span style="color:gray">
					(diff: '.sprintf('%.3f', $exec_time).' sec, rendering: '.sprintf('%.3f', $rendering_time).' sec, diff len: '.$diff_len.' chars)</span>
				</p><div><div class="pane diff" style="white-space:pre-wrap">'.$rendered_diff.'</div></pre></div></div>';
		return;
	}
}


function display_tree($tree, $level = 0, $id = 0) {
	$display_tree = array(0 => '');
	foreach ($tree[$id] as $menu) {
		$display_tree[$menu['id']] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level).html_encode($menu['name']);
		if (isset($tree[$menu['id']])) {
			$level++;
			$display_tree = $display_tree + display_tree($tree, $level, $menu['id']);;
			$level--;
		}
	}
	return $display_tree;
}

$menu_tree = get_menu_tree(false, $items);
$menu = 0;


if ($page['page_id']) {
	foreach($items as $item) {
		if ($item['link'] == $page['page_id']) {
			$menu = $item['id'];
			break;
		}
	}

	if ($menu === 0 && !empty($_POST['menu_item'])) {
		Db::Insert('menu', [
			'parent'   => $_POST['menu_item'], 
			'priority' => 0, 
			'name'     => $page['title'], 
			'icon'     => '', 
			'link'     => $page['page_id']
		]);
	}
}
?>
<ul class="nav nav-tabs">
	<li class="active"><a href="#page_edit" data-toggle="tab">Édition</a></li>
	<li><a href="#page_opts" data-toggle="tab">Options</a></li>
<?php if ($page['page_id']) { ?>
	<li><a href="#page_hist" data-toggle="tab">Historique</a></li>
	<li><a href="index.php?page=comments&page_id=<?php echo $page['page_id']; ?>" class="fancybox-ajax">Commentaires</a></li>
	<li><a href="<?=create_url($page['page_id'], ['rev' => $page['revision']]);?>">Voir</a></li>
<?php } ?>
</ul>
<form method="post" enctype="multipart/form-data">
	<input type="hidden" id="id" name="id" value="<?php echo $page['id'];?>">
	<input type="hidden" id="page_id" name="page_id" value="<?php echo $page['page_id'];?>">
	<div class="tab-content panel">
		<div class="tab-pane fade active in" id="page_edit">
			<div class="control-group">
				<div class="row">
					<div class="col-sm-9">
						<label class="control-label" for="title">Titre de la page :</label>
						<div class="controls">
								<input class="form-control" name="title" type="text" placeholder="Titre" value="<?php echo html_encode($page['title']);?>"/>
						</div>
					</div>
					<div class="col-sm-3">
						<label class="control-label" for="title">Type :</label>
						<div class="controls">
								<select name="type" class="form-control">
									<?php foreach ($types as $id => $type) echo '<option value="'.$id.'" '.($id == $page['type'] ? 'selected' : '').'>'.$type.'</option>'; ?>
								</select>
						</div>
					</div>
				</div>
				
				<br>
				
				<div class="row">
					<div class="col-sm-9">
						<label class="control-label" for="title">URL :</label>
						<div class="controls">							
							<div class="input-group">
								<span class="input-group-addon"><?php echo Site('url'); ?>/</span>
								<input class="form-control" name="slug" type="text" placeholder="Slug" value="<?php echo html_encode($page['slug']);?>"/>
							</div>
						</div>
					</div>
					<div class="col-sm-3">
						<label class="control-label" for="title">Visibilité :</label>
						<div class="controls">
								<select name="status" class="form-control">
									<option value="published">Publiée <small><?php if ($page['pub_date']) echo '(' . today($page['pub_date']) . ')'; ?></small></option>
									<option value="draft" <?php if (!$page['pub_rev'] || $page['pub_rev'] != $page['revision']) echo 'selected'; ?>>Brouillon</option>
								</select>
						</div>
					</div>
				</div>
			</div>
			
			<br>
			<textarea class="form-control" id="editor" name="content" placeholder="Contenu" style="height:300px;"><?php echo html_encode($page['content']);?></textarea>
			<em id="AutoSaveStatus"></em>
			<br>
			<div class="pull-right">
				<?php echo html_select('format', $_editors, $page['format'], true, ''); ?>
			</div>
			<a class="btn btn-xs btn-info fancybox-ajax" href="index.php?page=gallery" id="creategallery">Média</a>
			<br>
			<div class="text-center">
				<button class='btn btn-success'>Enregistrer</button>
				<button class='btn btn-danger' name="delete" value="delete" onclick="return confirm('Sur?');">Supprimer</button>
				<?php if ($page['pub_rev'] && $page['pub_rev'] == $page['revision']) { ?>
					<button class='btn btn-warning' name="draft" id="BtnDraft" value="1">Brouillon</button>
				<?php } else { ?>
					<button class='btn btn-info' name="status" id="BtnPublish" value="published">Publier</button>
				<?php } ?>
			</div>
		</div>
		
		<div class="tab-pane fade" id="page_hist">
			<table class="table">
				<thead>
					<th width="30px;"><button name="compare" class="btn btn-primary btn-xs" value="1">Comparer</button></th>
					<th>#</th>
					<th>Date</th>
					<th>Status</th>
					<th>Auteur</th>
					<th>Taille</th>
					<th>Attachement</th>
					<th style="width:120px;"></th>
				</thead>
				<tbody>
				<?php
				$q = Db::QueryAll('SELECT r.*, p.*, a.username, LENGTH(r.content) as size
								   FROM {pages} as p
								   JOIN {pages_revs} as r ON r.page_id = p.page_id
								   LEFT JOIN {users} as a ON author = a.id
								   WHERE p.page_id = ?
								   ORDER by revision DESC', $page['page_id']);
				for ($i = 0, $j = count($q); $i < $j; $i++) {
					echo '<tr ';
						if ($page['pub_rev'] == $q[$i]['revision'])
							echo 'class="success"';
						elseif ($page['revision'] == $q[$i]['revision'])
							echo 'class="info"';
						
					echo '><td class="text-center;">';
					echo '<input type="radio" name="rev1" value="'.$q[$i]['revision'].'"' . ($i+1 == $j ? 'disabled':'') . '> ';
					echo '<input type="radio" name="rev2" value="'.$q[$i]['revision'].'"' . ($i == 0 ? 'disabled':'') . '> ';
					echo '</td><td>'.$q[$i]['revision'].'</td><td>'.today($q[$i]['posted']).'</td>';
					echo '<td>'.$q[$i]['status'].'</td><td>'.$q[$i]['username'].'</td><td>'.$q[$i]['size'].'</td><td>'.implode('<br>', (array)@unserialize($q[$i]['attached_files'])).'</td><td class="btn-group">';
					echo '<a title="Ouvrir dans l\'éditeur" href="index.php?page=page_edit&id='.$q[$i]['id'].'" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i></button> ';
					echo '<a title="Voir" href="'.create_url('pageview', ['id' => $q[$i]['page_id'], 'rev' => $q[$i]['revision']]).'" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a> ';
					echo '</td></tr>';
				}
				?>
				</tbody>
			</table>
		</div>
		
		
		
		<div class="tab-pane fade" id="page_opts">
			<br>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Visibilité:</label>
					<div class="col-sm-4">
						<select name="" class="form-control" disabled>
							<option value="published">Publiée</option>
							<option value="draft" <?php if (!$page['pub_rev'] || $page['pub_rev'] != $page['revision']) echo 'selected'; ?>>Brouillon</option>
						</select>
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Date de publication:</label>
					<div class="col-sm-4">
						<input type="text" name="pub_date_text" value="<?php echo $page['pub_date'] ? date('Y-m-d H:i', $page['pub_date']) : ''; ?>" class="form-control">
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Créer menu:</label>
					<div class="col-sm-4">
						<?php echo html_select('menu_item', display_tree(get_menu_tree()), $menu, false); ?>
						<small>Sélectionner le <u>parent</u> du nouvel item.</small>
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Catégorie:</label>
					<div class="col-sm-4">
						<input type="text" name="category" value="<?php echo html_encode($page['category']); ?>" class="form-control" data-autocomplete="categorylist" data-autocomplete-instant>
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Commentaires:</label>
					<div class="col-sm-4">
						<select name="allow_comments" class="form-control">
							<option value="1">Oui</option>
							<option value="0" <?php if ($page['allow_comments'] == 0) echo 'selected'; ?>>Non</option>
							<option value="2" <?php if ($page['allow_comments'] == 2) echo 'selected'; ?>>Clôs</option>
						</select>
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Table des matières:</label>
					<div class="col-sm-4">
						<select name="display_toc" class="form-control">
							<option value="1">Oui</option>
							<option value="0" <?php if (!$page['display_toc']) echo 'selected'; ?>>Non</option>
						</select>
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Cacher le titre:</label>
					<div class="col-sm-4">
						<select name="hide_title" class="form-control">
							<option value="1">Oui</option>
							<option value="0" <?php if (!$page['hide_title']) echo 'selected'; ?>>Non</option>
						</select>
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Redirection:</label>
					<div class="col-sm-4">
						<input type="text" name="redirect" value="<?php echo html_encode($page['redirect']); ?>" class="form-control">
					</div>
				</div>
			</div>
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-sm-3 control-label" for="name">Épingler:</label>
					<small>Épingler pour que l'article soit toujours présent sur la page d'accueil</small>
					<div class="col-sm-4">
						<select name="sticky" class="form-control">
							<option value="0">Ne pas épingler</option>
							<?php
								foreach(range(1, 100) as $sticky) {
									if ($page['sticky'] == $sticky) {
										echo '<option selected="selected" value="'.$sticky.'">Position '.$sticky.'</option>';
									} else {
										echo '<option value="'.$sticky.'">Position '.$sticky.'</option>';
									}
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
<script src="<?= get_asset('/scripts/editors.php') ?>"></script>
<script>
choose_editor($('#format').val());

var editor_content = get_editor_content();
setInterval(function() {
	if (get_editor_content() != editor_content) {
		console.log('okay');
		$.ajax({
			url: '',
			type: 'POST',
			data: $('form').serialize() + '&autosave=1' + ($('#BtnDraft').length ? '&draft=1' : ''),
			success: function(data) {
				$('#AutoSaveStatus').html('Saved at ' + new Date().timeNow());
				$('#id').val($('#id', data).val());
				$('#page_id').val($('#page_id').val());
				if ("replaceState" in history) {
					history.replaceState(null, null, '?page=page_edit&id=' + $('#id').val());
				}
			}
		});
		editor_content = get_editor_content();
	}
}, 30000);

$('#format').change(function() {
	choose_editor($(this).val());
});
	<?php if ($page['id']) echo 'if ("replaceState" in history)  { history.replaceState(null, null, "?page=page_edit&id=' . $page['id'] . '");}' ?>
</script>
