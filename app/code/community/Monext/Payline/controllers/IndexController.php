<?php

/**
 * This controller manage all payline payment
 * cptAction, directAction, nxAction & walletAction are called just after the checkout validation
 * the return/notify/cancel are the urls called by Payline
 * An exception for notifyAction : it's not directly called by Payline, since it couldn't work in a local environment; it's then called by the returnAction.
 * @author fague
 *
 */
class Monext_Payline_IndexController extends Mage_Core_Controller_Front_Action
{
    /* @var $order Mage_Sales_Model_Order */
    private $order;
    
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * 
     * Set the order's status to the provided status (must be part of the cancelled state)  
     * Reinit stocks & redirect to checkout 
     * @param string $cancelStatus
     */
    private function cancelOrder($cancelStatus, $resCode = '',$message = ''){
        $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$cancelStatus,$message,false);
        Mage::helper('payline/payment')->updateStock($this->order);
        $this->order->save();
		
        $this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
    }

    /** 
     * Check if the customer is logged, and if it has a wallet
     * If not & if there is a walletId in the result from Payline, we save it
     */
    public function saveWallet($walletId){
        if (!Mage::getStoreConfig('payment/payline_common/automate_wallet_subscription')){
            return;
        }
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerSession->isLoggedIn()){
            $customer = $customerSession->getCustomer();
            if (!$customer->getWalletId()) {
                $customer->setWalletId($walletId);
                $customer->save();
            }
        }
    }
    
    /**
     * 
     * Initialise the requests param array
     * @return array
     */
    private function init()
    {
        $_session = Mage::getSingleton('checkout/session');
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($_session->getLastRealOrderId());
        return Mage::helper('payline/payment')->init($this->order);
    }

    /**
     * Force this action code of some payment methods to the given action code
     * @param $paymentMethod {string}
     * @param $array {array} conf array. $array is a reference, so no need to return it.
     * @param $actionCode {string} forced action code set in $array
     */
    private function forcePaymentActionTo($paymentMethod, &$array, $actionCode)
    {
        switch( $paymentMethod ) {
            case 'UKASH':
            case 'MONEYCLIC':
            case 'TICKETSURF':
            case 'SKRILL(MONEYBOOKERS)':
            case 'LEETCHI':
                Mage::helper('payline/logger')->log('[cptAction] order '.$array['order']['ref'].' - '.$paymentMethod.' selected => payment action is forced to '.$actionCode);
                $array['payment']['action'] = $actionCode;
                break;
            default: break;
        }
    }

    /**
     * Initialize the cpt payment request
     */
    public function cptAction(){
        //Check if wallet is sendable
        //Must be done before call to Payline helper initialisation
        $expiredWalletId=false;
        if(Mage::getSingleton('customer/session')->isLoggedIn()){
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if ($customer->getWalletId() && !Mage::getModel('payline/wallet')->checkExpirationDate()){
                 $expiredWalletId=true;
            }
        } 
        
        $array = $this->init();
        /* @var $paylineSDK PaylineSDK */
		$helperPayline = Mage::helper('payline');
        $paylineSDK = $helperPayline->initPayline('CPT',$array['payment']['currency']);
		$paymentMethod = $this->order->getPayment()->getCcType();
		$array['payment']['action'] = Mage::getStoreConfig('payment/PaylineCPT/payline_payment_action');
        $array['version'] = Monext_Payline_Helper_Data::VERSION;
		if($paymentMethod) {
            // debut ajout FSZ 15/11/2012
            Mage::helper('payline/logger')->log('[cptAction] order '.$array['order']['ref'].' - customer selected contract '.$paymentMethod);

            $contractCPT = Mage::getModel('payline/contract')
                ->getCollection()
                ->addFieldToFilter( 'number', $paymentMethod )
                ->getFirstItem();

            // $paymentMethod = contract number. Filter must be on contract type
            $this->forcePaymentActionTo( $contractCPT->getContractType(), $array, '101' );

            // fin ajout FSZ 15/11/2012
			$array['payment']['contractNumber'] = $paymentMethod;
			$array['contracts'] = array($paymentMethod);
		} else {
			$array['payment']['contractNumber'] = $helperPayline->contractNumber;
		}
		$array['payment']['mode'] = 'CPT';
		
		//second contracts
		$array['secondContracts'] = explode(';',$helperPayline->secondaryContractNumberList);


        //If wallet isn't sendable...
        if ($expiredWalletId){
            $helperPayline->walletId=null;
        }
        
        // PRIVATE DATA
        $privateData = array();
        $privateData['key'] = "orderRef";
        $privateData['value'] = substr(str_replace(array("\r","\n","\t"), array('','',''),$array['order']['ref']), 0,255);
        $paylineSDK->setPrivate($privateData);

        //ORDER DETAILS (optional)
        $items = $this->order->getAllItems();
        if ($items) {
            if(count($items)>100) $items=array_slice($items,0,100);
            foreach($items as $item) {
              $itemPrice = round($item->getPrice()*100);
              if($itemPrice > 0){
                  $product = array();
                  $product['ref'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r","\n","\t"), array('','',''),$item->getName()),0,50));
                  $product['price'] = round($item->getPrice()*100);
                  $product['quantity'] = round($item->getQtyOrdered());
                  $product['comment'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r","\n","\t"), array('','',''),$item->getDescription()), 0,255));
                  $paylineSDK->setItem($product);
                }
                continue;
            }
        }
		
		//WALLET		
		if(Mage::getStoreConfig('payment/PaylineCPT/send_wallet_id')) {
			if (!isset($array['buyer']['walletId'])) {
				if (isset($helperPayline->walletId)) {
					$array['buyer']['walletId'] = $helperPayline->walletId;
				}
			}
			if ($helperPayline->canSubscribeWallet()) {
				//If the wallet is new (registered during payment), we must save it in the private data since it's not sent back by default
				if ($helperPayline->isNewWallet) {
					if ($helperPayline->walletId) {
						$paylineSDK->setPrivate(array('key'=>'newWalletId','value'=>$helperPayline->walletId));
					}
				}
			}
		}

        // ADD CONTRACT WALLET ARRAY TO $array
        $array['walletContracts'] = Mage::helper('payline')->buildContractNumberWalletList();

        // EXECUTE
        try{
            $result = $paylineSDK->doWebPayment($array);
        }catch(Exception $e){
            Mage::logException($e);
            Mage::helper('payline/payment')->updateStock($this->order);
            $msg=Mage::helper('payline')->__('Error during payment');
            Mage::getSingleton('core/session')->addError($msg);
            $msgLog='Unknown PAYLINE ERROR (payline unreachable?)';
            Mage::helper('payline/logger')->log('[cptAction] ' .$this->order->getIncrementId().' '. $msgLog);
            $this->_redirect('checkout/onepage');
            return;
        }
        // RESPONSE
		$initStatus = Mage::getStoreConfig('payment/payline_common/init_order_status');
        if(isset($result) && is_array($result) && $result['result']['code'] == '00000'){         
            $this->order->setState(Mage_Sales_Model_Order::STATE_NEW,$initStatus,'',false);
            $this->order->save();
            header("location:".$result['redirectURL']);
            exit();
        }else {//Payline error
            Mage::helper('payline/payment')->updateStock($this->order);
            $msg=Mage::helper('payline')->__('Error during payment');
            Mage::getSingleton('core/session')->addError($msg);
            if (isset($result) && is_array($result)){
                $msgLog='PAYLINE ERROR : '.$result['result']['code']. ' ' . $result['result']['shortMessage'] . ' ('.$result['result']['longMessage'] . ')';
            } elseif (isset($result) && is_string($result)){
				$msgLog='PAYLINE ERROR : '.$result;
			} else{
                $msgLog='Unknown PAYLINE ERROR';
            }
			$this->order->setState(Mage_Sales_Model_Order::STATE_NEW,$initStatus,$msgLog,false);
            $this->order->save();
            Mage::helper('payline/logger')->log('[cptAction] ' .$this->order->getIncrementId().' '.$msgLog);
            $this->_redirect('checkout/onepage');
            return;
        }
    }

    /** Initialisize a WALLET payment request 
     * 
     */
    /**
     * Initialize & process a wallet direct payment request
     */
    public function walletAction(){
        $array = $this->init();
        $paylineSDK = Mage::helper('payline')->initPayline('WALLET',$array['payment']['currency']);

        //PAYMENT
        $array['payment']['action'] = Mage::getStoreConfig('payment/PaylineWALLET/payline_payment_action');
        $array['payment']['mode'] =  'CPT';

        //Get the wallet contract number from card type
        $wallet=Mage::getModel('payline/wallet')->getWalletData();
		$contract = Mage::getModel('payline/contract')
				->getCollection()
				->addFilterStatus(true,Mage::app()->getStore()->getId())
				->addFieldToFilter('contract_type',$wallet['card']['type'])
				->getFirstItem();
		
        $array['payment']['contractNumber']= $contract->getNumber();
		
        //ORDER
        $array['order']['date'] = date("d/m/Y H:i");

        //PRIVATE DATA
        $privateData1 = array();
        $privateData1['key'] = 'orderRef';
        $privateData1['value'] = substr(str_replace(array("\r","\n","\t"), array('','',''),$array['order']['ref']), 0,255);
        $paylineSDK->setPrivate($privateData1);
        
        //ORDER DETAILS (optional) 
        $items = $this->order->getAllItems();
        if ($items) {
            if(count($items)>100) $items=array_slice($items,0,100);
            foreach($items as $item) {
              $itemPrice = round($item->getPrice()*100);
              if($itemPrice > 0){
                  $product = array();
                  $product['ref'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r","\n","\t"), array('','',''),$item->getName()),0,50));
                  $product['price'] = round($item->getPrice()*100);
                  $product['quantity'] = round($item->getQtyOrdered());
                  $product['comment'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r","\n","\t"), array('','',''),$item->getDescription()), 0,255));
                  $paylineSDK->setItem($product);
                }
                continue;
            }
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $walletId = $customer->getWalletId();
        $array['walletId']=$walletId;
		$array['cardInd'] = '';
        $array['version'] = Monext_Payline_Helper_Data::VERSION;

        try{
            $author_result = $paylineSDK->doImmediateWalletPayment($array);
        }catch(Exception $e){
			Mage::logException($e);
            Mage::helper('payline/payment')->updateStock($this->order);
            $msg=Mage::helper('payline')->__('Error during payment');
            Mage::getSingleton('core/session')->addError($msg);
            $msgLog='Unknown PAYLINE ERROR (payline unreachable?) during wallet payment';
            Mage::helper('payline/logger')->log('[walletAction] ' .$this->order->getIncrementId().$msgLog);
            $this->_redirect('checkout/onepage');
            return;
        }
        // RESPONSE
        $failedOrderStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');
        if(isset($author_result) && is_array($author_result) && $author_result['result']['code'] == '00000'){
            $array_details = array();
            $array_details['orderRef'] = $this->order->getRealOrderId();
            $array_details['transactionId']     = $author_result['transaction']['id'];
			$array_details['startDate']         = '';
			$array_details['endDate']           = '';
			$array_details['transactionHistory']= '';
			$array_details['version']           = Monext_Payline_Helper_Data::VERSION;
            $array_details['archiveSearch']     = '';
            $detail_result = $paylineSDK->getTransactionDetails($array_details);
            
            if(is_array($detail_result) && Mage::helper('payline/payment')->updateOrder($this->order, $detail_result,$detail_result['transaction']['id'], 'WALLET')){
                $redirectUrl = Mage::getBaseUrl()."checkout/onepage/success/";
                if($detail_result['result']['code'] == '04003') {
					$newOrderStatus = Mage::getStoreConfig('payment/payline_common/fraud_order_status');
                    Mage::helper('payline')->setOrderStatus($this->order, $newOrderStatus);
                } else {
                    Mage::helper('payline')->setOrderStatusAccordingToPaymentMode(
                        $this->order, $array['payment']['action'] );
                }
                Mage::helper('payline')->automateCreateInvoiceAtShopReturn('WALLET', $this->order);
				$this->order->save();
            Mage_Core_Controller_Varien_Action::_redirectSuccess($redirectUrl);
            }else{
				$msgLog='Error during order update (#'.$this->order->getIncrementId().')';
                $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$failedOrderStatus,$msgLog,false);
				$this->order->save();
                $msg=Mage::helper('payline')->__('Error during payment');
                Mage::getSingleton('core/session')->addError($msg);
                Mage::helper('payline/logger')->log('[walletAction] ' .$this->order->getIncrementId().$msgLog);
                $this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
                return;
            }
            
        }else {
            Mage::helper('payline/payment')->updateStock($this->order);
			if(isset($author_result) && is_array($author_result)){
                $msgLog='PAYLINE ERROR during doImmediateWalletPayment: '.$author_result['result']['code']. ' ' . $author_result['result']['shortMessage'] . ' ('.$author_result['result']['longMessage'].')';
            }elseif(isset($author_result) && is_string($author_result)){
				$msgLog='PAYLINE ERROR during doImmediateWalletPayment: '.$author_result;
			} else {
                $msgLog='Unknown PAYLINE ERROR during doImmediateWalletPayment';
            }
			
            $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$failedOrderStatus,$msgLog,false);
            $this->order->save();
			
            $msg=Mage::helper('payline')->__('Error during payment');
            Mage::getSingleton('core/session')->addError($msg);
            Mage::helper('payline/logger')->log('[walletAction] ' .$this->order->getIncrementId().$msgLog);
            $this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
            return;
        }
    }
    /**
     * Initialize the NX payment request
     */
    public function nxAction(){
        //Check if wallet is sendable
        //Must be done before call to Payline helper initialisation
        $expiredWalletId=false;
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerSession->isLoggedIn()){
            $customer = $customerSession->getCustomer();
            if ($customer->getWalletId() && !Mage::getModel('payline/wallet')->checkExpirationDate()){
                $expiredWalletId=true;
            }
        }
        
        $array = $this->init();
		$helperPayline = Mage::helper('payline');
        $paylineSDK = $helperPayline->initPayline('NX',$array['payment']['currency']);
        $array['version'] = Monext_Payline_Helper_Data::VERSION;
            
        //If wallet isn't sendable...
        if ($expiredWalletId){
            Mage::helper('payline')->walletId=null;
        }
        
        $nx = Mage::getStoreConfig('payment/PaylineNX/billing_occurrences');
        $array['payment']['mode'] = 'NX';
		$array['payment']['action'] = 101;
		$array['payment']['contractNumber'] = $helperPayline->contractNumber;
        $array['recurring']['amount'] = round($array['payment']['amount']/$nx);
        $array['recurring']['firstAmount'] = $array['payment']['amount']-($array['recurring']['amount']*($nx-1));
        $array['recurring']['billingCycle'] = Mage::getStoreConfig('payment/PaylineNX/billing_cycle');
        $array['recurring']['billingLeft'] = $nx;
        $array['recurring']['billingDay'] = '';
        $array['recurring']['startDate'] = '';
		
		//second contracts
		$array['secondContracts'] = explode(';',$helperPayline->secondaryContractNumberList);
            
        // PRIVATE DATA
        $privateData = array();
        $privateData['key'] = "orderRef";
        $privateData['value'] = substr(str_replace(array("\r","\n","\t"), array('','',''),$array['order']['ref']), 0,255);
        $paylineSDK->setPrivate($privateData);
        
        //ORDER DETAILS (optional)
        $items = $this->order->getAllItems();
        if ($items) {
            if(count($items)>100) $items=array_slice($items,0,100);
            foreach($items as $item) {
              $itemPrice = round($item->getPrice()*100);
              if($itemPrice > 0){
                  $product = array();
                  $product['ref'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r","\n","\t"), array('','',''),$item->getName()),0,50));
                  $product['price'] = round($item->getPrice()*100);
                  $product['quantity'] = round($item->getQtyOrdered());
                  $product['comment'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r","\n","\t"), array('','',''),$item->getDescription()), 0,255));
                  $paylineSDK->setItem($product);
                }
                continue;
            }
        }
		
		//WALLET
		if(Mage::getStoreConfig('payment/PaylineCPT/send_wallet_id')) {
			if (!isset($array['buyer']['walletId'])) {
				if (isset($helperPayline->walletId)) {
					$array['buyer']['walletId'] = $helperPayline->walletId;
				}
			}
			if ($helperPayline->canSubscribeWallet()) {
				//If the wallet is new (registered during payment), we must save it in the private data since it's not sent back by default
				if ($helperPayline->isNewWallet) {
					if ($helperPayline->walletId) {
						$paylineSDK->setPrivate(array('key'=>'newWalletId','value'=>$helperPayline->walletId));
					}
				}
			}
		}

        // ADD CONTRACT WALLET ARRAY TO $array
        $array['walletContracts'] = Mage::helper('payline')->buildContractNumberWalletList();

        // EXECUTE
        try{
            $result =  $paylineSDK->doWebPayment($array);
        }catch(Exception $e){
			Mage::logException($e);
            Mage::helper('payline/payment')->updateStock($this->order);
            $msg=Mage::helper('payline')->__('Error during payment');
            Mage::getSingleton('core/session')->addError($msg);
            $msgLog='Unknown PAYLINE ERROR (payline unreachable?)';
            Mage::helper('payline/logger')->log('[nxAction] ' .$this->order->getIncrementId().$msgLog);
            $this->_redirect('checkout/onepage');
            return;
        }
        // RESPONSE
		$initStatus = Mage::getStoreConfig('payment/payline_common/init_order_status');
        if(isset($result) && is_array($result) && $result['result']['code'] == '00000'){
            $this->order->setState(Mage_Sales_Model_Order::STATE_NEW,$initStatus,'',false);
            $this->order->save();
            header("location:".$result['redirectURL']);
            exit();
        }else {
            Mage::helper('payline/payment')->updateStock($this->order);
            if(isset($result) && is_array($result)){
                $msgLog='PAYLINE ERROR : '.$result['result']['code']. ' ' . $result['result']['shortMessage'] . ' ('.$result['result']['longMessage'].')';
            } elseif(isset($result) && is_string($result)){
				$msgLog='PAYLINE ERROR : '.$result;
			} else{
                $msgLog='Unknown PAYLINE ERROR';
            }
			$this->order->setState(Mage_Sales_Model_Order::STATE_NEW,$initStatus,$msgLog,false);
            $this->order->save();
            $msg=Mage::helper('payline')->__('Error during payment');
            Mage::helper('payline/logger')->log('[nxAction] ' .$this->order->getIncrementId().$msgLog);
            Mage::getSingleton('core/session')->addError($msg);
            $this->_redirect('checkout/onepage');
            return;

        }
    }

    /**
     * Action called on the customer's return form the Payline website.
     */
    public function cptReturnAction(){
        $res = Mage::helper('payline')->initPayline('CPT')->getWebPaymentDetails(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
		
		$this->_getCustomerSession()->setWebPaymentDetails($res);
		
		if (isset($res['privateDataList']['privateData']['value'])){
            $orderRef=$res['privateDataList']['privateData']['value'];
        }else{
            foreach ($res['privateDataList']['privateData'] as $privateDataList){
                if($privateDataList->key == 'orderRef'){
                    $orderRef = $privateDataList->value;
                }
            }
        }
        
        if (!isset($orderRef)){
            $msgLog='Couldn\'t find order increment id in cpt payment result';
            Mage::helper('payline/logger')->log('[cptNotifAction] ' .$this->order->getIncrementId().$msgLog);
            $this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
            return;
        }
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($orderRef);
		
        //If order is still new, notifAction haven't been called yet
        if ($this->order->getState()==Mage_Sales_Model_Order::STATE_NEW){
            Mage_Core_Controller_Varien_Action::_redirectSuccess($this->cptNotifAction());
        }
        
        if($res['result']['code'] == '00000' || $res['result']['code'] == '04003'){
            $this->_redirect('checkout/onepage/success');
        }else{
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is refused'));
            $this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
        }
    }

    /**
     * Action called on the customer's return form the Payline website.
     */
    public function nxReturnAction(){
        Mage_Core_Controller_Varien_Action::_redirectSuccess($this->nxNotifAction());
    }

    /**
     * Save CPT payment result, called by the bank when the transaction is done
     */
    public function cptNotifAction(){
        $res = $this->_getCustomerSession()->getWebPaymentDetails(true);
        if (empty($res)) {
            $res = Mage::helper('payline')->initPayline('CPT')->getWebPaymentDetails(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        }

        if (isset($res['privateDataList']['privateData']['value'])){
            $orderRef=$res['privateDataList']['privateData']['value'];
        }else{
            foreach ($res['privateDataList']['privateData'] as $privateDataList){
                if($privateDataList->key == 'orderRef'){
                    $orderRef = $privateDataList->value;
                }
                if($privateDataList->key == 'newWalletId'){
                	$newWalletId = $privateDataList->value;
                }           
            }
        }
        if (!isset($orderRef)){
            $msgLog='Couldn\'t find order increment id in cpt payment result';
            Mage::helper('payline/logger')->log('[cptNotifAction] ' .$this->order->getIncrementId().$msgLog);
            $redirectUrl = Mage::getBaseUrl()."checkout/onepage/";
        }
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($orderRef);
        $payment = $this->order->getPayment();
        if ($payment->getBaseAmountPaid() != $payment->getBaseAmountOrdered()) {

            $failedOrderStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');

            if(is_array($res) && Mage::helper('payline/payment')->updateOrder($this->order, $res,$res['transaction']['id'], 'CPT')){
                $redirectUrl = Mage::getBaseUrl()."checkout/onepage/success/";

                if($res['result']['code'] == '04003') {
                    $newOrderStatus = Mage::getStoreConfig('payment/payline_common/fraud_order_status');
                    Mage::helper('payline')->setOrderStatus($this->order, $newOrderStatus);
                } else {
                    Mage::helper('payline')->setOrderStatusAccordingToPaymentMode(
                        $this->order, $res['payment']['action'] );
                }

            	if(isset($newWalletId)){
                    $this->saveWallet($newWalletId);
                }
                Mage::helper('payline')->automateCreateInvoiceAtShopReturn('CPT', $this->order);
            }else{
				if(isset($res) && is_array($res)){
                    $msgLog='PAYLINE ERROR : '.$res['result']['code']. ' ' . $res['result']['shortMessage'] . ' ('.$res['result']['longMessage'].')';
                } elseif(isset($res) && is_string($res)){
					$msgLog='PAYLINE ERROR : '.$res;
				} else{
                    $msgLog='Error during order update (#'.$this->order->getIncrementId().')';
                }

                if (is_array($res) && !($res['result']['code'] == '02306' || $res['result']['code'] == '02533')) {
                    if ($res['result']['code'] == '02304' || $res['result']['code'] == '02324' || $res['result']['code'] == '02534') {
                        $abandonedStatus = Mage::getStoreConfig('payment/payline_common/resignation_order_status');
                        $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$abandonedStatus,$msgLog,false);
                    } else {
                        $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$failedOrderStatus,$msgLog,false);
                    }
                }
                
                Mage::helper('payline/logger')->log('[cptNotifAction] ' .$this->order->getIncrementId().$msgLog);
                $redirectUrl = $this->_getPaymentRefusedRedirectUrl();
            }
            $this->order->save();
        } else {
            $redirectUrl = Mage::getBaseUrl()."checkout/onepage/success/";
        }
        return $redirectUrl;
    }

    /**
     * Save NX payment result, called by the bank when the transaction is done
     */
    public function nxNotifAction(){
        $res = Mage::helper('payline')->initPayline('NX')->getWebPaymentDetails(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        if (isset($res['privateDataList']['privateData']['value'])){
            $orderRef=$res['privateDataList']['privateData']['value'];
        }else{
            foreach ($res['privateDataList']['privateData'] as $privateDataList){
                if($privateDataList->key == 'orderRef'){
                    $orderRef = $privateDataList->value;
                }
            }
        }
        if (!isset($orderRef)){
            $msgLog='Référence commande introuvable dans le résultat du paiement Nx';
            Mage::helper('payline/logger')->log('[nxNotifAction] ' .$this->order->getIncrementId().' '.$msgLog);
            $redirectUrl = Mage::getBaseUrl()."checkout/onepage/";
        }
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($orderRef);

        $failedOrderStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');
            
        if(isset($res['billingRecordList']['billingRecord'])){
            $size = sizeof($res['billingRecordList']['billingRecord']);
        }else{
            $size = 0;
        }
        $billingRecord = false;
        for($i=0;$i<$size;$i++){
            if($res['billingRecordList']['billingRecord'][$i]->status == 1){
                $txnId = $res['billingRecordList']['billingRecord'][$i]->transaction->id;
                if(!$this->order->getTransaction($txnId)){
                    $billingRecord = $res['billingRecordList']['billingRecord'][$i];
                }
            }
        }
        if($billingRecord && Mage::helper('payline/payment')->updateOrder($this->order, $res,$billingRecord->transaction->id,'NX')) {
            $redirectUrl = Mage::getBaseUrl()."checkout/onepage/success/";

            if($res['result']['code'] == '04003') {
                $newOrderStatus = Mage::getStoreConfig('payment/payline_common/fraud_order_status');
                Mage::helper('payline')->setOrderStatus($this->order, $newOrderStatus);
            } else if( $res['result']['code'] == '02501' ) { // credit card (CC) will expire
                $statusScheduleAlert = Mage::getStoreConfig('payment/PaylineNX/status_when_payline_schedule_alert');
                Mage::helper('payline')->setOrderStatus( $this->order, $statusScheduleAlert );
            } else {
                Mage::helper('payline')->setOrderStatusAccordingToPaymentMode(
                    $this->order, $res['payment']['action'] );
            }

            if (isset($res['privateDataList']['privateData'][1]) && $res['privateDataList']['privateData'][1]->key=="newWalletId" && $res['privateDataList']['privateData'][1]->value!=''){
                $this->saveWallet($res['privateDataList']['privateData'][1]->value);
            }
            $payment = $this->order->getPayment();
            if ($payment->getBaseAmountPaid() != $payment->getBaseAmountOrdered()) {
                Mage::helper('payline')->automateCreateInvoiceAtShopReturn('NX', $this->order);
            }
        }else{
			if(isset($res) && is_array($res)){
                $msgLog='PAYLINE ERROR : '.$res['result']['code']. ' ' . $res['result']['shortMessage'] . ' ('.$res['result']['longMessage'].')';
            } elseif(isset($res) && is_string($res)){
				$msgLog='PAYLINE ERROR : '.$res;
			} else{
                $msgLog='Error during order update (#'.$this->order->getIncrementId().')';
            }
			
            if (is_array($res) && !($res['result']['code'] == '02306' || $res['result']['code'] == '02533')) {
                if (is_array($res) && ($res['result']['code'] == '02304' || $res['result']['code'] == '02324' || $res['result']['code'] == '02534')) {
                    $abandonedStatus = Mage::getStoreConfig('payment/payline_common/resignation_order_status');
                    $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$abandonedStatus,$msgLog,false);
                } else {
                    $statusScheduleAlert = Mage::getStoreConfig('payment/PaylineNX/status_when_payline_schedule_alert');
                    if( !empty( $statusScheduleAlert ) ) { // if user conf is set
                        $failedOrderStatus = $statusScheduleAlert;
                    }
                    $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$failedOrderStatus,$msgLog,false);
                }
            }
            
            Mage::helper('payline/logger')->log('[nxNotifAction] ' .$this->order->getIncrementId().$msgLog);
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is refused'));
            $redirectUrl = $this->_getPaymentRefusedRedirectUrl();
        }
        $this->order->save();
        return $redirectUrl;
    }

    /**
     * Method called by Payline to notify (except first) each term payment.
     * Url to this action must be set in Payline personnal account.
     */
    public function nxTermNotifAction()
    {
        $statusScheduleAlert    = Mage::getStoreConfig('payment/PaylineNX/status_when_payline_schedule_alert');
        $statusCCExpired        = Mage::getStoreConfig('payment/PaylineNX/status_when_credit_card_schedule_is_expired');
        if( !empty( $statusScheduleAlert ) || !empty( $statusCCExpired ) ) {
            if( $this->isNxTermParamsOk( $_GET ) ) {
                /* BILL     = value required for terms notifications
                   WEBTRS   = value for cash web payment */
                if( $_GET['notificationType'] == 'BILL' ) { //
                    $transactionParams = array();
                    $transactionParams['transactionId']     = $_GET['transactionId'];
                    $transactionParams['orderRef']          = $_GET['orderRef'];
                    $transactionParams['version']           = Monext_Payline_Helper_Data::VERSION;
                    $transactionParams['startDate']         = '';
                    $transactionParams['endDate']           = '';
                    $transactionParams['transactionHistory']= '';
                    $transactionParams['archiveSearch']     = '';

                    $res = Mage::helper('payline')->initPayline('NX')->getTransactionDetails( $transactionParams );

                    if( isset( $res )
                        && is_array( $res )
                        && isset( $res['result'] )
                        && isset( $res['result']['code'] ) )
                    {
                        $mustSave = true;
                        switch( $res['result']['code'] ) {
                            case '00000':
                            case '02500':
                            case '04003':
                                $mustSave = false;
                                break;
                            case '02501': // payment card will expire
                                if( !empty( $statusScheduleAlert ) ) {
                                    $this->order = $this->setOrderStatus( $statusScheduleAlert, $_GET['orderRef'] );
                                    break;
                                }
                            default: // if default => error (cc expired or other errors)
                                if( !empty( $statusCCExpired ) ) {
                                    $this->order = $this->setOrderStatus( $statusCCExpired, $_GET['orderRef'] );
                                } else {
                                    $mustSave = false;
                                }
                                break;
                        }
                        if( $mustSave ) { $this->order->save(); }
                    } // end if ( isset($res) ...
                } // end if BILL
            } // end if $this->isNxTermParamsOk
        } // end if !empty( $statusScheduleAlert ) || !empty( $statusCCExpired )
    } // end func

    /**
     * Check if $params contains all the required keys for PaylineSDK#getTransactionDetails()
     * @param $params {array} array params for PaylineSDK#getTransactionDetails(), should contain all keys required.
     * @return bool true if $params ok, otherwise false
     */
    private function isNxTermParamsOk($params)
    {
        if( !isset( $params['notificationType'] ) )     return false;
        if( !isset( $params['paymentRecordId'] ) )      return false;
        if( !isset( $params['walletId'] ) )             return false;
        if( !isset( $params['transactionId'] ) )        return false;
        if( !isset( $params['billingRecordDate'] ) )    return false;
        if( !isset( $params['orderRef'] ) )             return false;
        return true;
    }

    /**
     * Set an order status. If !isset($this->order) process order model from $orderRef
     * @param $status {string} status order to assign
     * @param $orderRef {string} entity_id order
     * @return Mage_Sales_Model_Order Return the order object with new status set
     */
    private function setOrderStatus($status, $orderRef)
    {
        if( isset( $this->order ) ) {
            $order = $this->order;
        } else {
            $order = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('increment_id', $orderRef)
                ->getFirstItem();
        }
        Mage::helper('payline')->setOrderStatus( $order, $status );
        return $order;
    }

    /**
     * Cancel a CPT payment request /order
     */
    public function cptCancelAction(){
        $res = Mage::helper('payline')->initPayline('CPT')->getWebPaymentDetails(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        
		if (isset($res['privateDataList']['privateData']['value'])){
            $orderRef=$res['privateDataList']['privateData']['value'];
        }else{
            foreach ($res['privateDataList']['privateData'] as $privateDataList){
                if($privateDataList->key == 'orderRef'){
                    $orderRef = $privateDataList->value;
                }
            }
        }
        if (!isset($orderRef)){
            $msgLog='Couldn\'t find order increment id in cpt payment cancel result';
            Mage::helper('payline/logger')->log('[cptCancelAction] ' .$this->order->getIncrementId().$msgLog);
            $redirectUrl = Mage::getBaseUrl()."checkout/onepage/";
			$this->_redirect($redirectUrl);
			return;
        }
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($orderRef);
		$msg = '';
		if(is_string($res)) {
			$msg='PAYLINE ERROR : '.$res;
            Mage::helper('payline/logger')->log('[cptCancelAction] ' .$this->order->getIncrementId(). ' ' . $msg);
            $cancelStatus=Mage::getStoreConfig('payment/payline_common/failed_order_status');
		} elseif (substr($res['result']['code'], 0, 2)=='01' || substr($res['result']['code'],0,3)=='021'){
            //Invalid transaction or error during the process on Payline side
            //No error display, the customer is already told on the Payline side
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is refused'));
            $msg='PAYLINE ERROR : '.$res['result']['code']. ' '.$res['result']['shortMessage'] . ' (' . $res['result']['longMessage'].')';
            Mage::helper('payline/logger')->log('[cptCancelAction] ' .$this->order->getIncrementId().$msg);
            $cancelStatus=Mage::getStoreConfig('payment/payline_common/failed_order_status');
        }else{
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is canceled'));
			$msg='PAYLINE INFO : '.$res['result']['code']. ' '.$res['result']['shortMessage'] . ' (' . $res['result']['longMessage'].')';
            //Transaction cancelled by customer
            $cancelStatus = Mage::getStoreConfig('payment/payline_common/canceled_order_status');
        }
        $this->cancelOrder($cancelStatus, $res['result']['code'], $msg);
    }

    /**
     * Cancel a NX payment request /order
     */
    public function nxCancelAction(){
        $res = Mage::helper('payline')->initPayline('NX')->getWebPaymentDetails(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        if (isset($res['privateDataList']['privateData']['value'])){
            $orderRef=$res['privateDataList']['privateData']['value'];
        }else{
            foreach ($res['privateDataList']['privateData'] as $privateDataList){
                if($privateDataList->key == 'orderRef'){
                    $orderRef = $privateDataList->value;
                }
            }
        }
        if (!isset($orderRef)){
            $msgLog='Couldn\'t find order increment id in nx payment cancel result';
            Mage::helper('payline/logger')->log('[nxCancelAction] ' .$this->order->getIncrementId().$msgLog);
            $redirectUrl = Mage::getBaseUrl()."checkout/onepage/";
        }
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($orderRef);

		if (is_string($res)) {
			$msg='PAYLINE ERROR : '.$res;
            Mage::helper('payline/logger')->log('[nxCancelAction] ' .$this->order->getIncrementId(). ' ' . $msg);
            $cancelStatus=Mage::getStoreConfig('payment/payline_common/failed_order_status');
		} elseif (substr($res['result']['code'], 0, 2)=='01' || substr($res['result']['code'],0,3)=='021'){
            //Invalid transaction or error during the process on Payline side
            //No error display, the customer is already told on the Payline side
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is refused'));
            $msg='PAYLINE ERROR : '.$res['result']['code']. ' '.$res['result']['shortMessage'] . ' (' . $res['result']['longMessage'].')';
            Mage::helper('payline/logger')->log('[nxCancelAction] ' .$this->order->getIncrementId().$msg);
            $cancelStatus=Mage::getStoreConfig('payment/payline_common/failed_order_status');
        }else{
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is canceled'));
			$msg='PAYLINE INFO : '.$res['result']['code']. ' '.$res['result']['shortMessage'] . ' (' . $res['result']['longMessage'].')';
            //Transaction cancelled by customer
            $cancelStatus = Mage::getStoreConfig('payment/payline_common/canceled_order_status');
        }
        $this->cancelOrder($cancelStatus, $res['result']['code'],$msg);
    }
	
	protected function _getPaymentRefusedRedirectUrl()
	{
		$option = Mage::getStoreConfig('payment/payline_common/return_payment_refused');
		switch($option) {
			case Monext_Payline_Model_Datasource_Return::CART_EMPTY : 
				$url = Mage::getUrl('checkout/onepage');
				break;
			case Monext_Payline_Model_Datasource_Return::HISTORY_ORDERS : 
				$url = Mage::getUrl('sales/order/history');
				break;
			case Monext_Payline_Model_Datasource_Return::CART_FULL :
				$url = Mage::getUrl('sales/order/reorder', array('order_id' => $this->order->getId()));
				break;
			default :
				$url = Mage::getUrl('checkout/onepage');
					
		}
		
		return $url;
	}
}
