<?php

/**
 * Payline contracts model 
 */

class Monext_Payline_Model_Contract extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('payline/contract');
    }
}
?>
