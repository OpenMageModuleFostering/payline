<?php
class Monext_Payline_Block_Wallet_Checkoutbtn extends Mage_Core_Block_Template{
    protected $_template='payline/wallet/checkoutbtn.phtml';
    
    public function getRedirectUrl(){
        $redirectUrl = Mage::getUrl('payline/wallet/subscribe');
        if(Mage::getSingleton('customer/session')->isLoggedIn()){
            $customer=Mage::getSingleton('customer/session')->getCustomer();
            if ($customer->getWalletId()){
                //Check if the wallet payment is available
                /* @var $walletPaymentMethod Monext_Payline_Model_Wallet */
                $walletPaymentMethod=Mage::getModel('payline/wallet');
                if ($walletPaymentMethod->checkExpirationDate()){
                    $redirectUrl = Mage::getUrl('payline/checkoutonepage/');
                }
            }
        }
        return $redirectUrl;
    }
    
    public function hasToDisplay()
    {
        $walletEnable = Mage::getStoreConfig('payment/PaylineWALLET/active');
        $oneClicEnable = Mage::getStoreConfig('payment/PaylineWALLET/enable_one_clic');
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        return $walletEnable && $oneClicEnable && $customer->getWalletId() != '';
    }
}