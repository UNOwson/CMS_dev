<?php has_permission('user.upload', true);

$mod_view = defined('EVO_ADMIN');

$view = isset($_SESSION['gallery-view']) ? $_SESSION['gallery-view'] : 'grid';
$view = $_SESSION['gallery-view'] = _GET('view', $view) === 'list' ? 'list' : 'grid';

$where = 'type like ? ';
$where_e = ['%'];

$poster = '';

if (_GP('filter')) {
	$where.= 'and (name like ? or path like ? or username like ?)';
	$where_e[] = $filter = '%' . _GP('filter') . '%';
	$where_e[] = $filter = '%' . _GP('filter') . '%';
	$where_e[] = $filter = '%' . _GP('filter') . '%';
}

if (!$mod_view) {
	$poster = ' and poster = ' . $user_session['id'];
	$where .= ' and f.poster = ?';
	$where_e[] = $user_session['id'];
}

if (isset($_FILES['ajaxup'])) {
	if ($file = upload_fichier('ajaxup', null, null, false, $mod_view ? '' : 'user/'.$user_session['username'])) {
		ob_end_clean();
		die(json_encode(array($file[3], $file[0], $file[1], filesize(ROOT_DIR.$file[3]))));
	}
	die($_warning);
}

if (!empty($_POST['delete'])) {
	foreach((array)$_POST['delete'] as $fileID) {
		if (Db::Get('select path, thumbs from {files} where id = ? ' . $poster, $fileID)) {
			delete_file((int)$fileID);
		}
	}
}

if (!empty($_POST['caption'])) {
	foreach($_POST['caption'] as $fileID => $newCaption) {
		Db::Exec('update {files} set caption = ? where id = ? ' . $poster, $newCaption, $fileID);
	}
}

$files = Db::QueryAll('select f.*, u.username from {files} as f join {users} as u on u.id = f.poster where ' . $where . ' order by id desc', $where_e);
	
?>
<div class="pull-left btn-group">
	<a data-gallery-view-switch="grid" class="btn btn-default" href="<?= create_url('gallery', ['view'=>'list']); ?>"><i class="fa fa-th" style="font-size: 14px;"></i></a>
	<a data-gallery-view-switch="list" class="btn btn-default" href="<?= create_url('gallery', ['view'=>'list']); ?>"><i class="fa fa-list" style="font-size: 14px;"></i></a>
	<button id="search" class="btn btn-default"><i class="fa fa-search" style="font-size: 14px;"></i></button>
</div>
<div class="pull-right form-inline gallery-controls">
	<button id="insertgal" class="btn btn-default hide"><?= __('gallery.in_gall') ?></button>
	<button id="insertfile" class="btn btn-default hide"><?= __('gallery.in_file') ?></button>
	<button id="insertthumb" class="btn btn-default hide"><?= __('gallery.in_mini') ?></button>
	<button id="uploadfile" class="btn btn-info"><i class="fa fa-upload"></i> <?= __('gallery.btn_upload') ?></button>
	<button id="deletefiles" class="btn  btn-danger hide"><i class="fa fa-times"></i> <?= __('gallery.btn_delete') ?></button>
	<button id="cancelfancy" class="btn  btn-danger"><?= __('gallery.btn_cancel') ?></button>
	<select id="gallery-thumbsize" class="form-control">
		<option value="150x150">Cropped Small (150px)</option>
		<option value="480x480">Cropped Medium (480px)</option>
		<option value="150">Scaled Small (150px)</option>
		<option value="480">Scaled Medium (480px)</option>
		<option value="0">Full Size</option>
	</select>
