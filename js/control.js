
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

})(jQuery);