<?php
/**
* This file is part of project "MyTeambook"
* Copyright (c) since 2020 Scavix Software GmbH & Co. KG
*
* @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
* @copyright since 2020 Scavix Software GmbH & Co. KG
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
    var $Options = [];
    
	function __construct($name = false)
	{
        parent::__construct($name);
        $this->attr('is','wdf-select2');
        $this->opt('width',false);
        store_object($this,$this->id);
    }
    
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
    
    function setValue($value)
    {
        return $this->opt('selected',$value);
    }
    
    function setMultiple($on=true)
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
        
        return $this->opt('multiple',$on);
    }
    
    function setAjax($url)
    {
        return $this->opt('ajax',['url'=> $url,'dataType'=>'json']);
    }
}
