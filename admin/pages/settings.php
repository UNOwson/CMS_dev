<?php 
	use Translation\Lang;
	use Translation\Translator;

	has_permission('admin.change_website', true);
	
	$pages = ['Mes pages ' => []] + array_map(function(&$a) { return end($a);}, Db::QueryAll('select p.page_id, title  from {pages} as p join {pages_revs} as r ON r.page_id = p.page_id AND r.revision = p.revisions order by pub_date desc, title asc', true)) +
			 ['' => '--------', 'Interne ' => []] +
			 get_scripts();
	
	$groups = Db::QueryAll('select name, id from {groups} where id <> 1 and id <> 4 order by priority asc');
	
	foreach($groups as $group) {
		$group_list[$group['id']] = $group['name'];
	}
	
	
	// if (Site('timezone') && !isset($_timezones[Site('timezone')])) {
		// if($tz = array_search(date_default_timezone_get(), $_timezones)) {
			// Site('timezone', $tz, true);
		// } elseif($tz = getTimezoneByOffset(null)) {
			// Site('timezone', $tz, true);
		// }
	// }
	
	$communities = array('' => 'Aucune');
	
	foreach($_communities as $key => $community) {
		$communities[$key] = isset($community['label']) ? $community['label'] : $key;
	}
	
	$locales = array_combine(lang::getLocales(true), lang::getLocales(true));
	/* types supportés:
		text : text
		bool : oui/non
		enum : array de choix 'choices'
		color : color picker
		image : image picker
	*/
	$settings = array(
		'name' 				=> array('type' => 'text', 'label' => 'Nom du site'),
		'description' 		=> array('type' => 'text', 'label' => 'Description'),
		'url' 				=> array('type' => 'text', 'label' => 'URL du site'),
		'url_rewriting' 	=> array('type' => 'bool', 'label' => 'Rewriting', 'help' => 'Votre serveur doit supporter la réécriture. Le CMS supporte Apache automatiquement. Pour nginx voir nginx.conf.'),
		'cache' 			=> array('type' => 'bool', 'label' => 'Activer le cache', 'help' => 'Fonctionne seulement si la réécriture est active'),
		'frontpage' 		=> array('type' => 'enum', 'label' => 'Page d\'accueil', 'choices' => $pages),
		'language'			=> array('type' => 'enum', 'label' => 'Langue du CMS', 'choices' => $locales),
		'email' 			=> array('type' => 'text', 'label' => 'Email Administrateur'),
		'timezone'          => array('type' => 'enum', 'label' => 'Fuseau horaire', 'choices' => generate_tz_list()),
		'open_registration'	=> array('type' => 'enum', 'label' => 'Permettre les inscriptions', 'choices' => [0 => 'Non', 1 => 'Oui', 3 => 'Oui, avec activation par email', 2 => 'Oui, seulement avec parrainage']),
		'default_user_group'=> array('type' => 'enum', 'label' => 'Groupe par défaut', 'choices' => $group_list),
		'editor'			=> array('type' => 'enum', 'label' => 'Éditeur par défaut', 'choices' => $_editors),
		'community_type'	=> array('type' => 'enum', 'label' => 'Type de communauté', 'choices' => $communities),
		'upload_groups'		=> array('type' => 'textarea', 'label' => 'Fichiers acceptés', 'help' => 'Un groupe par ligne au format:<br>groupe ext ext...<br>où ext est une extension ou un mime-type. <br>Pour limiter l\'upload dans une section du CMS vous pouvez créé un format au nom de la page, forums par example, et seules les extensions listés dans ce groupe seront acceptées.', 'allow_reset' => true),
	);

	if ($_POST) {
		if (settings_save($settings, $_POST)) {
			$_success = 'Configuration mise à jour!';
			rrmdir(ROOT_DIR . '/cache/', true);
		}
	}
?>
<legend>Informations générales</legend>
<?php 
	echo settings_form($settings);
?>