
$(document).ready( function() {
(function($j)
{
	controlHandler.prototype.AE_Init = function(overlayimg,loadingimg)
	{
//		Debug("AE_Init");
		controlHandler.prototype.OverlayImage = overlayimg;
		controlHandler.prototype.LoadingImage = loadingimg;
	};

	controlHandler.prototype.AE_Disable = function()
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

	controlHandler.prototype.AE_Enable = function()
	{
		var selector = '#'+$j(this.element).attr('id');
		var disablerid = 'disabler_'+selector.replace(/[^0-9a-z]/gi,"");
		var disabler = $('#'+disablerid);

		if( disabler.length > 0 )
			disabler.fadeOut(200, function() { $(this).remove(); } );
	};

	controlHandler.prototype.AE_ReloadPage = function()
	{
//		Debug('controlHandler.prototype.AE_ReloadPage');
		var data =
		{
//			reload:1
		}

		var selector = '#'+$j(this.element).attr('id');
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
		disabler.fadeIn(200);

		var url = document.site_root+_container.attr('id')+"/ReloadPage/";
		$j.post(url,data,function(d){ 
			_container.replaceWith(d);
			disabler.fadeOut(200, function() { $(this).remove(); } );
		});
	};

	controlHandler.prototype.AE_AutoReload = function()
	{
		var selector = '#'+$j(this.element).attr('id');

		$(selector).control('AE_ReloadPage');
		setTimeout(function(){$(selector).control('AE_AutoReload')},30000);
	}

})(jQuery);

});