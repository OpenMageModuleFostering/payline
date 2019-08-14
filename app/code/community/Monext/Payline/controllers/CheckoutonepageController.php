<?php
/**
 * This controller initialize the checkout for the oneclick payment
 */
require_once 'Mage/Checkout/controllers/OnepageController.php';
class Monext_Payline_CheckoutonepageController extends Mage_Checkout_OnepageController {
    
    /**
     * Initialize the onepage checkout by 1 click
     */
    public function indexAction(){
        if (!Mage::getStoreConfig('payment/PaylineWALLET/active')){
            $this->_redirect('checkout/onepage');
            return;
        }
        //INIT
        $customer=Mage::getSingleton('customer/session')->getCustomer();
        
        //Customer has wallet?
        if (!(Mage::getSingleton('customer/session')->isLoggedIn())){
            $this->_redirect('customer/account/login');
            return;
        }
        
        if (!($walletId=$customer->getWalletId())){
            $this->_redirect('checkout/onepage');
            return;
        }
        
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        /* @var $onepage Mage_Checkout_Model_Type_Onepage */
        $onepage = Mage::getSingleton('checkout/type_onepage');
        $quote=$onepage->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        
        //Check if the wallet payment is available
        /* @var $walletPaymentMethod Monext_Payline_Model_Wallet */
        $walletPaymentMethod=Mage::getModel('payline/wallet');
        $error='';
        if(!$walletPaymentMethod->checkExpirationDate()){
            $error=$this->__('The credit card of your wallet has expired.');
            $error.='<a href="'.Mage::getUrl('payline/wallet/manage').'">'.
                $this->__('Update your wallet information').'</a>'.
                $this->__(' or process your order below.');
            Mage::getSingleton('checkout/session')->addError($error);    
            $this->_redirect('checkout/cart');
            return;
        }elseif(!$walletPaymentMethod->isAvailable()){
            $error=$this->__('The wallet payment method is disabled. You can process your order below.');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure'=>true)));
        //Init customer quote, isMultiShipping false
        $onepage->initCheckout(); 

        //Checkout enabled?
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }

        if (version_compare(Mage::getVersion(), '1.4', 'ge')){
            $onepage->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
        }

        
        //Get rid of a few problems if order not completed
        //$onepage->getQuote()->re//moveAllAddresses();
        $shippingAddressId = $this->getRequest()->getPost('shipping_address_id', false);
        $billingAddressId = $this->getRequest()->getPost('billing_address_id', false);
/* PATCH for 1clic btn on checkout/cart */
        if (empty($shippingAddressId)){
            if($customer->getPrimaryShippingAddress()){
                $shippingAddressId = $customer->getPrimaryShippingAddress()->getId();
            }else{
                Mage::getSingleton('checkout/session')->addError($this->__('You must add an address to your account to be able to checkout in one clic.'));
                $this->_redirect('checkout/cart');
                return;
            }
        }
        if (empty($billingAddressId)){
            if ($customer->getPrimaryBillingAddress()){
                $billingAddressId = $customer->getPrimaryBillingAddress()->getId();
            }else{
                Mage::getSingleton('checkout/session')->addError($this->__('You must add an address to your account to be able to checkout in one clic.'));
                $this->_redirect('checkout/cart');
                return;
            }
        }
/* PATCH end */
        $billingAddress=Mage::getModel('customer/address')->load($billingAddressId)->getData();
        $shippingAddress=Mage::getModel('customer/address')->load($shippingAddressId)->getData();
        $billingAddressForCheckout=array(
            'address_id'             =>     $billingAddress['entity_id'],
        );
        $shippingAddressForCheckout=array(
            'address_id'             =>     $shippingAddress['entity_id'],
        );
       
        $onepage->saveBilling($billingAddressForCheckout, $billingAddressId);
        
        $onepage->saveShipping($shippingAddressForCheckout, $shippingAddressId);

        // Get available shipping rates; if one is available by default then use it, otherwise ask customer
        if ($methodCode=Mage::getStoreConfig('payment/PaylineWALLET/default_shipping_method')){
            //Needed to get shipping methods
            $onepage->getQuote()->getShippingAddress()->collectShippingRates()->save();
            $array=$onepage->saveShippingMethod($methodCode);
            
            Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$quote));
        }
        $shippingMethod=$onepage->getQuote()->getShippingAddress()->getShippingMethod();
        
        $data=array('method'=>'PaylineWALLET');
        $array=$onepage->savePayment($data);
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_getChargeProgress();
        
        $gotoSection = 'review';
        //If the shipping method is not configured, or the shipping address doesn't fit, 
        //the shipping method template is different : when the form is validated, the payment method will be validated as well
        if (!isset($shippingMethod) || empty($shippingMethod)){
            $this->getLayout()->getBlock('checkout.onepage.shipping_method')->setTemplate('payline/checkout/onepage/shipping-method.phtml');
            $gotoSection = 'shipping_method';
        }
        Mage::register('payline-goto-section', $gotoSection);
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->renderLayout();
    }
    
    public function reviewAction()
    {
        $this->loadLayout('checkout_onepage_review');
        $this->renderLayout();
    }
    
    protected function _getChargeProgress()
    {
        $steps = array('billing', 'shipping', 'shipping_method', 'payment');
        $checkout = Mage::getSingleton('checkout/session');
        foreach ($steps as $step) {
            $checkout->setStepData($step, 'complete', true);
            $checkout->setStepData($step, 'allow', true);
        }
    }

}