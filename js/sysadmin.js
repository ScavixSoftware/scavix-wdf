/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

var auto_id = 0;


var init_sysadmin = function()
{
    wdf.setCallbackDefault('exception', function(msg) 
    { 
        alert(msg);
    });
    
    hideLoaderOverlay();
    
//	$('input[type="button"], input[type="submit"], input[type="reset"], button:not(.stripe-button-el)').button();
	$('input,select,textarea','.field').addClass('ui-corner-all');
        
//    $('input[type="submit"]:not(.activated), button[type="submit"]:not(.activated):not(.stripe-button-el)').addClass('activated').click( function(e)
//    {
//       e.preventDefault();
//       $(this).button("disable");
//       $(this).closest('form').submit();
//    });

    $('form:not(.activated)').addClass('activated').submit(function(e)
    {
       showLoaderOverlay();
    });
    
    $('.side-menu .menu a[href^="http"]:not([target])').click( function() { showLoaderOverlay(); } );
    
    if(!wdf.orig_redirect)
    {
        wdf.orig_redirect = wdf.redirect;
        wdf.redirect = function(href,data)
        {
            showLoaderOverlay();
            setTimeout(hideLoaderOverlay, 5000);
            wdf.orig_redirect(href,data);
        }
    }
};

wdf.ready.add(function()
{
//    $('div.navigation a[href="'+document.location.href+'"]').addClass("current");

    $('#page_header .hamburger').click(function() {
        if($('.side-menu').is(':visible'))
            $('.side-menu').slideUp();
        else
            $('.side-menu').slideDown();
    });
    
    $('.menu ul li.hassubmenu>a').click(function() 
    {
        $li = $(this).closest('li');
        $ul = $(this).closest('ul');
        if(!$ul.hasClass('focused'))
        {
            $('.arrow', $li).html('<i class="fas fa-angle-down"></i>');
            $('.dropdown:eq(0)', $li).slideDown( function() {
                $ul.addClass('focused').addClass('open');
            });
        }
        else
        {
            $('.arrow', $li).html('<i class="fas fa-angle-right"></i>');
            $('.dropdown:eq(0)', $li).slideUp( function() {
                $ul.removeClass('focused').removeClass('open');
            });
        }
    });
    
    $('table.new_string input.create').click( function()
    { 
        var term = $(this).data('term') || $(this).closest('.new_string').find('[name="term"]').val();
        var text = encodeURIComponent( $('textarea.'+term).val() || ($(this).closest('.new_string').find('textarea').val()||'') );

        wdf.controller.post('CreateString',{term:term,text:text},function()
        {
			if( $('table.'+term).length > 0 )
				$('table.'+term).fadeOut( function(){ $('table.'+term).remove(); } );
			else
				wdf.reloadWithoutArgs();
        });
    });
    
    $('table.new_string input.delete, table.new_string button.delete').click( function()
    { 
        var term = $(this).data('term');
        wdf.controller.post('DeleteString',{term:term},function()
        {
            $('table.'+term).fadeOut( function(){ $('table.'+term).remove(); } );
        });
    });
	
	$('.translations input.save, .translations button.save').click( function()
    {
        showLoaderOverlay();
		var btn = $(this).attr('disabled',true);
		var lang = btn.data('lang') || $('.translations').data('lang');
        var term = btn.data('term');
		var text = encodeURIComponent( btn.closest('.tr').find('textarea').val()||'');
        wdf.controller.post('SaveString',{lang:lang,term:term,text:text},function()
		{
			btn.val('Saved').addClass('ok');
            hideLoaderOverlay();
			setTimeout(function(){ btn.removeAttr('disabled').val('Save').removeClass('ok err').focus(); },1000);
		});
    });

  	$('.translations input.copy, .translations button.copy').click( function()
    { 
        $(this).closest('.tr').find('textarea').val( JSON.parse($(this).data('def')) );
    });

	$('.translations .rename').not('.activated').addClass('activated').click( function()
    { 
        wdf.controller.post('Rename',{term:$(this).data('term')},function(d){ console.log('YO');$('body').append(d); });
    });
	
	$('.translations .remove').not('.activated').addClass('activated').click( function()
    { 
        wdf.controller.post('Remove',{term:$(this).data('term')});
    });
    
	wdf.exception.add( function(msg){ alert(msg); } );
    
    $.fn.insertAtCaret = function (text) {
        return this.each(function () {
            if (document.selection && this.tagName == 'TEXTAREA') {
                //IE textarea support
                this.focus();
                sel = document.selection.createRange();
                sel.text = text;
                this.focus();
            } else if (this.selectionStart || this.selectionStart == '0') {
                //MOZILLA/NETSCAPE support
                startPos = this.selectionStart;
                endPos = this.selectionEnd;
                scrollTop = this.scrollTop;
                this.value = this.value.substring(0, startPos) + text + this.value.substring(endPos, this.value.length);
                this.focus();
                this.selectionStart = startPos + text.length;
                this.selectionEnd = startPos + text.length;
                this.scrollTop = scrollTop;
            } else {
                // IE input[type=text] and other browsers
                this.value += text;
                this.focus();
                this.value = this.value; // forces cursor to end
            }
        });
    };
    
    init_sysadmin();
});

wdf.ajaxReady.add(function()
{
    init_sysadmin();
});


var showLoaderOverlay = function()
{
    if(window.event && window.event.ctrlKey)
        return;
    $('#loaderoverlay').fadeIn('fast', function() { setTimeout(hideLoaderOverlay, 15 * 1000) } );
};

var hideLoaderOverlay = function()
{
    $('#loaderoverlay').fadeOut('fast');
};