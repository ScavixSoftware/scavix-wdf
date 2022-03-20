<?
/**
 * Scavix Web Development Framework
 *
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
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls\Listing;

use ScavixWDF\Base\Args;
use ScavixWDF\Base\Template;
use ScavixWDF\Base\WdfClosure;
use ScavixWDF\Controls\Form\TextInput;
use ScavixWDF\JQueryUI\uiDateTimePicker;

class WdfListingFilter extends Template
{
    var $prefix = "";
    var $sql_builders = [];
    var $listings = [];
    var $onoffs = [];

    function __construct($controller,$method='',$object=false)
    {
        parent::__construct();

        $this->prefix = "lstfilter_{$controller}{$method}";
        if( $object )
        {
            $id = ifavail($object,'id');
            $this->prefix .= get_class_simple($object).$id;
        }

        $this->set('prefix',$this->prefix);
        if( isset($id) && $id )
            $this->set('action',buildQuery($controller,$method,compact('id')));
        else
            $this->set('action',buildQuery($controller,$method));
        
        $this->script("wdf.listings.initFilter('#{$this->id}');");
    }
    
    protected function persist($name,$value)
    {
        WdfListing::Storage()->set("{$this->prefix}_{$name}",$value);
    }
    protected function delValue($name)
    {
        WdfListing::Storage()->del("{$this->prefix}_{$name}");
    }
    protected function hasSetting($name)
    {
        return WdfListing::Storage()->has("{$this->prefix}_{$name}");
    }
    protected function getSetting($name,$default=false)
    {
        return WdfListing::Storage()->get("{$this->prefix}_{$name}", $default);
    }
    
    function setListing($listing)
    {
        // IMPORANT: Do not call this method, call WdfListing::setFilter instead
        $this->listings[] = $listing;
        $this->set('listings', array_map(function($l) { return $l->id; }, $this->listings));
        return $this;
    }
	
	function getInput($name)
	{
		foreach( $this->get('inputs') as $i )
			if( $i->attr('name') == $name )
				return $i;
		return null;
	}
	
	function addSearchInput($name,$title,$columns)
	{
		return $this->addInput(
			TextInput::Make($this->getValue($name),$name)->setTitle($title),
			$this->makeTermBuilder($columns),
			false
		);
	}
	
	function addEqualsSelect($name,$title,$column)
	{
		return $this->addInput(
			
			ScavixWDF\Controls\Form\Select::Make($name)->SetCurrentValue($this->getValue($name))->setTitle($title),
			$this->makeEqualsBuilder($column),
			false
		);
	}
	
	function addEqualsInput($control, $column)
	{
		$name = $control->attr('name');
		return $this->addInput(
			
			$control->setValue($this->getValue($name)),
			$this->makeEqualsBuilder($column)
		);
	}

    protected function addInput($control,$sql_builder,$return_self=true)
    {
        $name = $control->attr('name');
        $this->sql_builders[$name] = $sql_builder;
        $this->add2var('inputs',$control);

        $val = Args::post($name);
        if( $val )
            $this->persist($name, $val);
        
        if( $control instanceof \ScavixWDF\Controls\Form\CheckBox )
            $this->onoffs[] = $name;

        return $return_self?$this:$control;
    }

    function dataFromPost()
    {
        if( Args::request("reset") == 1 )
            return $this->resetValues();

        foreach( $this->sql_builders as $name=>$b )
        {
            $val = Args::get($name,null);
            if( $val !== null )
                $this->persist($name, $val);
        }
        foreach( $this->sql_builders as $name=>$b )
        {
            $val = Args::post($name,null);
            if( $val !== null )
                $this->persist($name, $val);
        }
        // wdflisting.js ensures that even unchecked checkboxes will be transferred as '0', so this bad 'always off' handling can be left out
//        foreach( $this->onoffs as $name )
//        {
//            $val = Args::request($name,null); 
//            if( $val === null )
//                $this->persist($name, 0);
//        }
        return $this;
    }
    
    function resetValues()
    {
        foreach( WdfListing::Storage()->keys() as $k )
            if( starts_with($k,"{$this->prefix}_") )
                $this->delValue(str_replace("{$this->prefix}_","",$k));
        
        foreach( $this->get('inputs') as $control )
            $control->setValue('');
        return $this;
    }

    function setValue($name,$value)
    {
        if( $value === false )
            $this->delValue($name);
        else
            $this->persist($name, $value);
        return $value;
    }

    function getValue($name,$default=false)
    {
        if(isset($_GET[$name]) && ($_GET[$name] != ''))     // filter value passed by GET parameter
            $this->persist($name, $_GET[$name]);
        if( !$this->hasSetting($name) )
        {
            if( $default )
                $this->persist($name, $default);
            return $default;
        }
        return $this->getSetting($name);
    }

    function getSql($for_listing_injection=false)
    {
        $res = [];

        foreach( $this->sql_builders as $name=>$b )
        {
            $val = $this->getSetting($name);
            $sql = $b($name,$val);
            if( !$sql ) continue;
            if( !starts_with($sql,"(") ) $sql = "($sql)";
            $res[] = $sql;
        }
        $sql = count($res)==0?"(1=1)":"(".implode("AND",$res).")";
        if( $for_listing_injection )
            $sql = "/*BEG {$this->prefix}*/".$sql."/*{$this->prefix} END*/";

