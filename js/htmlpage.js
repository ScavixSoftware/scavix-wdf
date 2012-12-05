
var ping_timer = false;
var waiting_dlg = false;
var waiting_dlg_timer = false;
var ajax_manager = false;
var ajax_script_cache = "";
var ajax_disabled = false;
$.jCache.maxSize = 100;
$.ajaxSetup({cache:false});
var original_jQuery_ajax = jQuery.ajax;

function HtmlPage_Init(settings)
{
	if( settings.session_id )
		document.session_id = settings.session_id;
	if( settings.session_name )
		document.session_name = settings.session_name;

	document.request_id = settings.request_id;

	if( settings.ajax_wait_dlg_id )
		waiting_dlg = settings.ajax_wait_dlg_id;
	if( settings.site_root )
	{
		document.site_root = settings.site_root;
		var tmp_href = location.href.substr(document.site_root.length);
		tmp_href = tmp_href.split("/");

		if( tmp_href.length > 0 )
			document.controller = tmp_href[0];
		if( tmp_href.length > 1 )
			document.method = tmp_href[1];
	}

	HtmlPage_ResetPing();
	
	// focus the first visible input on the page (or after the hash)
	if( location.hash && $('a[name="'+location.hash.replace(/#/,'')+'"]').length > 0 )
	{
		var anchor = $("a[name='"+location.hash.replace(/#/,'')+"']");
		var input = anchor.parentsUntil('*:has(input:text)').parent().find('input:text:first');
		if( input.length > 0 && anchor.position().top < input.position().top )
			input.select();
	}
	else
	{
		$('form').find('input[type="text"],input[type="password"],textarea,select').filter(':visible:first').select();
	}

	ajax_manager = $.manageAjax({manageType: 'queue', maxReq: 4, blockSameRequest: false, cache: false});
	jQuery.extend({
		ajax: function( s )
		{
			HtmlPage_ResetPing();
			if( !s.data )
				s.data = {};
			s.data.request_id = document.request_id;

			if( document.session_name && document.session_id )
			{
				if( s.url.indexOf('?')>=0 )
					s.url += "&"+document.session_name+"="+document.session_id;
				else
					s.url += "?"+document.session_name+"="+document.session_id;
			}

			if( s.dataType == 'json' || s.dataType == 'script' )
				return original_jQuery_ajax(s);

			if( s.data && s.data.PING )
				return original_jQuery_ajax(s);
			
			if( s.success )
				s.original_success = s.success;
			s.original_dataType = s.dataType;
			s.dataType = 'json';

			s.success = function(json_result,status)
			{
				var head = document.getElementsByTagName("head")[0];
				if( json_result && json_result.dep_css )
				{
					for( var i in json_result.dep_css )
					{
						var css = json_result.dep_css[i];
						if( $('link[href=\''+css+'\']').length == 0 )
						{
							var fileref = document.createElement("link")
							fileref.setAttribute("rel", "stylesheet");
							fileref.setAttribute("type", "text/css");
							fileref.setAttribute("href", css);
							head.appendChild(fileref);
						}
					}
				}

				if( json_result && json_result.dep_js )
				{
					for( var i in json_result.dep_js )
					{
						var js = json_result.dep_js[i];
						//Debug("loading: " + js);
						if( $('script[src=\''+js+'\']').length == 0 )
						{
							var script = document.createElement("script");
							script.setAttribute("type", "text/javascript");
							script.setAttribute("ajaxdelayload", "1");
							script.src = js;
							var jscallback = function() { /*Debug(this.getAttribute("src") + " loaded");*/ this.setAttribute("ajaxdelayload", "0"); };
							if (script.addEventListener)
								script.addEventListener("load", jscallback, false);
							else
								script.onreadystatechange = function() {
									if ((this.readyState == "complete") || (this.readyState == "loaded"))
										jscallback.call(this);
								}
							head.appendChild(script);
						}
					}
				}

				if( s.original_success )
				{
					var param = json_result ? (json_result.html ? json_result.html : json_result) : null;

					// async exec JS after all JS have been loaded
					var cbloaded = function() {
						if(($("script[ajaxdelayload='1']").length == 0)) // && ($("link[rel=stylesheet][ajaxdelayload=1]").length == 0))
							s.original_success(param, status);
						else
							setTimeout(cbloaded, 10);
					}
					cbloaded();
				}
			};

			s.error = function(XMLHttpRequest, textStatus, errorThrown)
			{
				// Mantis #6390: Sign up error with checkemail
				if( textStatus=="error" && !errorThrown )
					return;
				Error("<b>ERROR calling " + s.url + "</b>:" + textStatus + "<br/>" + XMLHttpRequest.responseText);
			}

			return original_jQuery_ajax(s);
		}
	});

	$(document).ajaxComplete( function(e, xhr, settings)
	{
		if( xhr && xhr.responseText == "__SESSION_TIMEOUT__" )
			document.location.reload();
	});
	
	location.reloadWithoutArgs = function()
	{
		var href = location.href.split('?').shift();
		if( location.href == href )
			location.reload();
		else
			location.href = href;
	};
}

function HtmlPage_ResetPing()
{
	if( ping_timer )
		clearTimeout(ping_timer);
	ping_timer = setTimeout(function()
	{
		$.post(document.site_root,{PING:document.request_id}, function()
		{
			HtmlPage_ResetPing();
		});
	},60000);
}

function GoAfterAjax(url)
{
	ShowLoadingMessage();
	if( ajax_manager.queue.length > 0 )
	{
		setTimeout(function() { GoAfterAjax(url); }, 10);
		return;
	}
	document.location.href = url;
}

function PerformPing(request_id)
{
	$.get("?",{PING:request_id}, function()
	{
		HtmlPage_ResetPing();
	});
}

if(typeof Debug != "function")
{
	var debug_counter = false;
	var debug_stack = false;
	var debug_logger = false;
	var Debug = function(o,label,depth)
	{
		if( !debug_logger )
		{
			try 
			{
				if( !debug_logger && window.console )
					debug_logger = window.console;
				if( !debug_logger && top.console )
					debug_logger = top.console;
				if( !debug_logger && console )
					debug_logger = console;
				if( !debug_logger )
					try{debug_logger = firebug.d.console.cmd}catch(ex){};
				if( !debug_logger )
					try{debug_logger = top.firebug.d.console.cmd}catch(ex){};
			} catch(ex) {}
		}
		if( !debug_logger || typeof(debug_logger.log)=="undefined" )
			return;

		if( !depth )
		{
			debug_counter = 0;
			debug_stack = new Array();
			depth = 0;
		}

		for(var i in debug_stack)
			if( debug_stack[i] == o )
			{
				debug_logger.log("RECURSION");
				return;
			}

		if( depth > 10 )
		{
			debug_logger.log("STOPPING because depth level of 10 reached.");
			return;
		}

		debug_counter++;
		if( debug_counter > 100 )
		{
			debug_logger.log("STOPPING because more than 100 entries found.");
			return;
		}

		if( typeof(o) == 'object' )
		{
			debug_logger.group(label?label+' (object)':'object');
			for(var i in o)
				try{Debug(o[i],i,depth+1);}catch(ex){}
			debug_logger.trace();
			debug_logger.groupEnd();
		}
		else if( typeof(o) == 'array' )
		{
			debug_logger.group(label?label+' (array)':'array');
			for(var i in o)
				try{Debug(o[i],i,depth+1);}catch(ex){}
			debug_logger.trace();
			debug_logger.groupEnd();
		}
		else
			debug_logger.log(label?label+': '+o:o);
	}
}

var block_event_once = function(id,type)
{
	$('#'+id).bind(type+'.block_once',function(event)
	{
		$(event.target).unbind(event.type+'.block_once');
		event.preventDefault();
		event.stopPropagation();
		return false;
	});
}

var ajax_request = function(method,url,data,allow_caching,silent,await_all_others)
{
	if( await_all_others &&  ajax_manager.queue.length > 0 )
	{
		ajax_disabled = true;
		var rawdata = "{";
		for(var p in data)
			rawdata += ","+p+":'"+data[p]+"'";
		rawdata += "}";
		rawdata = rawdata.replace(/\{,/,"{");
		setTimeout(function() { ajax_request(method, url, rawdata, allow_caching, silent, await_all_others); }, 100);

		if( waiting_dlg && $(waiting_dlg) )
			dialog_show(waiting_dlg);
		return;
	}

	if( !data ) data = {};
	data.request_id = document.request_id;
    if(jQuery.url && jQuery.url.param("XDEBUG_PROFILE") != null)
        data.XDEBUG_PROFILE = 1;

	var cache_key = false;
	var just_cache = false;
	if( allow_caching )
	{
		cache_key = url;
		for(var i in data)
			cache_key += "&"+i+"="+data[i];
	}

	switch( method )
	{
		case 'cache_get':
		case 'cache_post':
			method = method.substr(6);
			just_cache = true;
		case 'get':
		case 'post':
			if( cache_key && $.jCache.hasItem(cache_key) )
			{
				ajax_callback( $.jCache.getItem(cache_key) );
				return;
			}

			if( url.toLowerCase().indexOf('event=onchange') > -1 ||
				( data.event && data.event.toLowerCase() == "onchange" ) )
			{
				top.data_has_been_changed = true;
			}
			
			ajax_manager.add(
			{
				success: !cache_key?ajax_callback:function(d)
				{
					$.jCache.setItem(cache_key,d);
					if( !just_cache )
						ajax_callback(d);
				},
				url: url,
				data: data,
				type: method
			});
			break;
		default:
			if( cache_key && $.jCache.hasItem(cache_key) )
			{
				$('#'+method).replaceWith($.jCache.getItem(cache_key));
				return;
			}

			$.get(url,data,function(d)
			{
				if( d.substr(0,1) == "<" )
				{
					if( cache_key )
						$.jCache.setItem(cache_key,d);

					var blank = unescape(d).replace(/[\r\n]*/gi,"").replace(/<script>.*?<\/script>/gim,"");					
					$('#'+method).replaceWith( blank );
					ajax_load_callback(d);
				}
				else
					ajax_callback(d,true)
			});
			break;
	}

	if( !silent && !just_cache && dialog_show && waiting_dlg && $(waiting_dlg) && !waiting_dlg_timer )
		waiting_dlg_timer = setTimeout(function() { dialog_show(waiting_dlg); waiting_dlg_timer = false; }, 1000);
}

var ajax_callback = function (d,return_result)
{
	var res = true;
	HtmlPage_ResetPing();

	if( d )
	{
		try
		{
			eval(d);
		}catch(e){if( return_result ) res = false;else {}};
	}

	if( waiting_dlg_timer )
	{
		clearTimeout(waiting_dlg_timer);
		waiting_dlg_timer = false;
	}
	if( dialog_hide )
		dialog_hide(waiting_dlg);

	if( return_result )
		return res;
}

var ajax_load_callback = function(d)
{
	$('script',$(d).wrap('<p/>')).each( function ()
	{
		try
		{
			eval($(this).html());
		}catch(e){Debug(e);}
	});
	ajax_callback();
}

function ajax_get(url,data,allow_caching,silent,await_all_others)
{
	ajax_request('get',url,data,allow_caching,silent,await_all_others);
}

function ajax_post(url,data,allow_caching,silent,await_all_others)
{
	ajax_request('post',url,data,allow_caching,silent,await_all_others);
}

function ajax_load(target_id,url,data,allow_caching,silent,await_all_others)
{
	if( !data ) data = {};
	if( !data.load )
		data.load = target_id;
	ajax_request(target_id,url,data,allow_caching,silent,await_all_others);
}

function ajax_cache(method,url,data)
{
	ajax_request('cache_'+method,url,data,true);
}

function ajax_script(hash,url,script)
{
	if( ajax_script_cache.indexOf(" "+hash) > -1 )
	{
		setTimeout(function() { ajax_script_execute(hash, script); }, 10);
		return;
	}

	var loaded = top[hash + '_loaded'];
	if( !loaded )
	{
		$('head script').each( function()
		{
			if( $(this).attr('src') == url )
			{ 
				loaded = true; 
				return false; 
			} 
		});
	}

	if( loaded )
	{
		eval(unescape(script));
	}
	else
	{
		ajax_script_cache += " "+hash;
		$.getScript(url,function()
		{
			top[hash + '_loaded'] = true;
			ajax_script_execute(hash,script);
		});
	}
}

function ajax_script_execute(hash,script)
{
	var loaded = top[hash + '_loaded'];
	if( loaded )
		eval(unescape(script));
	else
		setTimeout(function() { ajax_script_execute(hash, script); }, 10);
}