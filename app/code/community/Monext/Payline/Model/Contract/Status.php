<?php

/**
 * Payline contracts status model 
 */

class Monext_Payline_Model_Contract_Status extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('payline/contract_status');
    }
}
?>
