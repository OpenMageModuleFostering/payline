<?php
/**
 * Class used as a datasource to display widget options
 */
class Monext_Payline_Model_Datasource_Widgettemplate
{
    public function toOptionArray()
    {
        return array(
        array('value' => 0, 'label'=>Mage::helper('payline')->__('disabled')),
        array('value' => 'lightbox', 'label'=>Mage::helper('payline')->__('lightbox')),
        array('value' => 'column', 'label'=>Mage::helper('payline')->__('column')),
        array('value' => 'tab', 'label'=>Mage::helper('payline')->__('tab'))
        );
    }
}
