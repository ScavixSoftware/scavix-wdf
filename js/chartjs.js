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
    Chart.defaults.global.tooltips.backgroundColor = '#FFF';
    Chart.defaults.global.tooltips.borderColor = '#BDBDBD';
    Chart.defaults.global.tooltips.borderWidth = 1;
    Chart.defaults.global.tooltips.displayColors = false;
    Chart.defaults.global.tooltips.cornerRadius = 2;
    Chart.defaults.global.tooltips.titleFontColor = 'rgba(0,0,0,0.8)';
    Chart.defaults.global.tooltips.titleFontStyle = 'normal';
    Chart.defaults.global.tooltips.titleFontSize = 14;
    Chart.defaults.global.tooltips.titleFontFamily = "'Open Sans', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
    Chart.defaults.global.tooltips.titleMarginBottom = 15;
    Chart.defaults.global.tooltips.bodyFontColor = '#000';
    Chart.defaults.global.tooltips.bodyFontStyle = 'normal';
    Chart.defaults.global.tooltips.bodyFontSize = 20;
    Chart.defaults.global.tooltips.bodyFontFamily = Chart.defaults.global.tooltips.titleFontFamily
    Chart.defaults.global.tooltips.xPadding = 15;
    Chart.defaults.global.tooltips.yPadding = 15;
    
    Chart.defaults.global.legend.position = 'right';
    
	win.wdf.chartjs = 
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
            return str.match(/\d\d\d\d-\d\d-\d\d/)
                ?win.luxon.DateTime.fromFormat(str,"yyyy-MM-dd").toLocaleString()
                :str;
        },
        _dataLabel: function(item, data)
        {
            try
            {
                return data.datasets[item.datasetIndex].data[item.index].label || ''
            }catch(ex){ console.log("_dataLabel",ex,item, data); }
            return '';
        },

		init: function(id,config)
		{
            var ctx = document.getElementById(id);
            if( config.options.percentScale )
            {
                var a = this._deepGet(config,'options.scales.yAxes',[{}]);
                for( var i=0; i<a.length; i++ )
                    this._deepSet(a[i],'ticks.callback',function(value,index,values){ return value+'%'; });
                this._deepSet(config,'options.scales.yAxes',a);
            }
            $('#'+id).closest('.chartjs').data('raw',config);
            switch( config.type )
            {
                case 'pie': 
                    this._deepSet(config,'options.tooltips.callbacks',win.wdf.chartjs.pieTooltips(id));
                    break;
                default: 
                    this._deepSet(config,'options.tooltips.callbacks',win.wdf.chartjs.stdTooltips(id));
                    break;
            }
            this.charts[id] = new Chart(ctx.getContext('2d'),config);
            if( config.options.refresh )
                win.wdf.chartjs.loadData(id,true);
		},
		
		getChart: function(id)
		{
			return this.charts[id] || null;
		},
        
        pieTooltips: function(id)
        {
            var res = this.stdTooltips(id);
            res.label = function(item, data)
            {
                //console.log("pieLabel",items,data);
                var val = data.datasets[item.datasetIndex].data[item.index] || 0;
                var sum = 0;
                for(var i=0; i<data.datasets[item.datasetIndex].data.length; i++)
                    sum += data.datasets[item.datasetIndex].data[i];
                var perc = val/sum*100;
                return val + " ("+(Math.round(perc * 100) / 100)+"%)";
            }
            return res;
        },
        
        stdTooltips: function(id)
        {
            var res =
            {
                title: function(items, data)
                {
                    try
                    {
                        //console.log("stdTitle",items,data);
                        var item = items[0] || false;
                        if( !item )
                            return "";
                        var lab = item.xLabel 
                                || item.label 
                                || data.datasets[item.datasetIndex].label 
                                || data.labels[item.index] 
                                || '';
                        return wdf.chartjs._dtFormat(lab);
                    }catch(ex){ return ""+ex; }
                },
                label: function(item, data)
                {
                    var n = wdf.chartjs._dataLabel(item,data);
                    if( n ) return n;
                    //console.log("stdLabel");
                    n=data.datasets[item.datasetIndex].label||"";
                    return n&&(n+=": "),item.value==null?n+=item.yLabel:n+=item.value,n
                }
            };
            if( $('#'+id).closest('.chartjs').data('raw').options.percentScale )
            {
                res.label = function(item, data)
                {
                    try
                    {
                        var lab = wdf.chartjs._dataLabel(item,data);
                        if( lab ) return lab;
                        //console.log("stdPercLabel",item,data);
                        lab = data.datasets[item.datasetIndex].label || '';
                        if( lab ) lab += ": ";
                        var val = item.yLabel || item.value || 0;
                        return lab + val + "%";
                    }catch(ex){ return ""+ex; }
                };
            }
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
                var $cjs = $('#'+id).closest('.chartjs'),
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
            for(var p in wdf.chartjs.charts)
                wdf.chartjs.loadData(p,'now');
        },
        
        loadData: function(id,start)
        {
            var chart = wdf.chartjs.getChart(id), 
                config = $('#'+id).closest('.chartjs').data('raw'),
                cfg = config.options.refresh || {},
                int = cfg.interval || false,
                url = cfg.url || false;
            if( !url ) return;
            if( start===true && wdf.chartjs._deepGet(chart,"data.datasets.0.data",[]).length > 0 )
                return setTimeout(wdf.chartjs.loadData,int?int:1000,id);
            
            wdf.get(url,function(data)
            {
                wdf.chartjs.updateData(id,data);
                if( int && start !== 'now' )
                    setTimeout(wdf.chartjs.loadData,int,id);
            });
        },
        
        updateData: function(id, data)
        {
            var chart = wdf.chartjs.getChart(id);
            if( wdf.chartjs.dataEquals(chart.data,data) )
                return;
            // todo: improve to detect changes only and change only that values -> better animations!
            chart.data = data;
            chart.update();
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
                if ( ! wdf.chartjs.dataEquals( x[ p ],  y[ p ] ) ) return false;
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
