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
    * Check whether payment method can be used
    * Rewrited from Abstract class
    * TODO: payment method instance is not supposed to know about quote
    * @param Mage_Sales_Model_Quote
    * @return bool
    */
	public function isAvailable($quote = null)
    {
    	if(!is_null($quote) && Mage::app()->getStore()->roundPrice($quote->getGrandTotal()) > 0){
    		return parent::isAvailable($quote);
    	}else{
    		return false;
    	}
    }
    
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
