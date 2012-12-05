/*
 * Center 1.0
 *
 * Copyright (c) 2007 Andreas Lagerkvist (exscale.se)
 */
jQuery.fn.center = function() {
	// Always return each...
	return this.each(function() {
		var t = jQuery(this);

		// Set position to other than 'static' so element shrink-wraps and width/height is calculated properly
		t.css({position: 'fixed'});

		// Why are there no jQuery.fn.outerWidth/Height:s?
		var w = t.width(),
			h = t.height(),
			lrPadding = parseInt(t.css('paddingLeft'), 10) + parseInt(t.css('paddingRight'), 10),
			lrBorder = parseInt(t.css('borderLeftWidth'), 10) + parseInt(t.css('borderRightWidth'), 10),
			tbPadding = parseInt(t.css('paddingTop'), 10) + parseInt(t.css('paddingBottom'), 10),
			tbBorder = parseInt(t.css('borderTopWidth'), 10) + parseInt(t.css('borderBottomWidth'), 10),
			leftMargin = (w + lrPadding + lrBorder) / 2;
			topMargin = (h + tbPadding + tbBorder) / 2;

		t.css({
			position: 'fixed',
			left: '50%',
			top: '30%',
			marginLeft: '-' +leftMargin +'px',
			marginTop: '-' +topMargin +'px',
			zIndex: '3001'
		});

		/* Use this code if you care about IE<7, this requires the dimensions plug-in tho
		// Calculate left and top pos values
		var leftPos = (jQuery(window).width() - jQuery(this).outerWidth()) / 2 + jQuery(window).scrollLeft(),
			topPos = (jQuery(window).height() - jQuery(this).outerHeight()) / 2 + jQuery(window).scrollTop();

		// Make sure element is not out of bounds
		leftPos = (leftPos < 0) ? 0 : leftPos;
		topPos = (topPos < 0) ? 0 : topPos;

		jQuery(this).css({left: leftPos +'px', top: topPos +'px', zIndex: '1000'});
		*/
	});
};
/*
// If dimensions plug-in isn't available
// Why is there no jQuery.fn.outerWidth bundled with jQuery?
if(!jQuery.fn.outerWidth) {
	jQuery.fn.outerWidth = function() {
		var t = $(this[0]),
			w = t.width(),
			lrPadding = parseInt(t.css('paddingLeft'), 10) + parseInt(t.css('paddingRight'), 10),
			lrBorder = parseInt(t.css('borderLeftWidth'), 10) + parseInt(t.css('borderRightWidth'), 10);

		return w + lrPadding + lrBorder;
	};
}
if(!jQuery.fn.outerHeight) {
	jQuery.fn.outerHeight = function() {
		return this.each(function() {
			var t = $(this)[0],
				h = t.height(),
				tbPadding = parseInt(t.css('paddingTop'), 10) + parseInt(t.css('paddingBottom'), 10),
				tbBorder = parseInt(t.css('borderTopWidth'), 10) + parseInt(t.css('borderBottomWidth'), 10);

			return h + tbPadding + tbBorder;
		});
	};
}
*/