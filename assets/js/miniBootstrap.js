/*!
 * Mini bootstrap javascript implementation
 *
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>
 * 
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */
$(document).on('click', '[data-toggle="tab"]', function() {
	 $(this).tab('show');
	 return false;
})

.on('click', '[data-toggle="collapse"]', function() {
	$($(this).attr('data-parent')).find('.panel-collapse.in').slideUp('fast', function() {
		$(this).removeClass('in');
	});
	if (!$(this.hash).hasClass('in')) {
		$(this.hash).slideDown('fast', function() {
			$(this).addClass('in');
		});
	}
	return false;
})

.on('click', '[data-toggle="dropdown"]', function() {
	return false;
})

.on('click', '[data-dismiss="alert"]', function() {
	$(this).parent().hide();
})
;

$.fn.tab = function(show) {
	// var tab = this;
	// var nav = $('[data-toggle="tab"][href="#' + tab[0].id + '"]');
	var tab  = $(this[0].hash);
	var last = tab.siblings('.tab-pane.active');
	
	var nav  = $(this).parents('li');
	
	nav.siblings('li.active').removeClass('active');
	nav.addClass('active');
	
	if (tab.hasClass('fade') && !tab.hasClass('active')) {
		last.removeClass('in');
		setTimeout(function() { /* We'd use transitionend if it had better browser support */
			last.removeClass('active');
			tab.addClass('active in');
		}, 150);
	} else {
		last.removeClass('active in');
		tab.addClass('active in');
	}
	return this;
}

$.fn.tooltip = function(position) {
	this.each(function() {
		if (typeof this.tip != 'undefined') {
			this.tip.remove();
			$(this).unbind('mouseenter.tip mouseleave.tip');
		}
		this.tiplocation = position['placement'] || position || 'bottom';
		this.tiptitle = this.title;
		this.title = '';
	});
	
	return this.on('mouseenter.tip', function(e) {
		var el = $(this);
		this.tip = $('<div class="tooltip fade"><div class="tooltip-arrow"></div><div class="tooltip-inner">' + this.tiptitle + '</div></div>')
			.appendTo('body')
			.addClass(this.tiplocation);
			
		this.tip.css({ top:  el.offset().top + el.outerHeight(), 
							left: el.offset().left + el.outerWidth() / 2 - this.tip.outerWidth() / 2})
				  .addClass('in');
	})
	.on('mouseleave.tip', function() {
		this.tip.remove();
	});
};

$('[data-toggle="tooltip"]').tooltip();

$.fn.button = function(action) {
	this.each(function() {
		if (this.nodeName != 'BUTTON') {
			return;
		}
		
		var el = $(this);

		switch(action) {
			case 'toggle':
				el.toggleClass('active');
				break;
			case 'reset':
				if (txt = el.attr('button-orig-text')) {
					el.text(txt);
				}
				break;
			default:
				console.log(action);
				el.attr('button-orig-text', el.text());
				el.text(action);
		}
		return el;
	});
};

$.fn.popover = function(position) {


};


$.modal = function(html, title) {

};



 /* 
	CSS: 
		button.dropdown-toggle:focus + .dropdown-menu {display:block;}
		
	Bootstrap-like pure css tooltips:
	
	[data-title]:hover{ /* We could use directly title here * /
		 position: relative;
	}

	[data-title]:hover:before{
		 position: absolute;
		 border: solid;
		 border-color: #333 transparent;
		 border-width: 0px 6px 6px 6px;
		 top: calc(100% + 2px);
		 left: 50%;
		 content: "";
	}

	[data-title]:hover:after{
		 position: absolute;
		 border-radius: 5px;
		 white-space: nowrap;
		 background: rgba(0,0,0,.8);
		 color: #ffffff;
		 top: calc(100% + 8px);
		 right: -20px;
		 font-size: 12px;
		 min-width:30px;
		 padding: 5px 10px;
		 content: attr(data-title) attr(title) " ";
	}
 */