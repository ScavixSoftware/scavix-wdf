
(function( $ ){

	var settings = {};
	
	$.tenfoot = function( options )
	{  
		settings = $.extend( {
			'current_class': 'focused',
			'selectables'  : 'a, input, button'
		}, options);
		
		tenfoot_init();
		
		return this;
	};
	
	var tenfoot_init = function()
	{
		$(settings['selectables'])
			.each(function()
			{
				tenfoot_nearest('left' , $(this));
				tenfoot_nearest('right', $(this));
				tenfoot_nearest('up'   , $(this));
				tenfoot_nearest('down' , $(this));
			})
			.focus(function()
			{
				$('.'+settings['current_class']).removeClass(settings['current_class']);
				$(this).addClass(settings['current_class']);
			})
			.mouseover(function(){ $(this).focus(); })
			
		$(settings['selectables']+':focus').addClass(settings['current_class']);
			
		$(document).keydown( function(e)
		{
			var elem = $('.'+settings['current_class']);
			if( elem.length == 0 )
				return;
			switch( e.which )
			{
				case 37: if( elem.data('nav-left') ) elem.data('nav-left').focus(); break;
				case 39: if( elem.data('nav-right') ) elem.data('nav-right').focus(); break;
				case 38: if( elem.data('nav-up') ) elem.data('nav-up').focus(); break;
				case 40: if( elem.data('nav-down') ) elem.data('nav-down').focus(); break;
			}
		});
	};
	
	var tenfoot_nearest = function(direction,elem)
	{
		var prop = 'nav-'+direction;
		if( elem.data(prop) ) return;
		
		var from = {}, off = elem.offset(), 
			min_dist = 999999,
			nearest = false;
			
		switch( direction )
		{
			case 'left':  from.x = off.left;                  from.y = off.top + elem.height()/2; break;
			case 'right': from.x = off.left + elem.width();   from.y = off.top + elem.height()/2; break;
			case 'up':    from.x = off.left + elem.width()/2; from.y = off.top;                   break;
			case 'down':  from.x = off.left + elem.width()/2; from.y = off.top + elem.height();   break;
		}
		
		$(settings['selectables']).not(elem).each(function()
		{
			var cur = $(this);
			off = cur.offset();
			switch( direction )
			{
				case 'left':  if( off.left + cur.width() > from.x ) return; break;
				case 'right': if( off.left < from.x ) return; break;
				case 'up':    if( off.top + cur.height() > from.y ) return; break;
				case 'down':  if( off.top < from.y ) return; break;
			}
			off.left += cur.width()/2;
			off.top  += cur.height()/2;
			
			var dx = off.left - from.x;
			var dy = off.top - from.y;
			var dist = Math.sqrt( (dx*dx) + (dy*dy) );
			if( dist < min_dist )
			{
				min_dist = dist;
				nearest = cur;
			}
		});
		
		if( nearest )
			elem.data(prop,nearest);
	};
	
})( jQuery );
