<?php
/**
 * Payline Nx web payment method 
 */
class Monext_Payline_Model_Nx extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'PaylineNX';
    protected $_formBlockType = 'payline/nx';
    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund = false;

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('payline/index/nx');
    }
}