//        log_debug($sql);
        return $sql;
    }

    protected function makeTermBuilder($columns)
    {
        return new WdfClosure(function($name,$value)use($columns)
        {
//            log_debug("TERM $name",$value);
            $res = [];
            foreach( explode("\n",wordwrap("$value",1)) as $t )
            {
                $ds = \ScavixWDF\Model\DataSource::Get();
                $t = $ds->EscapeArgument(trim($t));
                if( !$t ) continue;
                foreach( $columns as $col )
                    $res[] = "(".$ds->QuoteColumnName($col)." LIKE '%$t%')";
            }
            if( count($res)>0 )
                return "(".implode("OR",$res).")";
            return "";
        });
    }
    
    protected function makeEqualsBuilder($column)
    {
        return new WdfClosure(function($name,$value)use($column)
        {
            if( $value !== false && $value !== '' && !is_null($value) )
            {
                $ds = \ScavixWDF\Model\DataSource::Get();
                return $ds->QuoteColumnName($column)."='".$ds->EscapeArgument($value)."'";
            }
            return "";
        });
    }

    protected function makeIsNullBuilder($column,$inverted=false,$xor=false)
    {
        return new WdfClosure(function($name,$value)use($column,$inverted,$xor)
        {
            if( $inverted ) $value = !$value;
            $column = \ScavixWDF\Model\DataSource::Get()->QuoteColumnName($column);
            if( $value )
                return "($column IS NULL)";
            return $xor?"($column IS NOT NULL)":"";
        });
    }

    protected function makeDateBuilder($column,$op)
    {
        return new WdfClosure(function($name,$value)use($column,$op)
        {
            if( $value )
            {
                $ci = false;
                if(isset($this->listings) && $this->listings && isset($this->listings[0]) && isset($this->listings[0]->ci) && $this->listings[0]->ci)
                    $ci = $this->listings[0]->ci;
                if(!$ci && ScavixWDF\JQueryUI\uiDatePicker::$DefaultCI)
                    $ci = ScavixWDF\JQueryUI\uiDatePicker::$DefaultCI;
                if(!$ci)
                    return '';
                $v = date("Y-m-d",$ci->DateTimeFormat->StringToTime($value));
                return "DATE(".\ScavixWDF\Model\DataSource::Get()->QuoteColumnName($column).")$op'$v'";
            }
            return "";
        });
    }

    protected function makeDateTimeBuilder($column,$op)
    {
        return new WdfClosure(function($name,$value)use($column,$op)
        {
            if( $value )
            {
                $ci = false;
                if(isset($this->listings) && $this->listings && isset($this->listings[0]) && isset($this->listings[0]->ci) && $this->listings[0]->ci)
                    $ci = $this->listings[0]->ci;
                if(!$ci && ScavixWDF\JQueryUI\uiDatePicker::$DefaultCI)
                    $ci = ScavixWDF\JQueryUI\uiDatePicker::$DefaultCI;
                if(!$ci)
                    return '';
                $v = date("Y-m-d H:i:s",$ci->DateTimeFormat->StringToTime($value));
                return \ScavixWDF\Model\DataSource::Get()->QuoteColumnName($column)."$op'$v'";
            }
            return "";
        });
    }
}