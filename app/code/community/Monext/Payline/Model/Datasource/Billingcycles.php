<?php
/**
 * Class used as a datasource to display available billing cycles
 */
class Monext_Payline_Model_Datasource_Billingcycles
{
    public function toOptionArray()
    {
        return array(
        array('value' => 10, 'label'=>Mage::helper('payline')->__('daily')),
        array('value' => 20, 'label'=>Mage::helper('payline')->__('weekly')),
        array('value' => 30, 'label'=>Mage::helper('payline')->__('twice a month')),
        array('value' => 40, 'label'=>Mage::helper('payline')->__('monthly'))
        );
    }
}
