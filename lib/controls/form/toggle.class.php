<?php
/**
* Copyright (c) since 2023 Scavix Software GmbH & Co. KG
*
* @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
* @copyright since 2023 Scavix Software GmbH & Co. KG
*/
namespace ScavixWDF\Controls\Form;
use ScavixWDF\Base\Control;

/**
 * This is a Toggle element.
 */
class Toggle extends Control
{
    public $cb, $thumb, $label;
    public $Options = [];

	function __construct($name = false)
	{
        parent::__construct('div');
        $this->attr('is','wdf-toggle');
        $this->label = Control::Make('label')->appendTo($this);
        $wrap = Control::Make('div')->appendTo($this);
        $this->cb = CheckBox::Make($name)->setValue(1)->appendTo($wrap);
        $this->thumb = Control::Make('span')->appendTo($wrap);
        if ($name)
            $this->addClass("toggle-$name");
        store_object($this,$this->id);
    }

    /**
     * Sets the current value.
     *
     * @param mixed $value The value
     * @return static
     */
    function setValue($value)
    {
        $this->cb->setValue($value);
        return $this;
    }

    /**
     * Sets if checked or not.
     *
     * @param bool $on true for checked, false for unchecked
     * @return static
     */
    function setChecked($on)
    {
        $this->cb->setChecked($on);
        if ($on)
            $this->addClass('active');
        else
            $this->removeClass('active');
        return $this;
    }

    /**
     * Sets an URL to be called on change.
     * @param string $url The target URL
     * @return static
     */
    function setChangeUrl($url)
    {
        $this->data('change-url', $url);
        return $this;
    }

    /**
     * Sets a label.
     * @param string $label The label
     * @return static
     */
    function setLabel($label)
    {
        $this->label->content($label, true);
        return $this;
    }
}
