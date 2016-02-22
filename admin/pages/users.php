<?php
has_permission('admin.') || has_permission('mod.', true);
include ROOT_DIR . '/evo/misc/user_agent.php';

if (isset($_REQUEST['filter'])) {
	$columns = array_diff(Db::GetColumns('users', true), ['password', 'salt', 'raf']);
	$search = build_search_query($_REQUEST['filter'], preg_replace('/^/', 'a.', $columns));
	$where = 'where ' . $search['where'];
	$args = $search['args'];
} else {
	$where = '';
	$args = array();
}

$upp = 15;
$start = isset($_REQUEST['pn']) ? ($_REQUEST['pn']-1) * $upp: 0;

$args[] = $start;
$args[] = $upp;

$users = Db::QueryAll('SELECT a.*, g.name as gname, g.color as color,
							  (select reason from {banlist} as b where a.username like b.rule and b.type = "username" or b.rule like a.last_ip limit 1) as ban_reason
					   FROM {users} as a
					   LEFT JOIN {groups} as g ON g.id = a.group_id 
					   '.$where.' ORDER BY g.priority ASC, g.id DESC, username ASC LIMIT ?,?', $args);

// NOTE: found_rows not available with sqlite...
$ptotal = ceil(Db::Get('select count(*) from {users} as a left join {groups} as g on g.id = a.group_id ' . $where, array_slice($args, 0, -2)) / $upp);
?>
<form role="search" class="well" style="background:transparent" method="post">
	<input id="filter" name="filter" type="text" class="form-control" value="<?php echo isset($_REQUEST['filter']) ? html_encode($_REQUEST['filter']) : '';?>" placeholder="rechercher un membre par pseudo / email / grade / état">
</form>
<form method="post">
<div id="content">
	<?php if (!$users): ?>
		<div style="text-align: center;" class="alert alert-warning">Aucun membre trouvé!</div>
	<?php else: ?>
	<table class="table">
		<thead>
			<th style="width:115px"> </th>
			<th>Pseudo</th>
			<th>Email</th>
			<th>Grade</th>
			<th>Gestion</th>
		</thead>
		<tbody>
		<?php
		foreach($users as $member)
			{
				$vie = 'Signe de vie: ' . today($member['activity'], 'H:i');
				
				echo '<tr class="'.($member['ban_reason'] ? 'danger':'').'">';
					echo '<td>'.($member['activity'] > time() - 120 ? '<a class="ico-online" title="En Ligne<br>'.$vie.'"></a>' : '<a class="ico-offline" title="Hors Ligne<br>'.$vie.'"></a>' ).' &nbsp; '.get_useragent_icons($member['last_user_agent']).'</td>';
					echo '<td><a href="'.create_url('user', ['id'=>$member['username']]).'">'.html_encode($member['username']).'</a></td>';
					echo "<td>".html_encode($member['email'])."</td>";
					echo '<td><a style="color:'.$member['color'].';" href="index.php?page=users&filter=group_id:%20'.$member['group_id'].'">'.$member['gname'].'</a></td>';
					echo '<td>';
					
					if (has_permission('admin.edit_uprofile'))
						echo '<a href="index.php?page=user_view&id='.$member['id'].'" class="btn btn-primary btn-xs" title="Éditer le profil"><i class="fa fa-pencil"></i></a> ';
					
					if (has_permission('admin.del_member'))
						echo '<a href="index.php?page=user_delete&id='.$member['id'].'" class="btn btn-danger btn-xs" title="Supprimer le compte"><i class="fa fa-eraser"></i></a> ';
					
					if (has_permission('mod.ban_member')) {
						if ($member['ban_reason'])
							echo '<a href="?page=banlist&username='.$member['username'].'&ip='.$member['last_ip'].'&email='.$member['email'].'" class="fancybox-ajax btn btn-info btn-xs" title="Réactiver le compte" fancybox-title="Réactiver le compte"><i class="fa fa-unlock"></i></a> ';
						else
							echo '<a href="?page=banlist&hide&username='.$member['username'].'&ip='.$member['last_ip'].'&email='.$member['email'].'" class="fancybox-ajax btn btn-info btn-xs" title="Bannir ce membre" fancybox-title="Bannir ce membre"><i class="fa fa-lock"></i></a> ';
					}
					
					if (group_has_permission($member['group_id'], 'admin.download_bkp_web'))
						echo '<button class="btn btn-warning btn-xs" title="Super-Administrateur"><i class="fa fa-star"> </i></button> ';
					
					elseif (group_has_permission($member['group_id'], 'admin.'))
						echo '<button class="btn btn-warning btn-xs" title="Administrateur"><i class="fa fa-star-half-o"> </i></button> ';
					
					elseif (group_has_permission($member['group_id'], 'mod.'))
						echo '<button class="btn btn-warning btn-xs" title="Modérateur"><i class="fa fa-star-o"></i></button> ';
					
					echo '</td>';
				echo '</tr>';
			};
		?>
		</tbody>
	</table>
	<?php endif; ?>
<?php echo paginator(count($users) < $upp ? _GET('pn', 1) : $ptotal, _GET('pn', 1), 10, null, _GET('prevpn')); ?>
</div>
</form>