
wdf.RegisterElement('select','wdf-select2',class extends wdf.ExtendElement(HTMLSelectElement)
{
    onReady()
    {
        this.config = JSON.parse(this.dataset.config) || {};
        // console.log("Select 2 onReady", this.config);

        // known issue (otherwise search input is not focusable): https://select2.org/troubleshooting/common-problems
        if (this.config.dropdownParent && $(this).parents('.ui-dialog').length)
            this.config.dropdownParent = $(this).parents('.ui-dialog');

        if( !this.config.templateResult ) this.config.templateResult = this.renderItem;
        if( !this.config.templateSelection ) this.config.templateSelection = this.renderItem;

        $(this).select2(this.config);
        if( this.config.skip_multi_sorting )
            $(this).on("select2:select",function(evt){ var $element = $(evt.params.data.element); $element.detach(); $(this).append($element); $(this).trigger("change"); });
        if( this.config.selected )
            $(this).val(this.config.selected).trigger('change');
    }

    renderItem(item)
    {
        // console.log("renderItem",item);
        let res = $('');
        if (item.html) {
            try { res = $(item.html); } catch (e) { }
            if (!res.text())
                try { res = $('<span>' + item.html + '</span>'); } catch (e) { }
        }
        if( !res.text() && item.element && item.element.label )
            try{ res = $(item.element.label); }catch(e){ }
        if( !res.text() && item.text )
            try{ res = $(item.text); }catch(e){ }
        if( !res.text() && item.text )
            res = item.text;
        return res || '(undefined)';
    }
});