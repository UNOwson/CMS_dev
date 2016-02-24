<?php defined('EVO') or die('Que fais-tu là?'); ?>

<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="613" height="285" id="dewplayer" align="middle">
    <param name="allowScriptAccess" value="sameDomain" />
    <param name="movie" value="../themes/WoW/dewslider.swf?&amp;transition=fade&amp;speed=15&amp;timer=5&amp;showbuttons=1&amp;showtitles=1" />
    <param name="quality" value="high" />
    <embed src="../themes/WoW/dewslider.swf?&amp;transition=fade&amp;speed=15&amp;timer=5&amp;showbuttons=1&amp;showtitles=1" quality="high" width="613" height="285" name="dewplayer" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
</object>

<div class="wow_home1">

<?php
$num_art = (int) Db::Get('select count(*) from {pages} where `type` = "article" AND pub_rev > 0');

if (!$num_art) {
	return print '<div style="text-align: center;margin:20px;" class="alert alert-warning">'.__('home.no_article').' !</div>';
}

$pn = isset($_GET['pn']) ? (int)$_GET['pn'] : 0;
$start = Site('articles_per_page') * ($pn-1);
$ptotal = ceil($num_art / Site('articles_per_page'));
$home = true;

if ($start > $num_art || $start < 0) {
	$pn = 1;
	$start = 0;
}

define('CACHE_PAGE', $pn > 1 ? 'page/' . $pn : '/');

echo '<div id="content">';


$pagebreaks = implode('|', array (
	'<div style="page-break-after: always"><span style="display: none;">&nbsp;</span></div>',
	'<hr[^>]*>',
));

foreach(get_pages(Site('articles_per_page'), $start) as $article)
{
	switch($article['format']) {
		case 'markdown':
			$md = new \Parsedown\ParsedownExtra;
			$article['content'] = $md->text($article['content']);
			break;
			
		case 'bbcode':
			$article['content'] = bbcode2html($article['content']);
			break;
			
		case 'text':
			$article['content'] = nl2br(emoticons($article['content']));
			break;
	}
	$article['content'] = rewrite_links($article['content'], true);
	$article['abstract'] = preg_match('`^(.*)('.$pagebreaks.')`ms', $article['content'], $m) ? $m[1] : false;
	$article['page_link'] = create_url($article['slug'] ?: $article['page_id']);
	$article['author_link'] = create_url('user', ['id' =>$article['author']]);
	
	include get_template('pages/page_article.php');
}

if ($ptotal > 1) {
	echo paginator($ptotal, $pn, 10, create_url('page/'));
} else 
	echo '<br>';

echo '</div>';
?>
</div>