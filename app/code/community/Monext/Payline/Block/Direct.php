<?php
class Monext_Payline_Block_Direct extends Mage_Payment_Block_Form {
    protected $_canUseForMultishipping  = false;
    
    protected function _construct() {
        parent::_construct();
        
        $this->setTemplate('payline/Direct.phtml');
        $redirectMsg=Mage::getStoreConfig('payment/PaylineNX/redirect_message');
        $this->setRedirectMessage($redirectMsg);
        $this->setBannerSrc($this->getSkinUrl('images/payline-logo.png'));
    }
    
    public function getCcAvailableTypes()
    {		
		$types = array();
		$contracts = Mage::getModel('payline/contract')->getCollection()
						->addFilterStatus(true,Mage::app()->getStore()->getId())
						->addFieldToFilter('contract_type',array('CB','AMEX','MCVISA'));
		
        foreach($contracts as $contract) {
			$types[$contract->getId()] = $contract->getName();
		}
        
        return $types;
    }
    
    public function getCcMonths()
    {
        $months = array();
        $months[0] =  Mage::helper('payline')->__('Month');
        $months['01'] = '01';
        $months['02'] = '02';
        $months['03'] = '03';
        $months['04'] = '04';
        $months['05'] = '05';
        $months['06'] = '06';
        $months['07'] = '07';
        $months['08'] = '08';
        $months['09'] = '09';
        $months['10'] = '10';
        $months['11'] = '11';
        $months['12'] = '12';
        return $months;
    }
    
    public function getCcYears()
    {
        $years = array();
        $today = getdate();
        $years[0] =  Mage::helper('payline')->__('Year');
        $index1 = substr($today['year'],2);
        
        $years[$index1] = $today['year'];
        $years[$index1+1] = $years[$index1]+1;
        $years[$index1+2] = $years[$index1]+2;
        $years[$index1+3] = $years[$index1]+3;
        return $years;
    }
    
    public function hasVerification() 
    {
        return true;
    }
}
