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

    $.fn.table = function(opts)
    {
        this.opts = $.extend({bottom_pager:false,top_pager:false},opts||{});

        return this.each( function()
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

//            $('.pager',self).each( function(){ $(this).width(self.width());});
            $('.thead a[data-sort]',self).click( function(e) { self.overlay(); });
            $(this).placePager(opts);
        });
    };

    $.fn.updateTable = function(html, callbackwhendone)
    {
        var self = this;
        self.overlay('remove', function() {
            self.prev('.pager').remove(); 
            self.next('.pager').remove();
            var newobj = $(html);
            self.replaceWith(newobj); 
            $('.thead a',self).click(self.overlay);
            self.placePager(self.opts);
//            console.log($('.tbody > div', newobj).length);
            var lst = newobj.parent();
            if($('.tbody > div', newobj).length > 0)
                $('.multi-actions, .btnexport', lst).show();
            else
                $('.multi-actions, .btnexport', lst).hide();
            wdf.processCallback(callbackwhendone);
        });
    };

    $.fn.gotoPage = function(n)
    {
        var self = this;
        self.overlay();

        wdf.post(self.attr('id')+'/gotopage',{number:n},function(d,s,p)
        {
            self.updateTable(d,p.wait());
        });
    };

    $.fn.placePager = function(opts)
    {
        var $p = $('.pager',this).remove();

        if( opts )
        {
            if( opts.top_pager )
            {
                $(this).addClass('pager_top');
                $p = $p.insertBefore(this).clone(true);
            }
            if( opts.bottom_pager )
            {
                $(this).addClass('pager_bottom');
                $p.insertAfter(this);
            }
        }
    };

    $.fn.showLoadingOverlay = function()
    {
        wdf.debug("$.showLoadingOverlay is deprecated, use $.overlay() instead");
        return $(this).overlay();
    };

    $.fn.hideLoadingOverlay = function(callback)
    {
        wdf.debug("$.hideLoadingOverlay is deprecated, use $.overlay('remove') instead");
        return $(this).overlay('remove',callback);
    };

})(window,jQuery);
