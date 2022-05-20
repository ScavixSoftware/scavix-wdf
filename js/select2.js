
wdf.RegisterElement('select','wdf-select2',class extends wdf.ExtendElement(HTMLSelectElement)
{
    onReady()
    {
        this.config = JSON.parse(this.dataset.config) || {};
        //console.log("Select 2 onReady");
      
        if( !this.config.templateResult ) this.config.templateResult = this.renderItem;
        if( !this.config.templateSelection ) this.config.templateSelection = this.renderItem;
        
        $(this).select2(this.config);
        if( this.config.skip_multi_sorting )
            $(this).on("select2:select",function(evt){ var $element = $(evt.params.data.element); $element.detach(); $(this).append($element); $(this).trigger("change"); });
        if( this.config.selected )
        {
            //console.log("preselecting",this.config.selected);
            $(this).val(this.config.selected).trigger('change');
        }
    }
    
    renderItem(item)
    {
        //console.log("renderItem",item);
        let res = $('');
        if( item.html )
            try{ res = $(item.html); }catch(e){ }
        if( !res.text() && item.element && item.element.label )
            try{ res = $(item.element.label); }catch(e){ }
        if( !res.text() && item.text )
            try{ res = $(item.text); }catch(e){ }
        if( !res.text() && item.text )
            res = item.text;
        return res || '(undefined)';
    }
});