</div>
<div class="clearfix"></div>
<br>
<input id="filter" name="filter" type="text" class="form-control hide" value="" placeholder="Recherche...">
<form method="post" enctype="multipart/form-data">
	<div id="gallery-content" class="gallery">
	<?php 
		if ($view === 'list') {
			echo '<table class="table table-lists">';
			foreach($files as $file) {
				$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
				$icon = get_asset('/img/filetypes/'.$ext.'.png') ?:
						get_asset('/img/filetypes/'.$file['type'].'.png') ?:
						get_asset('/img/filetypes/'.explode('/', $file['mime_type'])[0].'.png') ?:
						get_asset('/img/filetypes/blank.png');
				
				echo '<tr>';
					if ($mod_view) echo '<td>'.$file['id'].'</td>';
					echo '<td><img title="'.$file['type'].' '.$file['mime_type'].'" src="'.$icon.'"></td>';
					echo '<td><a href="'.site('url').html_encode($file['path']).'">'.html_encode(short($file['name'], 60)).'</a></td>';
					echo '<td><a href="'.create_url('getfile', $file['id'].'/'.$file['name']).'" class="nofancy">&darr;</a></td>';
					echo '<td>'.today($file['posted']).'</td>';
					if ($mod_view) {
						echo '<td>'.html_encode($file['type']).'</td>';
						echo '<td>'.html_encode($file['origin']).'</td>';
						echo '<td>'.html_encode($file['username']).'</td>';
						echo '<td>'.$file['hits'].'</td>';
					}
					echo '<td>'.mk_human_unit($file['size']).'</td>';
					echo '<td><button onclick="return confirm(\'Sur?\');" name="delete" value="'.$file['id'].'" class="btn btn-xs btn-danger"><i class="fa fa-times"></i></button></td>';
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo '<div class="gallery-editor">';
			foreach($files as $file) {
				echo '<div class="gallery-container" data-id="' . $file['id'] . '" data-type="' . $file['type'] . '" data-mimetype="' . $file['mime_type'] . '" data-size="' . $file['size'] . '" data-caption="'.html_encode($file['caption'] ?: $file['name']).'"  data-href="?p='.urlencode($file['path']).'">';
				if (strpos($file['mime_type'], 'image') === 0) {
					echo '<img src="'.create_url($file['path'],['size'=>'150x150']).'">';
				} else {
					if ($file['type'] == 'file') {
						$class = 'fa-file-o';
					} else {
						$class = 'fa-file-'.str_replace(array('avatar', 'logo'), 'image', $file['type']) . '-o';
					}
					echo '<br><i class="fa fa-5x '.$class.'"></i><br>' . ucfirst($file['type']) . ' ' . mk_human_unit($file['size']);
				}
				echo '<br><input name="caption['.$file['id'].']" style="text-align:center;border:none" type="text" value="'. html_encode($file['caption'] ?: $file['name']) . '">';
				echo '</div>';
			}
			echo '</div>';
		}
	?>
	</div>
</form>
<script>
	var gallery_id = Math.random().toString(36).slice(2);
	var gallery_pos = 0;
	var gallery_insert_mode = $('textarea').length > 0;	
	var view_mode = '<?= $view ?>';
	var gallery_insert_gallery = (typeof find_html_editor == 'function' && find_html_editor() != false);

	$('.gallery').on('dblclick', '.gallery-container', function() {
		var e = $(this);
		$.fancybox({
					type: e.children('img').length != 0 ? 'image' : 'iframe', 
					href: e.attr('data-href'),
					title: '<a target="blank" href="' + e.attr('data-href') + '">' + e.attr('data-href') + '</a> &nbsp; ' + mk_human_unit(e.attr('data-size')),
					afterClose: function() {
							$('#creategallery').click();
						}
					});
	});
	
	$('.gallery').on('keyup', '.gallery-container > input', function(e) {
		if (e.keyCode == 13) {
			$(this).blur();
		}
	});
	
	$('.gallery').on('change', '.gallery-container > input', function() {
		var post = {'csrf': csrf};
		post[$(this).attr('name')] = $(this).val();
		
		$.post('',  post);
	});
	
	
	$('.gallery').on('click', '.gallery-container', function() {
		if ($(this).hasClass('active')) {
			$(this).removeClass('active');
		} else {
			$(this).addClass('active');
			$(this).attr('data-pos', gallery_pos++);
		}
		
		if ($('.gallery .active').length == 0) {
			$('#insertgal, #insertfile, #insertthumb, #deletefiles').addClass('hide');
		} else if (!gallery_insert_mode) {
			$('#deletefiles').removeClass('hide');
		} else if ($('.gallery .active').length > 1) {
			// if (gallery_insert_gallery) {
				$('#insertgal').removeClass('hide');
			// }
			$('#deletefiles').removeClass('hide');
			$('#insertfile, #insertthumb').addClass('hide');
		} else {
			$('#insertfile, #insertthumb, #deletefiles').removeClass('hide');
			$('#insertgal').addClass('hide');
		}
	});



	$('#deletefiles').click(function() {
		var files = [];
		var captions = [];
			$('.gallery .active').each(function() {
				files.push($(this).attr('data-id'));
				captions.push($(this).attr('data-caption'));
			});
		
		if (confirm('Êtes-vous certain de vouloir supprimer ces fichiers ? \n' + captions.join("\n"))) {
			$('#gallery-content').load('?page=gallery&p=gallery #gallery-content  > *', {'delete[]': files, csrf:csrf}, function() {
				if ($.fancybox.isOpen) {
					$.fancybox.close();
					$('#creategallery').click();
				}
			});
		}
	});



	$('#insertfile').click(function() {
		var e = $('.gallery .active').first();
		if (e.length != 0) {
			insertfile(e.attr('data-href'), e.attr('data-caption'), e.attr('data-type'), e.attr('data-size'));
		}
		$.fancybox.close();
	});

	$('#insertthumb').click(function() {
		var e = $('.gallery .active').first();
		if (e.length != 0 && e.attr('data-type') == 'image') {
			insertfile(e.attr('data-href'), e.attr('data-caption'), 'thumb', e.attr('data-size'), $('#gallery-thumbsize').val());
		}
		$.fancybox.close();
	});
	
	$('#insertgal').click(function() {
		var gallery = [], images = [];
		var html_editor = find_html_editor();
		var bbcode_editor = find_bbcode_editor();
		
		$('.gallery .active').sort(function (a, b) {
			var contentA = parseInt( $(a).attr('data-pos'));
			var contentB = parseInt( $(b).attr('data-pos'));
			return (contentA < contentB) ? -1 : (contentA > contentB) ? 1 : 0;
		}).each(function() {
			var e = $(this);
			if ($(this).attr('data-type') == 'image') {
				if (bbcode_editor) {
					gallery.push('[url=' + e.attr('data-href') + '][img]' + e.attr('data-href') + '&size=' + $('#gallery-thumbsize').val() + '[/img][/url] ');
				} else {
					images.push(e.attr('data-id'));
					gallery.push('<span class="gallery-container"><a rel="' + gallery_id + '" href="' + e.attr('data-href') + '"><img src="' + e.attr('data-href') + '&size=' + $('#gallery-thumbsize').val() + '" alt="' + e.attr('data-caption') + '" title="' + e.attr('data-caption') + '"></a></span>');
				}
			}
		});
		
		if (gallery.length > 0) {
			if (bbcode_editor) {
				bbcode_editor(gallery.join(' '));
			} else if (html_editor) {
				html_editor('<div class="gallery" images="' + images + '" contenteditable="false">' + gallery.join('') + '</div>');
			} else {
				alert('Votre éditeur ne supporte pas les galleries pour le moment.');
				return;
			}
		}
		
		$.fancybox.close();
	});

	$('#cancelfancy').click(function() {
		$.fancybox.close();
	});

	$('#uploadfile').click(function() {
		if ($.fancybox.isOpen) {
			$.fancybox.close();
			ajaxupload(function() {
				$('#creategallery').click();
			});
		} else {
			ajaxupload(function() {
				$('#gallery-content').load('?page=gallery&p=gallery&view=' + view_mode + ' #gallery-content > *')
			});
		}
		return false;
	});

	$('#search').click(function() {
		if (gallery_insert_mode) {
			alert('La recherche ne fonctionne pas encore en mode fancybox!');
			return false;
		}
		$('#filter').toggleClass('hide').focus();
		$(this).toggleClass('active');
	});
	
	$('[data-gallery-view-switch]').click(function(){
		view_mode = $(this).attr('data-gallery-view-switch');
		var url = '?page=gallery&p=gallery&view=' + view_mode;
		$('#gallery-content').load(url + ' #gallery-content > *');
		if (!$.fancybox.isOpen) {
			history.replaceState(null, null, url);
		}
		if (view_mode == 'list') {
			$('.gallery-controls button:not(#uploadfile)').addClass('hide');
		}
		$('[data-gallery-view-switch]').removeClass('active');
		$(this).addClass('active');
		return false;
	});
	
	$('[data-gallery-view-switch='+view_mode+']').addClass('active');
	
	
	if (!gallery_insert_mode) {
		$('#cancelfancy, #gallery-thumbsize').addClass('hide');
	}
</script>