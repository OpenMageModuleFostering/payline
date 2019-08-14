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

    /**
     * Clean payline
     * @see customer_logout
     */
    public function cleanPayline(Varien_Event_Observer $observer)
    {
        // Clean the wallet
        Mage::getSingleton('payline/wallet')->clean();
    }


    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkForConfigChanged(Varien_Event_Observer $observer)
    {
        $disablePayments = Mage::registry('payline_config_disable_payments');
        if ($disablePayments) {
            $config=Mage::getModel('core/config');
            $store=null;
            $paymentConfig = Mage::getStoreConfig('payment', $store);
            foreach ($paymentConfig as $code => $methodConfig) {
                if (Mage::getStoreConfigFlag('payment/'.$code.'/active', $store) and stripos($code,'payline')!==false ) {
                    $config->saveConfig('payment/'.$code.'/active', 0);
                }
            }
        }


        $change = Mage::registry('payline_config_change');
        if ($change) {
            $url = Mage::helper("adminhtml")->getUrl('adminhtml/payline_managecontracts/importFromConfig');

            Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            Mage::app()->getResponse()->sendResponse();
            exit;
        }
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function configNestedPayment(Varien_Event_Observer $observer)
    {
        $paymentGroups   = $observer->getEvent()->getConfig()->getNode('sections/payline/groups');

        $payments = $paymentGroups->xpath('payline_payments_availables/*');
        foreach ($payments as $payment) {
            if ((int)$payment->include) {

                $fields = $paymentGroups->xpath((string)$payment->group . '/fields');
                if (isset($fields[0])) {
                    $fields[0]->appendChild($payment, true);
                }
            }
        }
    }


    /**
     *
     * @param   Varien_Event_Observer $observer
     * @return  Monext_Payline_Model_Observer
     */
    public function updateHandleToUnsetPaymentStep(Varien_Event_Observer $observer)
    {
        $action = $observer->getEvent()->getAction();
        if ($action->getFullActionName() == "checkout_onepage_index" && Mage::helper('payline')->disableOnepagePaymentStep()) {
            $update = $observer->getEvent()->getLayout()->getUpdate();
            $update->addHandle('payline_remove_onepage_payment_step_handler');
        }
        return $this;
    }


    /**
     *
     * @param   Varien_Event_Observer $observer
     * @return  Monext_Payline_Model_Observer
     */
    public function updateSectionTitle(Varien_Event_Observer $observer)
    {
        $action = $observer->getEvent()->getAction();
        if ($action->getFullActionName() == "checkout_onepage_index" && Mage::helper('payline')->disableOnepagePaymentStep()) {
            Mage::getSingleton('checkout/session')->setStepData('payment', array(
                    'label'     => Mage::helper('checkout')->__('Payment Information'),
                    'is_show'   => true
            ));
            Mage::getSingleton('checkout/session')->setStepData('review', array(
                    'label'     => Mage::helper('checkout')->__('Payment Information'),
                    'is_show'   => true
            ));;
        }
        return $this;
    }


    /**
     *
     * @param Varien_Event_Observer $observer
     * @return Monext_Payline_Model_Observer
     */
    public function postdispatchOnepageSaveShippingMethod(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('payline')->disableOnepagePaymentStep()) {
            return $this;
        }

        /* @var $controller Mage_Checkout_OnepageController */
        $controller = $observer->getEvent()->getControllerAction();
        $response = Mage::app()->getFrontController()->getResponse()->getBody(true);

        if (!isset($response['default'])) {
            return;
        }

        $response = Mage::helper('core')->jsonDecode($response['default']);

        if ($response['goto_section'] == 'payment') {

            $contractNumber = Mage::helper('payline')->getDefaultContractNumberForWidget();
            if(empty($contractNumber)) {
                throw new Exception('Cannot find valid contract number');
            }
            $onePage = Mage::getSingleton('checkout/type_onepage');
            $onePage->getQuote()->getPayment()->importData(array('method'=>'PaylineCPT', 'cc_type'=>$contractNumber));


            $layout = $controller->getLayout();
            $update = $layout->getUpdate();
            // Needed with cache activated
            $update->setCacheId(uniqid("payline_onepage_review_payline"));

            $controller->loadLayout(array('checkout_onepage_review','payline_onepage_review_handler'), true, true);
            $response['goto_section'] = 'review';
            $response['update_section'] = array(
                    'name' => 'review',
                    'html' => $controller->getLayout()->getBlock('root')->toHtml()
            );

            $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        }

        return $this;
    }


    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function alterBlockHtmlAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('payline')->disableOnepagePaymentStep()) {
            return $this;
        }

        $block = $observer->getEvent()->getBlock();
        $transport = $observer->getEvent()->getTransport();

        if($block instanceof Mage_Checkout_Block_Onepage_Shipping_Method) {
            $block->getLayout()
                ->createBlock('payline/checkout_widget_opcheckout', 'payline_checkout_widget_opcheckout_init')
                ->setTemplate('payline/checkout/onepage/widget-opcheckout-js-init.phtml')
                ->addHtmlAsChild($block, $transport);
        }

    }


    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function predispatchCheckoutOnepage(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('payline')->disableOnepagePaymentStep()) {
            return $this;
        }

        $needRedirect = false;

        /* @var $controller Mage_Checkout_OnepageController */
        $controller = $observer->getEvent()->getControllerAction();
        $paylinetoken = $controller->getRequest()->getParam('paylinetoken');

        $referer = Mage::helper('core/http')->getHttpReferer();
        if ($paylinetoken) {
            $token = Mage::getModel('payline/token')->load($paylinetoken, 'token');
            if($token->getId()) {
                $needRedirect = true;
            }
        }

        if ($needRedirect) {
            $params = $controller->getRequest()->getParams();
            $params['_secure'] = true;

            $controller->getResponse()->setRedirect(
                    Mage::getUrl('payline/index/cptReturnWidget', $params)
            );
            $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
        }
    }
}
