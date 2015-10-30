<?php
header('content-type: text/plain');
set_time_limit(0);

include '../evo/common.php';
return;
echo "THIS TOOL SHOULD BE RUN ON A CLEAN INSTALLATION\nWARNING, THIS WILL CLEAR YOUR PAGES, COMMENTS, AND MENU\n\n";

$wp_prefix = 'alex_wp.wp_';
$wp_url = 'http://blog.alexou.net/';
Db::Exec('update {pages} as p set comments = (select count(*) from {comments} as c where c.page_id = p.page_id)');

echo "I will now truncate tables\n";

Db::Exec('Truncate {menu}');
Db::Exec('Truncate {pages');
Db::Exec('Truncate {pages_revs}');
Db::Exec('Truncate {comments}');

$posts = Db::QueryAll('select * from ' . $wp_prefix . 'posts where post_type <> "revision"');

$files_map = array();

foreach($posts as $post)
{
	if ($post['post_type'] != 'attachment') continue;
	
	if ($fp = fopen($post['guid'], 'r')) {
		$tmp_file = tempnam(sys_get_temp_dir(), 'evocms');
		$tfd = fopen($tmp_file, 'w');
		while($c = fread($fp, 8092)) {
			fwrite($tfd, $c);
		}
		fclose($tfd);
		fclose($fp);
		
		if ($file = attach_file($tmp_file, basename($post['guid']), null, $post['post_title'])) {
			$files_map['@'.$post['guid'].'@'] = '/?p=getfile&id=' . $file[2] . '/' . $file[0];
			$files_map['@'.preg_replace('/(\.[a-z]+)$/', '-([0-9]+x[0-9]+)$1', $post['guid']).'@'] = '/?p=getfile&thumb=$1&id=' . $file[2] . '/' . $file[0];
			echo "Imported file {$post['guid']}\n";
		}
	}
}


$posts_map = array(0 => 0);

foreach ($posts as $post)
{
	if ($post['post_type'] != 'page' && $post['post_type'] != 'post') continue;
	
	if ($post['post_type'] == 'post') $post['post_type'] = 'article';
	
	$post['post_content'] = preg_replace(array_keys($files_map), array_values($files_map), $post['post_content']);
	$post['post_content'] = str_replace($wp_url, Site('url') . '/', $post['post_content']);
	$post['post_content'] = wpautop($post['post_content']);
	
	Db::Exec('INSERT INTO {pages} (revisions, pub_rev, slug, pub_date, type, allow_comments) 
				 VALUES(1, 1, ?, ?, ?, 1)',
				 $post['post_name'],
				 strtotime($post['post_date']),
				 $post['post_type']
			);
	
	$posts_map[$post['ID']] = Db::$insert_id;
	
	Db::Exec('INSERT INTO {pages_revs} (posted, page_id, revision, author, slug, title, content, status)
				 VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
				 strtotime($post['post_date']),
				 Db::$insert_id,
				 1,
				 1,
				 $post['post_name'],
				 $post['post_title'],
				 $post['post_content'],
				 'published'
			);
		
	echo "Imported post: {$post['post_title']}\n";
}



foreach ($posts as $post)
{
	if ($post['post_type'] != 'page') continue;
	
	Db::Exec('INSERT INTO {menu} (id, parent, priority, name, link) 
				 VALUES(?, ?, ?, ?, ?)',
				$posts_map[$post['ID']],
				$posts_map[$post['post_parent']],
				$post['menu_order'],
				$post['post_title'],
				$posts_map[$post['ID']]
		);
	echo "Imported menu item: {$post['post_title']}\n";
}


$comments = Db::QueryAll('select * from '.$wp_prefix.'comments where comment_approved = 1');
$i = 0;
foreach($comments as $comment)
{
	if (isset($posts_map[$comment['comment_post_ID']])) {
	$i += Db::Exec('INSERT INTO {comments} (state, user_id, page_id, message, posted, user_ip, user_name, user_email)
						VALUES(0,0,?,?,?,?,?,?)',
						$posts_map[$comment['comment_post_ID']],
						$comment['comment_content'],
						strtotime($comment['comment_date']),
						$comment['comment_author_IP'],
						$comment['comment_author'],
						$comment['comment_author_email']
						);
	}
}

Db::Exec('update {pages} as p set comments = (select count(*) from {comments} as c where c.page_id = p.page_id)');
echo "Imported : {$i} comments\n";


























/**
 * Replaces double line-breaks with paragraph elements.
 *
 * A group of regex replaces used to identify text formatted with newlines and
 * replace double line-breaks with HTML paragraph tags. The remaining
 * line-breaks after conversion become <<br />> tags, unless $br is set to '0'
 * or 'false'.
 *
 * @since 0.71
 *
 * @param string $pee The text which has to be formatted.
 * @param bool $br Optional. If set, this will convert all remaining line-breaks after paragraphing. Default true.
 * @return string Text which has been converted into correct paragraph tags.
 */
function wpautop($pee, $br = true) {
	$pre_tags = array();

	if ( trim($pee) === '' )
		return '';

	$pee = $pee . "\n"; // just to make things a little easier, pad the end

	if ( strpos($pee, '<pre') !== false ) {
		$pee_parts = explode( '</pre>', $pee );
		$last_pee = array_pop($pee_parts);
		$pee = '';
		$i = 0;

		foreach ( $pee_parts as $pee_part ) {
			$start = strpos($pee_part, '<pre');

			// Malformed html?
			if ( $start === false ) {
				$pee .= $pee_part;
				continue;
			}

			$name = "<pre wp-pre-tag-$i></pre>";
			$pre_tags[$name] = substr( $pee_part, $start ) . '</pre>';

			$pee .= substr( $pee_part, 0, $start ) . $name;
			$i++;
		}

		$pee .= $last_pee;
	}

	$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
	// Space things out a little
	$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|noscript|legend|section|article|aside|hgroup|header|footer|nav|figure|details|menu|summary)';
	$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
	$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
	$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines

	if ( strpos( $pee, '</object>' ) !== false ) {
		// no P/BR around param and embed
		$pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
		$pee = preg_replace( '|\s*</object>|', '</object>', $pee );
		$pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
	}

	if ( strpos( $pee, '<source' ) !== false || strpos( $pee, '<track' ) !== false ) {
		// no P/BR around source and track
		$pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
		$pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
		$pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
	}

	$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
	// make paragraphs, including one at the end
	$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
	$pee = '';

	foreach ( $pees as $tinkle ) {
		$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
	}

	$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
	$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
	$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
	$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
	$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

	if ( $br ) {
		$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
		$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
	}

	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
	$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
	$pee = preg_replace( "|\n</p>$|", '</p>', $pee );

	if ( !empty($pre_tags) )
		$pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);

	return $pee;
}
