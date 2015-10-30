<?php
/* 
 * HTML Sanitizer: it will close unclosed tags, remove unwanted tags, filter attributes, filter css...
 * 
 * Copyright (c) 2013, Alex Duchesne <alex@alexou.net>
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */
 

function sanitize_html($html)
{
	//striptags is buggy	...
	$allowed_attr = array('src', 'href', 'style', 'title', 'alt', 'width', 'height', 'class', 'controls', 'size');
	$allowed_tags = array('a', 'b', 'i', 'h1', 'h2', 'h3', 'h4', 'span', 'font', 'p', 'img', 'small', 'strong', 'caption', 'tbody', 'li', 'ul', 'ol', 'table', 'thead', 'th', 'tr', 'td', 'br', 'iframe', 'video', 'audio', 'source', 'blockquote', 'code', 'div', 'hr');
	$no_closing_tags = array('br', 'img', 'hr');
	$disallowed_css = array('position', 'clear', 'content');
	$validate = array (
							'iframe' => array('src' =>'https?://(www\.)?(youtube.com|player.vimeo.com|dailymotion.com)/.*'),
							'a' => array('href' =>'(https?://|/|mailto:|\?).*'),
							);
							
	$html = preg_replace('#(<[^>]+)<#mui', '$1><', $html); // Close unclosed tags
	
	$parts = preg_split('~(</?[^>]+>)~', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	
	$open = array();
	
	foreach($parts as &$part)
	{
		if (preg_match('#<(/?)([a-z]+)[\s>]#iu', strtolower($part), $m))
		{
			if (!in_array($m[2], $allowed_tags))
			{
				$part = '';
			}
			elseif ($m[1] == '/')
			{
				if (end($open) == $m[2]) 
				{
					array_pop($open);
				}
				else
				{
					if ($open) {
						$part = '</'.array_pop($open).'>';
					}
					else
					{
						$part = '';
					}
				}
			}
			else
			{
				//wont support escaped sequences, but it should be good enough for what the wysiwyg produces...
				$new = '<' . $m[2];
				
				if (preg_match_all('#[\s\'\"]([a-z]+)=?(("[^"]*")|(\'[^\']*\')|[ >])#miu', $part, $p))
				{
					foreach($p[1] as $i => $attr) 
					{
						if (in_array($attr, $allowed_attr)) 
						{
							$value = stripslashes(substr($p[2][$i], 1, -1));
							if ($attr == 'style') 
							{
								foreach($disallowed_css as $css) 
								{
									$value = preg_replace('#'.$css.'\s*:\s*[^;]+;?#mis', '', $value);
								}
							} 
							elseif (isset($validate[$m[2]][$attr]) && !preg_match('#^'.$validate[$m[2]][$attr].'$#mui', $value)) 
							{
								$value = '';
							}
							$new .= ' ' . $attr . '="' . addslashes($value) . '"';
						}
					}
				}
				
				$part = $new . '>';
				
				if (!in_array($m[2], $no_closing_tags))
				{
					$open[] = $m[2];
				}
			}
		}
	}
	$html = trim(implode($parts));
	foreach(array_reverse($open) as $tag) $html .= '</'.$tag.'>';
	
	do { $html  = preg_replace('#<br ?/?>\s*(</[a-z]>)?\s*$#', '$1', $html, -1, $count);} while ($count);

	return $html;
}
