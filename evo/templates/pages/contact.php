<?php if ($mail_status === 'form') { ?>
	<form method="post" class="form-horizontal">		
		<legend>Informations de contact</legend>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="username">Nom d'utilisateur :</label>
			<div class="col-sm-6">
				<input class="form-control" name="username" value="<?=$user_session['username']?>" type="text">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label"  for="mail">Votre Email :</label>
			<div class="col-sm-6">
				<input class="form-control" name="email" value="<?=$user_session['email']?>" type="text">
			</div>
		</div>
	</br>
		<legend>Contenu de votre message</legend>
		<div class="form-group">
			<label class="col-sm-3 control-label">Sujet :</label>
			<div class="col-sm-6">
				<input class="form-control" name="sujet" type="text" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">Message :</label>
			<div class="col-sm-9">
				<textarea class="form-control" style="max-width:450px" rows="10" name="message" type="text"></textarea>
			</div>
		</div>
		<div class="form_btn_ok">
			<input class="btn btn-medium btn-block btn-primary" type="submit" style="width: 160px;margin: 0 auto;" name="envoi" value="Envoyer le message">
		</div>
	</form>
<?php } elseif($mail_status === 'error') { ?>
	<div class="bs-callout bs-callout-danger">
		 <h4>Attention</h4>
		 <p>L'envoi du message a échoué. Veuillez réessayer SVP.</p>
	</div>
<?php } elseif ($mail_status === 'incomplete') { ?>
	<div class="bs-callout bs-callout-danger">
		 <h4>Attention</h4>
		 <p>Vérifiez que tous les champs soient bien remplis et que l'email soit sans erreur.</p>
	</div>
<?php } elseif ($mail_status === 'yes') { ?>
	<div class="bs-callout bs-callout-success">
		 <h4>Félicitation</h4>
		 <p>Votre message a été envoyé avec succès. Nous vous répondrons dans un bref délais.</p>
	</div>
<?php } ?>