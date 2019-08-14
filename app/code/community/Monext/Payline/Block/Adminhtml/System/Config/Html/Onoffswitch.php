<?php

class Monext_Payline_Block_Adminhtml_System_Config_Html_Onoffswitch extends Mage_Core_Block_Html_Select
{
    /**
     * Render HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }

        $html = '<div class="onoffswitch">' .
                '<input type="checkbox" name="' . $this->getName() . '" class="onoffswitch-checkbox" id="' . preg_replace('/[\[\]]+/', '_', $this->getName()) . '" value="1" ' .
                '#{option_extra_attr_' . self::calcOptionHash(1) . '}' .
                '>' .
                '<label class="onoffswitch-label" for="' . preg_replace('/[\[\]]+/', '_', $this->getName()) . '">' .
                '<span class="onoffswitch-inner"></span>' .
                '<span class="onoffswitch-switch"></span>' .
                '</label>' .
                '</div>';

        return $html;
    }


}
