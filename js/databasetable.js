(function($) {

$.fn.gotoPage = function(n)
{
	var self = this;
	wdf.post(self.attr('id')+'/GotoPage',{number:n},function(d){ self.replaceWith(d); });
};

})(jQuery);
