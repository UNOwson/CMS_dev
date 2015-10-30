<?php require_once '../evo/boot.php'; header('Content-Type: text/javascript'); ?>
//<script>
$.ajaxSetup({cache: true });
function find_html_editor() {
	if (typeof CKEDITOR != 'undefined' && typeof CKEDITOR.instances.editor != 'undefined') {
		return function(content) { CKEDITOR.instances.editor.insertHtml(content); };
	} else if ($('textarea.markItUpEditor').length != 0) {
		return function(content) { $.markItUp({ target:'textarea.markItUpEditor', replaceWith: content});};
	}
	return false;
}

function find_bbcode_editor() {
	if (typeof $('textarea').sceditor != 'undefined') {
		return function(content) {$('textarea').sceditor('instance').insert(content);$('textarea').sceditor('instance').focus();};
	}
	return false;
}

function get_editor_content() {
	if (typeof $('textarea').editable != 'undefined') {
		return $('textarea').editable('getHTML', true, false);
	} else if (typeof CKEDITOR != 'undefined' && typeof CKEDITOR.instances.editor != 'undefined') {
		CKEDITOR.instances.editor.updateElement();
		return CKEDITOR.instances.editor.getData();
	} else if ($('textarea').length > 0) {
		return $('textarea').val();
	} else {
		return '';
	}
}

function insertfile(link, name, type, size, thumb) {
	var thumb = thumb || 150;

	if (typeof $('textarea').sceditor != 'undefined') {
		switch(type) {
			case 'image': content = ' [center][url=' + link + '][img]' + link + '[/img][/url][/center]\n[center][b]' + name + '[/b] (' + mk_human_unit(size) + ')[/center]\n'; break;
			case 'thumb': content = ' [center][url=' + link + '][img]' + link + '&size=' + thumb + '[/img][/url][/center]\n'; break;
			case 'audio': content = '[audio]' + link + ']\n[b]' + name + '[/b] (' + mk_human_unit(size) + ')'; break;
			case 'video': content = '[video]' + link + '[/video]\n[b]' + name + '[/b] (' + mk_human_unit(size) + ')'; break;
			default: content =  '[url=' + link + ']' + name + '[/url] (' + mk_human_unit(size) + ')';
		}
		$('textarea').sceditor('instance').insert(content);
		$('textarea').sceditor('instance').focus();
	} else if ($('textarea.markItUpEditor').length != 0) {
		switch(type) {
			case 'image': content = '!['+name+'](' + link + ')\n'; break;
			case 'thumb': content = '[!['+name+'](' + link + '&size='+thumb+')](' + link + ')\n'; break;
			case 'audio': content = '<div class="fichier"><audio controls src="' + link + '">'+ htmlencode(name) +'</audio><br><strong>' + htmlencode(name) + '</strong> (' + mk_human_unit(size) + ')</div>\n'; break;
			case 'video': content = '<div class="fichier"><video src="' + link + '" controls>'+ htmlencode(name) +'</video><br><strong>' + htmlencode(name) + '</strong> (' + mk_human_unit(size) + ')</div>\n'; break;
			default: content =  '[' + name + ' (' + mk_human_unit(size) + ')](' + link + ')\n';
		}
		$.markItUp({ target:'textarea.markItUpEditor', replaceWith: content});
	} else {
		var html_editor = find_html_editor();

		if (typeof html_editor == 'function') {
			switch(type) {
				case 'image': content = '<p><img src="' + link + '" alt="' + htmlencode(name) + '"><br><strong>' + htmlencode(name) + '</strong> (' + mk_human_unit(size) + ')</p>'; break;
				case 'thumb': content = '<a class="fancybox" href="' + link + '"><img src="' + link + '&size=' + thumb + '" alt="' + htmlencode(name) + '">'; break;
				case 'audio': content = '<div class="fichier"><audio controls src="' + link + '">'+ htmlencode(name) +'</audio><br><strong>' + htmlencode(name) + '</strong> (' + mk_human_unit(size) + ')</div><br>'; break;
				case 'video': content = '<div class="fichier"><video src="' + link + '" controls>'+ htmlencode(name) +'</video><br><strong>' + htmlencode(name) + '</strong> (' + mk_human_unit(size) + ')</div><br>'; break;
				default: content =  '<div class="fichier"><a href="' + link + '">' + htmlencode(name) + '</a> (' + mk_human_unit(size) + ')</div><br>';
			}
			html_editor(content);
		}
	}
}

