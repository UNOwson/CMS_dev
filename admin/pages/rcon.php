<?php has_permission('admin.rcon', true);

require ROOT_DIR . '/evo/misc/parse_minecraft_string.php';

$server = Db::Get('select * from {servers} where id = ?', $_GET['server']);

if (empty($_POST)) {
	$return = 'PLEASE TYPE A COMMAND';
} else {
	$command = empty($_POST['rcon']) ? 'version' : $_POST['rcon'];
	$host = empty($_POST['host']) ? $server['host'] : $_POST['host'];
	$port = empty($_POST['port']) ? $server['port'] : $_POST['port'];
	$password = empty($_POST['password']) ? $server['password'] : $_POST['password'];
	
	if ($command[0] === '/') {
		$command = substr($command, 1);
	}
	try
	{
		$rcon = new \Evo\RCON($host, $port, $password);
		$return =  nl2br(parse_minecraft_string($rcon->command( $command )));
	}
	catch( Exception $e )
	{
		$return = $e->getMessage( );
	}
	die($return);
}
?>


<div class="pull-right">
	Host: <input type="text" id="rcon_host" value="<?= $server['host']; ?>"> Port: <input type="text" id="rcon_port" value="<?= $server['rcon_port']; ?>"> Password: <input type="text" value="<?= $server['rcon_password']; ?>" id="rcon_password"> 
</div>

<legend>RCON Console: <?php echo $server['name']?></legend>

<div style="font-family: Consolas;color:white; width: 100%; height: 500px; padding: 0px;">
	<div id="console" style="color:white;background-color:rgba(0,0,0,0.65); height: 470px; width: 100%;overflow-y: auto;">
		<?php echo $return ?>
	</div>
	<input id="rcon_command" list="autocomplete" type="text" style="font-family: Consolas;font-size:110%; color:white;border: none;width: 100%; height: 30px; background: rgba(0,0,0,0.7);"> 
</div>
<datalist id="autocomplete">
	<?php
	$obj = array();
	if (file_exists('bukkit.txt')) {
		foreach(file('bukkit.txt') as $line) {
			$e = explode(': ', $line, 2);
			echo '<option value="/' .$e[0].' " label="'.html_encode('/'.$e[0].' '.$e[1]).'">';
		}
	}
	?>
</datalist>
<script>
	 $('#rcon_command').inputHistory(function(value) {	 
		$('#console').html($('#console').html() + '<br><strong>&gt; ' + value + '</strong>');
		$.post('', {rcon: value, csrf: csrf, host: $('#rcon_host').val(), port: $('#rcon_port').val(), password: $('#rcon_password').val()}, function(data){$('#console').html($('#console').html() + '<br>' + data).animate({ scrollTop: $('#console')[0].scrollHeight}, 800); });
	 });
	 
</script>