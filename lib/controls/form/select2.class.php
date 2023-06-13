<?php
/**
* Copyright (c) since 2022 Scavix Software GmbH & Co. KG
*
* @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
* @copyright since 2022 Scavix Software GmbH & Co. KG
*/
namespace ScavixWDF\Controls\Form;

/**
 * See https://select2.org/
 * 
 * @attribute[Resource('select2.min.css')]
 * @attribute[Resource('select2.full.min.js')]
 */
class Select2 extends Select
{
    public $Options = [];
    
	function __construct($name = false)
	{
        parent::__construct($name);
        $this->attr('is','wdf-select2');
        $this->opt('width',false);
        store_object($this,$this->id);
    }
    
    /**
	 * Sets or gets an option
	 * 
	 * if you specify a $value will set it and retunr `$this`. else will return the option value
	 * @param string $name option name
	 * @param mixed $value option value or null
	 * @return static|mixed If setting an option returns `$this`, else returns the option value
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
			return $this->Options[$name];
		$this->Options[$name] = $value;
        return $this->data('config',$this->Options);
	}
    
    /**
     * Sets the current value.
     * 
     * @param mixed $value The value
     * @return static
     */
    function setValue($value)
    {
        return $this->opt('selected',$value);
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
        
        return $this->opt('multiple',$on)->opt('skip_multi_sorting',!$sort_selection);
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
