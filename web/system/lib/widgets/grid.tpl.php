<?
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
log_debug($colModel);
?>
<table id="<?=$id?>" class="scroll" cellpadding="0" cellspacing="0"></table>
<div id="<?=$id?>_pager" class="scroll" style="text-align:left;"></div>
<script type="text/javascript">
jQuery(document).ready( function()
{
<?
system_start_script();
?>
    additionalWages_selectedRow = -1;
    var <?=$id?>_loadComplete = function()
    {
		try{ jQuery("#<?=$id?> *").tooltip({showURL: false}); }catch(ex){};
		var udata = jQuery("#<?=$id?>").getGridParam('userData')
<? if( $hasFooter ): ?>
		$("#t_<?=$id?>").html(udata.footer_html);
<? endif; ?>
<? if( !$this->Height ): ?>
		jQuery("#<?=$id?>").setGridHeight('auto');
<? endif; ?>
    }
	jQuery("#<?=$id?>").jqGrid(
    {
		url: '?load=<?=$id?>&event=GetData&reqid=<?=request_id()?>',
		datatype: function(postdata)
        {
            jQuery.ajax(
            {
                url: jQuery("#<?=$id?>").getGridParam("url"),
                data: postdata,
                dataType: "xml",
                complete: function(xmldata,stat)
                {
                    if(stat=="success")
                    {
                        var thegrid = jQuery("#<?=$id?>")[0];
                        thegrid.addXmlData(xmldata.responseXML);
<? if( !$this->Height ): ?>
                        jQuery("#<?=$id?>").setGridHeight('auto');
<? endif; ?>
                        var colCount = jQuery("#<?=$id?>").getGridParam('colNames').length;
                        $("row > cell",xmldata.responseXML).each( function(i)
                        {
                            var attributes = $(this).get(0).attributes;
                            for(var a=0; a<attributes.length; a++)
                                jQuery("#<?=$id?> td").get(i+colCount).setAttribute(attributes[a].nodeName,attributes[a].nodeValue);
                        });

                        <?=$id?>_loadComplete();
                    }
                }
            });
        },
		mtype: 'POST',
		colNames: [<?=$colNames?>],
		colModel: [<?=$colModel?>],
		rowNum: <?=$this->RowNum?>,
		viewrecords: true,
		imgpath:  '<?=skinPath()?>grid/basic/images',
<? if( $this->Caption ): ?>
		caption: '<?=$this->Caption?>',
<? endif; ?>
<? if( $this->Width ): ?>
		width: '<?=$this->Width?>',
<? endif; ?>
<? if( $this->Height ): ?>
		height: '<?=$this->Height?>',
<? endif; ?>
<? if( $this->Pager || $this->BtnAdd || $this->BtnDel || $this->BtnEdit || $this->BtnRefresh || $this->BtnSearch ): ?>
		pager: jQuery('#<?=$id?>_pager'),
<? endif; ?>
		shrinkToFit: false,
<? if( $hasFooter ): ?>
		toolbar : [true,"bottom"],
<? endif; ?>
		cellEdit : <?=$cellEdit?"true":"false"?>,
<? if( $cellEdit ): ?>
		cellsubmit: 'remote',
		cellurl : '<?=$cellurl?>&reqid=<?=request_id()?>',
		afterSubmitCell: function(resp, rowid, cellname, value, iRow, iCol)
		{
            var res = false;
			try{ eval(resp.responseText); }catch(ex){ res = resp.responseText; }
			try{ jQuery("#<?=$id?> *").tooltip({showURL: false}); }catch(ex){};

			if( res )
				return [false,res];
			return [true,""];
		},
		onSelectCell: function(rowid, celname, value, iRow, iCol)
		{
			Debug("onSelectCell("+rowid+","+celname+","+value+","+iRow+","+iCol+")");
		},
<? endif; ?>
<? if( $this->RowEdit ): ?>
		onSelectRow: function(id)
        {
            if(id && id !== <?=$id?>_selectedRow)
            {
                /*jQuery('#<?=$id?>').restoreRow(<?=$id?>_selectedRow);*/
                jQuery('#<?=$id?>').editRow(id,true);
                <?=$id?>_selectedRow = id;
            }
        },
<? endif; ?>

		loadError: function(xhr,st,err){ Debug(st); Debug(err); },
		loadComplete: <?=$id?>_loadComplete
	}).navGrid('#<?=$id?>_pager',{refresh: false, edit: false, add: false, del: false, search: false})
<? if( $this->BtnAdd ): ?>
        .navButtonAdd('#<?=$id?>_pager',
        {
            caption: 'TXT_ADD_ROW',
            buttonimg: '<?=skinFile('grid/basic/images/row_add.gif')?>',
            onClickButton: function(){ jQuery("#<?=$id?>").addRowData( jQuery("#<?=$id?>").getGridParam("records"), {}, 'last' ); },
            position: "last", 
            title: 'TXT_ADD_ROW'
        })
<? endif; ?>
    ;
<? if( !$this->Pager && ($this->BtnAdd || $this->BtnDel || $this->BtnEdit || $this->BtnRefresh || $this->BtnSearch) ): ?>
		$('#<?=$id?>_pager').children().not('.navtable').remove();
<? endif; ?>
<?
system_end_script(Grid::__js());
system_load_css(Grid::__css());
?>
});
</script>