document.onkeydown = function(e) {
    if (e.ctrlKey && e.keyCode === 83) {
        $('textarea').parents('form').submit();
        return false;
    }
};

$('textarea').keydown(function(e) {
	if(e.shiftKey && e.keyCode == 9) {
		var indent = false;
	} else if (e.keyCode == 9) {
		var indent = true;
	} else {
		return;
	}

	var column = '    ';

	 e.preventDefault();
	 var start = $(this).get(0).selectionStart;
	 var end = $(this).get(0).selectionEnd;

	 if (start === end) {
		if (!indent && $(this).val().substr(start - column.length, column.length) == column) {
		  $(this).val($(this).val().substring(0, start - column.length)
						  + $(this).val().substring(end));
			start = start - column.length * 2;
		} else {
		  $(this).val($(this).val().substring(0, start)
						  + column
						  + $(this).val().substring(end));
		}
		  $(this).get(0).selectionStart =
		  $(this).get(0).selectionEnd = start + column.length;
	 } else {
			var start = $(this).val().substring(0, start+1).lastIndexOf("\n") || 0;
			var sel = $(this).val().substring(start, end);

		  if (!indent) {
				sel = sel.replace(new RegExp('^' + column, 'g'), "");
				sel = sel.replace(new RegExp('\n' + column, 'g'), "\n");
			} else {
				sel = sel.split("\n").join("\n" + column);
			}
			$(this).val($(this).val().substring(0, start)
				+ sel
				+ $(this).val().substring(end, $(this).val().length));
			$(this).get(0).selectionStart = start;
			$(this).get(0).selectionEnd = start + sel.length;
	 }
});


function display_sceditor() {
	$('<link href="' + site_url + '/assets/js/sceditor/themes/square.min.css" rel="stylesheet" type="text/css">').appendTo('body');
	$('<script src="' + site_url + '/assets/js/sceditor/languages/fr.js"></script>').appendTo('body');
	$('<style>.sceditor-container * { box-sizing:  content-box !important; -moz-box-sizing:  content-box !important; }' +
			'.sceditor-button-preview div { background: url(' + site_url + '/assets/js/sceditor/page_white_magnify.png) !important; background-size: contain !important; background-repeat:no-repeat !important; }'+
			'.sceditor-button-upload div { background: url(' + site_url + '/assets/img/js/arrow.png) !important; background-size: contain !important; background-repeat:no-repeat !important; }'+
			'.sceditor-more { display: none; }'+
			'</style>').appendTo('body');

	$.getScript(site_url + '/assets/js/sceditor/jquery.sceditor.bbcode.min.js', function() {
		$.sceditor.command.set("preview", {
			exec: function() {
				$.post('<?=create_url('ajax')?>', {csrf: csrf, action: 'preview', format: 'bbcode', text: $('textarea').sceditor('instance').val()}, function(data) {
					$.fancybox.open(data, {minWidth: 700});
				});
			},
			txtExec: function() { },
			tooltip: "Preview"
		});
		$.sceditor.defaultOptions.toolbar += ",preview";
	<?php if (has_permission('user.upload')) { ?>
		$.sceditor.command.set("upload", {
			exec: function() {
				$.fancybox.open({type: 'ajax', href:'?p=gallery'});
			},
			txtExec: function() { },
			tooltip: "Attacher un fichier"
		});
		$.sceditor.defaultOptions.toolbar += "|upload";
	<?php } ?>
		$.sceditor.defaultOptions.emoticons.hidden = <?php	echo json_encode($_emoticons_hidden) ?>;
		$.sceditor.defaultOptions.emoticons.dropdown = <?php	echo json_encode($_emoticons) ?>;
		$.sceditor.defaultOptions.emoticons.more = [];

		$.sceditor.plugins.bbcode.bbcode
			.set("video", { tags: { video: null }, format: '[video]{0}[/video]', html: '<video src="{0}" controls>{0}</video>' })
			.set("audio", { tags: { video: null }, format: '[audio]{0}[/audio]', html: '<audio src="{0}" controls>{0}</audio>' });

		$("textarea").sceditor({
			plugins: 'bbcode',
			width: "98%",
			toolbarExclude: "copy,paste,cut,ltr,rtl,indent,outdent,print,pastetext,email,time",
			resizeWidth: false,
			emoticonsRoot: "<?= get_asset('emoticons') ?>/",
			locale : "fr",
			style: site_url + "/assets/js/sceditor/jquery.sceditor.default.min.css"
			});
	});
}

