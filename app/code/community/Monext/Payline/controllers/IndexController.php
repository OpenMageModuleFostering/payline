<?php
/**
 * This controller manage all payline payment
 * cptAction, directAction, nxAction & walletAction are called just after the
 * checkout validation
 * the return/notify/cancel are the urls called by Payline
 * An exception for notifyAction : it's not directly called by Payline, since it
 * couldn't work in a local environment; it's then called by the returnAction.
 *
 * @author fague
 *
 */
class Monext_Payline_IndexController extends Mage_Core_Controller_Front_Action
{

    const PAYMENT_ACTION = 101;

    const PAYMENT_MODE = 'CPT';
    /*
     * @var $order Mage_Sales_Model_Order
     */
    protected $order;

    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     *
     *
     * Set the order's status to the provided status (must be part of the
     * cancelled state)
     * Reinit stocks & redirect to checkout
     * @param string $cancelStatus
     */
    private function cancelOrder($cancelStatus, $resCode = '', $message = '')
    {
        $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $cancelStatus, $message, false);
        Mage::helper('payline/payment')->updateStock($this->order);
        $this->order->save();
        Mage::helper('payline/logger')->log('[cancelOrder] - order '.$this->order->getIncrementId().' state is set to '.$cancelStatus);
        $this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
    }

    /**
     * Check if the customer is logged, and if it has a wallet
     * If not & if there is a walletId in the result from Payline, we save it
     */
    public function saveWallet($walletId)
    {
        if (! Mage::getStoreConfig('payment/payline_common/automate_wallet_subscription')) {
            return;
        }
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerSession->isLoggedIn()) {
            $customer = $customerSession->getCustomer();
            if (! $customer->getWalletId()) {
                $customer->setWalletId($walletId);
                $customer->save();
            }
        }
    }

    /**
     * Initialise the requests param array
     *
     * @return array
     */
    private function _initCommonArray()
    {
        $_session = Mage::getSingleton('checkout/session');
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($_session->getLastRealOrderId());
        return Mage::helper('payline/payment')->init($this->order);
    }

    /**
     * Force this action code of some payment methods to the given action code
     *
     * @param $paymentMethod {string}
     * @param $array {array}
     *            conf array. $array is a reference, so no need to return it.
     * @param $actionCode {string}
     *            forced action code set in $array
     */
    private function forcePaymentActionTo($paymentMethod, &$array, $actionCode)
    {
        switch ($paymentMethod) {
            case 'UKASH':
            case 'MONEYCLIC':
            case 'TICKETSURF':
            case 'SKRILL(MONEYBOOKERS)':
            case 'LEETCHI':
                Mage::helper('payline/logger')->log('[cptAction] order ' . $array['order']['ref'] . ' - ' . $paymentMethod . ' selected => payment action is forced to ' . $actionCode);
                $array['payment']['action'] = $actionCode;
                break;
            default:
                break;
        }
    }

    /**
     * Initialize the cpt payment request
     */
    public function cptAction(){
        // Check if wallet is sendable
        // Must be done before call to Payline helper initialisation
        $expiredWalletId = false;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if ($customer->getWalletId() && ! Mage::getModel('payline/wallet')->checkExpirationDate()) {
                $expiredWalletId = true;
            }
        }
        $array = $this->_initCommonArray();
        if($this->order->getBaseGrandTotal() == 0){ // If order amount is null, exit payment process
        	Mage::helper('payline')->setOrderStatus($this->order, Mage::getStoreConfig('payment/payline_common/authorized_order_status'));
        	return true;
        }
        /* @var $paylineSDK PaylineSDK */
        $helperPayline = Mage::helper('payline');
        $paylineSDK = $helperPayline->initPayline(self::PAYMENT_MODE, $array['payment']['currency']);
        $paymentMethod = $this->order->getPayment()->getCcType();
        $array['payment']['action'] = Mage::getStoreConfig('payment/PaylineCPT/payline_payment_action');
        $array['version'] = Monext_Payline_Helper_Data::VERSION;
        if ($paymentMethod) {
            Mage::helper('payline/logger')->log('[cptAction] order ' . $array['order']['ref'] . ' - customer selected contract ' . $paymentMethod);
            $contractCPT = Mage::getModel('payline/contract')
                ->getCollection()
                ->addFieldToFilter('number', $paymentMethod)
                ->getFirstItem();
            $this->forcePaymentActionTo($contractCPT->getContractType(), $array, '101');
            $array['payment']['contractNumber'] = $paymentMethod;
        } else {
            $array['payment']['contractNumber'] = $helperPayline->contractNumber;
        }
    		$array['contracts'] = array($array['payment']['contractNumber']); // Payment mean chosen by the customer is the only one shown on Payline payment page
    		$sendPaylineproductCat = false;
    		$pcCol = null;
    		if(in_array($paymentMethod,array('3XONEY','3XONEY_SF','4XONEY','4XONEY_SF','ANCV'))){ // for these payment means, Payline product categories must be sent in order details
    			$sendPaylineproductCat = true;
    			$pcCol = Mage::getModel('payline/productcategories')->getCollection();
    		}

        $array['payment']['mode'] = self::PAYMENT_MODE;
        // second contracts
        $array['secondContracts'] = explode(';', $helperPayline->secondaryContractNumberList);
        // If wallet isn't sendable...
        if ($expiredWalletId) {
            $helperPayline->walletId = null;
        }
        // PRIVATE DATA
        $privateData = array();
        $privateData['key'] = 'orderRef';
        $privateData['value'] = substr(str_replace(array("\r","\n","\t"), array('','',''),$array['order']['ref']), 0,255);
        $paylineSDK->setPrivate($privateData);
        if (isset($customer)) {
            $privateData['key'] = 'plnAccountAge'; // customer account age, in
                                                   // days
            $privateData['value'] = round((time() - $customer->getCreatedAtTimestamp()) / (60 * 60 * 24));
            $paylineSDK->setPrivate($privateData);
            $privateData['key'] = 'plnLastCompleteOrderAge'; // last complete
                                                             // order age, in
                                                             // days
            if (isset($array['plnLastCompleteOrderAge'])) {
                $privateData['value'] = $array['plnLastCompleteOrderAge'];
            } else {
                $privateData['value'] = '-1';
            }
            $paylineSDK->setPrivate($privateData);
        }

        //ORDER DETAILS (optional)
        $helperPayline->setOrderDetails($paylineSDK, $this->order, $sendPaylineproductCat, $pcCol);
        // WALLET
        if (Mage::getStoreConfig('payment/PaylineCPT/send_wallet_id')) {
            if (! isset($array['buyer']['walletId'])) {
                if (isset($helperPayline->walletId)) {
                    $array['buyer']['walletId'] = $helperPayline->walletId;
                }
            }
            if ($helperPayline->canSubscribeWallet()) {
                // If the wallet is new (registered during payment), we must
                // save it in the private data since it's not sent back by
                // default
                if ($helperPayline->isNewWallet) {
                    if ($helperPayline->walletId) {
                        $paylineSDK->setPrivate(array('key' => 'newWalletId',
                                                       'value' => $helperPayline->walletId));
                    }
                }
            }
        }
        // ADD CONTRACT WALLET ARRAY TO $array
        $array['walletContracts'] = Mage::helper('payline')->buildContractNumberWalletList();
        // EXECUTE
        try {
            $result = $paylineSDK->doWebPayment($array);
        } catch (Exception $e) {
            return $this->_logOnException('cptAction', $e);
        }
        // RESPONSE
        $initStatus = Mage::getStoreConfig('payment/payline_common/init_order_status');
        if (isset($result) && is_array($result) && $result['result']['code'] == '00000') {
            $this->order->setState(Mage_Sales_Model_Order::STATE_NEW, $initStatus, '', false);
            $this->order->save();

            $token = Mage::getModel('payline/token')
                            ->setOrderId($this->order->getIncrementId())
                            ->setToken($result['token'])
                            ->setDateCreate(time());
            $token->save();

            header("location:" . $result['redirectURL']);
            exit();
        } else { // Payline error
            Mage::helper('payline/payment')->updateStock($this->order);
            $msg = Mage::helper('payline')->__('Error during payment');
            Mage::getSingleton('core/session')->addError($msg);
            if (isset($result) && is_array($result)) {
                $msgLog = 'PAYLINE ERROR : ' . $result['result']['code'] . ' ' . $result['result']['shortMessage'] . ' (' . $result['result']['longMessage'] . ')';
            } elseif (isset($result) && is_string($result)) {
                $msgLog = 'PAYLINE ERROR : ' . $result;
            } else {
                $msgLog = 'Unknown PAYLINE ERROR';
            }
            $this->order->setState(Mage_Sales_Model_Order::STATE_NEW, $initStatus, $msgLog, false);
            $this->order->save();
            Mage::helper('payline/logger')->log('[cptAction] ' . $this->order->getIncrementId() . ' ' . $msgLog);
            $this->_redirect('checkout/onepage');
            return;
        }
    }


    /**
     * directSecurizedAction
     * Initialize & process the direct payment request
     */
    public function directSecurizedAction()
    {
        $this->_initCommonArray();

        $array = Mage::helper('payline/payment')->getDirectActionHeader();

        $paylineSDK = Mage::helper('payline')->initPayline('DIRECT', $array['payment']['currency']);

        $verifyEnrollmentRequest = array();

        // VERSION
        $verifyEnrollmentRequest['version'] = Monext_Payline_Helper_Data::VERSION;

        // PAYMENT
        $verifyEnrollmentRequest['payment']['amount'] = $array['payment']['amount'];
        $verifyEnrollmentRequest['payment']['currency'] = $array['payment']['currency'];
        $verifyEnrollmentRequest['payment']['action'] = $array['payment']['action'];
        $verifyEnrollmentRequest['payment']['mode'] = $array['payment']['mode'];
        $verifyEnrollmentRequest['payment']['contractNumber'] = $array['payment']['contractNumber'];

        // CARD INFO
        $verifyEnrollmentRequest['card']['type'] = $array['card']['type'];
        $verifyEnrollmentRequest['card']['expirationDate'] = $array['card']['expirationDate'];
        $verifyEnrollmentRequest['card']['token'] = $array['card']['token'];

        // ORDER
        $verifyEnrollmentRequest['orderRef'] = $this->order->getIncrementId();

        // RESPONSE
        try {
            $verifyEnrollmentResponse = $paylineSDK->verifyEnrollment($verifyEnrollmentRequest);
            if (!is_array($verifyEnrollmentResponse)) {
                if(is_string($verifyEnrollmentResponse)) {
                    $msg = $verifyEnrollmentResponse;
                } else {
                    $msg = 'Invalid result from verifyEnrollment call';
                }
                throw new Exception($msg);
            }

            if ($verifyEnrollmentResponse['result']['code'] == paylineSDK::ERR_CODE) {
                $msg = 'Critical error at verifyEnrollment call (code ' . $verifyEnrollmentResponse['result']['code'] . ')';
                throw new Exception($msg);
            }

            if ($verifyEnrollmentResponse['result']['code'] != '03000') {
                $msg = 'Error at verifyEnrollment call : ' . $verifyEnrollmentResponse['result']['longMessage'] . ' (code ' . $verifyEnrollmentResponse['result']['code'] . ')';
                throw new Exception($msg);
            }

            $secureParams = array( 'TermUrl'=>Mage::getUrl('payline/index/validateAcs'),
                    'PaReq'=>$verifyEnrollmentResponse['pareqFieldValue'],
                    'MD'=>$verifyEnrollmentResponse['mdFieldValue']
            );

            $url= $verifyEnrollmentResponse['actionUrl'];

            if($verifyEnrollmentResponse['actionMethod']=='POST') {

                $this->loadLayout();

                $blockRedirect = $this->getLayout()->getBlock('payline-directsecurized');
                $blockRedirect->setSecureParams($secureParams);
                $blockRedirect->setSecureUrl($verifyEnrollmentResponse['actionUrl']);

                $this->renderLayout();
            } else {
                $this->_redirectUrl(Mage::helper('core/url')->addRequestParam($url,$secureParams));
            }

            Mage::helper('payline/logger')->log("[directSecurizedAction] " . $this->order->getIncrementId() . " user redirected to ".$url);
            return;

        } catch (Exception $e) {
            return $this->_logOnException('directSecurizedAction', $e);
        }
    }

    /**
     * Initialize & process the direct payment request
     */
    public function validateAcsAction()
    {

        $paRes = $this->getRequest()->getParam('PaRes');
        if($paRes){ // back from ACS with 3D Secure authentication data
            $this->_initCommonArray();
            //$array = $this->_getDirectActionHeader();
            $array = Mage::helper('payline/payment')->getDirectActionHeader();
            try {
                if(empty($array['card']['token'])) {
                    $msg = 'Critical error, missing card token.';
                    throw new Exception($msg);
                }

                $paylineSDK = Mage::helper('payline')->initPayline('DIRECT', $array['payment']['currency']);

                $doAuthorization3DSRequest = array();

                //VERSION
                $doAuthorization3DSRequest['version'] = Monext_Payline_Helper_Data::VERSION;

                //PAYMENT
                $doAuthorization3DSRequest['payment']['amount'] = $array['payment']['amount'];
                $doAuthorization3DSRequest['payment']['currency'] = $array['payment']['currency'];
                $doAuthorization3DSRequest['payment']['action'] = $array['payment']['action'];
                $doAuthorization3DSRequest['payment']['mode'] = self::PAYMENT_MODE;
                $doAuthorization3DSRequest['payment']['contractNumber'] = $array['payment']['contractNumber'];

                // CARD INFO
                $doAuthorization3DSRequest['card']['type'] = $array['card']['type'];
                $doAuthorization3DSRequest['card']['expirationDate'] = $array['card']['expirationDate'];
                $doAuthorization3DSRequest['card']['token'] = $array['card']['token'];
                $doAuthorization3DSRequest['card']['cvx'] = $array['card']['cvx'];
                $doAuthorization3DSRequest['card']['cardholder'] = $array['card']['cardholder'];

                //BUYER INFO
                $doAuthorization3DSRequest['buyer'] = $array['buyer'];

                //ORDER
                $doAuthorization3DSRequest['order']['ref'] = $this->order->getIncrementId();
                $doAuthorization3DSRequest['order']['amount'] = $doAuthorization3DSRequest['payment']['amount'];
                $doAuthorization3DSRequest['order']['date'] = date('d/m/Y H:i');
                $doAuthorization3DSRequest['order']['currency'] = $doAuthorization3DSRequest['payment']['currency'];

                //AUTHENTICATION 3DSECURE
                $doAuthorization3DSRequest['3DSecure']['md'] = $this->getRequest()->getParam('MD');
                $doAuthorization3DSRequest['3DSecure']['pares'] = $paRes;
                $doAuthorization3DSRequest['BankAccountData'] = $array['BankAccountData'];

                // RESPONSE
                $author_result = $paylineSDK->doAuthorization($doAuthorization3DSRequest);
            } catch (Exception $e) {
                return $this->_logOnException('validateAcsAction', $e);
            }



            $statusFinalize = Mage::helper('payline/payment')->finalizeDirectAction($author_result, $paylineSDK, $array);

            if (isset($author_result) && is_array($author_result) && $author_result['result']['code'] == '00000') {
                Mage::helper('payline/logger')->log("[validateAcsAction] " . $this->order->getIncrementId() . " payment validated by ACS");
            }

            if ($statusFinalize) {
                $redirectUrl = Mage::getBaseUrl() . "checkout/onepage/success/";
                Mage_Core_Controller_Varien_Action::_redirectSuccess($redirectUrl);
            } else {
                Mage::helper('payline/logger')->log('['.$fromAction.'] ' . $this->order->getIncrementId() . $msgLog);
                $this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
                return;
            }
        }
    }

    /**
     * Initialize & process a wallet direct payment request
     */
    public function walletAction(){
    	$array = $this->_initCommonArray();
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

        //ORDER DETAILS (optional)
        Mage::helper('payline')->setOrderDetails($paylineSDK, $this->order, false, null);

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $walletId = $customer->getWalletId();
        $array['walletId'] = $walletId;
		$array['cardInd'] = ''; //TODO
		$array['buyer']['walletId'] = $array['walletId'];
		$array['buyer']['walletCardInd'] = $array['cardInd'];
        $array['version'] = Monext_Payline_Helper_Data::VERSION;
        $failedOrderStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');

        //PRIVATE DATA
        $privateData1 = array();
        $privateData1['key'] = 'orderRef';
        $privateData1['value'] = substr(str_replace(array("\r","\n","\t"), array('','',''),$array['order']['ref']), 0,255);
        $paylineSDK->setPrivate($privateData1);
        if(isset($customer)){
        	$privateData['key'] = 'plnAccountAge'; // customer account age, in days
        	$privateData['value'] = round((time()-$customer->getCreatedAtTimestamp())/(60*60*24));
        	$paylineSDK->setPrivate($privateData);
        	$privateData['key'] = 'plnLastCompleteOrderAge'; // last complete order age, in days
        	if(isset($array['plnLastCompleteOrderAge'])){
        		$privateData['value'] = $array['plnLastCompleteOrderAge'];
        	}else{
        		$privateData['value'] = '-1';
        	}
        	$paylineSDK->setPrivate($privateData);
        }

        if(!isset($_POST['PaRes']) && in_array(Mage::getStoreConfig('payment/PaylineWALLET/wallet_payment_security'), array(Monext_Payline_Helper_Data::WALLET_3DS,Monext_Payline_Helper_Data::WALLET_BOTH))){
        	// customer has to be redirected on ACC for 3DS password filling
        	$verifyEnrollmentRequest = array();
        	$verifyEnrollmentRequest['version'] 					= $array['version'];
        	$verifyEnrollmentRequest['payment']['amount'] 			= '100';
        	$verifyEnrollmentRequest['payment']['mode'] 			= $array['payment']['mode'];
        	$verifyEnrollmentRequest['payment']['action'] 			= $array['payment']['action'];
        	$verifyEnrollmentRequest['payment']['currency'] 		= $array['payment']['currency'];
        	$verifyEnrollmentRequest['payment']['contractNumber'] 	= $array['payment']['contractNumber'];
        	$verifyEnrollmentRequest['orderRef']	 				= $array['order']['ref'];
        	$verifyEnrollmentRequest['walletId']					= $array['walletId'];
        	$verifyEnrollmentRequest['walletCardInd']				= 1;// TODO $array['walletCardInd'];
        	try{
        		$verifyEnrollmentResponse = $paylineSDK->verifyEnrollment($verifyEnrollmentRequest);
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
        	if($verifyEnrollmentResponse['result']['code'] == '03000'){
        		echo "<form method='POST' id='acsform' action='".$verifyEnrollmentResponse['actionUrl']."'>";
        		echo "	<input type='hidden' name='".$verifyEnrollmentResponse['pareqFieldName']."' value='".$verifyEnrollmentResponse['pareqFieldValue']."'>";
        		echo "	<input type='hidden' name='".$verifyEnrollmentResponse['mdFieldName']."' value='".$verifyEnrollmentResponse['mdFieldValue']."'>";
        		echo "	<input type='hidden' name='".$verifyEnrollmentResponse['termUrlName']."' value='".Mage::getUrl('payline/index/wallet')."'>";
        		echo "</form>";
        		echo "<script type='text/javascript'>document.getElementById('acsform').submit();</script>";
        		return;
        	}else{
        		Mage::helper('payline/payment')->updateStock($this->order);
        		$msgLog='PAYLINE ERROR during verifyEnrollment: '.$verifyEnrollmentResponse['result']['code']. ' ' . $verifyEnrollmentResponse['result']['shortMessage'] . ' ('.$verifyEnrollmentResponse['result']['longMessage'].')';
        		$this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$failedOrderStatus,$msgLog,false);
        		$this->order->save();
        		$msg=Mage::helper('payline')->__('Error during payment');
        		Mage::getSingleton('core/session')->addError($msg);
        		Mage::helper('payline/logger')->log('[walletAction] ' .$this->order->getIncrementId().$msgLog);
        		$this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
        		return;
        	}
        }

        if(isset($_POST['PaRes'])){ // back from ACS
        	$array['3DSecure']['md'] = $_POST['MD'];
			$array['3DSecure']['pares'] = $_POST['PaRes'];
        }

        try{
            $doImmediateWalletPaymentResponse = $paylineSDK->doImmediateWalletPayment($array);
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
        if(in_array($doImmediateWalletPaymentResponse['result']['code'], array('00000','04003'))){

            if(Mage::helper('payline/payment')->updateOrder($this->order, $doImmediateWalletPaymentResponse,$doImmediateWalletPaymentResponse['transaction']['id'], 'WALLET')){
                $redirectUrl = Mage::getBaseUrl()."checkout/onepage/success/";
                if($doImmediateWalletPaymentResponse['result']['code'] == '04003') {
					$newOrderStatus = Mage::getStoreConfig('payment/payline_common/fraud_order_status');
                    Mage::helper('payline')->setOrderStatus($this->order, $newOrderStatus);
                } else {
                    Mage::helper('payline')->setOrderStatusAccordingToPaymentMode($this->order, $array['payment']['action']);
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
            $msgLog='PAYLINE ERROR during doImmediateWalletPayment: '.$author_result['result']['code']. ' ' . $author_result['result']['shortMessage'] . ' ('.$author_result['result']['longMessage'].')';

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
        // Check if wallet is sendable
        // Must be done before call to Payline helper initialisation
        $expiredWalletId = false;
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerSession->isLoggedIn()) {
            $customer = $customerSession->getCustomer();
            if ($customer->getWalletId() && ! Mage::getModel('payline/wallet')->checkExpirationDate()) {
                $expiredWalletId = true;
            }
        }
        $array = $this->_initCommonArray();
        $helperPayline = Mage::helper('payline');
        $paylineSDK = $helperPayline->initPayline('NX', $array['payment']['currency']);
        $array['version'] = Monext_Payline_Helper_Data::VERSION;
        // If wallet isn't sendable...
        if ($expiredWalletId) {
            Mage::helper('payline')->walletId = null;
        }
        $nx = Mage::getStoreConfig('payment/PaylineNX/billing_occurrences');
        $array['payment']['mode'] = 'NX';
        $array['payment']['action'] = 101;
        $array['payment']['contractNumber'] = $helperPayline->contractNumber;
        $array['recurring']['amount'] = round($array['payment']['amount'] / $nx);
        $array['recurring']['firstAmount'] = $array['payment']['amount'] - ($array['recurring']['amount'] * ($nx - 1));
        $array['recurring']['billingCycle'] = Mage::getStoreConfig('payment/PaylineNX/billing_cycle');
        $array['recurring']['billingLeft'] = $nx;
        $array['recurring']['billingDay'] = '';
        $array['recurring']['startDate'] = '';

		//contrat list
        $array['contracts'] = array($array['payment']['contractNumber']);

        // second contracts
        $array['secondContracts'] = explode(';', $helperPayline->secondaryContractNumberList);
        // PRIVATE DATA
        $privateData = array();
        $privateData['key'] = "orderRef";
        $privateData['value'] = substr(str_replace(array("\r","\n","\t"), array('','',''),$array['order']['ref']), 0,255);
        $paylineSDK->setPrivate($privateData);
        if(isset($customer)){
        	$privateData['key'] = 'plnAccountAge'; // customer account age, in days
        	$privateData['value'] = round((time()-$customer->getCreatedAtTimestamp())/(60*60*24));
        	$paylineSDK->setPrivate($privateData);
        	$privateData['key'] = 'plnLastCompleteOrderAge'; // last complete order age, in days
        	if(isset($array['plnLastCompleteOrderAge'])){
        		$privateData['value'] = $array['plnLastCompleteOrderAge'];
        	}else{
        		$privateData['value'] = '-1';
        	}
        	$paylineSDK->setPrivate($privateData);
        }

        //ORDER DETAILS (optional)
        $helperPayline->setOrderDetails($paylineSDK, $this->order, false, null);

		//WALLET
        if (Mage::getStoreConfig('payment/PaylineCPT/send_wallet_id')) {
            if (! isset($array['buyer']['walletId'])) {
                if (isset($helperPayline->walletId)) {
                    $array['buyer']['walletId'] = $helperPayline->walletId;
                }
            }
            if ($helperPayline->canSubscribeWallet()) {
				//If the wallet is new (registered during payment), we must save it in the private data since it's not sent back by default
                if ($helperPayline->isNewWallet) {
                    if ($helperPayline->walletId) {
                        $paylineSDK->setPrivate(array('key' => 'newWalletId', 'value' => $helperPayline->walletId));
                    }
                }
            }
        }
        // ADD CONTRACT WALLET ARRAY TO $array
        $array['walletContracts'] = Mage::helper('payline')->buildContractNumberWalletList();
        // EXECUTE
        try {
            $result = $paylineSDK->doWebPayment($array);
        } catch (Exception $e) {
            return $this->_logOnException('nxAction', $e);
        }
        // RESPONSE
        $initStatus = Mage::getStoreConfig('payment/payline_common/init_order_status');
        if (isset($result) && is_array($result) && $result['result']['code'] == '00000') {
            $this->order->setState(Mage_Sales_Model_Order::STATE_NEW, $initStatus, '', false);
            $this->order->save();
            header("location:" . $result['redirectURL']);
            exit();
        } else {
            Mage::helper('payline/payment')->updateStock($this->order);
            if (isset($result) && is_array($result)) {
                $msgLog = 'PAYLINE ERROR : ' . $result['result']['code'] . ' ' . $result['result']['shortMessage'] . ' (' . $result['result']['longMessage'] . ')';
            } elseif (isset($result) && is_string($result)) {
                $msgLog = 'PAYLINE ERROR : ' . $result;
            } else {
                $msgLog = 'Unknown PAYLINE ERROR';
            }
            $this->order->setState(Mage_Sales_Model_Order::STATE_NEW, $initStatus, $msgLog, false);
            $this->order->save();
            $msg = Mage::helper('payline')->__('Error during payment');
            Mage::helper('payline/logger')->log('[nxAction] ' . $this->order->getIncrementId() . $msgLog, Zend_Log::ERR);
            Mage::getSingleton('core/session')->addError($msg);
            $this->_redirect('checkout/onepage');
            return;
        }
    }

    public function cptWidgetCustomAction()
    {
        $result = array();

        $method = $this->getRequest()->getParam('paymentmethod');

        try {
            if(empty($method)) {
                throw new Exception('No payment method');
            }
            $onePage = Mage::getSingleton('checkout/type_onepage');
            $quote = $onePage->getQuote();
            if($quote and $quote->getId()) {
                $data=array('method'=>$method);
                $quote->getPayment()->importData($data);
            } else {
                // Incorrect order_id
                throw new Exception('Incorrect quote_id');
            }
        } catch (Exception $e) {
            Mage::helper('payline/logger')->log('[cptWidgetCustomAction] '.$e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $this->__('Unable to set Payment Method.');
        }

        if(!empty($result)) {
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->getRequest()->setParam('form_key', Mage::getSingleton('core/session')->getFormKey());
            $this->_forward('saveOrder', 'onepage', 'checkout');
        }
    }



    public function cptReturnWidgetAction()
    {
        $paylineToken = $this->getRequest()->getParam('paylinetoken');
        if(empty($paylineToken)) {
            $paylineToken = $this->getRequest()->getParam('token');
        }

        try {
            if(empty($paylineToken)) {
                throw new Exception('No token');
            }

            $tokenModel = Mage::getModel('payline/token')->getCollection()->addFieldToFilter('token',$paylineToken);
            $tokenData = $tokenModel->getFirstItem()->getData();

            // Order is loaded from id associated to the token
            if(sizeof($tokenData) == 0){
                throw new Exception('No data for token '.$paylineToken);
            }

            $contractNumber = Mage::helper('payline')->getDefaultContractNumberForWidget();
            if(empty($contractNumber)) {
                throw new Exception('Cannot find valid contract number');
            }

            $order = Mage::getModel('sales/order')->loadByIncrementId($tokenData['order_id']);
            if(!$order->getId()) {
                $onePage = Mage::getSingleton('checkout/type_onepage');
                $quote = $onePage->getQuote();
                if($quote and $quote->getId() && $quote->getReservedOrderId()==$tokenData['order_id']) {
                    $data=array('method'=>'PaylineCPT', 'cc_type'=>$contractNumber);
                    $quote->getPayment()->importData($data);

                    $onePage->saveOrder();
                    $onePage->getQuote()->save();

                    $parameters = array('paylinetoken'=>$paylineToken);
                    $this->_forward('cptReturn',NULL,NULL,$parameters);
                    return $this;
                } else {
                    // Incorrect order_id
                    throw new Exception('Incorrect quote_id or reserved_order_id '.$paylineToken);
                }
            } else {
                // Order should not be created
                throw new Exception('Order already exist for '.$tokenData['order_id']);
            }

        } catch (Exception $e) {
            //TODO: If payment is done it should be canceled
            Mage::helper('payline/logger')->log('[cptReturnWidgetAction] '.$e->getMessage());
        }

        Mage::getSingleton('core/session')->addError($this->__('There was an error processing your order. Please contact us or try again later.'));
        $this->_redirect('checkout/cart');
        return $this;
    }




    /**
     * Action called on the customer's return/cancel form the Payline payment page OR when Payline notifies the shop
     */
    public function cptReturnAction(){

      $paylineToken = $this->getRequest()->getParam('paylinetoken');
      if(empty($paylineToken)) {
          $paylineToken = $this->getRequest()->getParam('token');
      }

    	$tokenModel = Mage::getModel('payline/token')->getCollection()->addFieldToFilter('token',$paylineToken);
    	$tokenData = $tokenModel->getFirstItem()->getData();

    	// Order is loaded from id associated to the token
    	if(sizeof($tokenData) == 0){
    		Mage::helper('payline/logger')->log('[cptReturnAction] - token '.$_GET['token'].' is unknown');
    		return;
    	}
    	$this->order = Mage::getModel('sales/order')->loadByIncrementId($tokenData['order_id']);

    	if(!in_array($tokenData['status'],array(0,3)) && !isset($_GET['force_upd'])){ // order update is already done => exit this function
    		if(isset($_GET['notificationType'])) return; // call from notify URL => no page to display

    		$acceptedCodes = array(
    			'00000', // Credit card -> Transaction approved
    		    '02500', // Wallet -> Operation successfull
    		    '02501', // Wallet -> Operation Successfull with warning / Operation Successfull but wallet will expire
    		    '04003', // Fraud detected - BUT Transaction approved (04002 is Fraud with payment refused)
    		    '00100',
    		    '03000',
    		    '34230', // signature SDD
    		    '34330' // prélèvement SDD
    		);

    		if(in_array($tokenData['result_code'], $acceptedCodes)){
            	$this->_redirect('checkout/onepage/success');
        	}else{
            	Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is refused'));
            	$this->_redirectUrl($this->_getPaymentRefusedRedirectUrl());
        	}
        	return;
    	}

    	  $tokenForUpdate = Mage::getModel('payline/token')->load($tokenData['id']);
        $webPaymentDetails = Mage::helper('payline')->initPayline('CPT')->getWebPaymentDetails(array('token' => $paylineToken, 'version' => Monext_Payline_Helper_Data::VERSION));
        if(isset($webPaymentDetails)){
        	if(is_array($webPaymentDetails)){
        		if(Mage::helper('payline/payment')->updateOrder($this->order, $webPaymentDetails,$webPaymentDetails['transaction']['id'], 'CPT')){ // payment OK
        			$redirectUrl = Mage::getBaseUrl()."checkout/onepage/success/";

        			// set order status
        			if($webPaymentDetails['result']['code'] == '04003') {
        				$newOrderStatus = Mage::getStoreConfig('payment/payline_common/fraud_order_status');
        				Mage::helper('payline')->setOrderStatus($this->order, $newOrderStatus);
        			} else {
        				Mage::helper('payline')->setOrderStatusAccordingToPaymentMode(
        				$this->order, $webPaymentDetails['payment']['action'] );
        			}

        			// update token model to flag this order as already updated and save resultCode & transactionId
        			$tokenForUpdate->setStatus(1); // OK
        			$tokenForUpdate->setTransactionId($webPaymentDetails['transaction']['id']);
        			$tokenForUpdate->setResultCode($webPaymentDetails['result']['code']);

        			// save wallet if created during this payment
        			if(!empty($webPaymentDetails['privateDataList']) and !empty($webPaymentDetails['privateDataList']['privateData'])) {
            			foreach ($webPaymentDetails['privateDataList']['privateData'] as $privateDataList){
            				if($privateDataList->key == 'newWalletId'){
            					if(isset($webPaymentDetails['wallet']) && $webPaymentDetails['wallet']['walletId'] == $privateDataList->value){ // Customer may have unchecked the "Save this information for my next orders" checkbox on payment page. If so, wallet is not created !
            						$this->saveWallet($privateDataList->value);
            					}
            				}
            			}
        			}

        			// create invoice if needed
        			Mage::helper('payline')->automateCreateInvoiceAtShopReturn('CPT', $this->order);

        		}else{ // payment NOT OK
        			$msgLog='PAYMENT KO : '.$webPaymentDetails['result']['code']. ' ' . $webPaymentDetails['result']['shortMessage'] . ' ('.$webPaymentDetails['result']['longMessage'].')';
        			$tokenForUpdate->setResultCode($webPaymentDetails['result']['code']);

        			$pendingCodes = array(
        				'02306', // Customer has to fill his payment data
        			    '02533', // Customer not redirected to payment page AND session is active
        			    '02000', // transaction in progress
        			    '02005', // transaction in progress
        			    '02015', // transaction delegated to partner
        			    paylineSDK::ERR_CODE // communication issue between Payline and the store
        			);

        			if(!in_array($webPaymentDetails['result']['code'], $pendingCodes)){
        			    if ($webPaymentDetails['result']['code'] == '00000') { // payment is OK, but a mismatch with order was detected
        			        $paymentDataMismatchStatus = Mage::getStoreConfig('payment/payline_common/payment_mismatch_order_status');
        			        $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$paymentDataMismatchStatus,$msgLog,false);
        			    }elseif ($webPaymentDetails['result']['code'] == '02304' || $webPaymentDetails['result']['code'] == '02324' || $webPaymentDetails['result']['code'] == '02534') {
        					$abandonedStatus = Mage::getStoreConfig('payment/payline_common/resignation_order_status');
        					$this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$abandonedStatus,$msgLog,false);
        				}elseif ($webPaymentDetails['result']['code'] == '02319') {
        					Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is canceled'));
        					$canceledStatus = Mage::getStoreConfig('payment/payline_common/canceled_order_status');
        					$this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$canceledStatus,$msgLog,false);
        				}else {
        					Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is refused'));
        					$failedOrderStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');
        					$this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED,$failedOrderStatus,$msgLog,false);
        				}
        				$tokenForUpdate->setStatus(2); // KO
        				$redirectUrl = $this->_getPaymentRefusedRedirectUrl();
        			}else{
        			    Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is saved'));
        			    $waitOrderStatus = Mage::getStoreConfig('payment/payline_common/wait_order_status');
        			    $this->order->setState(Mage_Sales_Model_Order::STATE_PROCESSING,$waitOrderStatus,$msgLog,false);
        			    $tokenForUpdate->setStatus(3); // to be determined
        			    $redirectUrl = Mage::getBaseUrl()."checkout/onepage/success/";        			    
        			}
        			Mage::helper('payline/logger')->log('[cptReturnAction] ' .$this->order->getIncrementId().$msgLog);        			
        		}
        		$tokenForUpdate->setDateUpdate(date('Y-m-d G:i:s'));
        		$tokenForUpdate->save();

        	}elseif(is_string($webPaymentDetails)){
        		Mage::helper('payline/logger')->log('[cptReturnAction] order '.$this->order->getIncrementId().' - ERROR - '.$webPaymentDetails);
        		return;
        	}
        }else{
        	Mage::helper('payline/logger')->log('[cptReturnAction] order '.$this->order->getIncrementId().' : unknown error during update');
        	return;
        }
        $this->order->save();
        if(isset($_GET['notificationType'])) return; // call from notify URL => no page to display
        $this->_redirectUrl($redirectUrl);
    }

    /**
     * Action called on the customer's return form the Payline website.
     */
    public function nxReturnAction(){
        Mage_Core_Controller_Varien_Action::_redirectSuccess($this->nxNotifAction());
    }


    /**
     * Save NX payment result, called by the bank when the transaction is done
     */
    public function nxNotifAction(){
        $res = Mage::helper('payline')->initPayline('NX')->getWebPaymentDetails(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        if (isset($res['privateDataList']['privateData']['value'])) {
            $orderRef = $res['privateDataList']['privateData']['value'];
        } else {
            foreach ($res['privateDataList']['privateData'] as $privateDataList) {
                if ($privateDataList->key == 'orderRef') {
                    $orderRef = $privateDataList->value;
                }
            }
        }
        if (! isset($orderRef)) {
            $msgLog = 'Référence commande introuvable dans le résultat du paiement Nx';
            Mage::helper('payline/logger')->log('[nxNotifAction] ' . $this->order->getIncrementId() . ' ' . $msgLog);
            $redirectUrl = Mage::getBaseUrl() . "checkout/onepage/";
        }
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($orderRef);
        $failedOrderStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');
        if (isset($res['billingRecordList']['billingRecord'])) {
            $size = sizeof($res['billingRecordList']['billingRecord']);
        } else {
            $size = 0;
        }
        $billingRecord = false;
        for ($i = 0; $i < $size; $i ++) {
            if ($res['billingRecordList']['billingRecord'][$i]->status == 1) {
                $txnId = $res['billingRecordList']['billingRecord'][$i]->transaction->id;
                if (! $this->order->getTransaction($txnId)) {
                    $billingRecord = $res['billingRecordList']['billingRecord'][$i];
                }
            }
        }
        if ($billingRecord && Mage::helper('payline/payment')->updateOrder($this->order, $res, $billingRecord->transaction->id, 'NX')) {
            $redirectUrl = Mage::getBaseUrl() . "checkout/onepage/success/";
            if ($res['result']['code'] == '04003') {
                $newOrderStatus = Mage::getStoreConfig('payment/payline_common/fraud_order_status');
                Mage::helper('payline')->setOrderStatus($this->order, $newOrderStatus);
            } else if( $res['result']['code'] == '02501' ) { // credit card (CC) will expire
                    $statusScheduleAlert = Mage::getStoreConfig('payment/PaylineNX/status_when_payline_schedule_alert');
                    Mage::helper('payline')->setOrderStatus($this->order, $statusScheduleAlert);
                } else {
                Mage::helper('payline')->setOrderStatusAccordingToPaymentMode(
                    $this->order, $res['payment']['action'] );
                }
            if (isset($res['privateDataList']['privateData'][1]) && $res['privateDataList']['privateData'][1]->key == "newWalletId" && $res['privateDataList']['privateData'][1]->value != '') {
                $this->saveWallet($res['privateDataList']['privateData'][1]->value);
            }
            $payment = $this->order->getPayment();
            if ($payment->getBaseAmountPaid() != $payment->getBaseAmountOrdered()) {
                Mage::helper('payline')->automateCreateInvoiceAtShopReturn('NX', $this->order);
            }
        } else {
            if (isset($res) && is_array($res)) {
                $msgLog = 'PAYLINE ERROR : ' . $res['result']['code'] . ' ' . $res['result']['shortMessage'] . ' (' . $res['result']['longMessage'] . ')';
            } elseif (isset($res) && is_string($res)) {
                $msgLog = 'PAYLINE ERROR : ' . $res;
            } else {
                $msgLog = 'Error during order update (#' . $this->order->getIncrementId() . ')';
            }
            if (is_array($res) && ! ($res['result']['code'] == '02306' || $res['result']['code'] == '02533')) {
                if (is_array($res) && ($res['result']['code'] == '02304' || $res['result']['code'] == '02324' || $res['result']['code'] == '02534')) {
                    $abandonedStatus = Mage::getStoreConfig('payment/payline_common/resignation_order_status');
                    $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $abandonedStatus, $msgLog, false);
                } else {
                    $statusScheduleAlert = Mage::getStoreConfig('payment/PaylineNX/status_when_payline_schedule_alert');
                    if( !empty( $statusScheduleAlert ) ) { // if user conf is set
                        $failedOrderStatus = $statusScheduleAlert;
                    }
                    $this->order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $failedOrderStatus, $msgLog, false);
                }
            }
            Mage::helper('payline/logger')->log('[nxNotifAction] ' . $this->order->getIncrementId() . $msgLog);
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
        $statusScheduleAlert = Mage::getStoreConfig('payment/PaylineNX/status_when_payline_schedule_alert');
        $statusCCExpired = Mage::getStoreConfig('payment/PaylineNX/status_when_credit_card_schedule_is_expired');
        if (! empty($statusScheduleAlert) || ! empty($statusCCExpired)) {
            if ($this->isNxTermParamsOk($_GET)) {
                /*
                 * BILL = value required for terms notifications
                 * WEBTRS   = value for cash web payment
                 */
                if ($_GET['notificationType'] == 'BILL') { //
                    $transactionParams = array();
                    $transactionParams['transactionId'] = $_GET['transactionId'];
                    $transactionParams['orderRef'] = $_GET['orderRef'];
                    $transactionParams['version'] = Monext_Payline_Helper_Data::VERSION;
                    $transactionParams['startDate'] = '';
                    $transactionParams['endDate'] = '';
                    $transactionParams['transactionHistory'] = '';
                    $transactionParams['archiveSearch'] = '';
                    $res = Mage::helper('payline')->initPayline('NX')->getTransactionDetails($transactionParams);
                    if( isset( $res )
                        && is_array( $res )
                        && isset( $res['result'] )
                        && isset( $res['result']['code'] ) )
                    {
                        $mustSave = true;
                        switch ($res['result']['code']) {
                            case '00000':
                            case '02500':
                            case '04003':
                                $mustSave = false;
                                break;
                            case '02501': // payment card will expire
                                if (! empty($statusScheduleAlert)) {
                                    $this->order = $this->setOrderStatus($statusScheduleAlert, $_GET['orderRef']);
                                    break;
                                }
                            default: // if default => error (cc expired or other errors)
                                if (! empty($statusCCExpired)) {
                                    $this->order = $this->setOrderStatus($statusCCExpired, $_GET['orderRef']);
                                } else {
                                    $mustSave = false;
                                }
                                break;
                        }
                        if ($mustSave) {
                            $this->order->save();
                        }
                    } // end if ( isset($res) ...
                } // end if BILL
            } // end if $this->isNxTermParamsOk
        } // end if !empty( $statusScheduleAlert ) || !empty( $statusCCExpired )
    } // end func
    /**
     * Check if $params contains all the required keys for
     * PaylineSDK#getTransactionDetails()
     *
     * @param $params {array}
     *            array params for PaylineSDK#getTransactionDetails(), should
     *            contain all keys required.
     * @return bool true if $params ok, otherwise false
     */
    private function isNxTermParamsOk($params)
    {
        if (! isset($params['notificationType']))
            return false;
        if (! isset($params['paymentRecordId']))
            return false;
        if (! isset($params['walletId']))
            return false;
        if (! isset($params['transactionId']))
            return false;
        if (! isset($params['billingRecordDate']))
            return false;
        if (! isset($params['orderRef']))
            return false;
        return true;
    }

    /**
     * Set an order status.
     * If !isset($this->order) process order model from $orderRef
     *
     * @param $status {string}
     *            status order to assign
     * @param $orderRef {string}
     *            entity_id order
     * @return Mage_Sales_Model_Order Return the order object with new status
     *         set
     */
    private function setOrderStatus($status, $orderRef)
    {
        if (isset($this->order)) {
            $order = $this->order;
        } else {
            $order = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('increment_id', $orderRef)
                ->getFirstItem();
        }
        Mage::helper('payline')->setOrderStatus($order, $status);
        return $order;
    }

    /**
     * Cancel a NX payment request /order
     */
    public function nxCancelAction(){
        $res = Mage::helper('payline')->initPayline('NX')->getWebPaymentDetails(array('token' => $_GET['token'], 'version' => Monext_Payline_Helper_Data::VERSION));
        if (isset($res['privateDataList']['privateData']['value'])) {
            $orderRef = $res['privateDataList']['privateData']['value'];
        } else {
            foreach ($res['privateDataList']['privateData'] as $privateDataList) {
                if ($privateDataList->key == 'orderRef') {
                    $orderRef = $privateDataList->value;
                }
            }
        }
        if (! isset($orderRef)) {
            $msgLog = 'Couldn\'t find order increment id in nx payment cancel result';
            Mage::helper('payline/logger')->log('[nxCancelAction] ' . $this->order->getIncrementId() . $msgLog);
            $redirectUrl = Mage::getBaseUrl() . "checkout/onepage/";
        }
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($orderRef);
        if (is_string($res)) {
            $msg = 'PAYLINE ERROR : ' . $res;
            Mage::helper('payline/logger')->log('[nxCancelAction] ' . $this->order->getIncrementId() . ' ' . $msg);
            $cancelStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');
        } elseif (substr($res['result']['code'], 0, 2) == '01' || substr($res['result']['code'], 0, 3) == '021') {
            // Invalid transaction or error during the process on Payline side
            //No error display, the customer is already told on the Payline side
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is refused'));
            $msg = 'PAYLINE ERROR : ' . $res['result']['code'] . ' ' . $res['result']['shortMessage'] . ' (' . $res['result']['longMessage'] . ')';
            Mage::helper('payline/logger')->log('[nxCancelAction] ' . $this->order->getIncrementId() . $msg);
            $cancelStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');
        } else {
            Mage::getSingleton('core/session')->addError(Mage::helper('payline')->__('Your payment is canceled'));
            $msg = 'PAYLINE INFO : ' . $res['result']['code'] . ' ' . $res['result']['shortMessage'] . ' (' . $res['result']['longMessage'] . ')';
            // Transaction cancelled by customer
            $cancelStatus = Mage::getStoreConfig('payment/payline_common/canceled_order_status');
        }
        $this->cancelOrder($cancelStatus, $res['result']['code'], $msg);
    }


    public function tokenReturnAction()
    {
        $params = $this->getRequest()->getParams();
        if(!empty($params) and is_array($params) and count($params)==1) {
            $html = key($params) . '=' . current($params);
        } else {
            $html = 'errorCode=Response malformed';
        }
        $this->getResponse()->setHeader('Content-type', 'text/html');
        $this->getResponse()->setBody($html);
    }


    protected function _getPaymentRefusedRedirectUrl()
    {
        $option = Mage::getStoreConfig('payment/payline_common/return_payment_refused');
        switch ($option) {
            case Monext_Payline_Model_Datasource_Return::CART_EMPTY:
                $url = Mage::getUrl('checkout/onepage');
                break;
            case Monext_Payline_Model_Datasource_Return::HISTORY_ORDERS:
                $url = Mage::getUrl('sales/order/history');
                break;
            case Monext_Payline_Model_Datasource_Return::CART_FULL:
                $url = Mage::getUrl('sales/order/reorder', array('order_id' => $this->order->getId()));
                break;
            default:
                $url = Mage::getUrl('checkout/onepage');
        }
        return $url;
    }



    protected function _logOnException($action, Exception $e) {
        Mage::getSingleton('payline/session')->clear();

        Mage::logException($e);

        Mage::helper('payline/payment')->updateStock($this->order);

        $msg = Mage::helper('payline')->__('Error during payment');
        Mage::getSingleton('core/session')->addError($msg);

        $msgLog = $e->getMessage();
        if(empty($msgLog)) {
            $msgLog = 'Unknown PAYLINE ERROR';
        }

        Mage::helper('payline/logger')->log('[' . $action . '] ' . $this->order->getIncrementId() . ' ' . $msgLog, Zend_Log::ERR);

        $this->_redirect('checkout/onepage');

        return;
    }
    
    public function waitingOrderUpdateAction(){
        $tokenCollection = Mage::getModel('payline/token')->getCollection()->addFieldToFilter('status',3); // 3 stands 'pending payment result' status
        foreach($tokenCollection as $token) {
            $this->getRequest()->setParam('token', $token->getToken());
            $_GET['notificationType'] = 'WAITUPD'; // force notificationType param to prevent cptReturnAction to display customer result page
            $this->cptReturnAction();
        }
    }

}