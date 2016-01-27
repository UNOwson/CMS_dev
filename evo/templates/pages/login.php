<?php if ($action === 'forget'): ?>
	<form class="form-horizontal" action="<?=create_url('login', ['action' => 'forget'])?>" method="post" id="forget">
		<input type="hidden" name="redir" value="user"/>
		<legend><?= __('login.forgot_title') ?></legend>
			<div class="form-group">
				<label class="col-sm-4 control-label"><?= __('login.forgot_field') ?></label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="login" value="<?=html_encode($login)?>">
				</div>
			</div>
			<div class="form-group text-center">
				<button type="submit" class="btn btn-medium btn-warning"><?= __('login.forgot_btn') ?></button>
			</div>
	</form>
<?php elseif($action === 'reset'): ?>
	<form class="form-horizontal" method="post" id="forget">
		<input type="hidden" name="redir" value="user"/>
		<legend><?= __('login.reset_title') ?></legend>
			<div class="form-group">
				<label class="col-sm-4 control-label"><?= __('login.reset_user') ?></label>
				<div class="col-sm-7">
					<?php echo html_encode($_GET['username'])?>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-4 control-label"><?= __('login.reset_pass') ?></label>
				<div class="col-sm-6">
					<input class="form-control" type="password" name="new_password" value="<?=html_encode($password)?>">
				</div>	
			</div>
			<div class="form-group">
				<label class="col-sm-4 control-label"><?= __('login.reset_confirm') ?></label>
				<div class="col-sm-6">
					<input class="form-control" type="password" name="new_password1" value="<?=html_encode($password)?>">
				</div>	
			</div>
			<div class="form-group text-center">
				<button type="submit" class="btn btn-medium btn-warning"><?= __('login.reset_btn') ?></button>
			</div>
	</form>
<?php else: ?>
	<form class="form-horizontal" action="<?=create_url('login', ['action' => 'login'])?>" method="post" id="login-form">
		<input type="hidden" name="redir" value="<?=html_encode(_GET('redir') ?: create_url('user'))?>"/>
		<legend><?= __('login.title') ?></legend>
			<div class="form-group">
				<label class="col-sm-4 control-label"><?= __('login.field1') ?></label>
				<div class="col-sm-6">
					<input class="form-control" type="text" name="login" value="<?=html_encode($login)?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-4 control-label"><?= __('login.field2') ?></label>
				<div class="col-sm-6">
					<input class="form-control" type="password" name="pass" value="<?=html_encode($password)?>">
					<label class="control-label"><input type="checkbox" name="remember" value="1"> <?= __('login.checkbox') ?></label>					
				</div>	
			</div>			
			<div class="text-center">
				<div class="btn-group">
					<button type="Submit" class="btn btn-medium btn-primary" type="submit" name="connexion"><?= __('login.btn_connect') ?></button>
					<a href="<?=create_url('login', ['action' => 'forget'])?>" class="btn btn-medium btn-warning"><?= __('login.btn_forgetpass') ?></a>
				</div>
			</div>
	</form>
<?php endif; ?>