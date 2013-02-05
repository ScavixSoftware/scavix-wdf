
var cultureInfo = null;

wdf.ready.add(function()
{
	if( $('.float_input').length != 0 )
	{
		$.post('/flow/GetCultureInfo', {}, function(d)
		{
			var data = jQuery.parseJSON(d);
			if( data.html )
				cultureInfo = data.html;
			else
				cultureInfo = data;
		});

		$('.float_input').bind('change',function(){
			validateFloat(this.value);
		}).bind('keyup',function(){
			stripNonNumbers(this);
		});
	}
});

function stripNonNumbers(elem)
{
	 elem.value = elem.value.replace(/[^0-9]/g, '');
}

function validateFloat(val)
{
	val.replace(/[^0-9]/g, '');
}

function isFloat(str)
{
    str = str.replace(/^\s+|\s+$/g, '');
    return /^[-+]?[0-9]+(\.[0-9]+)?$/.test(str);
}
