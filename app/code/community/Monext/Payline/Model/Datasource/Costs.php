<?php
/**
 * Class used as a datasource to display available costs for Nx payment
 */
class Monext_Payline_Model_Datasource_Costs
{
	const NO_COST      = 0;
	const COST_FIXED   = 1;
	const COST_PERCENT = 2;
	
    public function toOptionArray()
    {
        return array(
			array('value' => self::NO_COST, 'label'=> Mage::helper('payline')->__('No costs')),
			array('value' => self::COST_FIXED, 'label'=> Mage::helper('payline')->__('Fixed')),
			array('value' => self::COST_PERCENT, 'label'=> Mage::helper('payline')->__('Percentage'))
        );
    }
}
