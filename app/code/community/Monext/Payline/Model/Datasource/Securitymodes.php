<?php

/**
 * Class used as a datasource to display available payment security modes
 */
class Monext_Payline_Model_Datasource_Securitymodes
{
    public function toOptionArray()
    {
        return array(
        array('value' => '', 'label'=>Mage::helper('payline')->__('- none -')),
        array('value' => 'SSL', 'label'=>Mage::helper('payline')->__('SSL'))
        );
    }
}
