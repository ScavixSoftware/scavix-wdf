
$.ajaxSetup({cache:false});

(function(win,$,undefined)
{
	win.wdf = 
	{
		/* see http://api.jquery.com/jQuery.Callbacks/ */
		ready: $.Callbacks('unique memory'),
		ajaxReady: $.Callbacks('unique'),  // fires without args, just notifier
		ajaxError: $.Callbacks('unique'),  // fires with args (XmlHttpRequest object, TextStatus, ResponseText)
		exception: $.Callbacks('unique'),  // fires with string arg containing the message
		
		setCallbackDefault: function(name, func)
		{
			wdf[name].empty().add( func );
			wdf[name]._add = wdf[name].add;
			wdf[name].add = function( fn ){ wdf[name].empty()._add(fn); wdf[name].add = wdf[name]._add; delete(wdf[name]._add); };
		},
		
		init: function(settings)
		{
			// prepare settings object
			var tmp_href = location.href.substr(settings.site_root.length).split("/");
			settings.controller = tmp_href[0] || '';
			settings.method = tmp_href[1] || '';
			settings.focus_first_input = (settings.focus_first_input === undefined)?true:settings.focus_first_input;
			
			// Init
			this.settings = settings;
			this.request_id = settings.request_id;
			this.initLogging();
			this.initAjax();
			
			// Add some methods
			for(var method in {"get":1, "post":1})
			{
				this[ method ] = function( controller, data, callback )
				{
					var url = wdf.settings.site_root;
					if( typeof controller === "string"  )
						url += controller;
					else
						url += $(controller).attr('id')
					return $[method](url, data, callback);
				};
			};
			
			// Shortcuts for current controller 
			this.controller = {};
			for(var method in {"get":1, "post":1})
			{
				this.controller[ method ] = function( handler, data, callback )
				{
					return wdf[method](wdf.settings.controller+'/'+handler,data,callback);
				};
			};

			// Focus the first visible input on the page (or after the hash)
			if( this.settings.focus_first_input )
			{
				if( location.hash && $('a[name="'+location.hash.replace(/#/,'')+'"]').length > 0 )
				{
					var anchor = $("a[name='"+location.hash.replace(/#/,'')+"']");
					var input = anchor.parentsUntil('*:has(input:text)').parent().find('input:text:first');
					if( input.length > 0 && anchor.position().top < input.position().top )
						input.select();
				}
				else
				{
					$('form').find('input[type="text"],input[type="email"],input[type="password"],textarea,select').filter(':visible:first').select();
				}
			}
			
			this.resetPing();
			this.setCallbackDefault('exception', function(msg){ alert(msg); });
			this.ready.fire();
		},
		
		resetPing: function()
		{
			if( wdf.ping_timer ) clearTimeout(wdf.ping_timer);
			wdf.ping_timer = setTimeout(function()
			{
				wdf.post('',{PING:wdf.request_id}, function(){ wdf.resetPing(); });
			},wdf.settings.ping_time || 60000);
		},

		reloadWithoutArgs: function()
		{
			wdf.redirect(location.href.split('?').shift());
		},
		
		redirect: function(href)
		{
			if( location.href == href )
				location.reload();
			else
				location.href = href;
		},
		
		initAjax: function()
		{
			wdf.original_ajax = $.ajax;
			$.extend({
				ajax: function( s )
				{
					wdf.resetPing();
					if( !s.data )
						s.data = {};
					else if( $.isArray(s.data) )
					{
						var tmp = {};
						for(var i=0; i<s.data.length; i++)
							tmp[s.data[i].name] = s.data[i].value;
						wdf.log("remapped serialized data",s.data,tmp);
						s.data = tmp;
					}
					s.data.request_id = wdf.request_id;

					if( wdf.settings.session_name && wdf.settings.session_id )
					{
						if( s.url.indexOf('?')>=0 )
							s.url += "&"+wdf.settings.session_name+"="+wdf.settings.session_id;
						else
							s.url += "?"+wdf.settings.session_name+"="+wdf.settings.session_id;
					}

					if( s.dataType == 'json' || s.dataType == 'script' )
						return wdf.original_ajax(s);

					if( s.data && s.data.PING )
						return wdf.original_ajax(s);

					if( s.success )
						s.original_success = s.success;
					s.original_dataType = s.dataType;
					s.dataType = 'json';

					s.success = function(json_result,status)
					{
						if( json_result )
						{
							var head = document.getElementsByTagName("head")[0];
							if( json_result.dep_css )
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

							if( json_result.dep_js )
							{
								for( var i in json_result.dep_js )
								{
									var js = json_result.dep_js[i];
									if( $('script[src=\''+js+'\']').length == 0 )
									{
										var script = document.createElement("script");
										script.setAttribute("type", "text/javascript");
										script.setAttribute("ajaxdelayload", "1");
										script.src = js;
										var jscallback = function() { this.setAttribute("ajaxdelayload", "0"); };
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
							
							if( json_result.error )
							{
								wdf.exception.fire(json_result.error);
								if( json_result.abort )
									return;
							}
							if( json_result.script )
							{
								$('body').append(json_result.script);
								if( json_result.abort )
									return;
							}
						}

						var param = json_result ? (json_result.html ? json_result.html : json_result) : null;
						if( s.original_success || param )
						{
							// async exec JS after all JS have been loaded
							var cbloaded = function()
							{
								if(($("script[ajaxdelayload='1']").length == 0)) 
								{
									if( s.original_success )
										s.original_success(param, status);
									else if( param )
										$('body').append(param);
									wdf.ajaxReady.fire();
								}
								else
									setTimeout(cbloaded, 10);
							}
							cbloaded();
						}
					};

					s.error = function(xhr, st, et)
					{
						// Mantis #6390: Sign up error with checkemail
						if( st=="error" && !et )
							return;
						wdf.error("ERROR calling " + s.url + ": " + st,xhr.responseText);
						wdf.ajaxError.fire(xhr,st,xhr.responseText);
					}

					return wdf.original_ajax(s);
				}
			});

			$(document).ajaxComplete( function(e, xhr)
			{
				if( xhr && xhr.responseText == "__SESSION_TIMEOUT__" )
					wdf.reloadWithoutArgs();
			});
		},
		
		initLogging: function()
		{
			for( var method in {'log':1,'debug':1,'warn':1,'error':1,'info':1} )
			{
				if( console[method] === undefined || !this.settings.log_to_console )
					this[method] = function(){ /*just a dummy*/ };
				else
					this[method] = console[method];
			}
		}
	};
})(window,jQuery);

if(typeof Debug != "function")
{
	window.Debug = function()
	{
		wdf.debug("Deprecated debug function called! Use wdf.debug() instead.");
	}
}
