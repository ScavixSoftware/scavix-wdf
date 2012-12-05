
var external_loadings = new Object();

function dt_init(tableid,loading_id)
{
	//console.log("dt_init "+tableid);
	$('#' + tableid + '_overlay').hide();
	$('#' + tableid + ' span.pager a').click( function() {

		dt_load(tableid,$(this).attr('href') + '&load=' + tableid + '&event=gotopage');
		return false;
	});

	dt_loading(tableid,0);
}

/*
function dt_init_from_code(code)
{
	//console.log("dt_init_from_code ");
	$(code).each( function() {
		//console.log(this);
		if( !$(this).attr('class') && $(this).attr('id'))
		{
			//console.log("after load");
			dt_init($(this).attr('id'),'');
			return false;
		}
	});
}
*/

function dt_load(table_id,url)
{
	var tableid = table_id;
	if( tableid.substr(0,1) != '#' )
		tableid = '#' + tableid;
	else
		table_id = tableid.substr(1);

	dt_loading(table_id,1);

	var options = {
		margin:  true ,
		border:  false,
		padding: false,
		scroll:  true ,
		lite:    false
	};
	var offset = {
		width: $(tableid + ' > div > table').width(),
		height: $(tableid + ' > div > table').height()
	};
	$(tableid + ' > table').offset(options, offset);
	$(tableid + '_overlay').css(offset);

	$(tableid).load(
		url,
		function (responseText, textStatus, XMLHttpRequest){
			dt_init(table_id);
		}
	);
}

function dt_loading(tableid,on)
{
	//if( typeof on == 'undefined' ) on = 1;
	if( tableid.substr(0,1) != '#' )
		tableid = '#' + tableid;

	if( on == 0 )
		$(tableid + '_overlay').fadeOut('fast');
	else
		$(tableid + '_overlay').fadeIn('fast');

	$(tableid)
		.parent()
		.each( function() {
			var tabid = $(this).attr('id');
			$('.tabcontrol > ul > li > a')
				.each( function() {
					var href = $(this).attr('href');
					if( href == '#'+tabid )
					{
						if( on == 0 )
							$(this).find('img').fadeOut('fast');
						else
							$(this).find('img').fadeIn('fast');
						return false;
					}
				});
			return false;
		});
}