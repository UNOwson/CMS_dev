<?php if ($action === 'forget'): ?>
	<form class="form-horizontal" action="<?=create_url('login', ['action' => 'forget'])?>" method="post" id="forget">
		<input type="hidden" name="redir" value="user"/>
		<legend>Réinitialiser votre mot de passe</legend>
			<div class="form-group">
				<label class="col-sm-4 control-label">Nom d'utilisateur ou email</label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="login" value="<?=html_encode($login)?>">
				</div>
			</div>
			<div class="form-group text-center">
				<button type="submit" class="btn btn-medium btn-warning">Envoyer</button>
			</div>
	</form>
<?php elseif($action === 'reset'): ?>
	<form class="form-horizontal" method="post" id="forget">
		<input type="hidden" name="redir" value="user"/>
		<legend>Réinitialiser votre mot de passe</legend>
			<div class="form-group">
				<label class="col-sm-4 control-label">Nom d'utilisateur</label>
				<div class="col-sm-7">
					<?php echo html_encode($_GET['username'])?>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-4 control-label">Mot de passe</label>
				<div class="col-sm-6">
					<input class="form-control" type="password" name="new_password" value="<?=html_encode($password)?>">
				</div>	
			</div>
			<div class="form-group">
				<label class="col-sm-4 control-label">Confirmation</label>
				<div class="col-sm-6">
					<input class="form-control" type="password" name="new_password1" value="<?=html_encode($password)?>">
				</div>	
			</div>
			<div class="form-group text-center">
				<button type="submit" class="btn btn-medium btn-warning">Envoyer</button>
			</div>
	</form>
<?php else: ?>
	<form class="form-horizontal" action="<?=create_url('login', ['action' => 'login'])?>" method="post" id="login-form">
		<input type="hidden" name="redir" value="<?=html_encode(_GET('redir') ?: create_url('user'))?>"/>
		<legend>Connexion à l'espace membre</legend>
			<div class="form-group">
				<label class="col-sm-4 control-label">Nom d'utilisateur</label>
				<div class="col-sm-6">
					<input class="form-control" type="text" name="login" value="<?=html_encode($login)?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-4 control-label">Mot de passe</label>
				<div class="col-sm-6">
					<input class="form-control" type="password" name="pass" value="<?=html_encode($password)?>">
					<label class="control-label"><input type="checkbox" name="remember" value="1"> Se souvenir de moi</label>					
				</div>	
			</div>			
			<div class="text-center">
				<div class="btn-group">
					<button type="Submit" class="btn btn-medium btn-primary" type="submit" name="connexion">Connexion</button>
					<a href="<?=create_url('login', ['action' => 'forget'])?>" class="btn btn-medium btn-warning">J'ai oublié mon mot de passe</a>
				</div>
			</div>
	</form>
<?php endif; ?>