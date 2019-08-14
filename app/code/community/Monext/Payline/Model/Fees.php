<?php

/**
 * Payline nx fees model 
 */

class Monext_Payline_Model_Fees extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('payline/fees');
    }
}
