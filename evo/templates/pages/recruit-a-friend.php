<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title"><?= __('raf.recruit') ?></h3>
	</div>
	<div class="panel-body">
		<form class="form-horizontal form-group">
			<label class="col-sm-2 control-label"><?= __('raf.field_link') ?></label>
			<div class="col-sm-10">
				<input class="form-control" value="<?= html_encode($raf_url) ?>" onclick="this.setSelectionRange(0, this.value.length)" readonly style="cursor: pointer;">
			</div>
		</form>
	</div>
</div>
<div class="panel-body">
	<form class="form-horizontal" method="post">
		<div class="form-group">
			<table class="table friend_table">
			<thead>
				<th></th>
				<th><?= __('raf.username') ?></th>
				<th><?= __('raf.email') ?></th>
				<th><?= __('raf.rank') ?></th>
			</thead>
				<tbody>
					<?php
						
						foreach($users as $data)
							{
							echo "<tr style='font-size: 13px;'>";
								echo '<td><a class="ico-' . ($data['activity'] > time() - 120 ? 'online" title="'.__('raf.online').'"' : 'offline" title="'.__('raf.offline').'"') . '></a></td>';
								echo "<td>".html_encode($data['username'])."</td>";
								echo "<td>".html_encode($data['email'])."</td>";
								echo "<td><span style='color:{$data['color']};'>".$data['gname']."</span></td>";
							echo '</tr>';
							}
					?>
				</tbody>
			</table>
		</div>
	</form>
</div>