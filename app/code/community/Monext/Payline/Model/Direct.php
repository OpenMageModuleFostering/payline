<?php
/**
 * Payline direct payment method
 */
class Monext_Payline_Model_Direct extends Mage_Payment_Model_Method_Abstract
{
    protected $_code                    = 'PaylineDIRECT';
    protected $_formBlockType           = 'payline/direct';
    protected $_infoBlockType           = 'payline/info_direct';
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;
    protected $_canOrder                = true;

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        // Store the data for the current process
        Mage::register('current_payment_data', $data);

        // Fill the info instance
        $info = $this->getInfoInstance();
        $info
            ->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear());

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return Monext_Payline_Model_Direct
     */
    public function validate()
    {
        parent::validate();

        // Validate the credit card number
        if ($data = Mage::registry('current_payment_data')) {
            // @TODO
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
     */
    protected function _orderDirect(Mage_Sales_Model_Order_Payment $payment, $amount)
    {
        $order = $payment->getOrder();
        $data  = Mage::registry('current_payment_data');
        $array = $this->_orderInit($order);

        // Init the SDK with the currency and for DIRECT method
        $paylineSDK = Mage::helper('payline')->initPayline('DIRECT', $array['payment']['currency']);

        // Get the action and the mode
        $array['payment']['action'] = Mage::getStoreConfig('payment/PaylineDIRECT/payline_payment_action');
        $array['payment']['mode']   = 'CPT';

        // Get the contract
        $contract                           = Mage::helper('payline/payment')->getContractByData($data);
        $array['payment']['contractNumber'] = $contract->getNumber();

        // Set the order date
        $array['order']['date'] = date("d/m/Y H:i");

        // Set private data (usefull in the payline admin)
        $privateData1          = array();
        $privateData1['key']   = 'orderRef';
        $privateData1['value'] = substr(str_replace(array("\r", "\n", "\t"), array('', '', ''), $array['order']['ref']), 0, 255);
        $paylineSDK->setPrivate($privateData1);

        // Set the order details (each item, optional)
        $items = $order->getAllItems();
        if ($items) {
            if (count($items) > 100) {
                $items = array_slice($items, 0, 100);
            }
            foreach ($items as $item) {
                $itemPrice = round($item->getPrice() * 100);
                if ($itemPrice > 0) {
                    $product             = array();
                    $product['ref']      = Mage::helper('payline')->encodeString(substr(str_replace(array("\r", "\n", "\t"), array('', '', ''), $item->getName()), 0, 50));
                    $product['price']    = round($item->getPrice() * 100);
                    $product['quantity'] = round($item->getQtyOrdered());
                    $product['comment']  = Mage::helper('payline')->encodeString(substr(str_replace(array("\r", "\n", "\t"), array('', '', ''), $item->getDescription()), 0, 255));
                    $paylineSDK->setItem($product);
                }
            }
        }
        // Set the card info
        $array['card']['number']         = $data->getCcNumber();
        $array['card']['cardholder']     = $data->getCcOwner();
        $array['card']['type']           = $contract->getContractType();
        $array['card']['expirationDate'] = $data->getCcExpMonth() . $data->getCcExpYear();
        $array['card']['cvx']            = $data->getCcCid();

        // Set the customer's IP
        $array['buyer']['ip'] = Mage::helper('core/http')->getRemoteAddr();

        // Init 3DS to empty array
        $array['3DSecure'] = array();

        // Init bank acocunt data to empty array
        $array['BankAccountData'] = array();

        // Set the version
        $array['version'] = Monext_Payline_Helper_Data::VERSION;
        
        // Set the card owner's name
        $array['owner']['lastName'] = Mage::helper('payline')->encodeString($data->getCcOwner());

        try {
            // Do autorization
            $author_result = $paylineSDK->doAuthorization($array);

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

        /**
         * Process the authorization response
         */

        // The failed order status
        $failedOrderStatus = Mage::getStoreConfig('payment/payline_common/failed_order_status');

        // Authorization succeed
        if (isset($author_result) && is_array($author_result) && $author_result['result']['code'] == '00000') {

            /**
             * Update the order with the new transaction
             */
            // If everything is OK
            if (Mage::helper('payline/payment')->updateOrder($order, $author_result, $author_result['transaction']['id'], 'DIRECT')) {

                // Code 04003 - Fraud detected - BUT Transaction approved (04002 is Fraud with payment refused)
                if ($author_result['result']['code'] == '04003') {
                    // Fraud suspected
                    $payment->setIsFraudDetected(true);
                    $newOrderStatus = Mage::getStoreConfig('payment/payline_common/fraud_order_status');
                    Mage::helper('payline')->setOrderStatus($order, $newOrderStatus);
                } else {
                    // Set the status depending on the configuration
                    Mage::helper('payline')->setOrderStatusAccordingToPaymentMode(
                    $order, $array['payment']['action']);
                }

                // Create the wallet!
                $array['wallet']['lastName']  = $array['buyer']['lastName'];
                $array['wallet']['firstName'] = $array['buyer']['firstName'];
                $array['wallet']['email']     = $array['buyer']['email'];
                $array['address']             = $array['shippingAddress'];
                $array['ownerAddress']        = null;
                Mage::helper('payline')->createWalletForCurrentCustomer($paylineSDK, $array);

                // Create the invoice
                Mage::helper('payline')->automateCreateInvoiceAtShopReturn('DIRECT', $order);
            }

            // Everything _isn't OK_
            else {

                // Log a message and cancel the order. Alert the customer
                $msgLog = 'Error during order update (#' . $order->getIncrementId() . ')' . "\n";
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $failedOrderStatus, $msgLog, false);
                Mage::helper('payline/logger')->log('[directAction] ' . $order->getIncrementId() . $msgLog);

                // Error
                $payment->setSkipOrderProcessing(true);
                $msg = Mage::helper('payline')->__('An error occured during the payment. Please retry or use an other payment method.');
                Mage::throwException($msg);
            }

        }

        // Authorization doesn't succeed
        else {

            // Get the error message
            if (isset($author_result) && is_array($author_result)) {
                $msgLog = 'PAYLINE ERROR : ' . $author_result['result']['code'] . ' ' . $author_result['result']['shortMessage'] . ' (' . $author_result['result']['longMessage'] . ')';
            } elseif (isset($author_result) && is_string($author_result)) {
                $msgLog = 'PAYLINE ERROR : ' . $author_result;
            } else {
                $msgLog = 'Unknown PAYLINE ERROR';
            }

            // Update the stock
            Mage::helper('payline/payment')->updateStock($order);

            // Cancel the order
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $failedOrderStatus, $msgLog, false);

            // Alert the customer and log message
            Mage::helper('payline/logger')->log('[directAction] ' . $order->getIncrementId() . $msgLog);

            // Error
            $payment->setSkipOrderProcessing(true);
            $msg = Mage::helper('payline')->__('An error occured during the payment. Please retry or use an other payment method.');
            Mage::throwException($msg);
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
