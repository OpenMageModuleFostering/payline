<?php
/**
 * Class used as a datasource to display available payment actions
 */
class Monext_Payline_Model_Datasource_Actions
{
    public function toOptionArray()
    {
        return array(
        array('value' => 100, 'label'=>Mage::helper('payline')->__('authorization')),
        array('value' => 101, 'label'=>Mage::helper('payline')->__('authorization + capture'))
        );
    }
}
