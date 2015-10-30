<?php
namespace Plugins;

class yan extends \Plugins {
	const NAME 			= 'Yan Plugin';
	const VERSION 		= '0';
	const DESCRIPTION 	= 'Bouh!';
	const AUTHOR 		= 'alex';

	public static function init()
	{
		parent::hook('forum_before_forums_loop', function(&$forums) {
			echo '<style>.forum .num-posts, .forum .forum-forums thead td:nth-child(2), .forum .forum-forums thead td:nth-child(3) {display: none;}</style>';
			foreach($forums as &$c) {
				$total_posts = $total_topics = 0;
				foreach($c['forums'] as &$f) {
					$total_topics += $f['num_topics'];
					$total_posts += $f['num_posts'];
					$f['description'] = plural($f['num_topics'], 'discussion') . ' ' . plural($f['num_posts'], 'message');
				}
				$c['name'] .= ' <span class="label label-info pull-right">'. plural($total_topics, 'discussion') . ' ' . plural($total_posts, 'message') . '</span>';
			}
		});
	}
}