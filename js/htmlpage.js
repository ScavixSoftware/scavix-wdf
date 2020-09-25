/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

!function(e,n){"object"==typeof exports&&"undefined"!=typeof module?n():"function"==typeof define&&define.amd?define(n):n()}(0,function(){"use strict";function e(e){var n=this.constructor;return this.then(function(t){return n.resolve(e()).then(function(){return t})},function(t){return n.resolve(e()).then(function(){return n.reject(t)})})}function n(e){return!(!e||"undefined"==typeof e.length)}function t(){}function o(e){if(!(this instanceof o))throw new TypeError("Promises must be constructed via new");if("function"!=typeof e)throw new TypeError("not a function");this._state=0,this._handled=!1,this._value=undefined,this._deferreds=[],c(e,this)}function r(e,n){for(;3===e._state;)e=e._value;0!==e._state?(e._handled=!0,o._immediateFn(function(){var t=1===e._state?n.onFulfilled:n.onRejected;if(null!==t){var o;try{o=t(e._value)}catch(r){return void f(n.promise,r)}i(n.promise,o)}else(1===e._state?i:f)(n.promise,e._value)})):e._deferreds.push(n)}function i(e,n){try{if(n===e)throw new TypeError("A promise cannot be resolved with itself.");if(n&&("object"==typeof n||"function"==typeof n)){var t=n.then;if(n instanceof o)return e._state=3,e._value=n,void u(e);if("function"==typeof t)return void c(function(e,n){return function(){e.apply(n,arguments)}}(t,n),e)}e._state=1,e._value=n,u(e)}catch(r){f(e,r)}}function f(e,n){e._state=2,e._value=n,u(e)}function u(e){2===e._state&&0===e._deferreds.length&&o._immediateFn(function(){e._handled||o._unhandledRejectionFn(e._value)});for(var n=0,t=e._deferreds.length;t>n;n++)r(e,e._deferreds[n]);e._deferreds=null}function c(e,n){var t=!1;try{e(function(e){t||(t=!0,i(n,e))},function(e){t||(t=!0,f(n,e))})}catch(o){if(t)return;t=!0,f(n,o)}}var a=setTimeout;o.prototype["catch"]=function(e){return this.then(null,e)},o.prototype.then=function(e,n){var o=new this.constructor(t);return r(this,new function(e,n,t){this.onFulfilled="function"==typeof e?e:null,this.onRejected="function"==typeof n?n:null,this.promise=t}(e,n,o)),o},o.prototype["finally"]=e,o.all=function(e){return new o(function(t,o){function r(e,n){try{if(n&&("object"==typeof n||"function"==typeof n)){var u=n.then;if("function"==typeof u)return void u.call(n,function(n){r(e,n)},o)}i[e]=n,0==--f&&t(i)}catch(c){o(c)}}if(!n(e))return o(new TypeError("Promise.all accepts an array"));var i=Array.prototype.slice.call(e);if(0===i.length)return t([]);for(var f=i.length,u=0;i.length>u;u++)r(u,i[u])})},o.resolve=function(e){return e&&"object"==typeof e&&e.constructor===o?e:new o(function(n){n(e)})},o.reject=function(e){return new o(function(n,t){t(e)})},o.race=function(e){return new o(function(t,r){if(!n(e))return r(new TypeError("Promise.race accepts an array"));for(var i=0,f=e.length;f>i;i++)o.resolve(e[i]).then(t,r)})},o._immediateFn="function"==typeof setImmediate&&function(e){setImmediate(e)}||function(e){a(e,0)},o._unhandledRejectionFn=function(e){void 0!==console&&console&&console.warn("Possible Unhandled Promise Rejection:",e)};var l=function(){if("undefined"!=typeof self)return self;if("undefined"!=typeof window)return window;if("undefined"!=typeof global)return global;throw Error("unable to locate global object")}();"Promise"in l?l.Promise.prototype["finally"]||(l.Promise.prototype["finally"]=e):l.Promise=o});


