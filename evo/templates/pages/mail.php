<ul class="nav nav-tabs">
	<li class="<?=$tab_mail?:'active'?>"><a href="#inbox" data-toggle="tab"><?= __('mail.mailbox') ?> <span class='label label-success'><?= count($mail_inbox) ?></span></a></li>
	<li><a href="#outbox" data-toggle="tab"><?= __('mail.mailsent') ?> <span class='label label-info'><?= count($mail_outbox) ?></span></a></li>
	<li><a href="#trash" data-toggle="tab"><?= __('mail.maildelete') ?> <span class='label label-info'><?= count($mail_trash) ?></span></a></li>
	<li class="<?=!$tab_mail?:'active'?>"><a href="#mail" data-toggle="tab"><i class="fa fa-pencil"></i> <?= __('mail.mailwriter') ?></a></li>
	<li><a href="<?=create_url('mail');?>"><i class="fa fa-refresh"></i></a></li>
</ul>
<div class="tab-content">
<form method="post" class="tab-pane fade <?=$tab_mail?:'active in'?>" id="inbox" action="<?=create_url('mail');?>">
	<table class="table">
		<thead>
			<tr>
				<th style='width: 40px;'> </th>
				<th style='width:130px;'><?= __('mail.sender') ?></th>
				<th><?= __('mail.subject') ?></th>
				<th><?= __('mail.rdate') ?></th>
				<th style="width:80px;"> </th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($mail_inbox as $mail)
				{
					echo '<tr>';
						echo '<td>';
							if ($mail['viewed']) {
								echo '<i title="'.__('mail.ico_open').'" class="fa fa-2x ' . $_message_type[$mail['type']][3] . '">';
							} else {
								echo '<i title="'.__('mail.ico_new').'" class="fa fa-2x ' . $_message_type[$mail['type']][2] . '">';
							}
						echo '</td>';
						echo '<td>' . html_encode($mail['username']) . '</td>';
						echo '<td>' . html_encode($mail['sujet']) . '</td>';
						echo '<td style="white-space:nowrap;">' . today($mail['posted'], true) . '</td>';
						echo '<td class="text-right btn-group">';
							echo '<a href="' . create_url('mail#mail', ['id' => $mail['id']]) . '" title="'.__('mail.btn_read').'" class="btn btn-primary btn-sm"><i class="fa fa-eye fa-1"></i></a> ';
							echo "<button name='del_email' value='{$mail['id']}' title='".__('mail.btn_del')."' class='btn btn-danger btn-sm'><i class='fa fa-times'></i></button> ";
						echo '</td>';
					echo '</tr>';
				}
			?>
		</tbody>
	</table>
</form>
<form method="post" class="tab-pane fade" id="outbox" action="<?=create_url('mail')?>">
	<table class="table">
		<thead>
			<tr>
				<th><?= __('mail.recipient') ?></th>
				<th><?= __('mail.subject') ?></th>
				<th><?= __('mail.sdate') ?></th>
				<th><?= __('mail.readdate') ?></th>
				<th style="width:80px"> </th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($mail_outbox as $mailsent)
				{
					echo "<tr>";
						echo '<td style="white-space:nowrap;">' . html_encode($mailsent['username']) . '</td>';
						echo '<td>' . html_encode($mailsent['sujet']) . '</td>';
						echo '<td>' . today($mailsent['posted']) . '</td>';
						echo '<td>' . today($mailsent['viewed']) . '</td>';
						echo '<td class="btn-group"><a href="'.create_url('mail#mail', ['id' => $mailsent['id']]).'" title="'.__('mail.btn_read').'" class="btn btn-primary btn-sm"><i class="fa fa-eye fa-1"></i></a> ';
						echo "<button name='del_email' value='{$mailsent['id']}' title='".__('mail.btn_del')."' class='btn btn-danger btn-sm'><i class='fa fa-times'></i></button></td>";
					echo "</tr>";
				}
			?>
		</tbody>
	</table>
