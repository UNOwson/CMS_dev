/* 
 * jQuery Emoticons Extension
 *
 * Copyright (c) 2014, Alex Duchesne <alex@alexou.net>
 * 
 * Licensed under the MIT license:
 * 	http://opensource.org/licenses/MIT
 */
 
(function($) {
	$.fn.emoticons = function(folder) {
		var folder = folder || "emoticons";
		var icons = {
			':)': 'smile.png',
			'8-)': 'cool.png',
			":'(": 'cwy.png',
			':D': 'grin.png',
			'&lt;3': 'heart.png',
			':(': 'sad.png',
			':O': 'shocked.png',
			':P': 'tongue.png',
			';)': 'wink.png',
			':ermm:': 'ermm.png',
			':angel:': 'angel.png',
			':angry:': 'angry.png',
			':alien:': 'alien.png',
			':blink:': 'blink.png',
			':blush:': 'blush.png',
			':cheerful:': 'cheerful.png',
			':devil:': 'devil.png',
			':dizzy:': 'dizzy.png',
			':getlost:': 'getlost.png',
			':happy:': 'happy.png',
			':kissing:': 'kissing.png',
			':ninja:': 'ninja.png',
			':pinch:': 'pinch.png',
			':pouty:': 'pouty.png',
			':sick:': 'sick.png',
			':sideways:': 'sideways.png',
			':silly:': 'silly.png',
			':sleeping:': 'sleeping.png',
			':unsure:': 'unsure.png',
			':woot:': 'w00t.png',
			':wassat:': 'wassat.png',
			':whistling:': 'whistling.png',
			':love:': 'wub.png',
			':pfff:': 'pfff.png',
			':hmm:': 'hmm.gif',
			':hihi:': 'hihi.gif',
			':whistling:': 'whistling.png',
			':love:': 'wub.png',
			':bave:': 'bave.gif',
			':mybad:': 'mybad.png',
		}

		return this.each(function(){
			var html = $(this).html();
			for(var i in icons){
				html = html.replace( (new RegExp("([> \n]|^)" + i.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&") + "([< \n]|$)" ,'g')), '$1<img src="'+folder+'/'+icons[i]+'" alt="'+i+'">$2','g');
			}
			$(this).html(html);
		})
	}
}) (jQuery);