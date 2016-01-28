<?php defined('EVO') or die(''.__('403.msg').'');

echo  '<?xml version="1.0" encoding="UTF-8"?>'.
		'<?xml-stylesheet type="text/css" href="' . get_asset('css/style.css') . '" ?>'.
		'<rss version="2.0"
			xmlns:content="http://purl.org/rss/1.0/modules/content/"
			xmlns:wfw="http://wellformedweb.org/CommentAPI/"
			xmlns:dc="http://purl.org/dc/elements/1.1/"
			xmlns:atom="http://www.w3.org/2005/Atom"
			xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
			xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
			>'.
			'<channel>'.
				'<title>' . Site('name') . '</title>'.
				'<link>' . Site('url') . '</link>'.
				'<description>' . Site('description') . '</description>'.
				'<lastBuildDate>' . date('r') . '</lastBuildDate>'.
				'<sy:updatePeriod>hourly</sy:updatePeriod>'.
				'<sy:updateFrequency>1</sy:updateFrequency>'.
				'<generator>Evo-CMS</generator>';
	

$sql = 'SELECT page.*, rev.*, a.username
		FROM {pages} AS page
		JOIN {pages_revs} as rev ON rev.page_id = page.page_id AND page.pub_rev = rev.revision
		JOIN {users} as a ON a.id = rev.author
		WHERE page.pub_date < UNIX_TIMESTAMP()
		ORDER BY page.pub_date DESC LIMIT 0, 20';
 
foreach(Db::QueryAll($sql) as $page)
{
	echo '<item>'.
			'<title>' . $page['title'] . '</title>'.
			'<link>' . Site('url') . '/?' . ($page['slug'] ?: $page['id']) . '</link>'.
			'<pubDate>' . date('r', $page['pub_date']) . '</pubDate>'.
			'<lastBuildDate>' . date('r', $page['posted']) . '</lastBuildDate>'.
			'<dc:creator><![CDATA[' . $page['username'] . ']]></dc:creator>'.
			
			'<guid isPermaLink="false">' . create_url($page['id']) . '</guid>'.
			'<description>' . html_encode('<div class="article message">'.rewrite_links($page['content'], 1).'</div>') . '</description>'.
			'<content:encoded>' . html_encode('<div class="article message">'.rewrite_links($page['content'], 1).'</div>') . '</content:encoded>'.
		 '</item>';
}

echo	'</channel>'.
	'</rss>';

exit;