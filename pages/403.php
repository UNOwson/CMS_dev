<?php
defined('EVO') or die(''.__('403.msg').'');
header('HTTP/1.1 403 Denied');
include_template('403.php');
?>