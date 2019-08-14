<?php
/**
 * Payline Cash web payment method 
 */
class Monext_Payline_Model_Cpt extends Mage_Payment_Model_Method_Abstract
{
    const ACTION_AUTH           = 100;
    CONST ACTION_AUTH_CAPTURE   = 101;
    const ACTION_CAPTURE        = 201;
    const ACTION_RE_AUTH        = 202;
    
    protected $_code  = 'PaylineCPT';
    protected $_formBlockType = 'payline/cpt';
    protected $_infoBlockType = 'payline/info_default';  
    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canVoid = true;


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
    	//Mage::helper('payline/logger')->log('-- getOrderPlaceRedirectUrl --');
        return Mage::getUrl('payline/index/cpt');
    }

    /**
     * Refund money
     *
     * @param   Varien_Object $invoicePayment
     * @param float $amount
     * @return  Monext_Payline_Model_Cpt
     */
    public function refund(Varien_Object $payment, $amount, $useConfig='CPT')
    {
		$order = $payment->getOrder();
        $orderRef = $order->getRealOrderId();
		$transactionId = $payment->getCcTransId();	

                if ($invoice = $payment->getCreditmemo()->getInvoice()) {
                    if($invoice->getTransactionId()) {
                        $transactionId = $invoice->getTransactionId();
                    }
		}
        
        $array = array();
        $paylineSDK = Mage::helper('payline')->initPayline($useConfig,Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode()));
        
        // PAYMENT
        $array['payment']['amount'] = round($amount*100);
        $array['payment']['currency'] = Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode());
        $array['payment']['action'] = 421;
        $array['payment']['mode'] =  'CPT';
        $array['payment']['contractNumber']=Mage::helper('payline')->getTransactionContractNumber($paylineSDK, $payment->getCcTransId(), $orderRef);
        
        // TRANSACTION INFO
        $array['transactionID'] = $transactionId;
        $array['comment'] = "Remboursement de la transaction ".$transactionId." de la commande $orderRef depuis le back office Magento";
        $array['sequenceNumber'] = '';
        // PRIVATE DATA
        $privateData = array();
        $privateData['key'] = "orderRef";
        $privateData['value'] = $orderRef;
        $paylineSDK->setPrivate($privateData);

        // Magento version
        $array['version'] = Monext_Payline_Helper_Data::VERSION;

        // RESPONSE
        $response = $paylineSDK->doRefund($array);
		if(is_string($response)) {
			$errorMessage = Mage::helper('payline')->__("PAYLINE - Error in refunding the payment").": ";
            $errorMessage .= $response;
            Mage::helper('payline/Logger')->log('[refund] ['.$orderRef.'] ' .$errorMessage);
            Mage::throwException($errorMessage);
		} elseif(isset($response['result']) && isset($response['result']['code']) && $response['result']['code'] != '00000'){
			$errorMessage = Mage::helper('payline')->__("PAYLINE - Error in refunding the payment").": ";
            $errorMessage .= isset($response['result']['longMessage'])?$response['result']['longMessage']:'';
            $errorMessage .= isset($response['result']['code'])?" (code ".$response['result']['code'].")<br/>":'';
            Mage::helper('payline/Logger')->log('[refund] ['.$orderRef.'] ' .$errorMessage);
            Mage::throwException($errorMessage);
        }else{
            $transaction = Mage::getModel('sales/order_payment_transaction');
            $transaction->setOrder($order);
            $transaction->setOrderPaymentObject($payment);
            $transaction->setTxnId($response['transaction']['id']);
            $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
            //$transaction->setAdditionalInformation('amount',$amount);
            //$transaction->setAdditionalInformation('isDuplicated',$response['transaction']['isDuplicated']);
            //$transaction->setAdditionalInformation('isPossibleFraud',$response['transaction']['isPossibleFraud']);
            $transaction->save();
        }
        return $this;
    }

    /**
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @return  Monext_Payline_Model_Cpt
     */
    public function capture(Varien_Object $payment, $amount, $useConfig='CPT')
    {
        $order = $payment->getOrder();
        $orderRef = $order->getRealOrderId();
        $array = array();
        $paylineSDK = Mage::helper('payline')->initPayline($useConfig,Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode()));
        $transDetailsParams = array(
            'transactionId'=>$payment->getCcTransId(),
            'orderRef'=>$orderRef,
            'startDate' => '',
            'endDate' => '',
            'transactionHistory' => '',
            'version' => Monext_Payline_Helper_Data::VERSION,
            'archiveSearch' => ''
        );

		$transDetails=$paylineSDK->getTransactionDetails( $transDetailsParams );
        if (is_string($transDetails)) {
			Mage::helper('payline/logger')->log('[getTransactionContractNumber] ' . $transDetails);
			return;
		} elseif (isset($transDetails['result']) && $transDetails['result']['code']!='0000' && $transDetails['result']['code']!='2500' && $transDetails['result']['code']!='04003'){
            //Back to default
            Mage::helper('payline/logger')->log('[getTransactionContractNumber] ' .
            'Error while retrieving transaction contract number for transactionId'.' '.$payment->getCcTransId().' and order '.$orderRef.' error : '.$transDetails['result']['shortMessage']);
            return;
        }	
        $paymentMethod      = $payment->getMethod();
        $fireCaptureOption  = Mage::getStoreConfig('payment/'.$paymentMethod.'/capture_payment_when_i_said');
		if( isset($transDetails['payment']['action'])
            && ( $transDetails['payment']['action'] != self::ACTION_AUTH_CAPTURE
                || $fireCaptureOption == $order->getStatus() ) )
        {
            // PAYMENT
            $array['payment']['amount'] = round($amount*100);
            $array['payment']['currency'] = Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode());
            $array['payment']['action'] = Monext_Payline_Model_Cpt::ACTION_CAPTURE;
            $array['payment']['mode'] =  'CPT';
            $array['payment']['contractNumber']=$transDetails['payment']['contractNumber'];
            // TRANSACTION INFO
            $array['transactionID'] = $payment->getCcTransId();

            // PRIVATE DATA
            $privateData = array();
            $privateData['key'] = "orderRef";
            $privateData['value'] = $orderRef;
            $paylineSDK->setPrivate($privateData);

            $array['sequenceNumber']= '';
            $array['version']       = Monext_Payline_Helper_Data::VERSION;

            // Last week date
            $date = new Zend_Date(Mage::getModel('core/date')->gmtTimestamp());
            $date->subDay(7);
            $orderDate = new Zend_Date($order->getCreatedAt(), Zend_Date::ISO_8601);
            $isReAuthorization = false;
            if ($orderDate->isEarlier($date)) {
                $isReAuthorization  = true;
                $reauthAction       = Mage::getStoreConfig('payment/'.$paymentMethod.'/action_when_order_seven_day_old');
                $array['payment']['action'] = $reauthAction;

                $array['order']['ref'] = substr($orderRef,0,50);
                $array['order']['amount'] = $array['payment']['amount'];
                $array['order']['currency'] = $array['payment']['currency'];
                $response = $paylineSDK->doReAuthorization($array);
            } else {
                // RESPONSE
                $response = $paylineSDK->doCapture($array);
            }

			if(is_string($response)) {
				$errorMessage = Mage::helper('payline')->__("PAYLINE - Capture error").": ";
                $errorMessage .= $response;
                Mage::helper('payline/Logger')->log('[capture] ['.$orderRef.'] ' .$errorMessage);
                Mage::throwException($errorMessage);
			} elseif(isset($response['result']) && isset($response['result']['code']) && $response['result']['code'] != '00000'){
                $errorMessage = Mage::helper('payline')->__("PAYLINE - Capture error").": ";
                $errorMessage .= isset($response['result']['longMessage'])?$response['result']['longMessage']:'';
                $errorMessage .= isset($response['result']['code'])?" (code ".$response['result']['code'].")<br/>":'';
                Mage::helper('payline/Logger')->log('[capture] ['.$orderRef.'] ' .$errorMessage);
                Mage::throwException($errorMessage);
            }else{
				if($isReAuthorization) {
					$payment->setTransactionId($response['transaction']['id']);
                } else {
					$payment->setTransactionId($payment->getCcTransId());
				}
                $transaction = Mage::getModel('sales/order_payment_transaction');
                $transaction->setOrder($order);
                $transaction->setOrderPaymentObject($payment);
                $transaction->setTxnId($response['transaction']['id']);
                $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                //It looks Magento 1.8 saves the transaction by a other way, if we save here we've a constraint violation on primary key
                if (!(version_compare(Mage::getVersion(), '1.8', 'ge') && version_compare(Mage::getVersion(), '1.9', 'lt'))){
                    $transExistParam    = array( 'txn_id' => $response['transaction']['id'] );
                    $transFromDb        = Mage::helper('payline')->transactionExist( $transExistParam );
                    if( $transFromDb != false ) {
                        //transaction exist, update it's date
                        $now = Mage::getModel('core/date')->timestamp( time() );
                        $now = date("Y-m-d H:i:s", $now);
                        $transaction->setData(
                            array(
                                'created_at'        => $now,
                                'transaction_id'    => $transFromDb->getId(),
                                'txn_id'            => $transFromDb->getTxnId(),
                                'txn_type'          => $transFromDb->getTxnType()
                            )
                        );
                    }
                    $transaction->save();
                }
            }
        }
    }

    /**
     * Cancel payment
     *
     * @param   Varien_Object $payment
     * @return  Monext_Payline_Model_Cpt
     */
    public function void(Varien_Object $payment, $useConfig='CPT')
    {
        $order = $payment->getOrder();
        $orderRef = $order->getRealOrderId();
        $array = array();
        $paylineSDK = Mage::helper('payline')->initPayline($useConfig,Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode()));

        // TRANSACTION INFO
        $array['transactionID'] = $payment->getCcTransId();
        $array['comment'] = "Annulation de la transaction ".$payment->getCcTransId()." de la commande $orderRef depuis le back office Magento";
        
        // PRIVATE DATA
        $privateData = array();
        $privateData['key'] = "orderRef";
        $privateData['value'] = $orderRef;
        $paylineSDK->setPrivate($privateData);

        // RESPONSE
        $response = $paylineSDK->doReset($array);

		if(is_string($response)) {
			$errorMessage = Mage::helper('payline')->__("PAYLINE - Error in cancelling the payment").": ";
            $errorMessage .= $response;
            Mage::helper('payline/Logger')->log('[void] ['.$orderRef.'] ' .$errorMessage);
            Mage::throwException($errorMessage);
		} elseif(isset($response['result']) && isset($response['result']['code']) &&  $response['result']['code'] != '00000'){
            $errorMessage = Mage::helper('payline')->__("PAYLINE - Error in cancelling the payment").": ";
            $errorMessage .= isset($response['result']['longMessage'])?$response['result']['longMessage']:'';
            $errorMessage .= isset($response['result']['code'])?" (code ".$response['result']['code'].")<br/>":'';
            Mage::helper('payline/Logger')->log('[void] ['.$orderRef.'] ' .$errorMessage);
            Mage::throwException($errorMessage);
        }else{
            $transaction = Mage::getModel('sales/order_payment_transaction');
            $transaction->setOrder($order);
            $transaction->setOrderPaymentObject($payment);
            $transaction->setTxnId($response['transaction']['id']);
            $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
            $transaction->save();
        }
        return $this;
    }    
}
