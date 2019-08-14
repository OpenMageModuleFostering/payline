<?php
/**
 * On IE, seems like cookies are not sent when in an iframe 
 * We don't have to be logged since the customer email is transmitted in the order - we can retrieve the customer object from it
 * @todo Only move the customer out of the iframe here, and do the getWallet in the WalletController 
 *
 */
class Monext_Payline_UnloggedwalletController extends Mage_Core_Controller_Front_Action
{
    /**
     * New subscription notification
     */
    public function subscribeNotifyAction(){
        $res = Mage::helper('payline')->initPayline('WALLET')->getWebWallet(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        $redirectUrl="payline/wallet/manage";
        if (!isset($res['result']) || $res['result']['code']!='02500'){
            if(isset($res['result'])){
                $msgLog='PAYLINE ERROR on getWebWallet: '.$res['result']['code']. ' '.$res['result']['longMessage'];
            }else{
                $msgLog='Unknown PAYLINE ERROR on getWebWallet';
            }
            $msg=Mage::helper('payline')->__('Error during subscription');
            Mage::helper('payline/Logger')->log('[subscribeNotifyAction] ' .$msgLog);
            Mage::getSingleton('core/session')->addError($msg);
            $redirectUrl="payline/wallet/subscribe";
            return $redirectUrl;
        }
        
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($res['wallet']['email']);
        $customer->setWalletId($res['wallet']['walletId']);
        $customer->save();
        $msg=Mage::helper('payline')->__('Wallet subscription succeed');
        Mage::getSingleton('core/session')->addSuccess($msg);
        return $redirectUrl;
    }
    
    /**
     * Return from the iframe
     * Show a page in the iframe which only redirect the user to the manage wallet page (or the subscription page if error)
     */
    public function subscribeReturnAction(){
        $this->loadLayout();
        $redirectUrl=$this->subscribeNotifyAction();
        $this->getLayout()->getBlock('payline-iframe-leaver')->setRedirectUrl($redirectUrl);
        $this->renderLayout();
    }
    
    /**
     * Return from the iframe
     * Show a page in the iframe which only redirect the user to the subscription page
     */
    public function subscribeCancelAction(){
        $msg=Mage::helper('payline')->__('Error during subscription');
        $msgLog=$msg." (cancelAction)";
        Mage::helper('payline/Logger')->log('[subscribeCancelAction] ' .$msgLog);
        Mage::getSingleton('core/session')->addError($msg);
        
        $this->loadLayout();
        $redirectUrl='payline/wallet/subscribe';
        $this->getLayout()->getBlock('payline-iframe-leaver')->setRedirectUrl($redirectUrl);
        $this->renderLayout();
    }
    
    /**
     * New subscription notification
     */
    public function updateNotifyAction(){
        $res = Mage::helper('payline')->initPayline('WALLET')->getWebWallet(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        $redirectUrl="payline/wallet/manage";
        if (!isset($res['result']) || $res['result']['code']!='02500'){
            if(isset($res['result'])){
                $msgLog='PAYLINE ERROR on getWebWallet after update: '.$res['result']['code']. ' '.$res['result']['longMessage'];
            }else{
                $msgLog='Unknown PAYLINE ERROR on getWebWallet after update';
            }
            $msg=Mage::helper('payline')->__('Error during update');
            Mage::helper('payline/Logger')->log('[updateNotifyAction] ' .$msgLog);
            Mage::getSingleton('core/session')->addError($msg);
            $redirectUrl="payline/wallet/udpate";
            return $redirectUrl;
        }
        Mage::getSingleton('customer/session')->setWalletData(null);
        $msg=Mage::helper('payline')->__('Wallet update succeed');
        Mage::getSingleton('core/session')->addSuccess($msg);
        return $redirectUrl;
    }
    
    /**
     * Return from the iframe
     * Show a page in the iframe which only redirect the user to the manage wallet page
     */
    public function updateReturnAction(){
        $this->loadLayout();
        $redirectUrl=$this->updateNotifyAction();
        $this->getLayout()->getBlock('payline-iframe-leaver')->setRedirectUrl($redirectUrl);
        $this->renderLayout();
    }
    
    /**
     * Return from the iframe
     * Show a page in the iframe which only redirect the user to the manage page
     */
    public function updateCancelAction(){
        $msg=Mage::helper('payline')->__('Error during update');
        $msgLog=$msg." (cancelAction)";
        Mage::helper('payline/Logger')->log('[updateCancelAction] ' .$msgLog);
        Mage::getSingleton('core/session')->addError($msg);
        
        $this->loadLayout();
        $redirectUrl='payline/wallet/manage';
        $this->getLayout()->getBlock('payline-iframe-leaver')->setRedirectUrl($redirectUrl);
        $this->renderLayout();
    }
}