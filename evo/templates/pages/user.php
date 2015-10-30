<div class="row">
	<div class="col-sm-2 col-xs-2">
		<div><?= get_avatar($user_info, 100) ?></div>
	</div>
	<div class="col-sm-5 col-xs-3">
		<h4><?=ucfirst($user_info['username']) ?></h4>
		<p style="margin: -5px 0 8px; color:<?= $user_info['color'] ?>"><?= ucfirst($user_info['gname']) ?></p>
		<?=$user_info['ban_reason'] ? '<span class="label label-danger">Membre bannis</span>' : ''?>
		<?php if ($user_info['facebook']) { ?>
			<a target="_blank" href="https://facebook.com/<?= $user_info['facebook'] ?>">
				<span class="fa-stack fa-lg" title="Facebook">
					<i class="fa fa-square fa-stack-2x"></i>
					<i class="fa fa-facebook fa-stack-1x fa-inverse"></i>
				</span>
				<span class="sr-only">Facebook</span>
			</a>
		<?php } if ($user_info['twitter']) { ?>
			<a target="_blank" href="https://twitter.com/<?= $user_info['twitter'] ?>">
				<span class="fa-stack fa-lg" title="Twitter">
					<i class="fa fa-square fa-stack-2x"></i>
					<i class="fa fa-twitter fa-stack-1x fa-inverse"></i>
				</span>
				<span class="sr-only">Twitter</span>
			</a>
		<?php } if ($user_info['twitch']) { ?>
			<a target="_blank" href="http://www.twitch.tv/<?= $user_info['twitch'] ?>">
				<span class="fa-stack fa-lg" title="Twitch">
					<i class="fa fa-square fa-stack-2x"></i>
					<i class="fa fa-twitch fa-stack-1x fa-inverse"></i>
				</span>
				<span class="sr-only">Twitch</span>
			</a>
		<?php } if ($user_info['youtube']) { ?>
			<a target="_blank" href="<?= $user_info['youtube'] ?>">
				<span class="fa-stack fa-lg" title="Youtube">
					<i class="fa fa-square fa-stack-2x"></i>
					<i class="fa fa-youtube fa-stack-1x fa-inverse"></i>
				</span>
				<span class="sr-only">Youtube</span>
			</a>
		<?php } if ($user_info['skype']) { ?>
			<a target="_blank" href="skype:<?= $user_info['skype'] ?>">
				<span class="fa-stack fa-lg" title="Skype">
					<i class="fa fa-square fa-stack-2x"></i>
					<i class="fa fa-skype fa-stack-1x fa-inverse"></i>
				</span>
				<span class="sr-only">Skype</span>
			</a>
		<?php } if ($user_info['website']) { ?>
			<a target="_blank" href="<?= $user_info['website'] ?>">
				<span class="fa-stack fa-lg" title="<?= __('user.website') ?>">
					<i class="fa fa-square fa-stack-2x"></i>
					<i class="fa fa-home fa-stack-1x fa-inverse"></i>
				</span>
				<span class="sr-only"><?= __('user.website') ?></span>
			</a>
		<?php } ?>
	</div>
	<div class="col-sm-5 col-xs-3 text-right">
		<?php
		if ($can_edit)
			echo '<a class="btn btn-primary btn-sm" href="'.create_url('profile', $user_info['id']).'"><i class="fa fa-pencil fa-3x"></i><br>' . __('user.edit') . '</a> ';
		
		if (!$is_mine) {
			echo '<button class="btn btn-danger btn-sm" onclick="report(' . $user_info['id'] . ');"><i class="fa fa-flag-o fa-3x"></i><br>' . __('user.report') . '</button> ';
			if (has_permission('mod.ban_member'))
				echo '<a class="btn btn-danger btn-sm" href="' . Site('url') . '/admin/?page=banlist&id='.$user_info['username'].'"><i class="fa fa-ban fa-3x"></i><br>' . __('user.ban') . '</a> ';
		}
		
		if ($can_mod) {
			echo '<a class="btn btn-danger btn-sm" href="' . Site('url') . '/admin/?page=users&filter=username:'.$user_info['username'].'"><i class="fa fa-user-secret fa-3x"></i><br>' . __('user.manage') . '</a> ';
		}
		
		if ($is_mine) {
			if ($mail = Db::Get('select count(*) from {mailbox} WHERE viewed IS NULL AND deleted_rcv = 0 AND r_id = ?', $user_session['id']))
				echo '<a href="'.create_url('mail').'" class="btn btn-warning btn-sm"><i class="fa fa-envelope-o fa-3x"></i><br>('.$mail.')</a> ';
			if ($friends = Db::Get('SELECT COUNT(*) FROM {friends} WHERE state = 0 AND f_id = ?', $user_session['id']))
				echo '<a href="'.create_url('friends').'" class="btn btn-warning btn-sm"><i class="fa fa-user fa-3x"></i><br>('.$friends.')</a> ';
		}
		?>
	</div>
