<?php

/**
 * This controller manage wallet in the customer account (subscribe, update, disable, ...)
 * @author fague
 *
 */
class Monext_Payline_WalletController extends Mage_Core_Controller_Front_Action
{

    /**
     * Action predispatch
     *
     * Check customer authentication
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
        if (!Mage::getStoreConfig('payment/PaylineWALLET/active')) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Simply redirect to manageAction
     */
    public function indexAction()
    {
        $this->_redirect('*/*/manage');
    }

    /**
     * Display user's wallet informations
     */
    public function manageAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if (!($walletId = $customer->getWalletId())) {
            $this->_redirect('payline/wallet/subscribe');
            return;
        }

        $this->loadLayout();

        $this->getLayout()->getBlock('head')->setTitle($this->__('Wallet management'));

        $res = Mage::getModel('payline/wallet')->getWalletData();
        if ($res) {
            $this->getLayout()->getBlock('payline-wallet-details')->setWallet($res);
        }

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('core/session');
        $this->renderLayout();
    }

    /**
     * Display wallet subscription iframe
     */
    public function subscribeAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer->getWalletId()) {
            $this->_redirect('payline/wallet/manage');
            return;
        }
        $this->loadLayout();

        $this->getLayout()->getBlock('head')->setTitle($this->__('Subscribe to wallet'));

        /* @var $paylineHelper Monext_Payline_Helper_Data */
        $paylineHelper                  = Mage::helper('payline');
        $paylineHelper->notificationUrl = Mage::getUrl('payline/unloggedwallet/subscribeNotify');
        $paylineHelper->returnUrl       = Mage::getUrl('payline/unloggedwallet/subscribeReturn');
        $paylineHelper->cancelUrl       = Mage::getUrl('payline/unloggedwallet/subscribeCancel');
        /* @var $paylineSDK PaylineSDK */
        $paylineSDK                     = $paylineHelper->initPayline('WALLET');
        //TODO add address data
        $array                          = array(
            'buyer'           => array(
                'lastName'  => Mage::helper('payline')->encodeString(substr($customer->getLastname(), 0, 100)),
                'firstName' => Mage::helper('payline')->encodeString(substr($customer->getFirstname(), 0, 100)),
                'walletId'  => Mage::getModel('payline/wallet')->generateWalletId()
            ),
            'billingAddress'  => array(),
            'shippingAddress' => array()
        );

        $email         = $customer->getEmail();
        $pattern       = '/\+/i';
        $charPlusExist = preg_match($pattern, $email);
        if (strlen($email) <= 50 && Zend_Validate::is($email, 'EmailAddress') && !$charPlusExist) {
            $array['buyer']['email'] = Mage::helper('payline')->encodeString($email);
        } else {
            $array['buyer']['email'] = '';
        }
        $array['buyer']['customerId'] = Mage::helper('payline')->encodeString($email);
        ;

        $array['contractNumber'] = $paylineHelper->contractNumber;
        $array['contracts']      = explode(';', $paylineHelper->contractNumberList);

        $array['updatePersonalDetails'] = (Mage::getStoreConfig('payment/PaylineWALLET/update_personal_details') ? 1 : 0);
        $array['version']               = Monext_Payline_Helper_Data::VERSION;

        // ADD CONTRACT WALLET ARRAY TO $array
        $array['walletContracts'] = Mage::helper('payline')->buildContractNumberWalletList();

        try {
            $resultCreateWebWallet = $paylineSDK->createWebWallet($array);
        } catch (Exception $e) {
            Mage::logException($e);
            $msgLog = 'Unknown PAYLINE ERROR on createWebWallet (Payline unreachable?)';
            $msg    = Mage::helper('payline')->__('Error during subscription');
            Mage::helper('payline/logger')->log('[subscribeAction] ' . $msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
        }
        if (is_string($resultCreateWebWallet)) {
            $msgLog = 'PAYLINE ERROR on createWebWallet: ' . $resultCreateWebWallet;
            $msg    = Mage::helper('payline')->__('Error during subscription');
            Mage::helper('payline/logger')->log('[subscribeAction] ' . $msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
            $this->_redirect('customer/account');
            return;
        } elseif (!isset($resultCreateWebWallet['result']) || $resultCreateWebWallet['result']['code'] != '00000') {
            if (isset($resultCreateWebWallet['result'])) {
                $msgLog = 'PAYLINE ERROR on createWebWallet: ' . $resultCreateWebWallet['result']['code'] . ' ' . $resultCreateWebWallet['result']['longMessage'];
            } else {
                $msgLog = 'Unknown PAYLINE ERROR on createWebWallet';
            }
            $msg = Mage::helper('payline')->__('Error during subscription');
            Mage::helper('payline/logger')->log('[subscribeAction] ' . $msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
            $this->_redirect('customer/account');
            return;
        }

        //save contract number
        $customer->setWalletContractNumber($paylineHelper->contractNumber)->save();

        $this->getLayout()->getBlock('payline-wallet-subscribe-cmsblock')->setBlockId(Mage::getStoreConfig('payment/PaylineWALLET/payline_register-oneclick_customeraccount'));
        $urlPayline = $resultCreateWebWallet['redirectURL'];

        if (Mage::getStoreConfig('payment/PaylineWALLET/update_personal_details')) {
            $iframeClass = 'iframe-subscribe-wallet iframe-with-perso-data';
        } else {
            $iframeClass = 'iframe-subscribe-wallet';
        }
        $this->getLayout()->getBlock('payline-wallet-subscribe-iframe')->setIframeClassName($iframeClass);
        $this->getLayout()->getBlock('payline-wallet-subscribe-iframe')->setIframeSrc($urlPayline);

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('core/session');
        $this->renderLayout();
    }

    /**
     * Disable customer wallet, delete customer's walletId attribute value
     */
    public function disableAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if (!($walletId = $customer->getWalletId())) {
            $this->_redirect('payline/wallet/subscribe');
            return;
        }

        $paylineSDK = Mage::helper('payline')->initPayline('WALLET');
        $paylineSDK->setWalletIdList($walletId);
        $array      = array('contractNumber' => $customer->getWalletContractNumber(), 'cardInd' => '');
        try {
            $res = $paylineSDK->disableWallet($array);
            Mage::getSingleton('customer/session')->setWalletData(null);
        } catch (Exception $e) {
            $msgLog = 'Unknown PAYLINE ERROR on disableWallet (Payline unreachable?)';
            $msg    = Mage::helper('payline')->__('Error while disabling wallet');
            Mage::helper('payline/logger')->log('[disableAction] ' . $msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
        }

        if (!isset($res['result']) || ($res['result']['code'] != '02500' && $res['result']['code'] != '02501' && $res['result']['code'] == '02505')) {
            if (isset($res['result'])) {
                $msgLog = 'PAYLINE ERROR on disableWallet: ' . $res['result']['code'] . ' ' . $res['result']['longMessage'];
            } else {
                $msgLog = 'Unknown PAYLINE ERROR on disableWallet';
            }
            $msg = Mage::helper('payline')->__('Error during disableWallet');
            Mage::helper('payline/logger')->log('[disableAction] ' . $msg);
            Mage::getSingleton('customer/session')->addError($msg);
            $this->_redirect('customer/account');
            return;
        }

        $customer->setWalletId();
        $customer->setWalletContractNumber();
        $customer->save();
        Mage::getSingleton('customer/session')->addSuccess($this->__('Your wallet has been disabled.'));
        $this->_redirect('customer/account');
        return;
    }

    /**
     * 
     * Display wallet update iframe
     * Update payment card, not perso details
     */
    public function updateAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if (!($walletId = $customer->getWalletId())) {
            $this->_redirect('payline/wallet/subscribe');
            return;
        }
        $this->loadLayout();

        $this->getLayout()->getBlock('head')->setTitle($this->__('Update wallet'));

        /* @var $paylineHelper Monext_Payline_Helper_Data */
        $paylineHelper                  = Mage::helper('payline');
        $paylineHelper->notificationUrl = Mage::getUrl('payline/unloggedwallet/updateNotify');
        $paylineHelper->returnUrl       = Mage::getUrl('payline/unloggedwallet/updateReturn');
        $paylineHelper->cancelUrl       = Mage::getUrl('payline/unloggedwallet/updateCancel');
        /* @var $paylineSDK PaylineSDK */
        $paylineSDK                     = $paylineHelper->initPayline('WALLET');
        $array                          = array(
            'walletId'              => $walletId,
            'contractNumber'        => ($customer->getWalletContractNumber() ? $customer->getWalletContractNumber() : $paylineHelper->contractNumber),
            'cardInd'               => '',
            'updatePersonalDetails' => (Mage::getStoreConfig('payment/PaylineWALLET/update_personal_details') ? 1 : 0),
            'updatePaymentDetails'  => (Mage::getStoreConfig('payment/PaylineWALLET/update_payment_details') ? 1 : 0),
            'updateOwnerDetails'    => 0,
            'version'               => Monext_Payline_Helper_Data::VERSION,
            'billingAddress'        => '',
            'shippingAddress'       => '',
            'buyer'                 => array(
                'lastName'  => Mage::helper('payline')->encodeString(substr($customer->getLastname(), 0, 100)),
                'firstName' => Mage::helper('payline')->encodeString(substr($customer->getFirstname(), 0, 100)),
                'walletId'  => $walletId
            )
        );
        $email                          = $customer->getEmail();
        $pattern                        = '/\+/i';
        $charPlusExist                  = preg_match($pattern, $email);
        if (strlen($email) <= 50 && Zend_Validate::is($email, 'EmailAddress') && !$charPlusExist) {
            $array['buyer']['email'] = Mage::helper('payline')->encodeString($email);
        } else {
            $array['buyer']['email'] = '';
        }
        $paylineSDK->setPrivate(array('customerId' => $customer->getId()));
        try {
            $res = $paylineSDK->updateWebWallet($array);
        } catch (Exception $e) {
            Mage::logException($e);
            $msgLog = 'Unknown PAYLINE ERROR on updateWebWallet (Payline unreachable?)';
            $msg    = Mage::helper('payline')->__('Error during wallet update');
            Mage::helper('payline/logger')->log('[updateAction] ' . $msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
        }
        if (is_string($res)) {
            Mage::helper('payline/logger')->log('[updateAction] ' . $res);
            Mage::getSingleton('customer/session')->addError($res);
            $this->_redirect('customer/account');
            return;
        } elseif (!isset($res['result']) || ($res['result']['code'] != '00000' && $res['result']['code'] != '02502')) {
            if (isset($res['result'])) {
                $msgLog = 'PAYLINE ERROR on updateWebWallet: ' . $res['result']['code'] . ' ' . $res['result']['longMessage'];
            } else {
                $msgLog = 'Unknown PAYLINE ERROR on updateWebWallet';
            }
            $msg = Mage::helper('payline')->__('Error while updating wallet');
            Mage::helper('payline/logger')->log('[updateAction] ' . $msgLog);
            Mage::getSingleton('customer/session')->addError($msg);
            $this->_redirect('customer/account');
            return;
        }
        $urlPayline = $res['redirectURL'];

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('core/session');

        if (Mage::getStoreConfig('payment/PaylineWALLET/update_personal_details')) {
            $iframeClass = 'iframe-update-wallet iframe-with-perso-data';
        } else {
            $iframeClass = 'iframe-update-wallet';
        }
        $this->getLayout()->getBlock('payline-wallet-update-iframe')->setIframeClassName($iframeClass);
        $this->getLayout()->getBlock('payline-wallet-update-iframe')->setIframeSrc($urlPayline);

        $this->renderLayout();
    }

}
