<?php defined('EVO') or die('Que fais-tu là?');

$num_art = (int) Db::Get('select count(*) from {pages} where `type` = "article" AND pub_rev > 0');

if (!$num_art) {
	return print '<div style="text-align: center;margin:20px;" class="alert alert-warning">Aucun article à afficher !</div>';
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