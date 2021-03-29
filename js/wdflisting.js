
var listing_rowclick = function(evt, url)
{
    if (evt.ctrlKey)
    {
        url = wdf.validateHref(url);
        if( !url.match(/\/\//) )
            url = wdf.settings.site_root + url;
        window.open(url).focus();
    }
    else
    {
        wdf.redirect(url);
    }
}

var listing_geticon = function(req)
{
	switch( req )
	{
		case 'col-visible': return '<svg width="16px" aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z" class=""></path></svg>';
		case 'col-hidden': return '<svg width="16px" aria-hidden="true" focusable="false" data-prefix="far" data-icon="circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200z" class=""></path></svg>';
	}
	return'';
};

function init_listings()
{
	$('.listingfilter .go:not(.ui-button)').button();
	
    $('.listing.multiselect .thead .td:first-child input[type="checkbox"][data-multicheckboxname]')
        .off('click.multiselect')
        .on('click.multiselect', function(e)
        {
            var multiselname = $(this).attr('data-multicheckboxname');        
            $('[name="' + multiselname + '"]').prop('checked', $(this).prop('checked'));
            e.stopPropagation();
        });
    
    $('.listing.multiselect .td:first-child, .listing.multiselect .td:first-child td')
        .off('click.multiselect')
        .on('click.multiselect', function(e) { e.stopPropagation(); });

    $(document)
        .off('click.columnstate','.listing .thead .tr .td [data-column-state]')
        .on('click.columnstate','.listing .thead .tr .td [data-column-state]', function(e)
        {
            if( $('.column-state-popup').length )
                return $('.column-state-popup').slideUp(function(){ $(this).remove(); });
            
            $(document).one('click',function(){ $('.column-state-popup').slideUp(function(){ $(this).remove(); }); });
            
            var $listing = $(this).closest('.listing'),
                columns = $(this).data('column-state'), 
                $d = $('<div/>').addClass('column-state-popup').hide(),
                enabled = 0;
            for(var i=0; i<columns.length; i++)
            {
                if((columns[i].label == '') && (i == columns.length-1))
                    continue;
                if( columns[i].visible ) enabled++;
                $('<div style="display:flex; align-items:center"/>').appendTo($d).data('column',columns[i])
                    .append( (columns[i].visible)?listing_geticon('col-visible'):listing_geticon('col-hidden'))
                    .append( '&nbsp;&nbsp;'+columns[i].label )
                    .click(function()
                    {
                        var data = $(this).data('column');
                        if( data.visible && enabled < 2 )
                        {
                            $('.column-state-popup').slideUp(function(){ $(this).remove(); });
                            MessageBox(wdf.getText("ERR_CANNOT_REMOVE_LAST_COLUMN"));
                            return;
                        }
                        var prevPos = $listing.css('position');
                        $listing.css('position', 'relative');
                        $('.table',$listing).addClass('blurred').overlay();
                        $d.remove();
                        wdf.get($listing.attr('id')+'/togglecolumn',{name:data.name},function(d,s,p)
                        {
                            $listing.css('position', prevPos);
                            $('.table',$listing).updateTable(d,p.wait());
                            $listing.animate({'min-height':''},750);
                        });
                    });
            }
            $('<hr/>').css({border:0, borderTop:'1px dotted black', margin: '5px 0px'}).appendTo($d);
            $('<div/>').appendTo($d)
                .append( '<i class="fas fa-eraser"></i> ' )
                .append(wdf.getText("TXT_RESET"))
                .click(function()
                {
                    var prevPos = $listing.css('position');
                    $listing.css('position', 'relative');
                    $('.table',$listing).addClass('blurred').overlay();
                    $d.remove();
                    wdf.get($listing.attr('id')+'/reset',function(d,s,p)
                    {
                        $listing.css('position', prevPos);
                        $('.table',$listing).updateTable(d,p.wait());
                        $listing.animate({'min-height':''},750);
                    });
                });
            $d.appendTo($listing).position({my:'right top', at:'right bottom',of:$(this)}).slideDown();
        });
    
    $('form.listingfilter:not(.listingactivated)').addClass('listingactivated')
        .each(function() { $(':input',this).markValued(); })    
        .submit(function(e)
        {
            e.preventDefault();
            var $form = $(this), data = $form.serialize();
            if( $form.data('reset') )
            {
                $form.data('reset',false);
                data = {reset:1};
            }
            $(':input',$form).markValued();
            $('.go',$form).button('disable');
            $lsts = $form.data('listings').split(',');
            $.each($lsts, function(index, l)
            {
                var $listing = $('#'+l), elem = $('.table',$listing);
                $listing.css('min-height',$listing.height());
                if( elem.length==0 )        
                    elem = $('#'+l+'.table');
                var prevPos = $listing.css('position');
                $listing.css('position', 'relative');
                elem.addClass('blurred').overlay();
                wdf.get(l+'/setfiltervalues',data,function(d,s,p)
                {
                    $('.go',$form).button('enable');
                    elem.updateTable(d,p.wait());
                    $listing.css('position', prevPos);
                    $listing.animate({'min-height':''},750);
                });
            });
        }).find('button[type="reset"]').click(function(e)
        {
            e.preventDefault();
            var $form = $(this).closest('form').data('reset',true);
            $('option',$form).removeAttr('selected');
            $('input[type="text"], input[type="hidden"], select',$form).val('');
            $(':input',$form).markValued();
            $form.submit();
        });
}

jQuery.fn.markValued = function()
{
    return $(this).filter(':input').each(function()
    {
        if( $(this).val() )
            $(this).addClass('hasvalue')
        else
            $(this).removeClass('hasvalue')
    });
};

wdf.ready.add(init_listings);
wdf.ajaxReady.add(init_listings);

$.fn.reload = function()
{
	var self = $(this), tab = self.find('.table');
    var prevPos = self.css('position');
    self.css('position', 'relative');
    tab.addClass('blurred');
    tab.overlay();
    
    wdf.get(self.attr('id')+'/reload',(d,s,p) =>
    {
        tab.updateTable(d,p.wait());
        tab.removeClass('blurred');
        self.css('position', prevPos);
    });
};

$.fn.gotoPage = function(n)
{
	var self = $(this).closest('.listing'), tab = self.find('.table');
    var prevPos = self.css('position');
    self.css('position', 'relative');
    tab.addClass('blurred');
    tab.overlay();
    
    wdf.post(self.attr('id')+'/gotopage',{number:n},(d,s,p) =>
    {
        tab.updateTable(d,p.wait());
//        tab.removeClass('blurred');
        self.css('position', prevPos);
    });

};