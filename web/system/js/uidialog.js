function uidialog_show(id,url)
{
	//Debug("uidialog_show("+id+","+url+")");
	if( url )
	{
		var data = {id: id};
		$.get(url,data,function(d)
		{
			$('body').append(d);
		});
	}
	else
		$('#'+id).dialog('open');
}

function uidialog_hide(id)
{
	$('#'+id).dialog('close');
}