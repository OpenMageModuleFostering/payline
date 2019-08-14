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

    // Monsieur Biz Tag NEW_CONST

    // Monsieur Biz Tag NEW_VAR

    /**
     * Init a payment
     * @return array
     */
    public function init(Mage_Sales_Model_Order $order)
    {
        $array = array();

        $_numericCurrencyCode = Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode());

        // PAYMENT
        $array['payment']['amount']   = round($order->getBaseGrandTotal() * 100);
        $array['payment']['currency'] = $_numericCurrencyCode;

        // ORDER
        $array['order']['ref']      = substr($order->getRealOrderId(), 0, 50);
        $array['order']['amount']   = $array['payment']['amount'];
        $array['order']['currency'] = $_numericCurrencyCode;

        $billingAddress = $order->getBillingAddress();

        // BUYER
        $buyerLastName = substr($order->getCustomerLastname(), 0, 50);
        if ($buyerLastName == null || $buyerLastName == '') {
            $buyerLastName = substr($billingAddress->getLastname(), 0, 50);
        }
        $buyerFirstName = substr($order->getCustomerFirstname(), 0, 50);
        if ($buyerFirstName == null || $buyerFirstName == '') {
            $buyerFirstName = substr($billingAddress->getFirstname(), 0, 50);
        }
        $array['buyer']['lastName']  = Mage::helper('payline')->encodeString($buyerLastName);
        $array['buyer']['firstName'] = Mage::helper('payline')->encodeString($buyerFirstName);

        $email         = $order->getCustomerEmail();
        $pattern       = '/\+/i';
        $charPlusExist = preg_match($pattern, $email);

        if (strlen($email) <= 50 && Zend_Validate::is($email, 'EmailAddress') && !$charPlusExist) {
            $array['buyer']['email'] = Mage::helper('payline')->encodeString($email);
        } else {
            $array['buyer']['email'] = '';
        }
        $array['buyer']['customerId'] = Mage::helper('payline')->encodeString($email);

        // ADDRESS : !!!WARNING!!! PaylineSDK v4.33 reverse billingAddress & shippingAdress.
        // Take this : https://www.youtube.com/watch?v=MA6kXUgZ7lE&list=PLpyrjJvJ7GJ7bM5GjzwHvZIqe6c5l3iF6
        $array['shippingAddress']['name']     = Mage::helper('payline')->encodeString(substr($billingAddress->getName(), 0, 100));
        $array['shippingAddress']['street1']  = Mage::helper('payline')->encodeString(substr($billingAddress->getStreet1(), 0, 100));
        $array['shippingAddress']['street2']  = Mage::helper('payline')->encodeString(substr($billingAddress->getStreet2(), 0, 100));
        $array['shippingAddress']['cityName'] = Mage::helper('payline')->encodeString(substr($billingAddress->getCity(), 0, 40));
        $array['shippingAddress']['zipCode']  = substr($billingAddress->getPostcode(), 0, 12);
        //The $billing->getCountry() returns a 2 letter ISO2, should be fine
        $array['shippingAddress']['country']  = $billingAddress->getCountry();
        $forbidenCars                         = array(' ', '.', '(', ')', '-');
        $phone                                = str_replace($forbidenCars, '', $billingAddress->getTelephone());
        $regexpTel                            = '/^\+?[0-9]{1,14}$/';

        if (preg_match($regexpTel, $phone)) {
            $array['shippingAddress']['phone'] = $phone;
        } else {
            $array['shippingAddress']['phone'] = '';
        }

        $array['billingAddress'] = null;

        return $array;
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

    // Monsieur Biz Tag NEW_METHOD

}
