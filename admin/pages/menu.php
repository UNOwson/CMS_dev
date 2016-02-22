<?php has_permission('admin.edit_menu', true);

if (isset($_POST['add_menu'], $_POST['name']) && !empty($_POST['name'])) {
	if ($_POST['add_menu'] == 0 && Db::Insert('menu', ['parent'=>$_POST['parent'], 'priority'=>$_POST['priority'], 'name'=>$_POST['name'], 'icon'=>$_POST['icon'], 'link'=>$_POST['link']?:$_POST['internal_page']]))
		$_success = 'Élément ajouté!';
	elseif (Db::Exec('UPDATE {menu} SET parent =?, priority =?, name = ?, icon =? , link = ? WHERE id = ?', $_POST['parent'], $_POST['priority'], $_POST['name'], $_POST['icon'], $_POST['link'] ?: $_POST['internal_page'], $_POST['add_menu']))
		$_success = 'Élément mis à jour!';
	elseif(Db::$errno != 0)
		$_warning = Db::$error;
}
elseif (isset($_POST['del_menu'])) {
	if (Db::Exec('DELETE FROM {menu} WHERE id = ?', $_POST['del_menu'])) {
		$_success = 'Élément supprimé!';
	} else {
		$_warning = Db::$error;
	}
}
elseif (isset($_POST['menu-editor'])) {
	foreach($_POST['menu-editor'] as $priority => $k) {
		if (!$k) continue;
		Db::Exec('update {menu} set priority = ? where id = ?', $priority, $k);
	}
	$_success = 'Menu enregistré!';
}

$parent_list = array(0 => '');
$tree = get_menu_tree(true, $items);
$cur_elem = array('id' => '', 'parent' => 0, 'name' => '', 'icon' => '', 'link' => '', 'priority' => 0, 'page_name' => null);

if (isset($_POST['edit_menu']) && isset($items[$_POST['edit_menu']])) {
	$cur_elem =	$items[$_POST['edit_menu']];
}

?>
<legend>Éditeur de menus</legend>
<form method="post"  action="#edit">
<?php if (!$tree): ?>
	<div class="text-center alert alert-warning">Aucun élément trouvé!</div>
<?php else: ?>
	<table class="table sortable" id="menu-editor">
		<thead>
			<tr>
				<th>Nom</th>
				<th></th>
				<th>Ordre</th>
				<th>Adresse</th>
				<th style="width:90px;"> </th>
			</tr>
		</thead>
		<?php
		
		function display_tree($id = 0) {
			global $tree, $level, $parent_list;

			foreach ($tree[$id] as &$menu) {
				$parent_list[$menu['id']] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level).html_encode($menu['name']);
				echo '<tr id="' . $menu['id'] . '">';
					echo '<td>'.str_repeat('<span class="fa fa-arrow-right"></span> ', $level) . $menu['name'].'</td>';
					echo '<td><i class="fa fa-'.$menu['icon'].'"></i></td>';
					echo '<td>'.$menu['priority'].'</td>';
					if (is_null($menu['page_name']))
						echo '<td><a href="'.html_encode(strpos($menu['link'], '/') ? $menu['link'] : site('url').'/'.$menu['link']).'">'.html_encode(short($menu['link'], 40)).'</a></td>';
					else
						echo '<td><a href="'.create_url($menu['link']).'">'.html_encode(short($menu['page_name'], 40)).'</a></td>';
					echo '<td>'.
							'<button name="edit_menu" value="'.$menu['id'].'" class="btn btn-xs btn-primary" title="Éditer cet élément"><i class="fa fa-pencil"></i></button>&nbsp;'.
							'<button name="del_menu" value="'.$menu['id'].'" class="btn btn-xs btn-danger" title="Supprimer cet élément" onclick="return confirm(\'Sur?\');"><i class="fa fa-eraser"></i></button>'.
						 '</td>';
						 
				echo '</tr>';
				if (isset($tree[$menu['id']])) {
					$level++;
					display_tree($menu['id']);
					$level--;
				}
			}
		}
		
		display_tree(0);
		?>
	</table>
<?php endif; ?>
</form>
<br>
<?php if (!isset($_success) && !isset($_warning)) echo '<a name="edit"></a>'; ?>
<form class="form-horizontal" method="post" action="#">
	<?php
		if ($cur_elem['id'])
			echo '<legend>Modifier l\'élément #'.$cur_elem['id'].'</legend>';
		else
			echo '<legend>Ajouter un élément</legend>';
	?>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Nom :</label>
		<div class="col-sm-8 controls">
			<input class="form-control" name="name" type="text" value="<?php echo html_encode($cur_elem['name'])?>">
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Icône :</label>
		<div class="col-sm-8 controls"><span class="fa2">
		<?php
		if (preg_match_all('#\.fa-(?<label>[-a-z]+):before\s?{\s?+content:\s?"\\\(?<icon>[0-9a-z]+)"#msU', file_get_contents(ROOT_DIR.'/assets/css/font-awesome.min.css'), $m)) {
			$m['icon'][] = '';
			$m['label'][] = '';
			echo html_select('icon', array_combine($m['label'], array_map(function($e, $l) {return $e ? $l . ' &#x' . $e . ';' : $l;}, $m['icon'], $m['label'])), $cur_elem['icon'], false); 
		}
		?>
		</span></div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Parent :</label>
		<div class="col-sm-8 controls"><span class="fa2">
			<?php echo html_select('parent', $parent_list, $cur_elem['parent'], false); ?>
		</span></div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Ordre :</label>
		<div class="col-sm-8 controls">
			<?php echo html_select('priority', array_keys(array_fill(0, 100, '')), $cur_elem['priority']); ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label" for="account">Lien :</label>
		<div class="col-sm-8 controls">
			<input class="form-control" name="link" id="link" type="text" value="<?php echo $cur_elem['page_name'] ? '' : html_encode($cur_elem['link'])?>">
			ou
			<?php 
				foreach(glob(ROOT_DIR.'/pages/*.php') as $page)
					$pages[basename($page, '.php')] = basename($page);
					
				$pages =  array(
									'' => '----',
									'Pages' => array_map(function(&$a) { return end($a);}, Db::QueryAll('select p.page_id, title  from {pages} as p join {pages_revs} as r ON r.page_id = p.page_id AND r.revision = p.revisions order by pub_date desc, title asc', true)),
									'Interne' => $pages);
				
				echo html_select('internal_page', $pages, $cur_elem['link']); ?>
				<script> $('#internal_page').change(function() { $('#link').val(''); }); </script>
		</div>
	</div>
	<div class="text-center">
		<button class="btn btn-medium btn-primary" name="add_menu" value="<?php echo $cur_elem['id']?>" type="submit">Enregistrer le menu</button>  <button class="btn btn-danger">Annuler</button>
	</div>
</form>
