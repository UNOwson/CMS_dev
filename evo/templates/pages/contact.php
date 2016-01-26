<?php if ($mail_status === 'form') { ?>
	<form method="post" class="form-horizontal">		
		<legend><?= __('contact.title1') ?></legend>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="username"><?= __('contact.user') ?> :</label>
			<div class="col-sm-6">
				<input class="form-control" name="username" value="<?=$user_session['username']?>" type="text">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label"  for="mail"><?= __('contact.email') ?> :</label>
			<div class="col-sm-6">
				<input class="form-control" name="email" value="<?=$user_session['email']?>" type="text">
			</div>
		</div>
	</br>
		<legend><?= __('contact.title2') ?></legend>
		<div class="form-group">
			<label class="col-sm-3 control-label"><?= __('contact.subject') ?> :</label>
			<div class="col-sm-6">
				<input class="form-control" name="sujet" type="text" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label"><?= __('contact.message') ?> :</label>
			<div class="col-sm-9">
				<textarea class="form-control" style="max-width:450px" rows="10" name="message" type="text"></textarea>
			</div>
		</div>
		<div class="form_btn_ok">
			<input class="btn btn-medium btn-block btn-primary" type="submit" style="width: 160px;margin: 0 auto;" name="envoi" value="<?= __('contact.btn_send') ?>">
		</div>
	</form>
<?php } elseif($mail_status === 'error') { ?>
	<div class="bs-callout bs-callout-danger">
		 <h4><?= __('contact.state_error') ?></h4>
		 <p><?= __('contact.alert_error') ?></p>
	</div>
<?php } elseif ($mail_status === 'incomplete') { ?>
	<div class="bs-callout bs-callout-danger">
		 <h4><?= __('contact.state_error') ?></h4>
		 <p><?= __('contact.alert_incomplet') ?></p>
	</div>
<?php } elseif ($mail_status === 'yes') { ?>
	<div class="bs-callout bs-callout-success">
		 <h4><?= __('contact.state_ok') ?></h4>
		 <p><?= __('contact.alert_ok') ?></p>
	</div>
<?php } ?>