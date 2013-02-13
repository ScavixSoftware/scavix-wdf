
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
	
	$j.fn.enumAttr = function(attr_name)
	{
		var attr = []
		this.each( function(){ if( $(this).attr(attr_name) ) attr.push($(this).attr(attr_name)); } );
		return attr;
	}

})(jQuery);