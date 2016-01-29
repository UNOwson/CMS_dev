<?php defined('EVO') or die('Que fais-tu là?');

$poll = Db::Get('select * from {polls} where poll_id = ?', _GP('id'));

if (!$poll) {
	throw new Warning('Sondage introuvable !');
}

$_title = 'Sondage: ' . $poll['name'];

$poll['choices'] = unserialize($poll['choices']);

$has_voted = Db::Get('select id from {polls_votes} where user_id = ? and poll_id = ?', $user_session['id'], _GP('id'));
$can_vote = has_permission() && $poll['end_date'] > time() && !$has_voted;

if ($can_vote && isset($_POST['vote']) && isset($poll['choices'][$_POST['vote']])) {
	Db::Insert('{polls_votes}', array(
		'poll_id' => _POST('poll_id'),
		'user_id' => $user_session['id'],
		'choice' => $_POST['vote'],
		'date' => time(),
	));
	$can_vote = false;
	$_success = 'Merci d\'avoir voté !';
}

if ($poll['end_date'] < time()) {
	$_notice = 'La période de vote est terminée pour ce sondage.';
}

$votes = Db::QueryAll('select choice, count(*) as c from {polls_votes} where poll_id = ? GROUP BY choice', _GP('id'), true);
?>

<?php if (!$has_voted && !_GET('result')) { ?>
	<div class="pull-right">
		<a href="<?=create_url('poll', ['id' => $poll['poll_id'], 'result' => 1])?>">Voir les résultats</a>
	</div>
<?php } ?>
<legend><?= html_encode($poll['name']) ?></legend>


<div id="chart"></div>

<div style="font-size:80%" class="text-center">
	<?= bbcode2html($poll['description']) ?>
</div>

	
<?php if ($can_vote) { ?>
	<legend>Voter:</legend>
	<form method="post" action="<?=create_url('poll', _GP('id')); ?>">
	<input type="hidden" name="poll_id" value="<?php echo _GP('id'); ?>">
		<ul>
		<?php
			foreach($poll['choices'] as $id => $choice) {
				echo '<li><label><input type="radio" name="vote" value="'.$id.'"> ' . $choice . '</label></li>';
			}
		?>
		</ul>
		<button class="btn btn-success">Voter</button>
	</form>
<?php } ?>

<?php
	if (has_permission('mod.')) {
		echo '<legend>Votes</legend>';

		echo '<table class="table table-striped" style="font-size:120%">';
			foreach(Db::QueryAll('select u.*, p.choice, p.date from {users} as u join {polls_votes} as p where poll_id = ? and p.user_id = u.id', _GP('id')) as $user)
			{
				echo '<tr>';
					echo '<td class="poll_tab_avatar">'.get_avatar($user, 32).'</td>';
					echo '<td class="text-left"><a href="'.create_url('user', $user['id']).'"><strong>'. $user['username'] . '</strong></a></td>';
					echo '<td class="text-center">'.$poll['choices'][$user['choice']].'</td>';
					echo '<td class="text-right">'.today($user['date']).'</td>';
				echo '</tr>';
			}
			echo '</table>';
	}
?>

<?php if ($has_voted || _GET('result')) { ?>
	<script src="<?= get_asset('js/highcharts.js') ?>"></script>
	<script>
	 $('#chart').highcharts({
		  title: {
				text: ''
		  },
		  tooltip: {
			 pointFormat: '<b>{point.y}</b> ({point.percentage:.1f}%)'
		  },
		  plotOptions: {
				pie: {
					 allowPointSelect: true,
					 cursor: 'pointer'
				}
		  },
		  series: [{
				type: 'pie',
				data: [
					<?php
						foreach($poll['choices'] as $id => $choice) {
							echo json_encode(array($choice, isset($votes[$id]) ? (int)$votes[$id]['c'] : 0)) . ',';
						}
					?>
				]
		  }]
	 });
	</script>
<?php } ?>