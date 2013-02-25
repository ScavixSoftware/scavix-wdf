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
function uploadFile(fuid,target_url,preview_id)
	{
		$.ajaxFileUpload
		(
			{
				url:target_url,
				secureuri:false,
				fileElementId: fuid,
				dataType: 'json',
				success: function (data, status)
				{
					if(typeof(data.error) != 'undefined')
					{
						if(data.error != '')
							wdf.debug(data.error);
						else
						{
							data.url = data.url.replace(/&amp;/g, "&");
							data.thumb = data.thumb.replace(/&amp;/g, "&");
							$('#'+fuid).attr({value: data.url});
							if( preview_id )
								if( data.thumb == '' )
									$('#'+preview_id).attr({src: data.url});
								else
									$('#'+preview_id).attr({src: data.thumb});
						}
					}
				},
				error: function (data, status, e)
				{
					wdf.debug(e);
				}
			}
		)

		return false;

	}