<?php

/**
 * Payline product categories resource model 
 */

class Monext_Payline_Model_Mysql4_Productcategories extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct() 
    {
        $this->_init('payline/productcategories', 'id');
    }
}
