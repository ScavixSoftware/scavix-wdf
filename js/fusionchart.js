
var _fcChartStack = {};

function initFusionChart(settings)
{
	if( !settings.debug )
		settings.debug = 0;

	var fcid = settings.chartid + "_chart";
	if( _fcChartStack[fcid] )
		return;
	_fcChartStack[fcid] = true;
//	FusionCharts.setCurrentRenderer('javascript');
	
	var fc = new FusionCharts(settings.swfurl, fcid, settings.width, settings.height, settings.debug, 0);
	if( settings.data )
	{
		fc.setDataXML(settings.data);
		fc.setTransparent(true);
		fc.render(settings.chartid);
	}
	else if( settings.dataurl )
	{
		$.post(settings.dataurl,{},function(d)
		{
			fc.setDataXML(d);
			fc.setTransparent(true);
			fc.render(settings.chartid);
		});
	}
	else
		wdf.debug(fcid+": Missing data or dataurl setting");		
}