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