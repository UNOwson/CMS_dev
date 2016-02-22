<?php has_permission('mod.', true);

	$in = 0;
	
	if (_POST('save_poll')) {
		$choices = array_filter(_POST('poll_choices') ?: array());
		if (_POST('poll_id')) {
			$poll = Db::Get('select * from {polls} where poll_id = ?', _POST('poll_id'));
			foreach(unserialize($poll['choices']) as $id => $choice) {
				if (!isset($choices[$id])) {
					Db::Exec('delete from {polls_votes} where poll_id = ? and choice = ?', _POST('poll_id'), $id);
				}
			}
		}
		Db::Insert('{polls}', array (
			'poll_id'			=> _POST('poll_id'),
			'name' 				=> _POST('poll_name'),
			'description'		=> _POST('poll_description'),
			'choices' 			=> serialize($choices),
			'end_date'			=> strtotime(_POST('poll_end_date'))
		), true);
		
		$_success = 'Sondage enregistré !';
		$in = Db::$insert_id ?: _POST('poll_id');	
	}
	elseif (_POST('delete_poll')) {
		if (Db::Exec('delete from {polls} where poll_id = ?', $_POST['poll_id'])) {
			Db::Exec('delete from {polls_votes} where poll_id = ?', $_POST['poll_id']);
			$_success = 'Sondage supprimé !';
		}
	}
	
	
	$polls = Db::QueryAll('select *, (select count(*) from {polls_votes} as v where v.poll_id = p.poll_id) as c from {polls} as p order by poll_id desc', true);
?>
<legend>Édition des sondages</legend>
<div class="panel-group" id="accordion">
	<div class="panel panel-default">
		<div class="panel-heading">
			<a data-toggle="collapse" data-parent="#accordion" href="#pollNew">
				<h3 class="panel-title"><strong>Nouveau sondage</strong></h3>
			</a>
		</div>
		<div class="panel-collapse collapse <?php if (!$in) echo 'in'; ?>" id="pollNew">
			<div class="panel-body">
			<form class="form-horizontal" method="post">
				<div class="form-group">
					<label class="col-sm-3 control-label">Nom du sondage:</label>
					<div class="col-sm-8">
						<input type="text" class="form-control" name="poll_name">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">Description:</label>
					<div class="col-sm-8">
						<textarea class="form-control" name="poll_description"></textarea>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label">Choix:</label>
					<div class="col-sm-8">
						<div class="input-group"><span class="input-group-addon remove"><i class="fa fa-times"></i></span><input class="form-control" name="poll_choices[]" value=""></div>
						<div class="input-group"><span class="input-group-addon remove"><i class="fa fa-times"></i></span><input class="form-control" name="poll_choices[]" value=""></div>
						<div>
							<a class="plus" style="cursor: pointer">Plus</a>
						</div>
					</div>
				</div>
				
				<div class="form-group">
					<label class="col-sm-3 control-label">Date de clôture:</label>
					<div class="col-sm-8">
						<input class="form-control" name="poll_end_date" value="+2 weeks">
					</div>
				</div>

				<div class="text-center">
					<button type="submit" name="save_poll" value="1" class="btn btn-success">Créer le sondage</button>
				</div>
			</form>
			</div>
		</div>
	</div>

	
	
	
	
	
	
	<?php	foreach($polls as $i => $poll) { ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="pull-right">
					<small><?php echo plural('Aucun vote|Un vote|%count% votes', $poll['c']); ?></small>
					<a href="<?=create_url('poll', $i); ?>">Voir</a>
				</div>
				<a data-toggle="collapse" data-parent="#accordion" href="#poll<?php echo $i;?>">
				 <h4 class="panel-title"><?php echo html_encode($poll['name']); ?></h4>
				</a>
			</div>
		 <div id="poll<?php echo $i;?>" class="panel-collapse collapse <?php if ($i == $in) echo 'in';?>">
			<div class="panel-body">
				<form class="form-horizontal" method="post">
					<input type="hidden" name="poll_id" value="<?php echo $poll['poll_id']; ?>">
					<div class="form-group">
						<label class="col-sm-3 control-label">Nom du sondage:</label>
						<div class="col-sm-8">
							<input type="text" class="form-control" name="poll_name" value="<?php echo html_encode($poll['name']); ?>">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">Description:</label>
						<div class="col-sm-8">
							<textarea class="form-control" name="poll_description"><?php echo html_encode($poll['description']); ?></textarea>
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-3 control-label">Choix:</label>
						<div class="col-sm-8">
							<?php foreach(unserialize($poll['choices']) as $id => $choice) {
								echo '<div class="input-group"><span class="input-group-addon remove"><i class="fa fa-times"></i></span><input class="form-control" name="poll_choices['.$id.']" value="'.html_encode($choice).'"></div>';
							} ?>
							<div>
								<a class="plus" style="cursor: pointer">Plus</a>
							</div>
						</div>
					</div>
					
					<div class="form-group">
						<label class="col-sm-3 control-label">Date de clôture:</label>
						<div class="col-sm-8">
							<input class="form-control" name="poll_end_date"  value="<?php echo date('Y-m-d H:i', $poll['end_date']); ?>">
						</div>
					</div>
					<div class="text-center">
						<button type="submit" name="save_poll" value="1" class="btn btn-success">Enregistrer</button>
						<button type="submit" name="delete_poll" value="1" class="btn btn-danger">Supprimer</button>
					</div>
				</form>
			</div>
		 </div>
		</div>
	<?php } ?>
</div>
<style>.input-group {margin-bottom:5px;}</style>
<script>
$('.plus').click(function() {
	$(this).parent().prepend('<div class="input-group"><span class="input-group-addon"><i class="fa fa-times remove"></i></span><input class="form-control" name="poll_choices[]" value=""></div>');
	return false;
});

$('.remove').click(function() {
	if (confirm('Supprimer ce choix et tous les votes qui y sont rattachés?')) {
		$(this).parent().remove();
	}
});
</script>