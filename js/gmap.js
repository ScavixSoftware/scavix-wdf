
(function(win,$,undefined)
{
	win.wdf.gmap = 
	{
		markerClicked: $.Callbacks('unique'),
		
		geocoder: false,
		maps: {},
		markers: {},
		
		init: function(id,options)
		{
			this.maps[id] = new google.maps.Map($('#'+id).get(0),options);
		},
		
		addAddress: function(id, address)
		{
			if( !this.geocoder )
				this.geocoder = new google.maps.Geocoder();
			
			var req = {address:address};
			this.geocoder.geocode(req, function(result)
			{
				if( result )
				{
					var i = result.length - 1;
					if(i >= 0)
					{
						wdf.gmap.addMarker(id,
							result[i].geometry.location.lat(),
							result[i].geometry.location.lng(),
							{title:result[i].formatted_address});
					}
					else if(req.address.indexOf(","))
						wdf.gmap.addAddress(id, req.address.substr(req.address.indexOf(",")+1));
				}
			});
		},
		
		addMarker: function(id,lat,lng,options)
		{
			if( !this.maps[id] )
				throw "gMap "+id+" not found";
			
			var pos = new google.maps.LatLng(lat,lng);
			var opts =
			{
				map: this.maps[id],
				position: pos
			};
			if( options )
				for(var p in options)
					if(p != "onclick")
						opts[p] = options[p];

			var marker = new google.maps.Marker(opts);
			google.maps.event.addListener(marker, 'click', function() { wdf.gmap.markerClicked.fire(id,marker); } );

			if( options )
				for(var p in options)
					if(p == "onclick")
						google.maps.event.addListener(marker, 'click', function() { eval(options["onclick"]); } );
			
			if( !this.markers[id] )
				this.markers[id] = [];
			this.markers[id].push(marker);
		},
		
		showAllMarkers: function(id)
		{
			var bounds;
			if( this.markers[id] && this.markers[id].length == 1 )
			{
				bounds = new google.maps.LatLngBounds(this.markers[id][0].position,this.markers[id][0].position);
				this.maps[id].setCenter(bounds.getCenter());
			}
			else if( this.markers[id] && this.markers[id].length > 1 )
			{
				bounds = new google.maps.LatLngBounds();
				for(var i=0; i<this.markers[id].length; i++)
					bounds.extend(this.markers[id][i].position);
				this.maps[id].setCenter(bounds.getCenter());
				this.maps[id].fitBounds(bounds);
			}
		}
	};
	
})(window,jQuery);
