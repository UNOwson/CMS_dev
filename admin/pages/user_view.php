<?php has_permission('mod.', true);

include ROOT_DIR.'/pages/profile.php';
return;
?>
<ul class="nav nav-tabs">
		<li class="active"><a href="#admin" data-toggle="tab">Admin</a></li>
		<li class="active"><a href="#history" data-toggle="tab">Historique</a></li>
		<li><a href="#page_opts" data-toggle="tab">Profil</a></li>
</ul>
	<div class="tab-content panel">
		<div class="tab-pane fade active in" id="page_edit">