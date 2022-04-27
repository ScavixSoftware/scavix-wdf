
let WdfElement = (superclass) => class extends superclass
{
    autoId()
    {
        if( typeof WdfElement.nextId === 'undefined' )
            WdfElement.nextId = 0;
        return this.constructor.name+"_"+WdfElement.nextId++;
    }
    
    connectedCallback(e)
    {
        if( this.hasAttribute('id') )
            this.id = this.getAttribute('id');
        else
        {
            this.id = this.autoId();
            this.setAttribute('id',this.id);
        }
        setTimeout( ()=>{ this.onReady(); } );
    }
};

customElements.define('wdf-select2', class extends WdfElement(HTMLSelectElement)
{
    onReady()
    {
        this.config = JSON.parse(this.dataset.config) || {};
        //console.log("Select 2 onReady",this);
        
        if( !this.config.templateResult ) this.options.templateResult = this.renderItem;
        if( !this.config.templateSelection ) this.options.templateSelection = this.renderItem;
        
        $(this).select2(this.config);
        if( this.config.selected )
            $(this).val(this.config.selected).trigger('change');
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
}
,{ extends: 'select' });
