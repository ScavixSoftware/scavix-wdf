
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