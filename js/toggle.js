
wdf.RegisterElement('div','wdf-toggle',class extends wdf.ExtendElement(HTMLDivElement)
{
    onReady()
    {
        console.log("onReady");
        let cb = this.querySelector('input');
        cb.toggle = this;
        cb.addEventListener('click', this.cbClicked);
    }

    cbClicked()
    {
        console.log("cbClicked", this);
        if ($(this).is(':checked'))
            $(this.toggle).addClass('active');
        else
            $(this.toggle).removeClass('active');
        
        let url = $(this.toggle).data('change-url');
        if (url)
        {
            let args = {};
            args[this.name] = $(this).is(':checked') ? $(this).val() : '';
            wdf.get(url, args);
        }
    }
});