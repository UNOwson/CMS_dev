<?php 
isset($_REQUEST['type']) or $_REQUEST['type'] = 'user';
has_permission('admin.log_'.$_REQUEST['type'], true); 

if (isset($_REQUEST['filter'])) {
	$q = array ($_REQUEST['type'], '%'.$_REQUEST['filter'].'%', '%'.$_REQUEST['filter'].'%', '%'.$_REQUEST['filter'].'%');
	$where = 'h.type = ? and (a.username like ? or h.event like ? or h.ip like ?)';
} else {
	$q = array ($_REQUEST['type']);
	$where = 'h.type = ?';
}

$start = isset($_REQUEST['pn']) ? ($_REQUEST['pn']-1) * 15 : 0;

$req = Db::QueryAll ('SELECT h.*, a.username as username, b.username as ausername
					  FROM {history} as h
					  LEFT JOIN {users} as a ON a.id = h.e_uid
					  LEFT JOIN {users} as b ON b.id = h.a_uid
					  WHERE '.$where.' 
					  ORDER BY h.id DESC LIMIT '.$start.',15', $q);
?>
<form role="search" class="well" method="post">
	<input id="filter" type="text" class="form-control" placeholder="recherche">
</form>
<form method="post">
	<div id="content">
		<?php if (!$req): ?>
			<div class="alert alert-warning text-center">Aucune entrée trouvée!</div>
		<?php else: ?>
		<table class="table">
			<thead>
				<th>Date</th>
				<th>Pseudo</th>
				<th>Affecté</th>
				<th>IP</th>
				<th>Événement</th>
			</thead>
			<tbody>
			<?php
				foreach($req as $data) {
					echo '<tr>';
						echo '<td style="white-space:nowrap;">' . date('Y-m-d H:i', $data['timestamp']) . '</td>';
						echo '<td>' . html_encode($data['username']) . '</td>';
						echo '<td>' . html_encode($data['ausername']) . '</td>';
						echo '<td>' . $data['ip'] . '</td>';
						echo '<td>' . html_encode($data['event']) . '</td>';
					echo "</tr>";
				}
			?>		
			</tbody>
		</table>
	<?php endif; ?>
	<?php echo paginator(count($req) >= 15 ? 100 : _GET('pn', 1), _GET('pn', 1), 10); ?>
	</div>
</form>