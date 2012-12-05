
var tabcontrol_loading_img = '';

function tc_add_tab_from_url(tabcontrolid,title,url)
{
	var title = "<b ondblclick=\"tc_remove_current_tab('"+tabcontrolid+"');\">" + title + "</b>";

	//if( !top.current_tab_index ) top.current_tab_index = 1; else top.current_tab_index++;

	var loading_id = 'logtab_' + top.current_tab_index + '_loading';

	title += "<img id='"+loading_id+"' src='"+tabcontrol_loading_img+"' style='border:0; margin-top:2px;'/>";

	$('#' + tabcontrolid + ' > ul').tabs("add", '#logtab_'+top.current_tab_index, title);
	$('#logtab_' + top.current_tab_index).load(url);
}

function tc_remove_current_tab(tabcontrolid)
{
	var selected_index = -1;
	$('#' + tabcontrolid + ' > ul > li').each( function(i,elem) {

		if( $(elem).hasClass('ui-tabs-selected') )
		{
			selected_index = i;
			return false;
		}
	});

	$('#' + tabcontrolid + ' > ul').tabs("remove",selected_index);
}