(function(win,$,undefined)
{ 
    var location = win.location, 
        document = win.document, 
        console = win.console, 
        Promise = win.Promise,
        URLSearchParams = win.URLSearchParams,
        Math = win.Math,
        originalXhr = $.ajaxSettings.xhr;
        
    $.ajaxSetup(
    {
        cache:false,
        progress: function() { },
        progressUpload: function() { },
        xhr: function()
        {
            var req = originalXhr(), that = this;
            if( req )
            {
                if( req.addEventListener )
                {
                    req.addEventListener("progress", function(evt)
                    {
                        that.progress(evt);
                    },false);
                }
                if( req.upload.addEventListener)
                {
                    req.upload.addEventListener("progress", function(evt)
                    {
                        that.progressUpload(evt);
                    },false);
                }
            }
            return req;
        }
    });
        
	var wdf = win.wdf = 
	{
		/* see http://api.jquery.com/jQuery.Callbacks/ */
		ready: $.Callbacks('unique memory'),
		ajaxReady: $.Callbacks('unique'),  // fires without args, just notifier
		ajaxError: $.Callbacks('unique'),  // fires with args (XmlHttpRequest object, TextStatus, ResponseText)
		exception: $.Callbacks('unique'),  // fires with string arg containing the message
		
		arg: function(name,url)
		{
            if( !url ) url = location.search;
            url = url.split('?',2).pop();
            
            if( typeof(URLSearchParams) === 'undefined' )
            {
                var tmp, items = url.split("&");
                for (var i = 0; i < items.length; i++)
                {
                    tmp = items[i].split("=",2);
                    if( tmp[0] == name )
                        return tmp[1];
                }
                return null;
            }
            return (new URLSearchParams(url)).get(name);
		},
        
        args: function(url)
		{
            if( !url ) url = location.search;
            url = url.split('?',2).pop();
            var r = {};
            
            if( typeof(URLSearchParams) === 'undefined' )
            {
                var tmp, items = url.split("&");
                for (var i = 0; i < items.length; i++)
                {
                    tmp = items[i].split("=",2);
                    r[tmp[0]] = tmp[1];
                }
                return r;
            }
            (new URLSearchParams(url)).forEach(function(val,name)
            {
                r[name] = val;
            });
			    
            return r;
		},
		
		validateHref: function(href)
		{
			if( this.settings.rewrite || typeof href != 'string' || href.match(/\?wdf_route/) )
				return href;
            if( this.settings.site_root != href.substr(0,this.settings.site_root.length) )
                return href;
            
			href = href.substr(this.settings.site_root.length);
			var parts = href.split("/");
			var url_path = this.settings.site_root + '?wdf_route='+encodeURIComponent((parts[0]||'')+"/"+(parts[1]||'')+"/");
			if( parts.length > 2 )
			{
				parts.shift(); parts.shift();
				url_path += "&"+parts.join("/");
			}
			return url_path;
		},
		
		setCallbackDefault: function(name, func)
		{
			wdf[name].empty().add( func );
			wdf[name]._add = wdf[name].add;
			wdf[name].add = function( fn ){ wdf[name].empty()._add(fn); wdf[name].add = wdf[name]._add; delete(wdf[name]._add); };
		},
		
		init: function(settings)
		{
			// prepare settings object
			settings.route = location.href.substr(settings.site_root.length);
			settings.rewrite = (typeof(settings.rewrite)!='undefined')
                    ?settings.rewrite:(wdf.arg('wdf_route')===null);
			var route = (settings.rewrite ? settings.route : this.arg('wdf_route')) || '';
			if( route.indexOf("?") != -1)
				route = route.substr(0, route.indexOf("?"));
			settings.route = route.split("/");
			settings.controller = settings.route[0] || '~';
			settings.method = settings.route[1] || '';
			settings.route = settings.rewrite
				?settings.controller+"/"+settings.method+"/"
				:'?wdf_route='+encodeURIComponent(this.arg('wdf_route'));
			settings.url_path = settings.site_root + settings.route;
			settings.focus_first_input = (settings.focus_first_input === undefined)?true:settings.focus_first_input;
			settings.ajax_include_credentials = (settings.ajax_include_credentials === undefined)?false:settings.ajax_include_credentials;
			
			// Init
			this.settings = settings;
			this.request_id = settings.request_id;
            
			this.private.initLogging();
            if( !settings.skip_ajax_handling )
                this.private.initAjax(settings.skip_dependency_loading);
			
            wdf.get = this.private.ajax_function('get');
            wdf.post = this.private.ajax_function('post');
			
			// Shortcuts for current controller 
			this.controller = 
            {
                get: function( handler, data, callback )
				{
					return wdf.get(wdf.settings.controller+'/'+handler,data,callback);
				},
                post: function( handler, data, callback )
				{
					return wdf.post(wdf.settings.controller+'/'+handler,data,callback);
				},
                redirect: function(handler, data)
                {
                    return wdf.redirect(wdf.settings.controller+'/'+handler,data);
                }
            };

			// Focus the first visible input on the page (or after the hash)
			if( this.settings.focus_first_input )
			{
                $(function() {
                    if( location.hash && $('a[name="'+location.hash.replace(/#/,'')+'"]').length > 0 )
                    {
                        var anchor = $("a[name='"+location.hash.replace(/#/,'')+"']");
                        var input = anchor.parentsUntil('*:has(input:text)').parent().filter(':not([readonly])').find('input:text:first');
                        if( input.length > 0 && anchor.position().top < input.position().top )
                            input.focus().select();
                    }
                    else
                    {
                        $('form').find('input[type="text"]:not(.hasDatepicker),input[type="email"],input[type="password"],textarea,select').filter(':not([readonly])').filter(':visible:first').focus().select();
                    }
                });
			}
			
            this.resetPing();
			this.setCallbackDefault('exception', function(msg){ alert(msg); });
            
            if( !this.settings.rewrite ) // for now only for non-rewrite environments, theoretically this is generic
            {
                // Edit GET forms: move args from their actions to hidden inputs
                $('form[action*="?"]').each(function()
                {
                    if( $(this).get(0).method.toLowerCase() == 'post' )
                        return;
                    var args = wdf.args($(this).attr('action'));
                    for(var i in args)
                        $("<input type='hidden' name='"+i+"'/>").val(args[i]).prependTo(this);
                    var a = $(this).attr('action').split('?');
                    $(this).attr('action',a[0]);
                });
            }
            
			this.ready.fire();
		},
		
		resetPing: function()
		{
            if( wdf.settings.no_ping )
                return;
			if( wdf.ping_timer ) clearTimeout(wdf.ping_timer);
			wdf.ping_timer = setTimeout(function()
			{
				wdf.get('',{PING:wdf.request_id}, function(){ wdf.resetPing(); });
			},wdf.settings.ping_time || 60000);
		},

		reloadWithoutArgs: function()
		{
			this.redirect(this.settings.url_path);
		},
		
		redirect: function(href,data)
		{
			if( typeof href == 'object' )
			{
				data = href;
				href = this.settings.url_path;
			}
			href = this.validateHref(href);
			
			if( !href.match(/\/\//) )
				href = this.settings.site_root + href;
			
			if( typeof data == 'object' )
			{
				var cleaned = {};
				for(var i in data)
					if( i.substring(0,3) != 'ui-' )
						cleaned[i] = data[i];
				href += (this.settings.rewrite?"?":"&")+$.param(cleaned);
			}
			
			if( location.href == href )
				location.reload();
			else
            {
                if(location.hash && (location.hash != '#'))
                    location.hash = '';
				location.href = href;
                if(location.hash && (location.hash != '#'))
                    location.reload();
            }
		},
		
        private:
        {
            ajax_function: function(name)
            {
                return function( controller, data, callback )
				{
					var url = wdf.settings.site_root;
					if( typeof controller === "string" )
                    {
                        if( controller.match(/^(http:|https:|)\/\//) )
                            url = controller;
                        else
                            url += controller;
                    }
					else
						url += $(controller).attr('id');
					url = wdf.validateHref(url);
					return $[name](url, data, callback);
				};
            },
            initAjax: function(skip_dependency_loading)
            {
                if(!wdf.private.original_ajax)
                    wdf.private.original_ajax = $.ajax;
                $.extend({
                    ajax: function( s )
                    {
                        try
                        {
                            // reset pinger if the ajax url is the page root, not if we ajax to other sites
                            if(s.url.indexOf(wdf.settings.site_root) === 0)
                                wdf.resetPing();
                        }
                        catch(ex)
                        {
                            wdf.resetPing();
                        }
                        if(wdf.settings.ajax_include_credentials)
                            s.xhrFields = { withCredentials: true };

                        if( !s.data )
                            s.data = {};
                        else if( $.isArray(s.data) )
                        {
                            var tmp = {};
                            for(var i=0; i<s.data.length; i++)
                                tmp[s.data[i].name] = s.data[i].value;
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
                            return wdf.private.original_ajax(s);

                        if( s.data && s.data.PING )
                            return wdf.private.original_ajax(s);

                        if( s.success )
                            s.original_success = s.success;
                        s.original_dataType = s.dataType;
                        s.dataType = 'json';

                        s.success = function(json_result,status)
                        {
                            if( json_result )
                            {
                                var loading = [];
                                var head = document.getElementsByTagName("head")[0];
                                if( !skip_dependency_loading && json_result.dep_css )
                                {
                                    for( var i in json_result.dep_css )
                                    {
                                        var css = json_result.dep_css[i];
                                        if( !css || $('link[data-key=\''+i+'\']').length > 0 )
                                            continue;
                                        var fileref = document.createElement("link")
                                        fileref.setAttribute("rel", "stylesheet");
                                        fileref.setAttribute("type", "text/css");
                                        fileref.setAttribute("data-key", i);
                                        fileref.setAttribute("href", css);
                                        head.appendChild(fileref);
                                    }
                                }

                                if( !skip_dependency_loading && json_result.dep_js )
                                {
                                    for( var i in json_result.dep_js )
                                    {
                                        var js = json_result.dep_js[i];
                                        if( !js || $('script[data-key=\''+i+'\']').length > 0 )
                                            continue;
                                        loading.push( new Promise( function(resolve)
                                        {
                                            var script = document.createElement("script");
                                            script.setAttribute("type", "text/javascript");
                                            script.setAttribute("data-key", i);
                                            script.src = js;
                                            script.addEventListener("load", resolve, false);
                                            head.appendChild(script);
                                        }));
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
                                var wp = ajax_obj.wait();
                                Promise.all(loading||[]).finally(function()
                                {
                                    if( s.original_success )
                                        s.original_success(param, status, ajax_obj);
                                    else if( param )
                                        $('body').append(param);
                                    
                                    if( s.waitPromiseCounter == 1 )
                                        wp.done();
                                });
                            }
                        };

                        s.error = function(xhr, st, et)
                        {
                            // Mantis #6390: Sign up error with checkemail
                            if( (st=="error" && !et) || et=='abort' )
                                return;
                            wdf.error("ERROR calling " + s.url + ": " + st,xhr.responseText);
                            wdf.ajaxError.fire(xhr,st,xhr.responseText);
                        };

                        var ajax_obj = wdf.private.original_ajax(s);
                        ajax_obj.always( function(){ if( !s.waitPromise ) wdf.ajaxReady.fire(); } );
                        ajax_obj.wait = function()
                        {
                            if( !s.waitPromise )
                            {
                                s.waitPromise = wdf.wait();
                                s.waitPromise.finally(wdf.ajaxReady.fire);
                            }
                            s.waitPromiseCounter = s.waitPromiseCounter?(s.waitPromiseCounter+1):1;
                            return s.waitPromise;
                        };
                        return ajax_obj;
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
                var perform_logging = function(severity,data)
                {
                    if( wdf.settings.log_to_console )
                    {
                        if( typeof console != 'undefined' && typeof console[severity] != 'undefined' )
                            console[severity].apply(console,data);
                    }
                };
                wdf.log = function(){ perform_logging('log',arguments); };
                wdf.debug = function(){ perform_logging('debug',arguments); };
                wdf.warn = function(){ perform_logging('warn',arguments); };
                wdf.error = function(){ perform_logging('error',arguments); };
                wdf.info = function(){ perform_logging('info',arguments); };
            }
        },
		
		showScrollListLoadAnim: function()
		{
			$('#scrollloader_overlay_anim').fadeIn();
		},
		
		resetScrollListLoader: function()
		{
			wdf.initScrollListLoader();
		},
		
		scrollListLoaderHref: false,
		scrollListLoaderContainer: false,
		scrollListLoaderOffset: false,
		initScrollListLoader: function(href,target_container,offset)
		{
			if( href ) wdf.scrollListLoaderHref = this.validateHref(href);
			wdf.scrollListLoaderContainer = target_container || wdf.scrollListLoaderContainer || 'body';
			wdf.scrollListLoaderOffset = offset || 1;
			
			var trigger = $('#scrollloader_overlay_anim');
			if( trigger.length === 0 )
				trigger = $('<div/>').attr('id', 'scrollloader_overlay_anim').addClass('wdf_overlay_anim loadMoreContent_removable_trigger').insertAfter(wdf.scrollListLoaderContainer);

			var scroll_handler = function(e)
			{
                if( ($(win).scrollTop() + $(win).height()) < (trigger.position().top + trigger.height()) )
					return;
				
				wdf.showScrollListLoadAnim();
				$(win).unbind('scroll.loadMoreContent', scroll_handler);
				wdf.get(wdf.scrollListLoaderHref,{offset:wdf.scrollListLoaderOffset},function(result)
				{
					if( typeof(result) != 'string' || result == "" )
						return;
                    
					wdf.scrollListLoaderOffset++;
					$(wdf.scrollListLoaderContainer).append(result);
					$(win).unbind('scroll.loadMoreContent', scroll_handler).bind('scroll.loadMoreContent', scroll_handler);
					
                    if( ($(win).scrollTop() + $(win).height()) >= (trigger.position().top + trigger.height()) )
                        scroll_handler();		// keep loading until it fills the page
				});
            };
			$(win).bind('scroll.loadMoreContent', scroll_handler);
			scroll_handler();		// load more content if page not filled yet
		},
		
		stopScrollListLoader: function()
		{
			var trigger = $('#scrollloader_overlay_anim');
			if( trigger.length === 0 )
				trigger = $('.loadMoreContent_removable_trigger');
			trigger.fadeOut();
		},
        
        whenAvailable: function(name, callback)
        {
            win.setTimeout(function()
            {
                if (win[name])
                    callback(win[name]);
                else
                    win.setTimeout(arguments.callee, 10);
            }, 10);
        },
        
        getScrollParent: function(node)
        {
            if( node == null )
                return null;
            if( node.scrollHeight > node.clientHeight )
                return node;
            else
                return wdf.getScrollParent(node.parentNode);
        },
        
        isScrolledIntoView: function (elem)
        {
            wdf.warn("wdf.isScrolledIntoView is deprecated, use $(elem).isScrolledIntoView() instead.");
            return $(elem).isScrolledIntoView();
        },
        
        wait: function(timeout)
        {
            var res = new Promise(function(resolve,reject)
            {
                var wait = function()
                {
                    if( res._isDone ) 
                        return resolve();
                    setTimeout(wait, 50);
                };
                setTimeout(wait, 50);
            });
            res.done = function(){ res._isDone = true; }
            if( timeout )
                setTimeout(res.done,timeout);
            return res;
        },
        
        defer: function(callback,delay)
        {
            var context = win;
            if( typeof callback != "function" )
            {
                context = callback[0];
                callback = callback[1];
            }
            if( typeof callback == "string" )
                callback = context[callback];
                
            var res = wdf.wait(delay||1);
            if( (delay||0)>0 )
                res.done = function(){};
            res.prms = [];
            res.finally(function(){callback.apply(context,res.prms)});
            res.args = function()
            {
                res.prms = (arguments.length === 1 ? [arguments[0]] : Array.apply(null, arguments));
                res.done(); 
                return res;
            };
            return res;
        },
        
        processCallback: function()
        {
            var args = (arguments.length === 1 ? [arguments[0]] : Array.apply(null, arguments));
            
            var callback;
            if( args.length > 0 )
                callback = args.slice();
            
            if( !callback ) return;
            if( typeof callback == 'function' )
                return callback(args);
            if( (callback instanceof Promise) && (typeof callback.done == 'function') )
                callback.done(args);
        }
	};
	
	if( typeof win.Debug != "function" )
	{
		win.Debug = function()
		{
			wdf.debug("Deprecated debug function called! Use wdf.debug() instead.");
		};
	}
	
	$.fn.enumAttr = function(attr_name)
	{
		var attr = [];
		this.each( function(){ if( $(this).attr(attr_name) ) attr.push($(this).attr(attr_name)); } );
		return attr;
	};
	
	$.fn.overlay = function(method,callback)
	{
        if( !callback && typeof method == 'function' )
        {
            callback = method;
            method = false;
        }
		return this.each(function()
		{
            var elem = $(this);
            
			if( method === 'remove' )
			{
                elem.removeData('resize_wdf_overlay');
                var ol = $('.wdf_overlay',this);
				ol.fadeOut('fast',function()
                {
                    wdf.processCallback(callback);
                    ol.remove();
                });
				return;
			}
			var isTab = elem.is('.table'),
                topleft = elem, bottomright = elem;
                
            if( isTab )
            {
                if( elem.prev('.pager').length>0 )
                    topleft = elem.prev('.pager');
                if( elem.next('.pager').length>0 )
                    bottomright = elem.next('.pager');
            }
            
            var ol = $('<div class="wdf_overlay"/>')
                .append('<div class="lds-ripple"><div></div><div></div></div>')
				.appendTo(elem).show()
                .css({display:'none'})
            	.click( function(e){ e.preventDefault(); e.stopPropagation(); return false;} )
                .fadeIn('fast',function()
                {
                    wdf.processCallback(callback);
                });
            
            var resizer = function()
            {
                if( !elem.data('resize_wdf_overlay') )
                    return;
                var e = elem.get(0).getBoundingClientRect(),
                    offset = elem.offsetParent().offset(),
                    tl = topleft.get(0).getBoundingClientRect(),
                    br = bottomright.get(0).getBoundingClientRect(),
                    rect = {
                        left: tl.left + win.pageXOffset - offset.left, 
                        top: tl.top + win.pageYOffset - offset.top,
                        width: Math.max(e.right,tl.right,br.right) - Math.min(e.left,tl.left,br.left),
                        height: Math.max(e.bottom,tl.bottom,br.bottom) - Math.min(e.top,tl.top,br.top)
                    };
                if( rect.top < 0 )
                {
                    rect.height += rect.top;
                    rect.top = 0;
                    rect.height = Math.min(rect.height,$(win).height());
                }
                if( rect.left < 0 )
                {
                    rect.width += rect.left;
                    rect.left = 0;
                    rect.width = Math.min(rect.width,$(win).width());
                }
                
                ol.css(rect);
                setTimeout(resizer,100);
            };
            elem.data('resize_wdf_overlay',true);
            resizer();
		});
	};
	
    $.fn.posFrom = function(elem,xOffset,yOffset)
	{
        var e = $(elem).get(0), r = e?e.getBoundingClientRect():{left:0,top:0};
		return this.each(function()
		{
			$(this).css({left: r.left+win.pageXOffset+(xOffset||0), top: r.top+win.pageYOffset+(yOffset||0)});
		});
	};
    
	$.fn.sizeFrom = function(elem,add_width,add_height)
	{
		return this.each(function()
		{
			$(this).width( elem.width() + (add_width||0) ).height( elem.height() + (add_height||0) );
		});
	};
    
    $.fn.removeDialog = function()
    {
        try{ $(this).dialog("close"); }catch(noop){}
        $(this).remove();
    };
    
    $.fn.isScrolledIntoView = function()
    {
        var docViewTop = $(win).scrollTop();
        var docViewBottom = docViewTop + $(win).height();
        var elemTop = $(this).offset().top;
        return ((elemTop <= docViewBottom) && (elemTop >= docViewTop));
    };

})(window,jQuery);

