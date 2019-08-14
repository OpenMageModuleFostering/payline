<?php
/**
 * Class used as a datasource to display available payment actions
 */
class Monext_Payline_Model_Datasource_Paymentactionsreauth
{
    public function toOptionArray()
    {
        return array(
            array('value' => 101, 'label'=>Mage::helper('payline')->__('re-authorization + capture')),
            array('value' => 100, 'label'=>Mage::helper('payline')->__('re-authorization'))
        );
    }
}
