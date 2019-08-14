<?php

/**
 * Payline token collection 
 */

class Monext_Payline_Model_Mysql4_Token_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct() {
        $this->_init('payline/token');
    }
}
