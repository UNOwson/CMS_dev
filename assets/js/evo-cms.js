/*!
 * Evo-CMS: Main Javascript
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>.
 *
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */

function report(pid) {
	var reason = prompt('Pour quelle raison souhaitez-vous signaler?');
	if (reason) {
		$.post('', {'csrf': csrf, 'report': reason, 'pid': pid}, function () {alert('Merci!');});
	}
	return false;
}


function htmlencode(value) {
  return $('<div/>').text(value).html();
}


function mk_human_unit(size) {
	var units = ['B', 'KB', 'MB', 'GB', 'TB'];
	var unit = 0;
	while (size > 1024) {unit++; size /= 1024;}
	return Math.round(size, 2) +  " " + units[unit];
}


/* Modified from annakata : http://stackoverflow.com/a/487049 */
function insertGetParam(key, value, qs) {
	key = encodeURIComponent(key); 
	value = encodeURIComponent(value);

	var qs = qs || document.location.search;
	if (qs.substr(0,1) == '?') {
		qs = qs.substr(1);
	}
	
	var kvp = qs.split('&');
	
	if (kvp == '') {
		return key + '=' + value;
	} else {
		var i = kvp.length; var x;
		while (i--) {
			x = kvp[i].split('=');

			if (x[0] == key) {
				if (value == '') {
					kvp[i] = '';
				} else {
					x[1] = value;
					kvp[i] = x.join('=');
				}
				break;
			}
		}
		if (i < 0 && value != '') { kvp[kvp.length] = [key, value].join('='); }
		
		return kvp.filter(function(v){return v!==''}).join('&');
	}
}


function poptart(message, sound) {
	var pop = $('div.poptart');
	if (pop.length == 0) {
		var pop = $('<div class="poptart" style="display:none"></div>').appendTo('body');
	}
	
	if (pop.html() != message) {
		pop.fadeOut(500, function () { $('div.poptart').html(message).slideDown(500); });
		if (sound) {
			if ($('audio.poptart').length == 0) {
				$('<audio autoplay class="poptart" src="' + site_url + '/assets/audio/beep.mp3"></audio>').appendTo('body');
			} else {
				$('audio.poptart')[0].play();
			}
		}
	}
}


function TimedRefresh(element, url, timeout, startNow) {
	// (function (element, url) {
		// $.get(url + '&tz='+(new Date()).getTimezoneOffset(), function(data) { 
			// $('#notifications').html(data);
		// });
		// return this;
	// })()
	
	// setInterval(function(element, url) {
	// }, timeout || 600);
}


function ServerPoll() {
	$.get( site_url + '/scripts/ajax.php?action=servers&tz='+(new Date()).getTimezoneOffset(), function(data) { 
		$('#notifications').html(data);
	});
}


function draganddrop() {
	$('.sortable').tableDnD({
		onDrop: function(table, row) {
		$.post('', $.tableDnD.serialize() + '&csrf=' + csrf, function (data) { $(table).html($('#'+$(table).attr('id'), data).html()); draganddrop(); });
	}});
}


function spoiler() {
	$('.spoiler a').on('click', function () {
		$(this).parent().find('div').toggle('slow');
		return false;
	});
}


function changepage(page, post, callback, pop) {
	if (typeof pop == 'undefined')
		history.pushState({'page': page}, 'Home - Page ' + page, page);
	
	var post = post || {};
	var callback = callback || pageload;
	var top = $('#content').offset().top - 20;
	var obj = top < $(document).scrollTop() ? $('#content').fadeOut() : $('#content');
	
	post.csrf = csrf;
	
	obj.load(page + ' #content', post, function(data) {
			$(this).fadeIn(500);
			if (top < $(document).scrollTop()) {
				$('html, body').animate({scrollTop: top});
			}
			callback();
	});
	return true;
}


function hashchanged(event) {
	var hash = window.location.hash;
	
	if (hash.length < 2) return ;

	if (hash.substr(0,6) == '#alert') {
		var e = $('#msg' + hash.substr(6));
		if (!e) return;
		window.scrollTo(0, e.offset().top);
		e.css({'border-radius': '5px', 'background-color': '#f2dede', 'transition': 'background-color 1s linear'});
	} else  if (hash.substr(0,4) == '#msg')	{
		$('.forum .highlight, .commentaires .highlight').removeClass('highlight');
		$(hash).addClass('highlight');
	} else {
		$('a[href=' + hash + '][data-toggle="tab"]').click();
	}
	return false;
}


