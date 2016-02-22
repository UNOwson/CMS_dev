<?php
return array(
	'name' => 'Default',
	'categorie' => 'Officiel',
	'version' => 0,
	'settings' => array(
		'theme.logo' 		=> array('type' => 'image', 'label' => 'Logo'),
		'theme.background'	=> array('type' => 'image', 'label' => 'Background', 'css' => 'body{background-image: url(%s)}'),
		'theme.bg_color'	=> array('type' => 'color', 'label' => 'Background color', 'css' => 'body{background-color: %s}'),
		'theme.text_color'	=> array('type' => 'color', 'label' => 'Text color', 'css' => 'body{color: %s}'),
		'theme.facebook' 	=> array('type' => 'text', 'label' => '<i class="fa fa-facebook fa-2x"></i>'),
		'theme.twitter'		=> array('type' => 'text', 'label' => '<i class="fa fa-twitter fa-2x"></i>'),
		'theme.youtube'		=> array('type' => 'text', 'label' => '<i class="fa fa-youtube fa-2x"></i>'),
	),
);