</div>
<hr>
<div class="row text-center">
	<div class="col-xs-3">
        <a title="<?= __('user.forum_posts') ?>" href="<?=create_url('forums', ['search'=>'','poster'=>$user_info['username']]) ?>"><strong><?= $user_info['num_posts'] ?></strong> <i class="fa fa-pencil"></i></a>
    </div>
    <div class="col-xs-3">
        <a title="<?= __('user.comments') ?>"><strong><?= $num_comments ?></strong> <i class="fa fa-comment"></i></a>
    </div>
    <div class="col-xs-3">
        <a title="<?= __('user.friends') ?>"><strong><?= $num_friends ?></strong> <i class="fa fa-user"></i></a>
    </div>
    <div class="col-xs-3">
        <a title="<?= __('user.likes') ?>"><strong>N/A</strong> <i class="fa fa-heart"></i></a>
    </div>
</div>
<?php if (!$is_mine): ?>
	<hr>
	<div class="row">
		<div class="col-md-6">
			<a class="btn btn-success btn-sm btn-block" href="<?= create_url('mail/'.$user_info['username']) ?>"><i class="fa fa-envelope"></i> <?= __('user.send_message') ?></a>
		 </div>
		 <div class="col-md-6">
			<form method="post" action="<?= create_url('friends') ?>">
			<button class="btn btn-warning btn-sm btn-block" name="new_friend" value="<?= $user_info['username'] ?>"><i class="fa fa-user"></i> <?= __('user.add_friend') ?></button>
			</form>
		 </div>
	</div>
<?php endif; ?>
<br>
<div class="user-profile-content">
	<h5><strong><?= __('user.about_me') ?></strong></h5>
    <p><?= nl2br($user_info['about'] ?: __('user.info_unavailable')) ?></p>
    <hr>
    <div class="row">
        <div class="col-sm-6">
			<h5><strong><i class="fa fa-globe"></i> <?= __('user.country') ?></strong></h5>
			<?php 
				if ($user_info['country'])
					echo '<p style="margin-left: 28px"><img src="' . get_asset('/img/flags/' . strtolower($user_info['country']) .'.png') . '"> '. $_countries[$user_info['country']] .'</p>';
				else
					echo '<p>' . __('user.info_unavailable') . '</p>';
			?>
			<br>
			<h5><strong><i class="fa fa-envelope-o"></i></strong> <?= __('user.email') ?></h5>
			<p><?= $user_info['hide_email'] ? __('user.info_unavailable') : $user_info['email'] ?></p><br>
			<h5><strong><i class="fa fa-cloud"></i> <?= __('user.website') ?></strong></h5>
			<a style="margin-left:30px" href="<?= $user_info['website'] ?>" target="_blank"><?= $user_info['website'] ?></a>
        </div>
        <div class="col-sm-6">
			<h5><strong><i class="fa fa-calendar"></i></strong> <?= __('user.member_since') ?></h5>
            <p style="margin-left: 30px"><?= $user_info['registered'] > 0 ? date('Y-m-d @ H:i', $user_info['registered']) : __('user.info_unavailable') ?></p><br>
			<h5><strong><i class="fa fa-clock-o"></i></strong> <?= __('user.last_activity') ?></h5>
            <p style="margin-left: 30px"><?= $user_info['activity'] > 0 ? date('Y-m-d @ H:i', $user_info['activity']) : __('user.info_unavailable') ?></p><br>
        </div>
    </div>
</div>