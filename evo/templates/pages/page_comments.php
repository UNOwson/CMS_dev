<a name="comments"></a>
<div class="commentaires">
	<?php if ($comments) { ?>
		<legend><?= __('tpagec.title_comments')?></legend>
		<form method="post" action="<?=Site('url')?>/admin/?page=comments">

		<?php foreach($comments as $comment) { ?>
			<div class="commentaire" id="msg<?=$comment['id']?>">
				<div class="avatar"><?=get_avatar($comment['user_id'] ? $comment : array('email' => $comment['email']))?></div>
				<div class="cadre_message">
					<div class="auteur">
						<div class="pull-right date text-right">
							<small><a href="#msg<?=$comment['id']?>" style="color:inherit"><?=today($comment['posted'], 'H:i')?></a><br></small>
							<div class="flag btn-group">
							<?php if ($comment['state'] == 0) {?>
								<button class="btn btn-xs btn-danger" onclick="return report(<?=$comment['id']?>);" title="<?= __('tpagec.report')?>"><i class="fa fa-flag"></i></button>
							<?php } ?>
							<?php if (has_permission('mod.comment_censure')) { ?>
								<button class="btn btn-xs btn-warning" name="com_censure" onclick="return confirm('Sur?');" value="<?=$comment['id']?>" title="<?= __('tpagec.censor')?>"><i class="fa fa-ban"></i></button>
							<?php } ?>
							<?php if (has_permission('mod.comment_delete')) { ?>
								<button class="btn btn-xs btn-danger" name="com_delete" onclick="return confirm('Sur?');" value="<?=$comment['id']?>" title="<?= __('tpagec.delete')?>"><i class="fa fa-times"></i></button>
							<?php } ?>
							</div>
						</div>
						
						<?php if ($comment['username']) { ?>
							<a href="<?=create_url('user', ['id' => $comment['user_id']])?>"><strong><?=$comment['username']?></strong></a>
						<?php } else { ?>
							<strong><?=$comment['poster_name']?></strong>
						<?php } ?>
						<br>
						<span style="color:<?=$comment['gcolor']?>;"><?=$comment['gname']?></span>
					</div>
					<div class="comment">
						<?php if($comment['state'] == 2) { ?>
							<div style='text-align: center;' class='alert alert-danger'><?= __('tpagec.tou')?>"></div>
						<?php } else { ?>
							<?=emoticons(nl2br(html_encode($comment['message'])))?>
						<?php } ?>
					</div>
				</div>
			</div>
		<?php } ?>
		</form>
	<?php } ?>
	
	<?php if ($can_post_comment) { ?>
		<form method="post" action="#">
		<?php if (has_permission()) { ?>
			<legend><?= __('tpagec.title_comment')?></legend>
			<textarea class="form-control" name="commentaire" placeholder="<?= __('tpagec.area_compose')?>" maxlength="1024" rows="3"></textarea>
			<div style="text-align:right;margin-top: 10px;">
				<input class="btn btn-success" name="new_comment" type="submit" value="<?= __('tpagec.btn_send')?>">
			</div>
		<?php } else { ?>
			<legend><a href="<?=create_url('login', ['redir' => $page['page_id']])?>">Connectez-vous</a> ou postez en tant qu'invité:</legend>
			<div class="input-group">
				<span class="input-group-addon"><i class="fa fa-user"></i></span><span class="sr-only">Your Name</span>
				<input type="text" name="name" class="form-control" placeholder="Votre nom (optionnel)">
				<span class="input-group-addon"><i class="fa fa-envelope"></i></span><span class="sr-only">Your Email</span>
				<input type="text" name="email" class="form-control" placeholder="Votre email (optionnel)">
			</div>
			<br>
			<textarea class="form-control" name="commentaire" placeholder="Composez votre message..." maxlength="1024" rows="3"></textarea>
			<br>
			<div class="input-group form-group"><span class="input-group-addon">Vérification: <strong><?=$captcha_code?></strong></span>
				<input class="form-control" type="text" name="verif" maxlength="8" value="">
			</div>
			<div class="text-center">
				<input class="btn btn-success" name="new_comment" type="submit" value="Envoyer">
			</div>
		<?php } ?>
		</form>
	<?php } ?>
</div>