/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
wdf.ready.add(function()
{
    $('div.navigation a[href="'+document.location.href+'"]').addClass("current");
    
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
    
    $('table.new_string input.delete').click( function()
    { 
        var term = $(this).data('term');
        wdf.controller.post('DeleteString',{term:term},function()
        {
            $('table.'+term).fadeOut( function(){ $('table.'+term).remove(); } );
        });
    });
	
	$('.translations input.save').click( function()
    { 
		var btn = $(this).attr('disabled',true);
		var lang = $('.translations').data('lang');
        var term = btn.data('term');
		var text = encodeURIComponent($('textarea.'+term).val()||'');
        wdf.controller.post('SaveString',{lang:lang,term:term,text:text},function()
		{
			btn.val('Saved').addClass('ok');
			setTimeout(function(){ btn.removeAttr('disabled').val('Save').removeClass('ok err').focus(); },1000);
		});
    });

  	$('.translations input.copy').click( function()
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
});