</form>
<form method="post" class="tab-pane fade" id="trash" action="<?=create_url('mail')?>">
	<table class="table">
		<thead>
			<tr>
				<th> </th>
				<th><?= __('mail.recipient') ?></th>
				<th><?= __('mail.subject') ?></th>
				<th><?= __('mail.sdate') ?></th>
				<th><?= __('mail.readdate') ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($mail_trash as $smailbox)
				{
					echo '<tr>';
						echo '<td style="width: 40px;">';
						if ($smailbox['viewed']) {
							echo '<i title="'.__('mail.ico_open').'" class="fa fa-2x fa-eye">';
						} else {
							echo '<i title="'.__('mail.ico_new').'" class="fa fa-2x fa-envelope">';
						}
						echo '</td>';
						echo '<td style="width:110px;">' . html_encode($smailbox['username']) . '</td>';
						echo '<td>' . html_encode($smailbox['sujet']) . '</td>';
						echo '<td>' . today($smailbox['posted']) . '</td>';
						echo '<td>' . today($smailbox['viewed']) . '</td>';
						echo '<td class="text-right">';
							echo "<button name='restore_email' value='{$smailbox['id']}' title='".__('mail.btn_restore')."' class='btn btn-success btn-sm'><i class='fa fa-save'></i></button> ";
						echo '</td>';
					echo '</tr>';
				}
			?>
		</tbody>
	</table>
</form>
<div class="tab-pane fade <?=!$tab_mail?:'active in'?>" id="mail">

<?php if ($mails) { ?>

	<form action="<?=$action?>" method="post">
		<legend style="margin-bottom:5px;">Discussion: <i><?=html_encode($mails[0]['sujet'])?></i></legend>
		<div class="commentaires">
		<?php if (count($participants) > 1) {?>
			<span style="color:gray;"><strong>Participants:</strong> <small><?=implode(', ', $participants)?></small></span>
		<?php } ?>
		<br>
		
		<?php foreach($mails as $message) { ?>
			<div class="commentaire<?=($highlight == $message['id'] ? ' highlight':'')?>" id="msg<?=$message['id']?>">
				<div class="avatar"><?=get_avatar($message)?></div>
				<div class="cadre_message">
					<div class="flag">
						<a style="color:#aaa;" href="<?=create_url('mail#mail', ['id' => $message['id']])?>">#<?=$message['id']?></a><br> 
						<?=$_message_type[$message['type']][1]?>
						<button type="submit" onclick="return confirm(\'Sur?\');" name="del_email" value="<?=$message['id']?>" class="btn btn-xs btn-danger" title="<?= __('mail.btn_del') ?>" style="padding:2px;"><i class="fa fa-trash-o"></i></button>
					</div>
					<div class="auteur">
						<strong><a href="<?=create_url('user', ['id' => $message['s_id']])?>"><?=html_encode($message['username'])?></a></strong> 
						(<span style="color:<?=$message['color']?>;"><?=$message['gname']?></span>)
						 a dit <b><?=today($message['posted'])?>:</b><br>@<?=$message['rcpt']?>
						<small> <b><?=$message['viewed'] ? 'l\'a lu ' . today($message['viewed'], true) : 'ne l\'a pas lu'?></b></small>
					</div>
					<div class="message"><?=emoticons(nl2br(html_encode($message['message'])))?></div><hr>
				</div>
			</div>
		<?php } ?>
	
		</div>
	</form>

	<form method="post" action="<?=create_url('mail')?>">
		<div class="commentaires text-center">
		<textarea class="form-control" name="message" placeholder="<?= __('mail.disc_reply') ?>"></textarea><br>
		<button class="btn btn-success" name="id" value="<?=$reply?>" type="submit"><?= __('mail.btn_send') ?></button> <a class="btn btn-danger" href="<?=create_url('mail')?>#mail"><?= __('mail.btn_cancel') ?></a>
		</div>
	</form>
	
<?php } elseif($can_send) { ?>

	<form method="post" action="<?=create_url('mail')?>">
		<legend><?= __('mail.writer_title') ?></legend>
		<div class="form-horizontal text-center">
		<div class="form-group">
			<label class="col-sm-2 control-label" for="query"><?= __('mail.writer_to') ?> :</label>
			<div class="col-sm-8 control">
				<input id="query" name="username" class="form-control"  data-autocomplete="userlist" type="text" value="<?=html_encode(_POST('username') ?: ((string)(int)_GP('id') === _GP('id') ? '' : _GP('id'))) ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="sujet"><?= __('mail.subject') ?> :</label>
			<div class="col-sm-8 control">
				<input name="sujet" class="form-control" type="text" maxlength="32" value="<?=html_encode(_POST('sujet'))?>">
			</div>
		</div>
		<textarea class="form-control" name="message" placeholder="<?= __('mail.writer_textarea') ?>"><?=html_encode(_POST('message'))?></textarea><br>
		<button class="btn btn-primary" type="submit" name="id" value=""><?= __('mail.btn_send') ?></button>
		</div>
	</form>
	
<?php } ?>

</div>
</div>

<script>
$('form a').click(function() {
	if (location.href == this.href && window.location.hash == this.hash) {
		$('[data-toggle="tab"][href="' + window.location.hash + '"]').tab('show')
	}
});
</script>