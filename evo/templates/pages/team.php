<table class="table">
	<thead>
		<th><?= __('team.username') ?></th>
		<th><?= __('team.email') ?></th>
		<th><?= __('team.rank') ?></th>
		<th> </th>
	</thead>
	<tbody>
	<?php
		foreach($users as $row) {
			echo '<tr>'.
					 '<td><a href="'.create_url('user', ['id' => $row['id']]).'">'.html_encode($row['username']).'</a></td>'.
					 '<td>'.($row['hide_email'] ? 'Confidentiel' : html_encode($row['email'])).'</td>'.
					 '<td style="color:'.$row['color'].'">'.$row['gname'].'</td>'.
					 '<td class="text-right">'.
						 '<a href="'.create_url('mail', ['id' => $row['username']]).'" title="' . __('team.send_message') . '" class="btn btn-primary btn-small"><i class="fa fa-pencil"></i></a> '.
					 '</td>'.
				  '</tr>';
		}
	?>
	</tbody>
</table>