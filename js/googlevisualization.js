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

function wdf_gv_init(chart,data,options)
{
    var columns = [], selected=false;
    for (var i = 0; i < data.getNumberOfColumns(); i++)
        columns.push(i);
    
    google.visualization.events.addListener(chart, 'select', function ()
    {
        var sel = chart.getSelection();
        if (sel.length > 0)
        {
            if (typeof sel[0].row === 'undefined' || sel[0].row === null )
            {
                var col = sel[0].column;
                selected = (col === selected)?false:col;
                for (var i=0; i<columns.length; i++)
                {
                    if( col != i && selected!==false )
                    {
                        columns[i] =
                        {
                            label: data.getColumnLabel(i),
                            type: data.getColumnType(i),
                            calc: function ()
                            {
                                return null;
                            }
                        };
                    }
                    else
                        columns[i] = i;
                }
                var view = new google.visualization.DataView(data);
                view.setColumns(columns);
                chart.draw(view, options);
            }
        }
    });
}

(function($) {


})(jQuery);
