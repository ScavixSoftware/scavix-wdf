
wdf.ready.add(
	function()
	{
		try{
		$.datepicker.setDefaults($.datepicker.regional['de']);
		$.datepicker.setDefaults({ yearRange: '1900:2100',speed: 'fast' });
		$('input.isadatepicker').attachDatepicker().attr({type:"text"}).removeClass("isadatepicker");
		}catch(ex){}
	}
);