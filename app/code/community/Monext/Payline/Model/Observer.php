<?php

class Monext_Payline_Model_Observer
{

    protected $_mode;

    public function createInvoiceWhenStatusChange(Varien_Event_Observer $observer)
    {
        // Only if the payment method is one of Payline
        $code = $observer->getOrder()->getPayment()->getMethodInstance()->getCode();
        if (!Mage::helper('payline')->isPayline($code)) {
            return;
        }

        // infinite loop protection
        if (is_null(Mage::registry('payline_create_invoice'))) {
            $order = $observer->getEvent()->getOrder();
            if ($this->_canCreateInvoice($order)) {
                $this->_createInvoice($order);
            }
            // capture or not, that is the question
            $paymentMethod     = $order->getPayment()->getMethod();
            $paymentActionConf = Mage::getStoreConfig('payment/' . $paymentMethod . '/payline_payment_action');
            // if payment action user conf == authorization => need to capture
            if ($paymentActionConf == "100") {
                $fireCaptureOption = Mage::getStoreConfig('payment/' . $paymentMethod . '/capture_payment_when_i_said');
                // if status match w/ user conf && !PaylineNX
                if ($order->getStatus() == $fireCaptureOption && $paymentMethod != 'PaylineNX') {
                    $invoice = $this->_getInvoiceFromOrder($order);
                    if ($invoice) {
                        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
                    }
                    if ($invoice && $invoice->parentCanCapture()) { // invoice present && ok => capture
                        Mage::register('payline_create_invoice', true);
                        $invoice->capture();
                        Mage::unregister('payline_create_invoice');
                    }
                } // end if status matches
            } // end if( $paymentActionConf == 100 )
        }
    }

// end createInvoiceWhenStatusChange()

    /**
     * Return the invoice's order data or false if not exist or NX payment
     */
    protected function _getInvoiceFromOrder($order)
    {
        $invoice = $order->getInvoiceCollection();
        $invoice = sizeof($invoice) == 1 ? $invoice->getFirstItem() : false;
        return $invoice;
    }

    protected function _getMode($order)
    {
        if ($this->_mode === null) {
            $paymentMethod = $order->getPayment()->getMethod();
            $mode          = explode('Payline', $paymentMethod);
            if (isset($mode[1])) {
                $mode        = $mode[1];
                $this->_mode = $mode;
            }
        }
        return $this->_mode;
    }

    protected function _canCreateInvoice($order)
    {
        $result = false;
        if ($order->canInvoice()) {
            $paymentMethod = $order->getPayment()->getMethod();
            if (strstr($paymentMethod, 'Payline') !== false) {
                $mode = $this->_getMode($order);
                if (!empty($mode)) {
                    $statusToCreateInvoice = Mage::getStoreConfig('payment/' . $paymentMethod . '/automate_invoice_creation');
                    if ($order->getStatus() == $statusToCreateInvoice && !empty($statusToCreateInvoice)) {
                        if ($order->getData('status') !== $order->getOrigData('status')) {
                            $result = true;
                        }
                    }
                }
            }
        }
        return $result;
    }

    protected function _createInvoice($order)
    {
        $transId = $order->getPayment()->getCcTransId();
        if (!empty($transId)) {
            $array = array(
                'transactionId'      => $transId,
                'orderRef'           => $order->getRealOrderId(),
                'startDate'          => '',
                'endDate'            => '',
                'transactionHistory' => '',
                'version'            => Monext_Payline_Helper_Data::VERSION,
                'archiveSearch'      => ''
            );
            try {
                $mode = $this->_getMode($order);
                $res  = Mage::helper('payline')->initPayline($mode)->getTransactionDetails($array);
                if (isset($res['payment']['action'])) {
                    $order->setCreateInvoice(true);
                    $action = $res['payment']['action'];
                    if ($mode == 'NX') {
                        $action = Monext_Payline_Model_Cpt::ACTION_AUTH_CAPTURE;
                    }
                    Mage::helper('payline')->createInvoice($action, $order);
                }
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::helper('payline/logger')->log(
                        '[createInvoiceWhenStatusChange] '
                        . '[' . $order->getIncrementId() . '] '
                        . '[' . $transId . '] '
                        . $e->getMessage()
                );
            }
        }
    }

    public function saveQuoteNxFees(Varien_Event_Observer $observer)
    {
        // Only if the payment method is one of Payline
        $code = $observer->getQuote()->getPayment()->getMethod();
        if (!Mage::helper('payline')->isPayline($code)) {
            return;
        }

        $applyCosts = (int) Mage::getStoreConfig('payment/PaylineNX/cost_type');
        if (!$applyCosts) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if (!$quote->getPaylineFee()) {
            $payment = $quote->getPayment();
            if ($payment) {
                $paymentMethod = $payment->getMethod();
                $fee           = Mage::getModel('payline/fees')->getCollection()
                                ->addFieldtoFilter('quote_id', $quote->getId())->getFirstItem();
                $quote->setPaylineFee($fee);
                if ($paymentMethod == 'PaylineNX') {
                    $amount     = (float) Mage::getStoreConfig('payment/PaylineNX/cost_amount');
                    $baseamount = (float) Mage::getStoreConfig('payment/PaylineNX/cost_amount');

                    if ($applyCosts == Monext_Payline_Model_Datasource_Costs::COST_PERCENT) {
                        $amount     = round(($quote->getSubtotal() * $amount) / 100, 2);
                        $baseamount = round(($quote->getBaseSubtotal() * $baseamount) / 100, 2);
                    }

                    //save fees
                    if ($fee->getId()) {
                        $fee->setAmount($amount)->setBaseAmount($baseamount)->save();
                    } else {
                        Mage::getModel('payline/fees')->setQuoteId($quote->getId())
                                ->setAmount($amount)
                                ->setBaseAmount($baseamount)
                                ->save();
                    }
                } elseif ($fee->getId()) {
                    $fee->delete();
                }
            }
        }
    }