function display_ckeditor() {
	CKEDITOR_BASEPATH = site_url + '/assets/js/ckeditor/';
	$.getScript(site_url + '/assets/js/ckeditor/ckeditor.js', function() {
		CKEDITOR.basePath = site_url + '/assets/js/ckeditor/';

		<?php if (has_permission('user.upload')) { ?>
		if (typeof CKEDITOR.plugins.registered.upload == 'undefined') {
			CKEDITOR.plugins.add('upload', {
				init: function(editor) {
					editor.ui.addButton("Upload", {
						label:'Insérer un fichier',
						icon:this.path+'../../gallery.png',
						command:'Upload'
					});
					editor.addCommand( 'Upload', {
						exec : function( editor ) {
							$.fancybox.open({type: 'ajax', href:'?p=gallery'});
							//ajaxupload();
						},
						canUndo : false
					});
				}
			});
		}
		<?php } ?>

		if (typeof CKEDITOR.plugins.registered.spellcheck == 'undefined') {
			CKEDITOR.plugins.add('spellcheck', {
				init: function(editor) {
					editor.ui.add('spellcheck', CKEDITOR.UI_BUTTON, {
						label : 'Correction d\'orthographe. Shift+Click sur un mot pour voir les corrections!',
						toolbar : 'spellcheck',
						command : 'toggle_spellcheck',
						icon : this.path+'../../spell.png',
						modes: { wysiwyg: true},
						onRender: function() {
							var that = this;
							editor.on('spellCheckState', function(ev) {
								if(typeof ev.data !== undefined) {
									that.setState(ev.data == 'true' ? CKEDITOR.TRISTATE_ON : CKEDITOR.TRISTATE_OFF);
								}
							});
							setTimeout(function() {
								editor.fire('spellCheckState', $(editor.document.getBody()).attr('spellcheck'));
							}, 750);
						}
					});
					editor.addCommand('toggle_spellcheck', {
						exec : function( editor ) {
							var b = $(editor.document.getBody());
							b.attr('spellcheck', b.attr('spellcheck') == 'true' ? 'false' : 'true');
							editor.fire('spellCheckState', $(editor.document.getBody()).attr('spellcheck'));
							if (typeof(localStorage) !== 'undefined') {
								localStorage.setItem('spellcheck', b.attr('spellcheck'));
							}
						}
					});
				}
			});
		}

		if (typeof CKEDITOR.plugins.registered.a11yhelpbtn == 'undefined') {
			CKEDITOR.plugins.add('a11yhelpbtn', {
				init: function(editor) {
					editor.ui.addButton('a11yHelp', {
						label : 'Help',
						command : 'a11yHelp',
						icon : 'about'
					});
				}
			});
		}

		CKEDITOR.replace('editor', {
			language: 'fr',
			customConfig : '',
			extraPlugins: 'upload,a11yhelpbtn,spellcheck,tableresize',
			removePlugins: 'divarea',
			autoGrow_maxHeight: 400,
			autoGrow_onStartup: true,
			disableNativeSpellChecker: typeof(localStorage) == 'object' && localStorage.getItem('spellcheck') != 'true',
			allowedContent: true,
			enableTabKeyTools: true,
			baseHref: site_url,
			tabSpaces: 4,
			magicline_everywhere: true,
			magicline_color: '#ccc',
			contentsCss: '<?php echo Site('url') . '/themes/' . Site('theme'); ?>/css/wysiwyg.css',
			toolbar: [
				{ name: 'styles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'  ] },
				{ name: 'colors', items: [ 'TextColor', 'BGColor' ] },
				{ name: 'insert', items: [ 'Image', 'Link', 'Unlink', 'Iframe', 'Youtube', 'Upload' ] },
				{ name: 'document', items: [ 'NewPage', 'Save', 'Preview'] },
				{ name: 'editing', items: [ 'Find', 'spellcheck'] },
				{ name: 'tools', items: [ 'ShowBlocks', 'Maximize' ] },
				{ name: 'source', items: [ 'Source' ] },
				'/',
				{ name: 'style', items: ['Format', 'Font', 'FontSize'] },
				{ name: 'alignment', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
				{ name: 'insert2', items: ['Blockquote', 'Anchor', 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak'] },
				{ name: 'list',  items: [ 'NumberedList', 'BulletedList' ] },
				{ name: 'indent', items: [ 'Outdent', 'Indent'] },
				{ name: 'help', items: ['a11yHelp' , 'Styles'] },
			]
		});
		CKEDITOR.dtd.$removeEmpty['i'] = false;
		CKEDITOR.instances.editor.setKeystroke(CKEDITOR.CTRL + 83, 'save');
	});
}


function display_markitup(set) {
	$('<link rel="stylesheet" type="text/css" href="' + site_url + '/assets/js/markitup/markitup/skins/evo/style.css">').appendTo('body');
	$('<link rel="stylesheet" type="text/css" href="' + site_url + '/assets/js/markitup/markitup/sets/' + set + '/style.css">').appendTo('body');

	$.getScript(site_url + '/assets/js/markitup/markitup/sets/' + set + '/set.js', function() {
		$.getScript(site_url + '/assets/js/markitup/markitup/jquery.markitup.js', function() {
			mySettings.previewAutoRefresh = false;
			mySettings.onTab = {};
			mySettings.previewHandler = function() {
				$.post('<?=create_url('ajax')?>', {csrf: csrf, action: 'preview', format: set, text: $('textarea').val()}, function(data) {
					$.fancybox.open(data, {minWidth: 700});
				});
			}
			$('textarea').markItUp(mySettings);
		});
	});
}

function choose_editor(format) {
	if (typeof CKEDITOR != 'undefined' && typeof CKEDITOR.instances.editor != 'undefined') {
		CKEDITOR.instances.editor.destroy();
	}

	/* sceditor is very random when it gets destroyed... */
	if (typeof $.sceditor != 'undefined' && typeof $('textarea').sceditor('instance') == 'object' && typeof $('textarea').sceditor('instance').destroy == 'function') {
		$('textarea').sceditor('instance').destroy();
	}

	if (typeof $.markItUp != 'undefined') {
		$('textarea').markItUpRemove();
	}

	if (typeof $.fn.editable != 'undefined') {
		$('textarea').editable("destroy");
	}

	if (typeof $('textarea').destroy == 'function') {
		alert($('textarea').code());
		$('textarea').val($('textarea').code());
		$('textarea').destroy();
	}

	setTimeout(function() {
		if ($('iframe').length == 0) return;
		$('iframe')[0].contentWindow.document.onkeydown = function(e) {
			 if (e.ctrlKey && e.keyCode === 83) {
				  $('textarea').parents('form').submit();
				  return false;
			 }
		};
	}, 1500);

	switch (format) {
		case 'html':
			return display_ckeditor();
		case 'bbcode':
			return display_sceditor();
		case 'markdown':
			return display_markitup('markdown');
		case 'html-basic':
			return display_markitup('default');
	}
}