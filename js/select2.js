
wdf.RegisterElement('select','wdf-select2',class extends wdf.ExtendElement(HTMLSelectElement)
{
    onReady()
    {
        this.config = JSON.parse(this.dataset.config) || {};
        //console.log("Select 2 onReady");
      
        if( !this.config.templateResult ) this.config.templateResult = this.renderItem;
        if( !this.config.templateSelection ) this.config.templateSelection = this.renderItem;
        
        $(this).select2(this.config);
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
            res = $(item.html);
        if( !res.text() && item.element && item.element.label )
            res = $(item.element.label);
        if( !res.text() && item.text )
            res = $(item.text);
        if( !res.text() && item.text )
            res = item.text;
        return res || '(undefined)';
    }
});