<table class="table table-hover">
	<thead>
        <tr>
			<th></th>
          	<th><?= __('tpolls.name')?></th>
          	<th><?= __('tpolls.end')?></th>
        	<th></th>
    	</tr>
    </thead>
	<tbody>
	<?php foreach($polls as $poll) { ?>
        <tr>
			<?php if ($poll['open']) { ?>
				<td><i class="fa fa-clock-o"></i></td>
			<?php } else { ?>
				<td><i class="fa fa-pie-chart"></i></td>
			<?php } ?>
          	<td><a href="<?=create_url('poll', $poll['poll_id'])?>"><?=html_encode($poll['name'])?></a></td>
          	<td><?=today($poll['end_date'])?></td>
			<?php if ($poll['can_vote'] && $poll['open']) { ?>
				<td><i class="fa fa-check-square-o"></i></td>
			<?php } else { ?>
				<td><i class="fa fa-eye"></i></td>
			<?php } ?>
    	</tr>
	<?php } ?>
	</tbody>
</table>