<div class="btn-group" id="user-dropdown">
	<a type="button" href="<?=create_url('user');?>" class="btn btn-default"><?= __('dropdown.my_account') ?> (<strong><?= $user_session['username'] ?></strong>)</a>
	<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
		<span class="sr-only">Toggle Dropdown</span>
	</button>
	<ul class="dropdown-menu" role="menu">
		<?php
		echo '<li><a href="'.create_url('profile').'"><i class="fa fa-pencil"></i> '.__('dropdown.edit').'</a></li>';
		if (has_permission('view_friendlist')) {
				echo '<li><a href="'.create_url('friends').'"><i class="fa fa-user"></i> '.__('dropdown.friends').'';
//										echo ' <span class="badge">'. ltrim(Db::Get('SELECT COUNT(*) FROM {friends} WHERE state = 0 AND f_id = ?', [$user_session['id']]), '0') . '</span>';
				echo "</a></li>";
		}
		if (has_permission('raf')) {
				echo '<li><a href="'.create_url('recruit-a-friend').'"><i class="fa fa-sitemap"></i> '.__('dropdown.raf').'</a></li>';
		}
		
		echo '<li><a href="'.create_url('mail').'"><i class="fa fa-envelope"></i> '.__('dropdown.email').'</a></li>';
		// echo ' <span class="badge">'. ltrim(Db::Get('SELECT COUNT(*) FROM {friends} WHERE state = 0 AND f_id = ?', [$user_session['id']]), '0') . '</span>';
		
		if (has_permission('user.upload')) {
				echo '<li><a href="'.create_url('gallery').'"><i class="fa fa-download"></i> '.__('dropdown.files').'</a></li>';
		}
		
		echo '<li class="divider"></li>';
		if (has_permission('admin.') || has_permission('mod.')) {
			echo '<li><a href="' . Site('url') . '/admin/"><i class="fa fa-cog"></i> '.__('dropdown.admin').'</a></li>';
		}
		?>
		<li><a href="<?=create_url('login', ['action'=>'logout'])?>"><i class="fa fa-power-off"></i> <?= __('dropdown.logoff') ?></a>
	</ul>
</div>