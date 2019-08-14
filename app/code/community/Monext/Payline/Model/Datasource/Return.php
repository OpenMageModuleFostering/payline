<?php
/**
 * Class used as a datasource to display return options to the store after payment refused
 */
class Monext_Payline_Model_Datasource_Return
{
	const CART_EMPTY      = 0;
	const CART_FULL       = 1;
	const HISTORY_ORDERS  = 2;
	
    public function toOptionArray()
    {
        return array(
			array('value' => self::CART_EMPTY, 'label'=> Mage::helper('payline')->__('Empty cart')),
			array('value' => self::CART_FULL, 'label'=> Mage::helper('payline')->__('Cart with items')),
			array('value' => self::HISTORY_ORDERS, 'label'=> Mage::helper('payline')->__('Orders history'))
        );
    }
}
