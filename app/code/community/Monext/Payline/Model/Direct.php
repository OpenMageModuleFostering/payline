<?php
/**
 * Payline direct payment method
 */
class Monext_Payline_Model_Direct extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'PaylineDIRECT';

    protected $_formBlockType = 'payline/direct';

    protected $_infoBlockType = 'payline/info_direct';

    protected $_canCapture = true;

    protected $_canCapturePartial = true;

    protected $_canRefund = true;

    protected $_canRefundInvoicePartial = true;

    protected $_canVoid = true;

    protected $_canOrder = true;


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

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $saveInfo = true;
        if(!$data->getCardTokenPan()) {
            // If we don't have the token the CcNumber is stored in memory for the current process
            Mage::register('current_payment_data', $data);
        } else {
            // With the token no need for CcNumber the data is stored in session so we can check 3DS
            $paylineSession = Mage::getSingleton('payline/session');
            if($data->getAssignSession()) {

                $paylineSession->setCcType($data->getCcType())
                        //->setCcOwner($data->getCcOwner())
                        ->setCcLast4($data->getCcLast4())
                        //TODO: Monext have to avoid using cid
                        ->setCcCid($data->getCcCid())
                        ->setCardTokenPan($data->getCardTokenPan())
                        ->setSubscribeWallet($data->getSubscribeWallet());
            } else {
                $saveInfo = false;
            }
        }

        if($saveInfo) {
            // Fill the info instance
            $info = $this->getInfoInstance();
            $info->setCcType($data->getCcType())
                ->setCcOwner($data->getCcOwner())
                ->setCcLast4($data->getCcLast4())
                ->setCcExpMonth($data->getCcExpMonth())
                ->setCcExpYear($data->getCcExpYear())
                ->setCcSsIssue($data->getCcSsIssue())
                ->setCcSsStartMonth($data->getCcSsStartMonth())
                ->setCcSsStartYear($data->getCcSsStartYear())
                ->setCardTokenPan($data->getCardTokenPan());
        }

        return $this;
    }


    /**
     * Order payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function order(Varien_Object $payment, $amount)
    {
        // Call parent
        parent::order($payment, $amount);

        $this->_orderDirect($payment, $amount);

        return $this;
    }

    /**
     * Order the payment via Payline Direct
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @throws Exception
     */
    protected function _orderDirect(Mage_Sales_Model_Order_Payment $payment)
    {
        $array = Mage::helper('payline/payment')->getDirectActionHeader($payment);

        $paylineSDK = Mage::helper('payline')->initPayline('DIRECT', $array['payment']['currency']);

        $order = $payment->getOrder();
        try {
            // Do autorization
            $author_result = $paylineSDK->doAuthorization($array);
            if(Mage::helper('payline/payment')->useSecureContract()) {
                return $this->_flagRedirectSecurized();
            //Check $author_result and redirect to directSecurizedAction if 3DS needed
            } elseif ($author_result['transaction'] and !empty($author_result['transaction']['isPossibleFraud'])) {
                if (Mage::helper('payline/payment')->switchToSecureContract()) {
                    $msgLog = ' Payline detect a possible fraud';
                    Mage::helper('payline/logger')->log('[directAction] ' . $order->getIncrementId() . $msgLog);
                    return $this->_flagRedirectSecurized();
                } else {
                    $msg = 'Fraud suspected and no 3DS contract found for ccType: ' . Mage::helper('payline/payment')->getPaymentUserData()->getCcType();
                    throw new Exception($msg);
                }
            }

        } catch (Exception $e) {
            // We get an exception, log it
            Mage::logException($e);

            // Update the stocks
            Mage::helper('payline/payment')->updateStock($order);

            // Send message to user (and log)
            $msg    = Mage::helper('payline')->__('Error during payment');
            $msgLog = 'Unknown PAYLINE ERROR (payline unreachable?)';
            Mage::helper('payline/logger')->log('[directAction] ' . $order->getIncrementId() . $msgLog);
            Mage::throwException($msg);
        }



        $statusFinalize = Mage::helper('payline/payment')->finalizeDirectAction($author_result, $paylineSDK, $array, $payment);

        if (!$statusFinalize) {
            // Alert the customer and log message
            Mage::helper('payline/logger')->log('[directAction] ' . $order->getIncrementId() . $msgLog);
        }
    }


    /**
     * Set flag to control the PlaceRedirectUrl
     *
     * @param string $action
     */
    protected function _flagRedirectSecurized()
    {
        Mage::register('payline_redirect_securized', true);
    }


    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        if(Mage::registry('payline_redirect_securized')) {
            return Mage::getUrl('payline/index/directSecurized');
        } else {
            return false;
        }
    }

    /**
     * Initialise the requests param array on the order call
     * @return array
     */
    protected function _orderInit(Mage_Sales_Model_Order $order)
    {
        return Mage::helper('payline/payment')->init($order);
    }

    /**
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @return  Monext_Payline_Model_Cpt
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::getModel('payline/cpt')->capture($payment,$amount,'DIRECT');
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
        Mage::getModel('payline/cpt')->refund($payment,$amount, 'DIRECT');
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
        Mage::getModel('payline/cpt')->void($payment, 'DIRECT');
        return $this;
    }
}
