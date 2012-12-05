
$(document).ready( function() {
	controlHandler.prototype.PE_Init = function(overlayimg,loadingimg)
	{
		controlHandler.prototype.OverlayImage = overlayimg;
		controlHandler.prototype.LoadingImage = loadingimg;
	};

	controlHandler.prototype.PE_Disable = function()
	{
		var selector = '#'+$(this.element).attr('id');
		var _container = $(selector);
		var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");
		
		var img = $('<img/>').attr("src",controlHandler.prototype.LoadingImage);
		var disabler = $('#'+disablerid);
		if( disabler.length == 0 )
			disabler = $('<div id='+disablerid+'/>').addClass('ui-widget-overlay').append(img).appendTo(_container.parent());

		_container.resize( function()
		{
			var padding = parseInt( ($(this).height()-img.height()) / 3 );
			disabler.css(
			{
				position: 'absolute',
				left: $(this).position().left,
				top: $(this).position().top,
				width: $(this).width(),
				height: $(this).height() - padding,
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

	controlHandler.prototype.PE_Enable = function()
	{
		var selector = '#'+$(this.element).attr('id');
		var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");
		var disabler = $('#'+disablerid);

		if( disabler.length > 0 )
			disabler.fadeOut(200, function() { $(this).remove(); } );
	};

	controlHandler.prototype.PE_GotoPage = function(pagenumber)
	{
		var data =
		{
			number: pagenumber
		}

		var selector = '#'+$(this.element).attr('id');
		var _container = $(selector);
		var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");

		var img = $('<img/>').attr("src",controlHandler.prototype.LoadingImage);
		var disabler = $('#'+disablerid);
		
		if( disabler.length == 0 )
			disabler = $('<div id='+disablerid+'/>').addClass('ui-widget-overlay').append(img).appendTo(_container.parent());

		_container.resize( function()
		{
			var padding = parseInt( ($(this).height()-img.height()) / 3 );

			disabler.css(
			{
				position: 'absolute',
				left: $(this).position().left,
				top: $(this).position().top,
				width: $(this).width(),
				height: $(this).height() - padding,
				textAlign: 'center',
				paddingTop: padding,
				display: 'none'
			})
		}).unload( function()
		{
			disabler.fadeOut(200, function() { $(this).remove(); } );
		}).resize();
		
		disabler.fadeIn(200, function()
		{
			var maxZ = Math.max.apply(null,$.map($('*'), function(e,n){
				return parseInt($(e).css('z-index'))||1 ;
			}));
			disabler.css({zIndex:maxZ}); 
		});

		var url = document.site_root+$(this.element).attr('id')+"/GotoPage/";
		$.post(url,data,function(d){ _container.replaceWith(d); disabler.fadeOut(200, function() { $(this).remove(); } ); })
	};
});