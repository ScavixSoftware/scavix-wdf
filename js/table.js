(function($) {

$.widget("ui.table",
{
	version: "0.0.1",
	options: {},
	_create: function()
	{
		var self = this.element, o = this.options, current_row;
		
		var actions = $('.ui-table-actions .ui-icon',self);
		if( actions.length > 0 )
		{
			var w = 0;
			$('.ui-table-actions > div',self)
				.hover( function(){ $(this).toggleClass('ui-state-hover'); } )
				.each(function(){ w+=$(this).width(); });
				
			$('.ui-table-actions .ui-icon',self)
				.click(function()
				{
					wdf.post(self.attr('id')+'/OnActionClicked',{action:$(this).data('action'),row:current_row.attr('id')});
				})
				
			$('.ui-table-actions',self).width(w);
			
			var on = function()
			{
				current_row = $(this); 
				$('.ui-table-actions',self).show()
					.position({my:'right center',at:'right-1 center',of:current_row});
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
