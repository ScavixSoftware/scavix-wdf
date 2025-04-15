(function( $ )
{
	$.extend( $.ui.autocomplete.prototype,
	{
		_renderItem: function( ul, item)
		{
			var content = this.options.renderItem
				?this.options.renderItem.call(this,ul,item)
				:(item.html?item.html:(item.label?item.label:item.value));
			var ret = $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append($( "<a></a>" ).html(content))
                .appendTo(ul);
            if ((typeof (item.disabled) != "undefined") && item.disabled)
                ret.addClass("ui-state-disabled");
            return ret;
		}
	});
})(jQuery);