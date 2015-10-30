<?php
namespace Plugins;

class ChatBox extends \Plugins
{
	const AUTHOR 		= 'alex';
	const NAME 			= 'ChatBox';
	const DESCRIPTION 	= 'ChatBox';
	const VERSION 		= '0';
	
	protected static $settings = array(
		'a' 	=> array('type' => 'color', 'label' => 'Couleur'),
		'b' 	=> array('type' => 'image', 'label' => 'Image'),
		'c' 	=> array('type' => 'text',  'label' => 'say what?'),
		'd' 	=> array('type' => 'enum',  'label' => 'page', 'choices' => [1,2,3]),
		'e' 	=> array('type' => 'text',  'label' => 'aaa'),
		'f'		=> array('type' => 'bool',  'label' => 'aaa'),
	);
	
	public static function init()
	{
		parent::hook('ajax', function($action)  {
			switch(_GP('action')) {
				case 'chat_refresh':
					echo ChatLog(_GET('since')).'<script> '._GP('id').'_time = '.time().';</script>';
				break;
			}
		});
		parent::route('/chatbox', function($params) {
			die('<h1>Hello from chatbox plugin!</h1><pre>' . print_r($params, true));
		});
	}
	
	
	public static function ChatLog($since = 0)
	{
		if ($since == 0) {
			echo '<div class="commentaire" id="msg66"><div class="avatar"><img src="/assets//img/avatar.png" alt="avatar" class="avatar" height="85" width="85"></div><div class="cadre_message"><div class="auteur"><div class="pull-right date text-right"><small><a href="#msg66" style="color:inherit">2013-11-09</a><br></small><div class="flag btn-group"><button class="btn btn-xs btn-warning" name="com_censure" value="66" title=""><i class="fa fa-ban"></i></button><button class="btn btn-xs btn-danger" name="com_delete" value="66" title=""><i class="fa fa-times"></i></button></div></div><strong>God miché</strong><br><span style="color:;"></span></div><div class="comment">Attends.... Attends... Ça vient...</div></div></div>';
		} else {
			echo '<div class="commentaire" id="msg66"><div class="avatar"><img src="/assets//img/avatar.png" alt="avatar" class="avatar" height="85" width="85"></div><div class="cadre_message"><div class="auteur"><div class="pull-right date text-right"><small><a href="#msg66" style="color:inherit">2013-11-09</a><br></small><div class="flag btn-group"><button class="btn btn-xs btn-warning" name="com_censure" value="66" title=""><i class="fa fa-ban"></i></button><button class="btn btn-xs btn-danger" name="com_delete" value="66" title=""><i class="fa fa-times"></i></button></div></div><strong>God miché</strong><br><span style="color:;"></span></div><div class="comment">HOLLY NIGGER<br><img src="https://fkcd.ca/s5V.jpg"></div></div></div>';
		}
	}
	

	public static function Widget()
	{	
		$id = 'chatbox'.rand(0,100);
		return '<div class="chatbox" id="'.$id.'">'.ChatLog().'<div class="chatboxlog commentaires"></div><div class="chatboxinput"><input type="text"><button>Envoyer</button></div>'.
			   '<script>
					var '.$id.'_time = 0;
					setInterval(function() {
						$.get(site_url + "/?p=ajax&action=chat_refresh&id='.$id.'&since=" + '.$id.'_time, function(data) {
							$("#'.$id.' .chatboxlog").append(data);
						});
					}, 5000);
				</script>';
	}
}