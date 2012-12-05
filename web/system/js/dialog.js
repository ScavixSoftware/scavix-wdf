var dialog_last_opened = false;
var dialog_zIndex = 3000;
var dialogStack = new Array();
var dialogOptions = {modal:true,toTop: true,overlayClass: 'whiteOverlay'};

function dialog_show(id,url,callback)
{
	$("*").not('.thisisabuttondisabler').each( function()
	{
		var zi = $(this).css("z-index");
		if( zi > dialog_zIndex )
			dialog_zIndex = parseInt(zi, 10);
	});
	dialog_zIndex = parseInt(dialog_zIndex) + 100;

    // save for later usage
    if(!dialogStack[id])
    {
        dialogStack[id] = true;
        dialogStack.length ++;
    }

	if( url )
	{
		$.get(url,function(d)
		{
			$('#'+id+'_content').html(d);
			dialog_last_opened = $('#'+id).css({zIndex:dialog_zIndex}).prependTo('body').jqm(dialogOptions).jqmShow();
		});
	}
	else
		dialog_last_opened = $('#'+id).css({zIndex:dialog_zIndex}).prependTo('body').jqm(dialogOptions).jqmShow();

	if( callback )
		callback();
}

function dialog_hide(id)
{
    if(!id && dialog_last_opened[0])
        id = dialog_last_opened[0].id;
    if(dialogStack[id])
    {
        delete(dialogStack[id]);
        dialogStack.length --;
    }

	if( id )
		$('#'+id).jqmHide();
	else if( dialog_last_opened )
		dialog_last_opened.jqmHide();
}

function Message(text,direkt)
{
	if( direkt )
	{
		try
		{
            msg = unescape(text);
			$('#global_alert_dlg_content').html(msg);
			dialog_show('global_alert_dlg');
		}
		catch(ex)
		{
			alert(unescape(text));
		}
	}
	else
		setTimeout("Message('"+escape(text)+"',true);",1);
}

function Confirm(text,okcallback)
{
	var result = confirm(unescape(text));
	if( result && okcallback )
	{
		if( typeof(okcallback) == "function" )
			okcallback();
		else
			eval(unescape(okcallback));
		return;
	}
	if( !okcallback )
		return result;
}

function ShowLoadingMessage(txt)
{
	if(!txt)
		txt = TEXT_LOADING_MESSAGE;
	var progress_dlg = waiting_dlg;
	$("#spWaitDlgMessage").html(txt);
	dialog_show(progress_dlg);
}

function HideLoadingMessage()
{
	var progress_dlg = waiting_dlg;
	dialog_hide(progress_dlg);
}