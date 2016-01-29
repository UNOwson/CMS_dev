<?php defined('EVO') or die(__('403.msg'));

$mail_status = 'form';

if (isset($_POST['username'], $_POST['email'], $_POST['sujet'], $_POST['message']))
{
	// formulaire envoyé, on récupère tous les champs.
	$nom     = preg_replace('#[^a-z0-9_-]#i', '_', remove_accents($_POST['username']));
	$email   = $_POST['email'];
	$objet   = $_POST['sujet'];
	$message = $_POST['message'];

	if (preg_match(PREG_EMAIL, $email) && !e_empty($nom, $email, $objet, $message))
	{
		$headers  = 'From: '.Site('name').' <'.Site('email').'>' . "\r\n";
		$headers .= 'Reply-To: '.$nom.' <'.$email.'>' . "\r\n";
		$headers .= 'Content-Type: text/plain;charset=UTF-8';

		// Envoi du mail
		$mail_status= mail(Site('email'), $objet, $message, $headers) ? 'yes' : 'error';
	}
	else
	{
		$mail_status = 'incomplete';
	}
}

include_template('pages/contact.php', compact('nom', 'email', 'objet', 'message', 'mail_status'));