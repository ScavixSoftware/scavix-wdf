<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\JQueryUI;

use ScavixWDF\Base\Control;

/**
 * This is an inline message.
 * 
 * Will use the jQueryUI standard theming.
 * @attribute[Resource('jquery-ui/ui.message.css')]
 */
class uiMessage extends uiControl
{
	public $sub;
    public $messages = [];
	
	function __construct($message,$type='highlight',$closeable=true,$autoclose=false)
	{
		parent::__construct('div');
		$this->class = "ui-widget ui-message";
		
		if( function_exists('translation_string_exists') && translation_string_exists($message) )
			$message = getString($message);
		$icon = $type=='highlight'?'info':'alert';
		
		$this->sub = $this->content( new Control('div') );
		$this->sub->class = "ui-state-$type ui-corner-all";
        if($autoclose)
        {
            if ($autoclose === true)
                $autoclose = 10;
            $this->sub->script("setTimeout(function() { $('#".$this->sub->id."').parent().slideUp('fast', function(){ $(this).remove(); }); }, ".intval($autoclose)."*1000);");
        }
        if($closeable)
            $this->sub->content("<span class='ui-icon ui-icon-close' onclick=\"$(this).parent().parent().slideUp('fast', function(){ $(this).remove(); })\"></span>");
		$this->sub->content("<p><span class='ui-icon ui-icon-$icon'></span>$message</p>");
		
		$this->InitFunctionName = false;
        $this->messages[] = $message;            
	}
	
	/**
	 * Creates a new uiMessage as hint.
	 * 
	 * @param string $message Hint text
     * @param bool $closeable Display close button true|false
     * @param int|bool $autoclose If int, represents the number of seconds until autoclose. If True will be 10 seconds, if false autoclose is disabled.
	 * @return static A new uiMessage 
	 */
	static function Hint($message,$closeable=true,$autoclose=false)
	{
		return new uiMessage($message,'highlight',$closeable,$autoclose);
	}
	
	/**
	 * Creates a new uiMessage as error.
	 * 
	 * @param string $message Error text
     * @param bool $closeable Display close button true|false
     * @param int|bool $autoclose If int, represents the number of seconds until autoclose. If True will be 10 seconds, if false autoclose is disabled.
	 * @return static A new uiMessage 
	 */
	static function Error($message,$closeable=true,$autoclose=false)
	{
		return new uiMessage($message,'error',$closeable,$autoclose);
	}
	
    /**
     * @override
     */
	function &content($content, $replace = false)
	{
		if( $this->sub )
		{
			$this->sub->insert($content,1);
			return $this;
		}
		return parent::content($content, $replace);
	}
	
    /**
     * @override
     */
	function append($content)
	{
		if( $this->sub )
			return $this->sub->insert($content,1);
		return parent::append($content);
	}
    
    /**
     * Prepends a line to the message.
     * 
     * @param mixed $message The message to be added
     * @param string $icon Icon to be used
     * @return static
     */
    function prependLine($message,$icon='blank')
    {
        array_unshift($this->messages,$message);
        $this->sub->prepend("<p><span class='ui-icon ui-icon-$icon'></span>$message</p>");
        return $this;
    }
    
    /**
     * Adds a line to the message.
     * 
     * @param mixed $message The message to be added
     * @param string $icon Icon to be used
     * @return static
     */
    function addLine($message,$icon='blank')
    {
        $this->messages[] = $message;
        $this->sub->content("<p><span class='ui-icon ui-icon-$icon'></span>$message</p>");
        return $this;
    }
    
    /**
     * Adds lines to the message.
     * 
     * @param array $messages Array of messages to be added
     * @param string $icon Icon to be used
     * @return static
     */
    function addLines($messages,$icon='blank')
    {
        foreach( $messages as $m )
            $this->addLine($m,$icon);
        return $this;
    }
}
