
function debugger_min_topleft()
{
	$('a.debugger_hide').hide();
	$('a.debugger_show').show();
	$('div.debugger_lines').slideUp('fast', function() {
		$('div.debugger').width( 250 );
		$('div.debugger').css( {left: 0, top:0} );
	});
}

function debugger_min_topright()
{
	$('a.debugger_hide').hide();
	$('a.debugger_show').show();
	$('div.debugger_lines').slideUp('fast', function() {
		$('div.debugger').width( 250 );
		$('div.debugger').css( {left: $(document).width() - 255, top:0} );
	});
}

function debugger_show()
{
	$('div.debugger').width( $(document).width() - 5 );
	$('div.debugger').css( {left: 0, top:0} );
	$('a.debugger_hide').show();
	$('a.debugger_show').hide();
	$('div.debugger_lines').slideDown('fast');
}
