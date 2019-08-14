<?php

class Monext_Payline_Model_Total_Nx_Quote extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode('payline_nx');
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return Mage::helper('payline')->__('Payline fees');
    }

    /**
     * Collect totals information
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {	
        parent::collect($address);

		$applyCosts = (int)Mage::getStoreConfig('payment/PaylineNX/cost_type');
		if(!$applyCosts) {
			return $this;			
		}
		
        if (($address->getAddressType() == 'billing')) { // we collect fee for only one address
            return $this;
        }	  
		
		$quote = $address->getQuote();
		if($quote) {
			$payment = $quote->getPayment();
			if($payment && ($payment->getMethod() == 'PaylineNX')) {
				$fee = Mage::getModel('payline/fees')->getCollection()
							->addFieldtoFilter('quote_id',$quote->getId())->getFirstItem();
				if($fee->getId()) {						
					$this->_addAmount($fee->getAmount());
					$this->_addBaseAmount($fee->getBaseAmount());
				}
			}
		}

        return $this;
    }

    /**
     * Add fees to address object
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
		$applyCosts = (int)Mage::getStoreConfig('payment/PaylineNX/cost_type');
		if(!$applyCosts) {
			return $this;			
		}	

        if (($address->getAddressType() == 'billing')) { // we collect fee for only one address
			$quote = $address->getQuote();
			if($quote) {
				$payment = $quote->getPayment();
				if($payment && ($payment->getMethod() == 'PaylineNX')) {
					$fee = Mage::getModel('payline/fees')->getCollection()
							->addFieldtoFilter('quote_id',$quote->getId())->getFirstItem();
					if($fee->getId()) {
						$address->addTotal(array(
							'code'  => $this->getCode(),
							'title' => $this->getLabel(),
							'value' => $fee->getAmount()
						));
					}
				}
			}
		}

        return $this;
    }
}

?>
