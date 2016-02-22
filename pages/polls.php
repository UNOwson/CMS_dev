<?php defined('EVO') or die('Que fais-tu là?');

$polls = Db::QueryAll('select * from {polls} order by poll_id desc');

if (empty($polls)) {
	throw new Warning('IL n\'y a aucun sondage!', 'Aucun sondage à afficher.');
}

$votes_c  = Db::QueryAll('select *, count(*) as count from {polls_votes} group by poll_id, choice');
$my_votes = Db::QueryAll('select poll_id from {polls_votes} where user_id = ?', $user_session['id'], true);

$votes = [];
$can_vote = false;
$my_votes = array_keys($my_votes);

foreach($votes_c as $vote) {
	$votes[$vote['poll_id']][$vote['choice']] = $vote['count'];
}

foreach($polls as &$poll) {
	$choices = unserialize($poll['choices']);
	$poll['choices']  = array_fill_keys($choices, 0);
	$poll['can_vote'] = has_permission() && !in_array($poll['poll_id'], $my_votes);
	$poll['open']     = $poll['end_date'] > time();
	
	foreach($choices as $i => $choice) {
		if (isset($votes[$poll['poll_id']][$i])) {
			$poll['choices'][$choice] = $votes[$poll['poll_id']][$i];
		}
	}
}

include_template('pages/polls.php', compact('polls'));
