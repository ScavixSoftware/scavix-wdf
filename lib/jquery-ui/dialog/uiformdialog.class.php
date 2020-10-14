<?php
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\JQueryUI\Dialog;

use ScavixWDF\Controls\Table\Table;

default_string('TITLE_DIALOG', 'Dialog');

/**
 * Dialog that contains a single form.
 * 
 * This is handy for AJAX based option requests.
 */
class uiFormDialog extends uiDialog
{
    var $form;
    
	function __initialize($title,$width = 450, $height = 300)
    {
        $options = [
            'width' => $width,
            'height' => $height,
            'autoOpen' => false, 
            'show' => ['effect' => 'fade', 'duration' => 300], 
            'hide' => ['effect' => 'fade', 'duration' => 300]
        ];
        parent::__initialize($title, $options);
        $this->form = $this->content(\ScavixWDF\Controls\Form\Form::Make());
        $this->AddButton("BTN_OK", "$(this).closest('.ui-dialog').find('form').submit({$this->CloseButtonAction}).submit();");
        $this->AddButton("BTN_CANCEL",$this->CloseButtonAction);
        $this->script("$('#{self}').dialog('open');");
    }
    
    private function setButtonText($index,$text)
    {
        $keys = array_keys($this->Buttons);
        $keys[$index] = $text;
        $this->Buttons = array_combine($keys, array_values($this->Buttons));
        return $this;
    }
    
    /**
     * Sets the text for the OK button.
     * 
     * @param string $text Text
     * @return $this
     */
    function setOkText($text) { return $this->setButtonText(0, $text); }

    /**
     * Sets the text for the Cancel button.
     * 
     * @param string $text Text
     * @return $this
     */
    function setCancelText($text) { return $this->setButtonText(1, $text); }
}