function ajaxupload(oncomplete) {
	var e = $('<input type="file" class="hide">');
	
	e.appendTo('body');
	
	e.on('change', function () {
			
			if (!$(this)[0].files[0]) return;
			
			var form = new FormData();
			form.append("ajaxup", $(this)[0].files[0]);
			
			$('body').append('<div class="modal-backdrop in"></div><div id="spinner" title="loading" style="position : fixed; width:100%; left:0; text-align: center; top: 50%; z-index:1500;color:#52bcdc;text-align:center;"><i class="fa fa-5x fa-refresh fa-spin"></i><br><strong>Uploading...</strong></div></di>');
			
			$.ajax({
			  url: '',
			  xhr: function() {
					var myXhr = $.ajaxSettings.xhr();
					if(myXhr.upload){
						 myXhr.upload.addEventListener('progress',function (e){ if(e.lengthComputable){ $('#spinner strong').html('Uploading... ' + Math.round(e.loaded / e.total * 100) + '%'); } }, false);
					}
					return myXhr;
			  },
			  data: form,
			  dataType: 'json',
			  processData: false,
			  contentType: false,
			  type: 'POST',
			  error: function(xhr) {
					alert("Erreur d'upload: " + xhr.responseText);
				},
			  complete: function() {
					$('#spinner, .modal-backdrop').remove();
				},
			  success: function(data){
					if (data && !oncomplete) {
						insertfile(data[0], data[1], data[2], data[3]);
					}
					if (typeof oncomplete == 'object' || typeof oncomplete == 'function') {
						oncomplete(data);
					}
			  }
			});
	});
	e.click();
}



$.fn.image_selector = function (select) {
	var optgroup = select.find('option:selected').parent('optgroup');
	var that = $(this);
	
	if (optgroup.length == 0) {
		var images = select.find('option');
		group = '___root';
	} else {
		var images = optgroup.find('option');
		group = optgroup.attr('label');
	}
	
	if (images.length == 0) return;
	
	var selector_box = $('<div></div>').addClass('image_selector').attr('data-group', group);
	
	images.each(function() {
		var option = $(this);
		if (option.val() === '' && !option.attr('data-src-alt')) return;
		var img = $('<img>',{'data-value':option.val(), 'data-group':group, title: option.text(), src: option.attr('data-src-alt') || site_url + option.val()});
		
		img.tooltip({placement:'bottom'});
		option.attr('data-group', group);
		
		if (option.is(':selected'))
			img.addClass('selected');
		
		img.click(function() {
			select.val($(this).attr('data-value')).change().focus();
			option.focus();
		});
		
		selector_box.append(img);
	});
	
	select.unbind("change.imgsel keyup.imgsel click.imgsel");
	
	select.bind("change.imgsel keyup.imgsel click.imgsel", function() {
		
		var src = $(this).find(':selected').attr('data-src-alt') || site_url + $(this).find(':selected').val();
		
		$('#image_selector_preview').attr('src', src);
	
		if ($(this).find(':selected').attr('data-group') != selector_box.attr('data-group')) {
			selector_box.remove();
			that.image_selector(select);
		} else {
			selector_box.find('img').removeClass('selected');
			selector_box.find('img[data-value="'+$(this).val()+'"]').addClass('selected');
		}
	});
	
	if ($(this).length != 0) {
		$(this).html(selector_box);
	} else {
		select.after(selector_box);
	}
}


function autocomplete(callback, query, css) {
	autocomplete.popup = autocomplete.popup || 
		$('<div/>')
			.addClass('list-group autocomplete')
			.css({position: 'absolute', 'min-width': '300px', 'max-height':'250px', 'z-index': 999, 'overflow-y': 'auto'})
			.appendTo('body');
	
	autocomplete.open = autocomplete.open || false;
	
	var popup = autocomplete.popup;
	
	autocomplete.next = function() {
		if (!this.open) return false;
		var next = this.popup.find('.active').removeClass('active').next();
		if (!next.length) {
			next = this.popup.find('a:first-child');
		}
		next.addClass('active');
		return this.value = next.attr('data-complete');
	}
	
	autocomplete.prev = function() {
		if (!this.open) return false;
		var prev = this.popup.find('.active').removeClass('active').prev();
		if (!prev.length) {
			prev = this.popup.find('a:last-child');
		}
		prev.addClass('active');
		return this.value = prev.attr('data-complete');
	}
	
	autocomplete.select = function() {
		if (!this.open) return false;
		return this.popup.find('.active').click();
	}
	
	autocomplete.hide = function() {
		this.popup.slideUp();
		this.open = false;
		return !this.open;
	}
	
	if (typeof callback != 'object' && typeof callback != 'function') {
		autocomplete.hide();
		return;
	}
	
	query.action = query.action || 'userlist';
	
	popup.css(css);
	
	$.get(site_url + '/scripts/ajax.php', query, function(items) {
		popup.find('a').remove();
		for (var i in items) {
			var img = typeof items[i][2] == 'string' ? 
				'<img class="pull-right" style="max-height: 20px" src="' + items[i][2] + '">' : '';
			
			items[i][1] = items[i][1] || items[i][0];
			
			var u = $('<a href="" data-complete="' + items[i][0] + '"' +
							'class="list-group-item">' + items[i][1] + img + '</a>');
			u.click(function() {
				callback($(this).attr('data-complete'));
				autocomplete.hide();
				return false;
			});
			popup.append(u).slideDown('fast');
		};
		popup.find('a:first-child').addClass('active');
		autocomplete.value = i ? items[0][0] : null;
		autocomplete.open = !!i;
	}, 'json');
}