    public function saveOrderNxFees(Varien_Event_Observer $observer)
    {
        // Only if the payment method is one of Payline
        $code = $observer->getOrder()->getPayment()->getMethodInstance()->getCode();
        if (!Mage::helper('payline')->isPayline($code)) {
            return;
        }

        $applyCosts = (int) Mage::getStoreConfig('payment/PaylineNX/cost_type');
        if (!$applyCosts) {
            return;
        }

        $order   = $observer->getEvent()->getOrder();
        if (!$order->getPaylineFee()) {
            $quoteId = $order->getQuoteId();
            $payment = $order->getPayment();
            if ($quoteId && $payment) {
                $paymentMethod = $payment->getMethod();
                $fee           = Mage::getModel('payline/fees')->getCollection()
                                ->addFieldtoFilter('quote_id', $quoteId)->getFirstItem();
                $order->setPaylineFee($fee);
                if ($paymentMethod == 'PaylineNX') {
                    $amount     = (float) Mage::getStoreConfig('payment/PaylineNX/cost_amount');
                    $baseamount = (float) Mage::getStoreConfig('payment/PaylineNX/cost_amount');

                    if ($applyCosts == Monext_Payline_Model_Datasource_Costs::COST_PERCENT) {
                        $amount     = round(($order->getSubtotal() * $amount) / 100, 2);
                        $baseamount = round(($order->getBaseSubtotal() * $baseamount) / 100, 2);
                    }

                    //save fees
                    if ($fee->getId()) {
                        $fee->setOrderId($order->getId())->setAmount($amount)->setBaseAmount($baseamount)->save();
                    }
                } elseif ($fee->getId()) {
                    $fee->delete();
                }
            }
        }
    }

    public function saveInvoiceNxFees(Varien_Event_Observer $observer)
    {
        // Only if the payment method is one of Payline
        $code = $observer->getInvoice()->getOrder()->getPayment()->getMethodInstance()->getCode();
        if (!Mage::helper('payline')->isPayline($code)) {
            return;
        }

        $applyCosts = (int) Mage::getStoreConfig('payment/PaylineNX/cost_type');
        if (!$applyCosts) {
            return;
        }

        $invoice = $observer->getEvent()->getInvoice();
        $order   = $invoice->getOrder();
        $payment = $order->getPayment();
        if ($payment) {
            $paymentMethod = $payment->getMethod();
            if ($paymentMethod == 'PaylineNX') {
                $fee = Mage::getModel('payline/fees')->getCollection()
                                ->addFieldtoFilter('order_id', $order->getId())->getFirstItem();

                $amount     = (float) Mage::getStoreConfig('payment/PaylineNX/cost_amount');
                $baseamount = (float) Mage::getStoreConfig('payment/PaylineNX/cost_amount');

                if ($applyCosts == Monext_Payline_Model_Datasource_Costs::COST_PERCENT) {
                    $amount     = round(($order->getSubtotal() * $amount) / 100, 2);
                    $baseamount = round(($order->getBaseSubtotal() * $baseamount) / 100, 2);
                }

                //save fees
                if ($fee->getId() && !$fee->getInvoiceId()) {
                    $fee->setInvoiceId($invoice->getId())->save();
                }
            }
        }
    }

    public function afterSaveShippingAction(Varien_Event_Observer $observer)
    {
        $controller = $observer->getControllerAction();

        $paymentMethodsBlock = $controller->getLayout()->createBlock('checkout/onepage_payment_methods');
        $methods             = $paymentMethodsBlock->getMethods();

        // check if more than one methods available
        if (count($methods) != 1) {
            return;
        }

        $method = current($methods);

        // check if only payline methods (direct method should not be skipped)
        if (!in_array($method->getCode(), array('PaylineCPT', 'PaylineNX', 'PaylineWALLET'))) {
            return;
        }

        $data = array('method' => $method->getCode());

        if ($method->getCode() == 'PaylineCPT') {
            // retrive sub methods (card types)
            $cptBlock = $controller->getLayout()->createBlock('payline/cpt');
            $ccTypes  = $cptBlock->getPaymentMethods();
            if (count($ccTypes) != 1) {
                return;
            }
            $ccType          = current($ccTypes);
            $data['cc_type'] = $ccType['number'];
        }

        // seems that payment step can be skipped, so save the unique payment method now
        $result = Mage::getSingleton('checkout/type_onepage')->savePayment($data);
        if (!empty($result['error'])) {
            return;
        }

        $layout     = Mage::getModel('core/layout');
        $layout->getUpdate()->load('checkout_onepage_review');
        $layout->generateXml()->generateBlocks();
        $layout->getBlock('root')->getChild('button')->setTemplate('checkout/onepage/review/button.phtml');
        $reviewHtml = $layout->getBlock('root')->toHtml();

        $result['goto_section']   = 'review';
        $result['update_section'] = array(
            'name' => 'review',
            'html' => $reviewHtml
        );

        $json     = Mage::helper('core')->jsonEncode($result);
        $response = Mage::helper('core')->jsonDecode($controller->getResponse()->getBody());

        $response['update_section']['html'].= '<script type="text/javascript">
                                                //<![CDATA[
                                                    paylinePaymentSavedTransport = ' . $json . ';
                                                    paylineTrySkipPaymentMethod();
                                                //]]>
                                                </script>';


        $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

}
