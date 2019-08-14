<?php
class Monext_Payline_Model_Order_Payment extends Mage_Sales_Model_Order_Payment
{
    /* @Override
     * Exec parent, then set order status according to user conf
     */
    public function capture($invoice)
    {
        parent::capture( $invoice );

        $msgError = '[Monext_Payline_Model_Order_Payment#capture] ERROR Unable to set order status';

        if( $invoice->getTransactionId() ) {
            $order          = $this->getOrder();
            $method         = str_replace( 'Payline', '', $order->getPayment()->getMethod() );
            $paylineSDK     = Mage::helper('payline')->initPayline( $method );
            // get the last transaction id in order to get the details from payline in order to get the action, then i die
            $transId       = Mage::getModel('sales/order_payment_transaction')
                ->getCollection()
                ->addFieldToFilter('order_id', $order->getId() )
                ->setOrder('txn_id', 'DESC')
                ->getFirstItem()
                ->getTxnId();

            $array_details                      = array();
            $array_details['orderRef']          = $order->getRealOrderId();
            $array_details['transactionId']     = $transId;
            $array_details['startDate']         = '';
            $array_details['endDate']           = '';
            $array_details['transactionHistory']= '';
            $array_details['version']           = Monext_Payline_Helper_Data::VERSION;
            $array_details['archiveSearch']     = '';
            $transDetails                       = $paylineSDK->getTransactionDetails( $array_details );

            if( isset( $transDetails ) ) {
                $isReAuth = $transDetails['payment']['action'] == Monext_Payline_Model_Cpt::ACTION_RE_AUTH ? true:false;
                Mage::helper('payline')->setOrderStatusAccordingToPaymentMode(
                    $order, $transDetails['payment']['action'], $isReAuth );
                $order->save();

            } else {
                Mage::log( $msgError );
            }
        } else {
            Mage::log( $msgError );
        }

        return $this;
    }
}