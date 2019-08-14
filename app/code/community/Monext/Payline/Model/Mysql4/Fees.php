<?php

/**
 * Payline nx fees resource model 
 */

class Monext_Payline_Model_Mysql4_Fees extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct() 
    {
        $this->_init('payline/fees', 'id');
    }
}
