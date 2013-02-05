wdf.ready.add( function(){

	$(document).click( function(){
		var dd_id = top.opened_dropdown_id;
		top.opened_dropdown_id = '';
		$(dd_id + '_list').slideUp('fast',function(){
			$(dd_id + '_container').removeClass("dropdown_open");
		});
	});
});

function dropdown_init(dd_id)
{
	//wdf.log("dropdown_init("+dd_id+")");
	dd_id = "#" + dd_id;

	$(dd_id + '_list').attr("height",'0');
	$(dd_id + '_list').css("width", $(dd_id).css('width') );
	$(dd_id + '_list a').css("width",$(dd_id).css('width') );

	$(dd_id + '_container').click( function(){

		if( top.opened_dropdown_id && top.opened_dropdown_id != "" && opened_dropdown_id != dd_id + '_list' )
		{
			$(top.opened_dropdown_id+'_container').removeClass("dropdown_open");
			$(top.opened_dropdown_id+'_list').hide();
		}

		if( $(dd_id + '_list').css("display") == "none" )
		{
			$(dd_id + '_list').slideDown('fast');
			$(dd_id + '_container').addClass("dropdown_open");
		}
		else
		{
			$(dd_id + '_list').slideUp('fast',function(){
				$(dd_id + '_container').removeClass("dropdown_open");
			});
		}

		top.opened_dropdown_id = dd_id;
		return false;
	});

	$(dd_id + '_list a').click( function(){

		top.opened_dropdown_id = '';
		var value = ($(this).attr('href')||"").split(",")[0];
		var label = ($(this).attr('href')||"").substr(value.length+1);

		$(dd_id).val(value);
		$(dd_id + '_text').html(label);
		$(dd_id + '_list').slideUp('fast', function() {
			$(dd_id + '_container').removeClass("dropdown_open");
			$(dd_id).change();
		});
		return false;
	});
}