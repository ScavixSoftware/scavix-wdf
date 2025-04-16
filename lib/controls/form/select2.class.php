<?php
/**
* Copyright (c) since 2022 Scavix Software GmbH & Co. KG
*
* @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
* @copyright since 2022 Scavix Software GmbH & Co. KG
*/
namespace ScavixWDF\Controls\Form;


/**
 * See https://github.com/select2/select2 and https://select2.org/
 *
 * @attribute[Resource('select2/select2.min.css')]
 * @attribute[Resource('select2/select2.full.min.js')]
 * //@ attribute[Resource('select2/i18n/de.js')]
 */
class Select2 extends Select
{
    public $Options = [];

	function __construct($name = false)
	{
        parent::__construct($name);
        $this->attr('is','wdf-select2');
        $this->opt('width',false);

        if( system_is_module_loaded('localization') )
		{
            global $CONFIG;
			$ci = \ScavixWDF\Localization\Localization::detectCulture();
			if( $ci->IsRTL )
				$this->opt('dir', 'rtl');
            $this->opt('language', $ci->Iso2);
            // if (($ci->Iso2 == 'de') && avail($CONFIG, 'resources_system_url_root'))
            // {
            //     // $this->opt('amdLanguageBase', '/system/res/select2/');
            //     // $this->Ress
            // }
		}

        store_object($this,$this->id);
    }

    /**
	 * Sets or gets an option
	 *
	 * if you specify a $value will set it and return `$this`. else will return the option value
	 * @param string $name option name
	 * @param mixed $value option value or null
	 * @return static|mixed If setting an option returns `$this`, else returns the option value
     * see https://select2.org/configuration/options-api for options
	 */
	function opt($name,$value=null)
	{
        if( is_array($name) )
        {
            foreach( $name as $k=>$v )
                $this->opt($k,$v);
            return $this;
        }
		if( $value === null )
			return ifavail($this->Options, $name);
		$this->Options[$name] = $value;
        return $this->data('config',$this->Options);
	}

    public function attr(...$args)
    {
        if(count($args) != 2)
            return parent::attr(...$args);

        $name = $args[0];
        if(($name == 'placeholder') && ($args[1] != $this->$name))
            $this->opt('placeholder', $args[1]);

        $this->$name = $args[1];
        return $this;
    }

    /**
     * Sets the current value.
     *
     * @param mixed $value The value
     * @return static
     */
    function setValue($value)
    {
        $this->opt('selected',$value);
        return $this;
    }

    /**
     * De-/Activates the multi-select feature.
     *
     * @param bool $on True=on, false=off
     * @param bool $sort_selection If true embedded sorting is on, else it will be deactivated
     * @return static
     */
    function setMultiple($on=true, $sort_selection=true)
    {
        if( $on )
        {
            if( strpos($this->attr('name'),"[]") === false )
                $this->setName($this->attr('name')."[]");
            $this->attr('multiple','multiple');
        }
        else
        {
            $this->setName(str_replace("[]","",$this->attr('name')));
            $this->removeAttr('multiple');
        }

        $this->opt('multiple',!!$on)->opt('skip_multi_sorting',!$sort_selection);
        return $this;
    }

    /**
     * Sets up AJAX loading of values.
     *
     * @param string $url The data URL
     * @return static
     */
    function setAjax($url)
    {
        return $this->opt('ajax',['url'=> $url,'dataType'=>'json']);
    }
}
