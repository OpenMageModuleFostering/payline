<?php

/**
 * This file is part of Monext_Payline for Magento.
 *
 * @license GNU General Public License (GPL) v3
 * @author Jacques Bodin-Hullin <j.bodinhullin@monsieurbiz.com> <@jacquesbh>
 * @category Monext
 * @package Monext_Payline
 * @copyright Copyright (c) 2014 Monsieur Biz (http://monsieurbiz.com)
 */

/**
 * Payment Helper
 * @package Monext_Payline
 */
class Monext_Payline_Helper_Payment extends Mage_Core_Helper_Abstract
{
    /**
     * Init a payment
     *
     * @return array
     */
    public function initWithQuote(Mage_Sales_Model_Quote $quote)
    {
        return $this->_init($quote);
    }

    /**
     * Init a payment
     *
     * @return array
     */
    public function init(Mage_Sales_Model_Order $order)
    {
        return $this->_init($order);
    }

    /**
     * Init a payment
     *
     * @return array
     */
    protected function _init($salesObject)
    {
        $array = array();
        $_numericCurrencyCode = Mage::helper('payline')->getNumericCurrencyCode($salesObject->getBaseCurrencyCode());
        // PAYMENT
        $array['payment']['amount'] = round($salesObject->getBaseGrandTotal() * 100);
        $array['payment']['currency'] = $_numericCurrencyCode;

        // ORDER
        $array['order']['ref'] = substr($salesObject->getRealOrderId(), 0, 50);

        $array['order']['amount'] = $array['payment']['amount'];
        $array['order']['currency'] = $_numericCurrencyCode;
        $billingAddress = $salesObject->getBillingAddress();
        // BUYER
        $customer = Mage::getModel('customer/customer')->load($salesObject->getCustomerId());
        $buyerLastName = substr($customer->getLastname(), 0, 50);
        if ($buyerLastName == null || $buyerLastName == '') {
            $buyerLastName = substr($billingAddress->getLastname(), 0, 50);
        }
        $buyerFirstName = substr($customer->getFirstname(), 0, 50);
        if ($buyerFirstName == null || $buyerFirstName == '') {
            $buyerFirstName = substr($billingAddress->getFirstname(), 0, 50);
        }
        $array['buyer']['lastName'] = Mage::helper('payline')->encodeString($buyerLastName);
        $array['buyer']['firstName'] = Mage::helper('payline')->encodeString($buyerFirstName);
        $email = $customer->getEmail();
        if ($email == null || $email == '') {
            $email = $salesObject->getCustomerEmail();
        }
        $pattern = '/\+/i';
        $charPlusExist = preg_match($pattern, $email);
        if (strlen($email) <= 50 && Zend_Validate::is($email, 'EmailAddress') && ! $charPlusExist) {
            $array['buyer']['email'] = Mage::helper('payline')->encodeString($email);
        } else {
            $array['buyer']['email'] = '';
        }
        $array['buyer']['customerId'] = Mage::helper('payline')->encodeString($email);
        $array['buyer']['accountCreateDate'] = date('d/m/y', $customer->getCreatedAtTimestamp());
        $ordersHistory = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $salesObject->getCustomerId());
        $cumulAmount = 0;
        $maxEntity = 0;
        foreach ($ordersHistory as $oldOrder) {
            $oldOrderData = $oldOrder->getData();
            if ($oldOrderData['entity_id'] > $maxEntity && $oldOrderData['state'] == Mage_Sales_Model_Order::STATE_COMPLETE) {
                $maxEntity = $oldOrderData['entity_id'];
                $array['plnLastCompleteOrderAge'] = round((time() - strtotime($oldOrderData['created_at'])) / (60 * 60 * 24));
            }
            $cumulAmount += $oldOrder->getBaseGrandTotal();
        }
        $ordersHistoryCount = $ordersHistory->count();
        $array['buyer']['accountOrderCount'] = $ordersHistory->count(); // orders
                                                                        // count
        if($ordersHistoryCount>0) {
            $array['buyer']['accountAverageAmount'] = round(($cumulAmount / $ordersHistoryCount) * 100); // average
                                                                                                     // order
                                                                                                     // amount,
                                                                                                     // in
                                                                                                     // cents
        } else {
            $array['buyer']['accountAverageAmount'] = 0;
        }
        $forbidenPhoneCars = array(
                                ' ',
                                '.',
                                '(',
                                ')',
                                '-',
                                '/',
                                '\\',
                                '#');
        $regexpPhone = '/^\+?[0-9]{1,14}$/';
        $shippingAddress = $salesObject->getShippingAddress();
        if ($shippingAddress != null) {
            $array['shippingAddress']['name'] = Mage::helper('payline')->encodeString(substr($shippingAddress->getName(), 0, 100));
            $array['shippingAddress']['title'] = Mage::helper('payline')->encodeString($shippingAddress->getPrefix());
            $array['shippingAddress']['firstName'] = Mage::helper('payline')->encodeString(substr($shippingAddress->getFirstname(), 0, 100));
            $array['shippingAddress']['lastName'] = Mage::helper('payline')->encodeString(substr($shippingAddress->getLastname(), 0, 100));
            $array['shippingAddress']['street1'] = Mage::helper('payline')->encodeString(substr($shippingAddress->getStreet1(), 0, 100));
            $array['shippingAddress']['street2'] = Mage::helper('payline')->encodeString(substr($shippingAddress->getStreet2(), 0, 100));
            $array['shippingAddress']['cityName'] = Mage::helper('payline')->encodeString(substr($shippingAddress->getCity(), 0, 40));
            $array['shippingAddress']['zipCode'] = substr($shippingAddress->getPostcode(), 0, 12);
            $array['shippingAddress']['country'] = $shippingAddress->getCountry();
            $array['shippingAddress']['state'] = Mage::helper('payline')->encodeString($shippingAddress->getRegion());
            $shippingPhone = str_replace($forbidenPhoneCars, '', $shippingAddress->getTelephone());
            if (preg_match($regexpPhone, $shippingPhone)) {
                $array['shippingAddress']['phone'] = $shippingPhone;
            }
        }
        $array['billingAddress']['name'] = Mage::helper('payline')->encodeString(substr($billingAddress->getName(), 0, 100));
        $array['billingAddress']['title'] = Mage::helper('payline')->encodeString($billingAddress->getPrefix());
        $array['billingAddress']['firstName'] = Mage::helper('payline')->encodeString(substr($billingAddress->getFirstname(), 0, 100));
        $array['billingAddress']['lastName'] = Mage::helper('payline')->encodeString(substr($billingAddress->getLastname(), 0, 100));
        $array['billingAddress']['street1'] = Mage::helper('payline')->encodeString(substr($billingAddress->getStreet1(), 0, 100));
        $array['billingAddress']['street2'] = Mage::helper('payline')->encodeString(substr($billingAddress->getStreet2(), 0, 100));
        $array['billingAddress']['cityName'] = Mage::helper('payline')->encodeString(substr($billingAddress->getCity(), 0, 40));
        $array['billingAddress']['zipCode'] = substr($billingAddress->getPostcode(), 0, 12);
        $array['billingAddress']['country'] = $billingAddress->getCountry();
        $array['billingAddress']['state'] = Mage::helper('payline')->encodeString($billingAddress->getRegion());
        $billingPhone = str_replace($forbidenPhoneCars, '', $billingAddress->getTelephone());
        if (preg_match($regexpPhone, $billingPhone)) {
            $array['billingAddress']['phone'] = $billingPhone;
        }
        return $array;
    }

    /**
     * Get User payment data collected by Monext_Payline_Model_Direct::assignData
     *
     * @return Varien_Object
     */
    public function getPaymentUserData()
    {
        //If we do not have CardTokenPan
        if(Mage::registry('current_payment_data')) {
            $paymentData = Mage::registry('current_payment_data');
        } else {
            $paymentData = Mage::getSingleton('payline/session');
        }

        return $paymentData;
    }


    /**
     * Check for a securized contract
     */
    public function switchToSecureContract()
    {
        $paymentData = $this->getPaymentUserData();

        $currentCcType = $paymentData->getCcType();

        $currentContractType = false;
        $allAvailableContracts = Mage::helper('payline')->getCcContracts();
        foreach ($allAvailableContracts as $contract) {

            if($contract->getId()==$currentCcType) {
                $currentContractType = $contract->getContractType();
                break;
            }
        }

        $contract = Mage::helper('payline')->getContractByType($currentContractType,true);
        if($contract) {
            $paymentData->setCcType($contract->getId());
            if(Mage::registry('current_payment_data')) {
                Mage::unregister('current_payment_data');
                Mage::register('current_payment_data', $paymentData);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * check if current contract is securized
     */
    public function useSecureContract()
    {
        $paymentData = $this->getPaymentUserData();
        $currentId = $paymentData->getCcType();
        $contracts = Mage::helper('payline')->getCcContracts(true);
        foreach ($contracts as $contract) {
            if($contract->getId()==$currentId) {
                return true;
            }
        }

        return false;
    }


    /**
     * Initialise the requests param array to share common information between doAuthorization and verifyEnrollment
     *
     * @return array
     */
    public function getDirectActionHeader(Mage_Sales_Model_Order_Payment $payment = null)
    {
        if($payment) {
            $order = $payment->getOrder();
        } else {
            $_session = Mage::getSingleton('checkout/session');
            $order = Mage::getModel('sales/order')->loadByIncrementId($_session->getLastRealOrderId());
            $payment = $order->getPayment();
        }

        $array = Mage::helper('payline/payment')->init($order);

        // Get user data
        $paymentData = $this->getPaymentUserData();

        // Init the SDK with the currency and for DIRECT method
        $paylineSDK = Mage::helper('payline')->initPayline('DIRECT', $array['payment']['currency']);

        // PAYMENT
        $array['payment']['action'] = Mage::getStoreConfig('payment/PaylineDIRECT/payline_payment_action');
        $array['payment']['mode'] = 'CPT';

        // Get the contract
        $contract = Mage::getModel('payline/contract')->load($paymentData->getCcType());


        $array['payment']['contractNumber'] = $contract->getNumber();

        // Set the order date
        $array['order']['date'] = date("d/m/Y H:i");

        // Set private data (usefull in the payline admin)
        $privateData1 = array();
        $privateData1['key'] = 'orderRef';
        $privateData1['value'] = substr(str_replace(array("\r", "\n", "\t"), array('', '', ''), $array['order']['ref']), 0, 255);
        $paylineSDK->setPrivate($privateData1);

        // Set the order details (each item, optional)
        $items = $order->getAllItems();
        if ($items) {
            if (count($items) > 100)
                $items = array_slice($items, 0, 100);
            foreach ($items as $item) {
                $itemPrice = round($item->getPrice() * 100);
                if ($itemPrice > 0) {
                    $product = array();
                    $product['ref'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r", "\n", "\t"), array('', '', ''), $item->getName()), 0, 50));
                    $product['price'] = round($item->getPrice() * 100);
                    $product['quantity'] = round($item->getQtyOrdered());
                    $product['comment'] = Mage::helper('payline')->encodeString(substr(str_replace(array("\r", "\n", "\t"), array('', '', ''), $item->getDescription()), 0, 255));
                    $paylineSDK->setItem($product);
                }
            }
        }

        // Set the card info
        $array['owner']['lastName'] = Mage::helper('payline')->encodeString($paymentData->getCcOwner());

        // CARD INFO
        if ($paymentData->getCardTokenPan()) {
            $paymenTokentData = Mage::helper('payline')->getDecryptedCardTokenPan($paymentData->getCardTokenPan(), $order);

            if ($paymenTokentData['orderRef'] != $order->getIncrementId()) {
                Mage::logException('Payline error: Order incrementId in crypted token "%s" do not match with current order "%s"', $paymenTokentData['orderRef'], $order->getIncrementId());
            }
            $array['card']['token'] = $paymenTokentData['cardTokenPan'];
            $array['card']['cvx'] = $paymenTokentData['vCVV'];
            $array['card']['expirationDate'] = $paymenTokentData['cardExp'];
            $array['card']['type'] = $paymenTokentData['cardType'];
        } else {
            // Should not be used any more
            $array['card']['number'] = $paymentData->getCcNumber();
            $array['card']['cvx'] = $paymentData->getCcId();
            $array['card']['expirationDate'] = $paymentData->getCcExpMonth() . $paymentData->getCcExpYear();
            $array['card']['type'] = $contract->getContractType();
        }
        $array['card']['cardholder'] = $paymentData->getCcOwner();

        // Customer IP
        $array['buyer']['ip'] = Mage::helper('core/http')->getRemoteAddr();

        // 3D secure
        $array['3DSecure'] = array();

        // BANK ACCOUNT DATA
        $array['BankAccountData'] = array();

        // version
        $array['version'] = Monext_Payline_Helper_Data::VERSION;

        return $array;
    }

    /**
     * Finalize the final redirection from directAction or return from validateAcs
     *
     * @param array $author_result
     * @param paylineSDK $paylineSDK
     * @param array $array
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function finalizeDirectAction($author_result, $paylineSDK, $array, Mage_Sales_Model_Order_Payment $payment = null)
    {
        if($payment) {
            $order = $payment->getOrder();
        } else {
            $_session = Mage::getSingleton('checkout/session');
            $order = Mage::getModel('sales/order')->loadByIncrementId($_session->getLastRealOrderId());
            $payment = $order->getPayment();
        }

        $paymentData = $this->getPaymentUserData();

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
                    Mage::helper('payline')->setOrderStatusAccordingToPaymentMode($order, $array['payment']['action']);
                }

                if (Mage::getStoreConfig('payment/PaylineWALLET/active') and ($paymentData->getSubscribeWallet() or Mage::getStoreConfig('payment/payline_common/automate_wallet_subscription'))) {
                    // Create the wallet!
                    $array['wallet']['lastName'] = $array['buyer']['lastName'];
                    $array['wallet']['firstName'] = $array['buyer']['firstName'];
                    $array['wallet']['email'] = $array['buyer']['email'];
                    if (! empty($array['card']['token'])) {
                        $array['wallet']['token'] = $array['card']['token'];
                        // TODO: Supprimer le ccid
                        $array['card']['cvx'] = $paymentData->getCcCid();
                    }
                    // remember, the Beast is not so far
                    $array['address'] = $array['shippingAddress'];
                    $array['ownerAddress'] = null;
                    Mage::helper('payline')->createWalletForCurrentCustomer($paylineSDK, $array);
                }
                Mage::helper('payline')->automateCreateInvoiceAtShopReturn('DIRECT', $order);

                Mage::getSingleton('payline/session')->clear();
                return true;

            } else {
                $msgLog = 'Error during order update (#' . $order->getIncrementId() . ')' . "\n";
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $failedOrderStatus, $msgLog, false);
                //$order->save();

                $payment->setSkipOrderProcessing(true);
                $msg = Mage::helper('payline')->__('An error occured during the payment. Please retry or use an other payment method.');
                Mage::getSingleton('core/session')->addError($msg);
                Mage::throwException($msg);
                return false;
            }
        } else {
            if (isset($author_result) && is_array($author_result)) {
                $msgLog = 'PAYLINE ERROR : ' . $author_result['result']['code'] . ' ' . $author_result['result']['shortMessage'] . ' (' . $author_result['result']['longMessage'] . ')';
            } elseif (isset($author_result) && is_string($author_result)) {
                $msgLog = 'PAYLINE ERROR : ' . $author_result;
            } else {
                $msgLog = 'Unknown PAYLINE ERROR';
            }
            Mage::helper('payline/payment')->updateStock($order);
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $failedOrderStatus, $msgLog, false);

            // Error
            $payment->setSkipOrderProcessing(true);
            $msg = Mage::helper('payline')->__('An error occured during the payment. Please retry or use an other payment method.');
            Mage::throwException($msg);
            Mage::getSingleton('core/session')->addError($msg);
            return false;
        }
    }

    /**
     * Add payment transaction to the order, reinit stocks if needed
     * @param $res array result of a request
     * @param $transactionId
     * @return boolean (true=>valid payment, false => invalid payment)
     */
    public function updateOrder($order, $res, $transactionId, $paymentType = 'CPT')
    {
        // First, log message which says that we are updating the order
        Mage::helper('payline/logger')->log("[updateOrder] Mise à jour commande " . $order->getIncrementId() . " (mode $paymentType) avec la transaction $transactionId");

        // By default this process isn't OK
        $orderOk = false;

        // If we have a result code
        if ($resultCode = $res['result']['code']) {

            // List of accepted codes
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

            // Transaction OK
            if (in_array($resultCode, $acceptedCodes)) {

                // This process is not OK
                $orderOk = true;

                // N time payment?
                if ($paymentType == 'NX') {
                    Mage::helper('payline/logger')->log("[updateOrder] Cas du paiement NX");
                    if (isset($res['billingRecordList']['billingRecord'][0])) {
                        $code_echeance = $res['billingRecordList']['billingRecord'][0]->result->code;
                        if ($code_echeance == '00000' || $code_echeance == '02501') {
                            Mage::helper('payline/logger')->log("[updateOrder] première échéance paiement NX OK");
                            $orderOk = true;
                        } else {
                            Mage::helper('payline/logger')->log("[updateOrder] première échéance paiement NX refusée, code " . $code_echeance);
                            $orderOk = false;
                        }
                    } else {
                        Mage::helper('payline/logger')->log("[updateOrder] La première échéance de paiement est à venir");
                    }
                }

                // Set the transaction in the payment object
                $order->getPayment()->setCcTransId($transactionId);
                if (isset($res['payment']) && isset($res['payment']['action'])) {
                    $paymentAction = $res['payment']['action'];
                } else {
                    $paymentAction = Mage::getStoreConfig('payment/Payline' . $paymentType . '/payline_payment_action');
                }

                // Add transaction (with payment action)
                $this->addTransaction($order, $transactionId, $paymentAction);
                
                // check that payment data match order data
                $orderTotal = round($order->getBaseGrandTotal() * 100);
                $orderRef = $order->getRealOrderId();
                $orderCurrency = Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode());
                if($orderTotal != $res['payment']['amount']){
                    Mage::helper('payline/logger')->log("ERROR for order $orderRef - paid amount (".$res['payment']['amount'].") does not match order amount ($orderTotal)");
                    $orderOk = false;
                }
                if($orderCurrency != $res['payment']['currency']){
                    Mage::helper('payline/logger')->log("ERROR for order $orderRef - payment currency (".$res['payment']['currency'].") does not match order amount ($orderCurrency)");
                    $orderOk = false;
                }
                
                // Save the order
                $order->save();
            }

            // Transaction NOT OK
            else {

                // Update the stock
                $this->updateStock($order);
            }
        }

        return $orderOk;
    }

    /**
     * Reinit stocks
     */
    public function updateStock($order)
    {
        if (Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_CAN_SUBTRACT) == 1) { // le stock a été décrémenté à la commande
            // ré-incrémentation du stock
            $items = $order->getAllItems();
            if ($items) {
                foreach ($items as $item) {
                    $quantity   = $item->getQtyOrdered(); // get Qty ordered
                    $product_id = $item->getProductId(); // get its ID
                    $stock      = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id); // Load the stock for this product
                    $stock->setQty($stock->getQty() + $quantity); // Set to new Qty
                    //if qtty = 0 after order and order fails, set stock status is_in_stock to true
                    if ($stock->getQty() > $stock->getMinQty() && !$stock->getIsInStock()) {
                        $stock->setIsInStock(1);
                    }
                    $stock->save(); // Save
                }
                Mage::helper('payline/logger')->log('[updateStock] done for order '.$order->getIncrementId());
            }
        }
    }

    /**
     * Add a transaction to the current order, depending on the payment type (Auth or Auth+Capture)
     * @param string $transactionId
     * @param string $paymentAction
     * @return null
     */
    public function addTransaction($order, $transactionId, $paymentAction)
    {
        if (version_compare(Mage::getVersion(), '1.4', 'ge')) {
            /* @var $payment Mage_Payment_Model_Method_Abstract */
            $payment = $order->getPayment();
            if (!$payment->getTransaction($transactionId)) { // if transaction isn't saved yet
                $transaction = Mage::getModel('sales/order_payment_transaction');
                $transaction->setTxnId($transactionId);
                $transaction->setOrderPaymentObject($order->getPayment());
                if ($paymentAction == '100') {

                } else if ($paymentAction == '101') {
                    $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
                }
                $transaction->save();
                $order->sendNewOrderEmail();
            }
        } else {
            $order->getPayment()->setLastTransId($transactionId);
            $order->sendNewOrderEmail();
        }
    }

    /**
     * Retrieve the contract object for specified data.
     * We store the contract in the data and we load it only if it doesn't exist.
     * @return Monext_Payline_Model_Contract The contract
     */
    public function getContractByData(Varien_Object $data)
    {
        if (!$contract = $data->getContract()) {
            $contract = Mage::getModel('payline/contract')->load($data->getCcType());
            $data->setContract($contract);
        }
        return $contract;
    }
}