function highlightjs() {
	if ($('pre').length != 0) {
		if ( typeof hljs != 'object' ) {
			$('<link href="' + site_url + '/assets/js/highlightjs/default.css" rel="stylesheet">').appendTo('head');
			$.getScript(site_url + '/assets/js/highlightjs/highlightjs.min.js', function() {
				$('pre').each(function(i, e) {hljs.highlightBlock(e)});
			});
		} else {
			$('pre').each(function(i, e) {hljs.highlightBlock(e)});
		}
	}
}


function paginator() {
	if ('pushState' in history) {
		$(".paginator a[href]").unbind('click').click(function() {
			return !changepage(this.href);
		});
	}
}


function pageload() {
	highlightjs();
	spoiler();
	draganddrop();
	paginator();
	$('[title]:not([title=""])').tooltip({placement:'bottom'});
	$('.fancybox-image, .gallery > .gallery-container a, a[href$=".png"], a[href$=".jpg"], a[href$=".gif"]').not('.nofancy').fancybox({
				openEffect : 'elastic',
				openSpeed  : 150,
				closeEffect : 'elastic',
				closeSpeed  : 150,
				type: 'image',
				beforeShow : function() {
					var alt = this.element.find('img').attr('alt');
					this.inner.find('img').attr('alt', alt);
					this.title = alt;
				},
				helpers: {
					overlay : {
						css : {
							'background' : 'transparent'
						}
					}
				},
				closeClick : true,
	});
	$(".fancybox").fancybox();
	$('.fancybox-ajax').fancybox({type: 'ajax', scrolling: 'auto'});
//	$('.fancybox-iframe').fancybox({type: 'iframe'});
	$('a.confirm, button.confirm, input.confirm').click(function() {
		return confirm('Êtes vous certain de vouloir effectuer cette action?');
	});
}


// From http://stackoverflow.com/questions/10211145/getting-current-date-and-time-in-javascript
// For todays date;
Date.prototype.today = function () { 
    return ((this.getDate() < 10)?"0":"") + this.getDate() +"/"+(((this.getMonth()+1) < 10)?"0":"") + (this.getMonth()+1) +"/"+ this.getFullYear();
}

// For the time now
Date.prototype.timeNow = function () {
     return ((this.getHours() < 10)?"0":"") + this.getHours() +":"+ ((this.getMinutes() < 10)?"0":"") + this.getMinutes() +":"+ ((this.getSeconds() < 10)?"0":"") + this.getSeconds();
}
	
	
	
	

window.onhashchange = hashchanged;
hashchanged();

if (enable_poll) {
	setInterval(ServerPoll, 20000);
	ServerPoll();
}
pageload();

$('#avatar_selector_box').image_selector($('select.avatar_selector'));	

setTimeout(function() {
	$('.alert-success.auto-dismiss').slideUp('slow');
}, 1800);


$.fn.autocomplete = function() {
	
}

$('[data-autocomplete]').on('keyup focusin', function(e) {
	var that = this;
	var m = $(this).attr('data-autocomplete-instant');

	if (e.keyCode == 9 || e.keyCode == 38 || e.keyCode == 40) {
		return false;
	}
	console.log(e);
	if (that.value.length < (m == undefined ? 1 : m)) return autocomplete();
	
	if (typeof that.acEnabled == 'undefined') {
		that.acEnabled = true;
		$(that).attr('autocomplete', 'off').unbind('blur').blur(function() {
			setTimeout(autocomplete, 100); // Time for the click event to register before in slides up.
		});
	}
	
	autocomplete(
		function(user) { that.value = user; },
		{ action: that.getAttribute('data-autocomplete'), query: that.value },
		{ top: $(that).offset().top + $(that).outerHeight(true),
		  left: $(that).offset().left,
		  'min-width': $(that).outerWidth(true) }
	);
})
.on('keydown', function(e) {
	if (!autocomplete.open) return;
		switch(e.keyCode) {
			case 9: //Tab
				autocomplete.select();
				e.preventDefault();
				break;
			case 38: //up
				autocomplete.prev();
				e.preventDefault();
				break;
			case 40: //down
				autocomplete.next();
				e.preventDefault();
				break;
		}
});

$('[data-autocomplete]').attr('autocomplete', 'off');


$('#filter').attr('autocomplete', 'off').keyup(function() {
	var filter = $(this).val();
	var qs = insertGetParam('filter', filter, insertGetParam('pn', ''));
	
	$.get('?' + qs, 
		function (data) {
			$('#content').html($('#content', '<div>' + data + '</div>').html()); 
			paginator();
			history.replaceState(null, null, '?' + qs);
		}
	);
});

$('form').on('submit', function () {
	/* If we do a real "disabled" the value of the button won't be sent. Some of our forms depend on it */
	$(this).find(':submit').click(function() {return false;}).addClass('disabled');
});

/* chrome fix */
window.addEventListener('load', function() {
	setTimeout(function() {
		$(window).bind('popstate', function(e) {
			changepage(document.location, null, null, true);
		});
	}, 0);
});