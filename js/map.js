var maps = {};
var markers = {};
var geocoder = false;
var geocoder_requests = 0;
var mapid = false;
var mapsloaded = false;

function map_loaded()
{
//	Debug("map_loaded");
	map_initialize(mapid);
}

function map_initialize(id, options)
{
//	Debug("map_initialize: " + id);
	try {
		mapsloaded = (google + "" != "undefined") && (google.maps + "" != "undefined") && (google.maps.LatLng + "" != "undefined");
	} catch(ex) {}
	if( !mapsloaded )
	{
		mapid = id;
		var script = document.createElement("script");
		script.type = "text/javascript";
		script.src = "https://maps.googleapis.com/maps/api/js?v=3&sensor=false&callback=map_loaded";
		document.body.appendChild(script);
		return;
	}
	
	var opts = {
		zoom:10,
		center: new google.maps.LatLng(-34.397, 150.644),
		mapTypeId: google.maps.MapTypeId.ROADMAP
	}
	if( options )
		for(var p in options)
			opts[p] = options[p];

    map = new google.maps.Map(document.getElementById(id), opts);
	maps[id] = map;
}

function map_add_address(id, address)
{
//	Debug("map_add_address: " + id + " " + address);
	if( !geocoder )
		geocoder = new google.maps.Geocoder();

	var requ = {address:address};
	geocoder_requests++;
	geocoder.geocode(requ, function(result, status)
	{
		if( result )
		{
			var i = result.length - 1;
			if(i >= 0)
			{
				map_add_marker(id,
					result[i].geometry.location.lng(),
					result[i].geometry.location.lat(),
					{title:result[i].formatted_address});
			}
			else
			{
				if(requ.address.indexOf(","))
				{
					map_add_address(id, requ.address.substr(requ.address.indexOf(",")+1));
				}
			}
		}
		geocoder_requests--;
	});
}

function map_add_marker(id,longitude,latitude,options)
{
//	Debug("map_add_marker(" + id + ", " + longitude + ", " + latitude + ")");
	var pos = false;
	try {
		pos = new google.maps.LatLng(latitude,longitude);
	} catch(ex) { 
		setTimeout( function() { map_add_marker(id,longitude,latitude,options); }, 100);
		return; 
	}
		
	if( typeof(maps[id]) == "undefined" )
		map_initialize(id,{center:pos});

	var opts =
	{
		map: maps[id],
		position: pos
	};
	if( options )
	{
		for(var p in options)
		{
			if(p != "onclick")
				opts[p] = options[p];
		}
	}

	var marker = new google.maps.Marker(opts);
	
	if( options )
	{
		for(var p in options)
		{
			if(p == "onclick")
			{
				google.maps.event.addListener(marker, 'click', function() { eval(options[p]); } );
			}
		}
	}	
	if( typeof(markers[id]) == "undefined" )
		markers[id] = [];
	markers[id].push(marker);
}

function map_show_all(id)
{
//	Debug("map_show_all: " + id);
	if( (geocoder_requests > 0) || !mapsloaded )
	{
		setTimeout(function(){map_show_all(id);},100);
		return;
	}
	
	// run callback to change map after it was rendered:
	if(typeof(cbmapshown) == "function")
	{
		cbmapshown(maps[id]);
		return;
	}

	if( markers[id] && markers[id].length == 1 )
	{
		var bounds = new google.maps.LatLngBounds(markers[id][0].position,markers[id][0].position);
		maps[id].setCenter(bounds.getCenter());
	}
	else if( markers[id] && markers[id].length > 1 )
	{
		var bounds = new google.maps.LatLngBounds(markers[id][0].position,markers[id][1].position);
		for(var i=2; i<markers[id].length; i++)
			bounds.extend(markers[id][i].position);
		maps[id].setCenter(bounds.getCenter());
		maps[id].fitBounds(bounds);
	}
	else
	{
		if( typeof(maps[id]) == "undefined" )
			map_initialize(id);
	}	
}