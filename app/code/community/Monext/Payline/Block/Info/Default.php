<?php
/**
 * Base payment iformation block
 *
 */
class Monext_Payline_Block_Info_Default extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payline/payment/info/monext.phtml');
    }
}