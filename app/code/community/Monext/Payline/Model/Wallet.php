<?php
/**
 * Payline Wallet payment method, or pay in 1 click 
 */
class Monext_Payline_Model_Wallet extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'PaylineWALLET';
    protected $_formBlockType = 'payline/wallet';
    protected $_infoBlockType = 'payline/wallet_infos';
    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canVoid = true;
    
    protected $_walletData;
    
    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('payline/index/wallet');
    }
    
    /**
     * Retrieve the expiration date of the saved credit card
     * @return string
     */
    public function getExpirationDate(){
        $wallet=$this->getWalletData();
        if (isset($wallet['card']) && isset($wallet['card']['expirationDate'])){
            $exp=$wallet['card']['expirationDate'];
            return $exp;
        }else{
            Mage::helper('payline/logger')->log('[getExpirationDate] Error while retrieving wallet for expiration date');
        }
    }

    /**
     * Check if the saved credit card expiration date is OK
     * @return bool
     */
    public function checkExpirationDate()
    {
        if (Mage::getSingleton('customer/session')->getCustomer()->getWalletId()) {
            $exp = $this->getExpirationDate();
            $date_exp=substr($exp, 2).substr($exp,0,2);
            if (date('ym')<=$date_exp){
                return true;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Check whether payment method can be used
     * Rewrited from Abstract class
     * TODO: payment method instance is not supposed to know about quote
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null){
        $checkResult = new StdClass;
        $checkResult->isAvailable=false;
        $customer=Mage::getSingleton('customer/session');
        if ($customer->isLoggedIn()){
            $customer=Mage::getModel('customer/customer')->load($customer->getId());
            if ($customer->getWalletId()){
                $checkResult->isAvailable=true;
            }
        }
        $checkResult->isAvailable=$this->checkExpirationDate();
        $checkResult->isAvailable = (
            $checkResult->isAvailable && 
            $this->getConfigData('active', ($quote ? $quote->getStoreId() : null))
        );
        
        // On Magento Pro 1.8, reward module is broken without quote. Let's provide it...
        if (!$quote)
            $quote=Mage::getSingleton('checkout/type_onepage')->getQuote();
        Mage::dispatchEvent('payment_method_is_active', array(
            'result'          => $checkResult,
            'method_instance' => $this,
            'quote'           => $quote,
        ));

        // disable method if it cannot implement recurring profiles management and there are recurring items in quote
        if ($checkResult->isAvailable) {
            //This is not implemented on Magenot 1.3
            if (method_exists($this,'canManageRecurringProfiles')){
                $implementsRecurring = $this->canManageRecurringProfiles();
                // the $quote->hasRecurringItems() causes big performance impact, thus it has to be called last
                if ($quote && (!$implementsRecurring) && $quote->hasRecurringItems()) {
                    $checkResult->isAvailable = false;
                }
            }
        }
        return $checkResult->isAvailable;
    }
    
    public function getWalletData(){
        if (!empty($this->_walletData)) {
            return $this->_walletData;
		}
		
        $customerSession=Mage::getSingleton('customer/session');
        if ($customerSession->getWalletData() != null) {
            return $customerSession->getWalletData();
        }
		
        if ($customerSession->isLoggedIn()){
            $customer=$customerSession->getCustomer();
            $walletId=$customer->getWalletId();
        }
		
        if (!isset($walletId) || empty($walletId)){
            return false;
        }
        /* @var $paylineSDK PaylineSDK */
		$helperPayline = Mage::helper('payline');
        $paylineSDK = $helperPayline->initPayline('WALLET');
		$walletContractNumber = $customer->getWalletContractNumber();
		if(!$walletContractNumber) {
			$walletContractNumber = $helperPayline->contractNumber;
		}
        $array=array('walletId'=>$walletId, 'cardInd' => '', 'contractNumber' => $walletContractNumber, 'version' => Monext_Payline_Helper_Data::VERSION);
		try{
            $res=$paylineSDK->getWallet($array);
        }catch(Exception $e){
            $msgLog='Unknown PAYLINE ERROR on getWallet for wallet '.$walletId.' (Payline unreachable?)';
            $msg=Mage::helper('payline')->__('Error while retrieving wallet information');
            Mage::helper('payline/logger')->log('[getWalletData] '.$msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
        }
        
        if (!isset($res['result']) || $res['result']['code']!='02500'){
            if(isset($res['result'])){
                $msgLog='PAYLINE ERROR on getWallet: '.$res['result']['code']. ' '.$res['result']['longMessage'].' (wallet '.$walletId.')';
            }else{
                $msgLog='Unknown PAYLINE ERROR on getWallet for wallet '.$walletId;
            }
            $msg=Mage::helper('payline')->__('Error while retrieving wallet information');
            Mage::helper('payline/logger')->log('[getWalletData] '.$msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
            return false;
        }else{
            $this->_walletData=$res['wallet'];
            $customerSession->setWalletData($res['wallet']);
            //$this->getInfoInstance()->setAdditionalInformation('owner','qsdf');
            return $res['wallet'];
        }
    }
    

    /**
     * Generate a random wallet_id
     * @param int $length
     * @return string
     */
    public function generateWalletId($length=32){
        $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWZ";
        $string = '';
        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters)-1)];
        }
        return $string;
    }
    
	/**
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @return  Monext_Payline_Model_Cpt
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::getModel('payline/cpt')->capture($payment,$amount, 'WALLET');
        return $this;
    }

    /**
     * Refund money
     *
     * @param   Varien_Object $invoicePayment
     * @return  Monext_Payline_Model_Cpt
     */
    public function refund(Varien_Object $payment, $amount)
    {
        Mage::getModel('payline/cpt')->refund($payment,$amount, 'WALLET');
        return $this;
    }
    
    /**
     * Cancel payment
     *
     * @param   Varien_Object $payment
     * @return  Monext_Payline_Model_Cpt
     */
    public function void(Varien_Object $payment)
    {
        Mage::getModel('payline/cpt')->void($payment, 'WALLET');
        return $this;
    }
}