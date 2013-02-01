(function($) {

$.widget("ui.table",
{
	version: "0.0.1",
	options: {},
	_create: function()
	{
		var self = this.element, o = this.options, current_row;
		
		var actions = $('.ui-table-actions',self);
		if( actions.length > 0 )
		{
			var w = 0;
			actions
				.children()
				.click(function()
				{
					wdf.post(self.attr('id')+'/OnActionClicked',
						{action:$(this).data('action'),row:current_row.attr('id')},
						function(d){ $('body').append(d); });
				})
				.hover( function(){ $(this).toggleClass('ui-state-hover') } )
				.each(function(){w+=$(this).width();});
			actions.width(w);
			
			var on = function()
			{
				current_row = $(this); 
				$('.ui-table-actions',self).show()
					.position({my:'right center',at:'right center',of:current_row});
			};
			var off = function(){ $('.ui-table-actions',self).hide(); };
			
			$('.tbody .tr',self).bind('mouseenter click',on);
			$('.caption, .thead, .tfoot',self).bind('mouseenter',off);
			self.bind('mouseleave',off);
				
			$('.tbody .tr .td:last-child',self).css('padding-right',w)
		}
	}
});

})(jQuery);
