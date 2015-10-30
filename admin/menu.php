<?php has_permission('admin.') || has_permission('mod.', true);

$menu = [

	'Informations' => [
				'icon' => 'fa-info',
				'url' => Site('url') . '/admin/',
	],
									
	'Général' => [
				'icon' => 'fa-desktop',
				'url' => '#',
				'dropdown' => [
							'Configuration du site' => ['fa-keyboard-o', 'index.php?page=settings', 'admin.change_website'],
							'Configuration du thème' => ['fa-laptop', 'index.php?page=theme', 'admin.change_website'],
							'Les Signalements' => ['fa-exclamation-circle', 'index.php?page=reports', 'mod.'],
							'Serveurs' => ['fa-list', 'index.php?page=servers', 'admin.change_serv'],
							'Modules additionnels' => ['fa-cogs', 'index.php?page=plugins', 'admin.change_website'],
				],
	],
									
	'Contenu' => [
				'icon' => 'fa-pencil',
				'url' => '#',
				'dropdown' => [
							'Nouvelle Page' => ['fa-file-o', 'index.php?page=page_edit', 'admin.page_create'],
							'Gestion des pages' => ['fa-file-text-o', 'index.php?page=pages', 'admin.page_edit'],
							'Gestion du menu' => ['fa-list', 'index.php?page=menu', 'admin.edit_menu'],
							'Bibliothèque médias' => ['fa-picture-o', 'index.php?page=gallery', 'admin.media'],
							'Bibliothèque d\'avatars' => ['fa-smile-o', 'index.php?page=avatars', 'admin.media'],
							'Téléchargements' => ['fa-files-o', 'index.php?page=downloads', 'admin.media'],
				],
	],
										
	'Communauté' => [
				'icon' => 'fa-share-alt',
				'url' => '#',
				'dropdown' => [
							'Gestion des forums' => ['fa-list-alt', 'index.php?page=forums', 'admin.forum_edit'],
							'Gestion des sondages' => ['fa-question-circle', 'index.php?page=polls', 'admin.poll_edit'],
							'Les commentaires' => ['fa-comment', 'index.php?page=comments', 'admin.comment_censure'],
				],
	],
											
	'Utilisateurs' => [
				'icon' => 'fa-child',
				'url' => '#',
				'dropdown' => [
							'Les groupes' => ['fa-users', 'index.php?page=groups', 'admin.change_group'],
							'Les membres' => ['fa-user', 'index.php?page=users', 'mod.'],
							'Membres bannis' => ['fa-ban', 'index.php?page=banlist', 'mod.ban_member'],
							'Message de masse' => ['fa-envelope', 'index.php?page=broadcast', 'admin.broadcast'],
				],
	],
		
	'Historiques' => [
				'icon' => 'fa-history',
				'url' => '#',
				'dropdown' => [
							'Administration' => ['fa-file-text-o', 'index.php?page=history&type=admin', 'admin.log_admin'],
							'Utilisateurs' => ['fa-file-text-o', 'index.php?page=history&type=user', 'admin.log_user'],
							'Forum' => ['fa-file-text-o', 'index.php?page=history&type=forum', 'admin.log_forum'],
				],
	],
	
	'Système' => [
				'icon' => 'fa-cog',
				'url' => '#',
				'dropdown' => [
							'Traductions' => ['fa-globe', 'index.php?page=translate', 'admin.translate'],
							'Importation Système' => ['fa-magic', 'index.php?page=import_sql', 'admin.import_sql'],
							'Sauvegarde SQL' => ['fa-file-zip-o', 'index.php?page=sql_dump', 'admin.backup_sql'],
							'Sauvegarde Web' => ['fa-file-zip-o', 'index.php?page=web_dump', 'admin.download_bkp_web'],
							'Distribution' => ['fa-qrcode', 'index.php?page=web_dump&dist=1',  'admin.download_bkp_web'],
							'Adminer' => ['fa-database', 'adminer/', 'admin.sql'],
				],
	],
];
	
	echo '<ul class="nav navbar-nav" id="admin-menu">';
	
	foreach($menu as $label => $m) {
		if (isset($m['dropdown'])) {
			foreach($m['dropdown'] as $k => $link) {
				if (isset($link[2]) && !has_permission($link[2])) {
					unset($m['dropdown'][$k]);
				}
			}
			if (empty($m['dropdown'])) {
				unset($menu[$label]);
			}
		}
	}

	
	foreach($menu as $label => $item) {
		$drop = isset($item['dropdown']);
		
		echo $drop ? '<li class="dropdown">' : '<li>';
		
		echo '<a href="' . html_encode($item['url']) . '"' . ($drop ? ' class="dropdown-toggle" data-hover="dropdown"' : '') .
			  '><i class="fa fa-fw fa-2x ' . $item['icon'] . '"></i><br>' . '</a>';
		
		if ($drop) {
			echo '<ul class="dropdown-menu">';
            echo '<li class="dropdown-header">' . $label . '</li>';
            echo '<li role="separator" class="divider"></li>';
			foreach($item['dropdown'] as $label => $link) {
				echo '<li><a href="' . $link[1] . '"><i class="fa fa-fw ' . $link[0] . '"></i> ' . $label . '</a></li>';
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	
	echo '<li><a style="text-align: right;" href="' . Site('url') . '" tabindex="0">
			<i class="fa fa-fw fa-2x fa-sign-out"></i></a></li>';

	echo '</ul>';
?>