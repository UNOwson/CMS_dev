<?php 
/* 
 * BBCode parser
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */

class BBCode 

	public static function Parse($bbcode, $encode_html = true)
	{
		$bbcodes = [
				'b' => '<strong>$1</strong>',
				'u' => '<span style="text-decoration:underline;">$1</span>',
				's' => '<span style="text-decoration:line-through;">$1</span>',
				'i' => '<i>$1</i>',
				'h' => '<h4>$1</h4>',
				
				'sub' => '<sub>$1</sub>',
				'sub' => '<sup>$1</sup>',
				
				'font=([-a-z\s,\']+)' => '<span style="font-family:$1">$2</span>',
				'color=(#?[a-z0-9]+)' => '<span style="color:$1">$2</span>',
				'size=([0-9]+)' => '<font size="$1">$2</font>', //We should map font size to span sizes instead small/xxlarge,etc
				
				'justify' => '<div style="text-align:justify;">$1</div>',
				'center' => '<div style="text-align:center;">$1</div>',
				'left' => '<div style="text-align:left;">$1</div>',
				'right' => '<div style="text-align:right;">$1</div>',
				
				'youtube' => '<iframe src="http://www.youtube.com/embed/$1" width="600px" height="360px"></iframe>',
				
				'spoiler' => '<div class="spoiler"><a style="cursor:pointer">Afficher le Spoiler</a><div>$1</div></div>',
				'spoiler=([^\]]+)?' => '<div class="spoiler"><a style="cursor:pointer">$1</a><div>$2</div></div>',
				
				'quote' => '<blockquote>$1</blockquote>',
				'quote=([-a-z0-9_]+)' => '<blockquote>$1 a dit:<br>$2</blockquote>',
				"quote='([-a-z0-9_]+)' pid='([0-9]+)' dateline='([0-9]+)'" => '<blockquote><a href="'.create_url('forums', ['pid'=>'$2']).'">$1 a dit</a>:<br>$4</blockquote>',
				
				'code' => '<pre><code>$1</code></pre>',
				
				'img' => '<img src="$1">',
				'img=([0-9]+)x([0-9]+)' => '<img width="$1" height="$2" src="$3">',
				
				'poll' => '<div id="poll"></div><script>$.get("'.create_url('poll', '$1').'", function(data) { $("#poll").html(data); })</script>',
				
				'url=((https?://|irc://|\?|\/)[^"\'\]]+)' => '<a href="$1">$3</a>',
				'url' => '<a href="$1">$1</a>',
				
				'list=\*' => '<ul>$1</ul>',
				'list' => '<ol>$1</ol>',
				
				'\*' => '<li>',
				'/\*' => '</li>',

				'ul' => '<ul>$1</ul>',
				'ol' => '<ol>$1</ol>',
				'li' => '<li>$1</li>',
				
				'table' => '<table>$1</table>',
				'tr' => '<tr>$1</tr>',
				'td' => '<td>$1</td>',
				
				'hr' => '<hr>',
				];
		
		$filters = [
				'url' => '(https?://|irc://|\?|\/)',
				];
						
		$block = 'right|left|center|justify|h|youtube|spoiler|quote|hr|\*';
		$notext = 'list|ul|ol|li|table|tr|td|code';
		
		foreach($bbcodes as $bb => $html) {
			$code = explode('=', $bb, 2);
			$codes[] = $code[0];
			if (strpos($html, '$') !== false) {
				$regexes[] = '!\['.$bb.'\]('.(isset($filters[$bb]) ? $filters[$bb] : '').'[^\]]*)\[/'.$code[0].'\]!msui';
			} else {
				$regexes[] = '!\['.$bb.'\]!msui';
			}
			$replacements[] = $html;
		}
		
		if ($encode_html)
			$bbcode = html_encode($bbcode);
		
		$bbcode = preg_replace('@\[(/?(' . $block . ")(=[^\]]+)?)\][\r ]*\n@musi", '[$1]', $bbcode);
		$bbcode = preg_replace('@\s*\[(/?(' . $notext . ")(=[^\]]+)?)\]\s*@mui", '[$1]', $bbcode);
		$bbcode = preg_replace('@(?!\[/?('.implode('|', $codes).')(=[^\]]+)?\])(\[([^\[\]]+)\])@msiu', '&#91;$4&#93;', $bbcode);
		$bbcode = preg_replace('@(?!\[/?('.implode('|', $codes).')(=[^\]]+)?\])(\[([^\[\]]+)\])@msiu', '&#91;$4&#93;', $bbcode);

		do {
			$bbcode = preg_replace($regexes, $replacements, $bbcode, -1, $count);
		} while($count != 0);
		
		
		$blocks = preg_split('!(</?pre>)!m', $bbcode, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		foreach($blocks as &$block) {
			if (strpos($block, '<code>') === false) {
				$block = emoticons(nl2br($block, false));
			}
		}
		
		return implode($blocks);
	}
}