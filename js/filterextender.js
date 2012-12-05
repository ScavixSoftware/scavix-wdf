
$(document).ready( function() {
(function($j)
{
	controlHandler.prototype.FE_Init = function(overlayimg,loadingimg)
	{
		//console.log("PE_Init");
		controlHandler.prototype.OverlayImage = overlayimg;
		controlHandler.prototype.LoadingImage = loadingimg;
	};

	controlHandler.prototype.FE_Disable = function()
	{
		var selector = '#'+$j(this.element).attr('id');
		var _container = $(selector);
		var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");

		var img = $('<img/>').attr("src",controlHandler.prototype.LoadingImage);
		var disabler = $('#'+disablerid);
		if( disabler.length == 0 )
			disabler = $('<div id='+disablerid+'/>').append(img).appendTo('body');
		var padding = parseInt( (_container.height()-img.height()) / 2 );

		_container.resize( function()
		{
			var padding = parseInt( ($(this).height()-img.height()) / 2 );
			disabler.css(
			{
				position: 'absolute',
				left: $(this).offset().left,
				top: $(this).offset().top,
				width: $(this).width(),
				height: $(this).height() - padding,
				backgroundImage: 'url('+controlHandler.prototype.OverlayImage+')',
				textAlign: 'center',
				paddingTop: padding,
                display: 'none'
			})
		}).unload( function()
		{
			disabler.fadeOut(200, function() { $(this).remove(); } );
		}).resize();

		disabler.fadeIn(200);
	};

	controlHandler.prototype.FE_Enable = function()
	{
		var selector = '#'+$j(this.element).attr('id');
		var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");
		var disabler = $('#'+disablerid);

		if( disabler.length > 0 )
			disabler.fadeOut(200, function() { $(this).remove(); } );
	};

	controlHandler.prototype.FE_Filter = function(filter)
	{
		var fe_elem = $j(".filterextender",this.element);
		var current_filter = fe_elem.attr('title');
		if( filter.length > 0 && filter.length < 3 )
			return;
		if( filter == current_filter )
			return;
		fe_elem.attr('title',filter);
		
		var data =
		{
			load: $j(this.element).attr('id'),
			event: 'FilterData',
			filter: filter
		}

		var selector = '#'+data.load;
		var _container = $(selector);
		var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");

		var img = $('<img/>').attr("src",controlHandler.prototype.LoadingImage);
		var disabler = $('#'+disablerid);
		if( disabler.length == 0 )
			disabler = $('<div id='+disablerid+'/>').append(img).appendTo('body');

		_container.resize( function()
		{
			var padding = parseInt( ($(this).height()-img.height()) / 2 );
			disabler.css(
			{
				position: 'absolute',
				left: $(this).offset().left,
				top: $(this).offset().top,
				width: $(this).width(),
				height: $(this).height() - padding,
				backgroundImage: 'url('+controlHandler.prototype.OverlayImage+')',
				textAlign: 'center',
				paddingTop: padding,
                display: 'none'
			})
		}).unload( function()
		{
			disabler.fadeOut(200, function() { $(this).remove(); } );
		}).resize();		

		if( fe_elem.attr('current_call') )
			clearTimeout(fe_elem.attr('current_call'));

		var cc = setTimeout( function($j,data,elem,fe_elem)
		{
			var selector = '#'+data.load;
			var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");
			var disabler = $('#'+disablerid);

			disabler.fadeIn(200);
			fe_elem.css("backgound-color","yellow").attr("readonly",true).blur();

			var url = document.site_root + data.load + "/" + data.event + "/";
			data = { filter:filter };
			$j.post(url,data,function(d){ _container.replaceWith(d); disabler.fadeOut(200, function() { $(elem).remove(); } ); })
		},1000,$j,data,this,fe_elem);
		fe_elem.attr('current_call',cc);
	};

})(jQuery);

});