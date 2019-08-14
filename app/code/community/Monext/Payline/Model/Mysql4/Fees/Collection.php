<?php

/**
 * Payline nx fees collection 
 */

class Monext_Payline_Model_Mysql4_Fees_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct() {
        $this->_init('payline/fees');
    }
}
