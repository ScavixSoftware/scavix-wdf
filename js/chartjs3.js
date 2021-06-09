/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

(function(win,$,undefined)
{
    Chart.defaults.plugins.tooltip.backgroundColor = '#FFF';
    Chart.defaults.plugins.tooltip.borderColor = '#BDBDBD';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.displayColors = false;
    Chart.defaults.plugins.tooltip.cornerRadius = 2;
    Chart.defaults.plugins.tooltip.titleColor = 'rgba(0,0,0,0.8)';
    Chart.defaults.plugins.tooltip.titleFont.tyle = 'normal';
    Chart.defaults.plugins.tooltip.titleFont.size = 14;
    Chart.defaults.plugins.tooltip.titleFont.family = "'Open Sans', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
    Chart.defaults.plugins.tooltip.titleMarginBottom = 15;
    Chart.defaults.plugins.tooltip.bodyColor = '#000';
    Chart.defaults.plugins.tooltip.bodyFont.style = 'normal';
    Chart.defaults.plugins.tooltip.bodyFont.size = 20;
    Chart.defaults.plugins.tooltip.bodyFont.family = Chart.defaults.plugins.tooltip.titleFontFamily
    Chart.defaults.plugins.tooltip.padding = 15;
    
    Chart.defaults.plugins.legend.position = 'right';
    
	win.wdf.chartjs3 = 
	{
		charts: {},

        _deepGet: function(obj,path,def)
        {
            path = path.split(".");
            var cur = obj;
            while( path.length>0 )
            {
                var p = path.shift();
                if( !cur[p] )
                    return def;
                cur = cur[p];
            }
            return cur || def;
        },
        _deepSet: function(obj,path,value)
        {
            path = path.split(".");
            var last = path.pop();
            var cur = obj;
            while( path.length>0 )
            {
                var p = path.shift();
                if( !cur[p] )
                    cur[p] = {};
                cur = cur[p];
            }
            cur[last] = value;
        },
        _dtFormat: function(str)
        {
            if( !str )
                return str;
            if( str.match(/\d\d\d\d-\d\d-\d\d/) )
                str = win.luxon.DateTime.fromFormat(str,"yyyy-MM-dd").toLocaleString();
            return str.replace(/,\s\d\d:\d\d:\d\d/,'');
        },

		init: function(id,config)
		{
            var ctx = document.getElementById(id);
            
            $('#'+id).closest('.chartjs3').data('raw',config);
            switch( config.type )
            {
                case 'pie': 
                    this._deepSet(config,'options.plugins.tooltip.callbacks',win.wdf.chartjs3.pieTooltips(id));
                    break;
                default: 
                    this._deepSet(config,'options.plugins.tooltip.callbacks',win.wdf.chartjs3.stdTooltips(id));
                    break;
            }
            this.charts[id] = new Chart(ctx.getContext('2d'),config);
            if( config.options.refresh )
                win.wdf.chartjs3.loadData(id,true);
		},
		
		getChart: function(id)
		{
			return this.charts[id] || null;
		},
        
        pieTooltips: function(id)
        {
            var res = this.stdTooltips(id);
            res.label = function(item)
            {
                //console.log("pie.label",typeof(item.raw),item);
                if( typeof(item.raw) !== 'object' )
                        item.raw = {raw:item.raw};
                    
                if( !item.raw.label )
                {
                    var val = item.parsed || 0;
                    var sum = 0;
                    for(var i=0; i<item.dataset.data.length; i++)
                        sum += item.dataset.data[i];
                    var perc = val/sum*100;
                    item.raw.label = val + " ("+(Math.round(perc * 100) / 100)+"%)";
                }
                return item.raw.label;
            }
            return res;
        },
        
        stdTooltips: function(id)
        {
            var res =
            {
                title: function(items)
                {
                    //console.log("std.title",typeof(items[0].raw),items[0]);
                    if( typeof(items[0].raw) !== 'object' )
                        items[0].raw = {raw:items[0].raw};
                    if( !items[0].raw.title ) 
                    {                    
                        var t = (items.length > 1)
                            ?items[0].label || items[0].dataset.label || '' // return X label on multi-series tooltips
                            :items[0].dataset.label || items[0].label || '';
                        items[0].raw.title = wdf.chartjs3._dtFormat(t);
                    }
                    return items[0].raw.title;
                },
                label: function(item)
                {
                    if( !item.raw.label )
                    {
                        var val = (item.chart.options.percentScale)
                            ?item.raw.yval+' ('+Math.round(item.raw.y)+'%)'
                            :item.formattedValue;
                        var l = (item.chart.config.data.datasets.length > 1)
                            ?item.dataset.label || item.label || ''
                            :item.label || item.dataset.label || '';

                        item.raw.label = wdf.chartjs3._dtFormat(l) +": "+ val;
                    }
                    return item.raw.label;
                }
            };
            return res;
        },
        
        convertToSvg: function()
        {
            if( !window.C2S )
                return;

            window.C2S.prototype.getContext = function (contextId) { return (contextId=="2d" || contextId=="2D")?this:null; };
            window.C2S.prototype.style = function () { return this.__canvas.style; };
            window.C2S.prototype.classList = {add:console.log, remove:console.log};
            window.C2S.prototype.getAttribute = function (name) { return this[name]; };
            window.C2S.prototype.addEventListener =  function(type, listener, eventListenerOptions) { };

            for(var id in this.charts)
            {
                var $cjs = $('#'+id).closest('.chartjs3'),
                    $wrap = $('#'+id).closest('.wrap'),
                    raw = $cjs.data('raw'),
                    c2s = C2S($wrap.width(),$wrap.height());
             
                raw.options.responsive = false;
                raw.options.animation = false;
                this.charts[id] = new Chart(c2s,raw);
                var svg = c2s.getSerializedSvg(true);
                $('#'+id).replaceWith(svg);
                console.log("Rendered SVG chart ","W="+$wrap.width(),"H="+$wrap.height(),"T="+raw.type);
            }
            this.charts = {};
        },
        
        refreshAll: function()
        {
            for(var p in wdf.chartjs3.charts)
                wdf.chartjs3.loadData(p,'now');
        },
        
        loadData: function(id,start)
        {
            var chart = wdf.chartjs3.getChart(id), 
                config = $('#'+id).closest('.chartjs3').data('raw'),
                cfg = config.options.refresh || {},
                int = cfg.interval || 0,
                url = cfg.url || false;
            if( !url ) return;
            if( start===true && wdf.chartjs3._deepGet(chart,"data.datasets.0.data",[]).length > 0 )
                return setTimeout(wdf.chartjs3.loadData,int?int:1000,id);
            
            wdf.get(url,function(data)
            {
                wdf.chartjs3.updateData(id,data);
                if( int > 0 && start !== 'now' )
                    setTimeout(wdf.chartjs3.loadData,int,id);
            });
        },
        
        updateData: function(id, data)
        {
            var chart = wdf.chartjs3.getChart(id);
            if( wdf.chartjs3.dataEquals(chart.data,data) )
                return;
            // todo: improve to detect changes only and change only that values -> better animations!
            chart.data = data;
            chart.update('none'); // remove 'none' once we have DIFFed data
        },
        
        dataEquals: function( x, y )
        {
            if ( x === y ) return true;
            if ( ! ( x instanceof Object ) || ! ( y instanceof Object ) ) return false;
            if ( x.constructor !== y.constructor ) return false;
            for ( var p in x )
            {
                if( p[0] == '_' ) continue;
                if ( ! x.hasOwnProperty( p ) ) continue;
                if ( ! y.hasOwnProperty( p ) ) return false;
                if ( x[ p ] === y[ p ] ) continue;
                if ( typeof( x[ p ] ) !== "object" ) return false;
                if ( ! wdf.chartjs3.dataEquals( x[ p ],  y[ p ] ) ) return false;
            }
            for ( p in y )
            {
                if( p[0] == '_' ) continue;
                if ( y.hasOwnProperty( p ) && ! x.hasOwnProperty( p ) ) return false;
            }
            return true;
          }
	};
	
})(window,jQuery);
