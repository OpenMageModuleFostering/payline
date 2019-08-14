<?php

/**
 * Payline contracts status collection 
 */

class Monext_Payline_Model_Mysql4_Contract_Status_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct() {
        $this->_init('payline/contract_status');
    }
}

