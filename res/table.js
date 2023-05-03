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
(function(win,$) {

    var wdf = win.wdf;

    wdf.tables = 
    {
        init: function(table,opts)
        {
            var $elem = $(table);

            return $elem.each( function()
            {
                var self = $(this), current_row;

                var actions = $('.ui-table-actions .ui-icon',self);
                if( actions.length > 0 )
                {
                    var w = 0;
                    $('.ui-table-actions > div',self)
                        .hover( function(){ $(this).toggleClass('ui-state-hover'); } )
                        .each(function(){ w+=$(this).width(); });

                    $('.ui-table-actions .ui-icon',self)
                        .click(function()
                        {
                            self.overlay();
                            wdf.post(self.attr('id')+'/onactionclicked',{action:$(this).data('action'),row:current_row.attr('id')},function(d)
                            {
                                $('body').append(d);
                                self.overlay('remove');
                            });
                        });

                    $('.ui-table-actions',self).width(w);

                    var on = function()
                    {
                        if( $('.ui-table-actions.sorting',self).length>0 )
                            return;
                        current_row = $(this); 
                        $('.ui-table-actions',self).show()
                            .position({my:'right center',at:'right-1 center',of:current_row});
                    };
                    var off = function(){ $('.ui-table-actions',self).hide(); };

                    $('.tbody .tr',self).bind('mouseenter click',on);
                    $('.caption, .thead, .tfoot',self).bind('mouseenter',off);
                    self.bind('mouseleave',off);

                    $('.tbody .tr .td:last-child, .thead .tr .td:last-child, .tfoot .tr .td:last-child',self).css('padding-right',w+10);
                }

                $('.thead a[data-sort]',self).click( function(e) { self.overlay(); });
                wdf.tables.placePager(this);
            });
        },
        gotoPage: function(table,n)
        {
            var self = $(table), handler = self.data('gotoPage');
            if( handler )
            {
                handler(table,n);
                return;
            }
            self.overlay();
            wdf.post(self.attr('id')+'/gotopage',{number:n},function(d,s,p)
            {
                wdf.tables.update(self,d,p.wait());
            });
        },
        update: function(table, html, callbackwhendone)
        {
            var self = $(table);
            self.overlay('remove', function() {
                self.prev('.pager').remove(); 
                self.next('.pager').remove();
                self.replaceWith(html);
                self = $(table);
                $('.thead a',self).click(self.overlay);
                wdf.tables.placePager(self);
                var lst = self.parent();
                if($('.tbody > div', self).length > 0)
                    $('.multi-actions, .btnexport', lst).show();
                else
                    $('.multi-actions, .btnexport', lst).hide();
                wdf.processCallback(callbackwhendone);
            });
        },
        placePager: function(table,opts)
        {
            var self = $(table);
            var $p = $('.pager',self).remove();
            
            if( !opts )
                opts = self.data('options');

            if( opts )
            {
                if( opts.top_pager )
                {
                    self.addClass('pager_top');
                    $p = $p.insertBefore(self).clone(true);
                }
                if( opts.bottom_pager )
                {
                    self.addClass('pager_bottom');
                    $p.insertAfter(self);
                }
            }
        }
    };

})(window,jQuery);
