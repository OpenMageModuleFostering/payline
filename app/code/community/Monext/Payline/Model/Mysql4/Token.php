<?php

/**
 * Payline token resource model 
 */

class Monext_Payline_Model_Mysql4_Token extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct() 
    {
        $this->_init('payline/token', 'id');
    }
}
