<div id="viewcontrols" class="btn-group pull-right" role="group">
	<a class="btn btn-default" aria-label="Left Align" title="<?= __('users.grid') ?>" href="<?=create_url('users', ['view'=>'grid'])?>">
	  <i class="fa fa-th" style="font-size: 14px;"></i>
	</a>
	<a class="btn btn-default" aria-label="Left Align" title="<?= __('users.list') ?>" href="<?=create_url('users', ['view'=>'list'])?>">
	  <i class="fa fa-list" style="font-size: 14px;"></i>
	</a>
</div>
<legend><?= __('users.title') ?></legend>
<form role="search" class="well" style="background:transparent" method="post">
	<input id="filter" name="filter" type="text" class="form-control" value="<?=html_encode(_GP('filter'))?>" placeholder="<?= __('users.search_placeholder') ?>">
</form>
<div id="content">
<?php
	if ($display === 'list')
	{
		echo '<table class="table users-table"><thead><tr>';
		
		foreach($columns as $label => $column) {
			if ($column === $sort) {
				echo '<th><a href="'.create_url('users', ['view'=>'list','sort'=>$column.'+desc']).'"><em>' . $label . '</em></th>';
			} else {
				echo '<th><a href="'.create_url('users', ['view'=>'list','sort'=>$column]).'">' . $label . '</th>';
			}
		}
		
		echo '</tr></thead><tbody>';
		
		foreach ($users as $user)
		{
			echo '<tr>
					  <td><a  title="' . $user['gname'] . '" style="color:' . $user['color'] . '" href="'.create_url('user', ['id'=>$user['username']]).'">'.html_encode($user['username']).'</a></td>
					  <td>' . $user['ingame'] . '</td>
					  <td>' . $user['cmt'] . '</td>
					  <td>' . $user['fnd'] . '</td>
				  </tr>';
		}
		echo '</tbody></table>';
	}
	else
	{
		echo '<div class="users-block text-center" style="color:#aaa;">';
		foreach ($users as $user)
		{
			echo '<div style="display: inline-block; border: 1px solid #AAAAAA;padding-top:10px;width:20%;height:160px;">'.
					'<a  title="' . $user['gname'] . '" style="color:' . $user['color'] . '" href="'.create_url('user', ['id'=>$user['username']]).'">'.get_avatar($user).'<br>'.html_encode($user['username']).'</a>';
			
			if ($user['country'] && ($flag = get_asset('/img/flags/' . strtolower($user['country']) .'.png')))
				echo ' <img src="' . $flag . '" style="position:relative; top:-1px;" title="'.$_countries[$user['country']].'"> ';

			echo '<br><br>';
			
			echo $user['cmt'] . ' <i title="' . __('users.comments') . '" class="fa fa-comment"></i> ' . $user['fnd'] . ' <i title="' . __('users.friends') . '" class="fa fa-user"></i>';

			echo 	'</div>';
		}
		echo '</div>';
	}
	
	echo $paginator;
?>
</div>