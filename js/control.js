
var framework_root_offset = "";
function controlHandler(el)
{
	this.element = el
};


(function($j)
{
	$j.fn.control = function()
	{
		var method = typeof arguments[0] == 'string' && arguments[0];
		var args = method && Array.prototype.slice.call(arguments, 1) || arguments;

		return this.each(function()
		{
			if( method )
			{
				var ch = new controlHandler(this);
				ch[method].apply(ch,args);
			}
		});
	}

	controlHandler.prototype.bindMethod = function(event,handler,method,caller)
	{
		//Debug("bindMethod: "+caller);
		if( event == "autocomplete" )
		{
			//var url = framework_root_offset+"?load="+handler+"&event="+method;
			var url = document.site_root + handler + "/" + method + "/";
			var opts = {
				extraParams: {request_id:document.request_id,caller:caller},
				minChars: 3,
				autoFill: false,
				highlight: false,
				selectFirst: false,
				scroll: true,
				scrollHeight: 300,
				cacheLength:0,
				multiple: false,
				parse: function(row){ return $j.map(eval(row), function(row){ return { data: row, value: unescape(row.value), result: unescape(row.value) } }); },
				formatItem: function(row, i, max, term){ if( row.init ) eval(unescape(row.init)); return unescape(row.html); },
				formatResult: function(row){ return unescape(row.value); }
			};
			$j(this.element).autocomplete(url,opts).result(function(e,row)
			{
				//Debug("autocomplete "+$j(this).attr("id"));
				$j(this).change();
				if( row.select )
					eval(unescape(row.select));
			});
		}
		else
		{
			$j(this.element).bind(event+".method",function()
			{
				var data = {request_id:document.request_id,caller:caller};

				if( event == 'change' )
					data.value = $j('#'+caller).val();

				var url = document.site_root + handler + "/" + method + "/";
				$j.post(url,data,function(){},'script');
			});
		}
	};

	controlHandler.prototype.bindProperty = function(attribute,object,property,caller)
	{
		$j(this.element).bind('change.property',function()
		{
			var val = $j(this).attr(attribute);
			var data = {property:property,value:val,request_id:document.request_id,caller:caller};
			var url = document.site_root + object + "/AssignProperty/";
			$j.post(url,data,function(){},'script');
		});
	};

})(jQuery);