<?php
namespace Plugins;

class Radio extends \Plugins
{
	const AUTHOR 		= 'alex';
	const NAME 			= 'Radio';
	const DESCRIPTION 	= 'Radio';
	const VERSION 		= '0.1';
	
	public static function init()
	{
		parent::hook('ajax', function($action)  {
			switch(_GP('action')) {
				case 'radio_nowplaying':
					PlayerWidgetContent(_GET('id'));
					break;
				case 'radio_playlist':
					return;
					PlaylistWidget(_GET('id'));
					break;
			}
		});
	}

	public static function HttpContext()
	{
		$opts = array(
		'http'=>array(
		"method" => "GET",
		"header" => "Accept-language: en\r\n" .
		"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13)\r\n".
		"HTTP_X_REQUESTED_WITH: xmlhttprequest\r\n"
		)
		);
		
		return stream_context_create($opts);		
	}


	public static function LookupSong($title) {
		if ($buffer = file_get_contents('http://www.discogs.com/search/ac?q='.urlencode($title).'&type=a_m_r_13', 0, HttpContext())) {
			$json = json_decode($buffer);
			return reset($json);
		}
		return null;
	}

	public static function PlayerWidgetContent($server_id = null, $cache_only = false)
	{
		$cached_file = sys_get_temp_dir().'/radiocache_'.$server_id;
		
		if (file_exists($cached_file) && filemtime($cached_file) > (time() - 15)) {
			$server = unserialize(file_get_contents($cached_file));
		} elseif($cache_only) {
			echo '<div class="albumArt"></div><span class="title">Chargement...</span>';
			return; 
		} else {
			try {
				$server = queryServer($server_id ? array('id' => $server_id) : array());
				file_put_contents($cached_file, serialize($server));
			} catch (\Exception $e) {
				echo 'La radio n\'est pas en diffusion pour le moment! ';
				return;
			}
		}
		
		$cached_meta_file = sys_get_temp_dir().'/radiocache_meta_'.md5($server->query['songTitle']);
		
		if (file_exists($cached_meta_file) && filemtime($cached_meta_file) > (time() - 86400)) {
			$meta = unserialize(file_get_contents($cached_meta_file));
		} elseif (!$cache_only && $meta = LookupSong($server->query['songTitle'])) {
			file_put_contents($cached_meta_file, serialize($meta));
		}
		
		echo '<div class="albumArt">';
		if (isset($meta->thumb)) {
			echo '<img src="'.$meta->thumb.'">';
		}
		echo '</div>';

		if (!empty($meta->title) && !empty($meta->artist)) {
			echo '<span class="artist">'.$meta->artist[0].'</span> '.
				 '<span class="title">'.$meta->title[0].'</span>';
		} else {
			echo '<span class="title">'.utf8_encode($server->query['songTitle']).'</span>';
		}
	}

	
	public static function PlayerWidget($server_id = null, $cache_only = false)
	{
		echo '<div class="radioplayer" id="radioplayer'.$server_id.'">';
		
		PlayerWidgetContent($server_id, $cache_only);
		
		echo '</div>
			  <script>
				function RadioPoll_'.$server_id.'() {
					$.get(site_url + "/?p=ajax&action=radio_nowplaying&id='.$server_id.'", function(data) {
						$("#radioplayer'.$server_id.'").html(data);
					});
				}
				setInterval(RadioPoll_'.$server_id.', 30000);
				RadioPoll_'.$server_id.'();
				</script>';
	}


	public static function PlaylistWidget($server_id = null, $cache_only = false)
	{
		if ($server = queryServer($server_id ? array('id' => $server_id) : array())) {
			$buffer = file_get_contents('http://'.$server->host.':'.$server->port.'/played.html', 0, HttpContext());
			if (preg_match_all('#<tr><td>(?<time>[0-9\:]+)</td><td>(?<title>.*?)(</td>|<td>|</tr>)(?<np><b>)?#', $buffer, $songs)) {
				echo '<table class="radio_playlist">';
				foreach($songs['time'] as $i => $time) {
					$title = str_replace('_', ' ', $songs['title'][$i]);
					
					echo $songs['np'][$i] ? '<tr class="song nowplaying">' : '<tr class="song">';
					echo '<td class="time">'.$time.'</td>'.
						 '<td class="title"><a href="https://www.google.ca/search?output=search&sclient=psy-ab&q='.urlencode($title.' site:youtube.com').'&btnI=">'.
						  utf8_encode($title).'</a></td>'.
						 '</tr>';
				}
				echo '</table>';
			}
		}
	}

}