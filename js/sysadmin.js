
$(document).ready(function()
{
    $('div.navigation a[href="'+document.location.href+'"]').addClass("current");
    
    $('table.new_string input.create').click( function()
    { 
        var term = $(this).data('term');
        var text = $('textarea.'+term).val()||'';
        wdf.controller.post('CreateString',{term:term,text:text},function(d)
        {
            if( d == 'ok' )
            {
                $('table.'+term).fadeOut( function(){ $('table.'+term).remove(); } );
                return;
            }
			$('body').append(d);
        });
    });
    
    $('table.new_string input.delete').click( function()
    { 
        var term = $(this).data('term');
        wdf.controller.post('DeleteString',{term:term},function(d)
        {
            if( d == 'ok' )
            {
                $('table.'+term).fadeOut( function(){ $('table.'+term).remove(); } );
                return;
            }
        });
    });
});