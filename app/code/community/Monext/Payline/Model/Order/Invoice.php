<?php
class Monext_Payline_Model_Order_Invoice extends Mage_Sales_Model_Order_Invoice
{
    /**
     * Override parent to add the admin BO conf
     * @return bool true if can capture, otherwise false
     */
    public function canCapture()
    {
        $canCapture         = parent::canCapture();
        $paymentMethod      = $this->getOrder()->getPayment()->getMethod();
        $paymentActionConf  = Mage::getStoreConfig('payment/'.$paymentMethod.'/payline_payment_action');
        $result             = true;
        // if only auth (100), check if it's time to capture
        if( $paymentActionConf == "100" ) {
            $fireCaptureOption  = Mage::getStoreConfig('payment/'.$paymentMethod.'/capture_payment_when_i_said');
            $result             = ( strcasecmp('invoice', $fireCaptureOption) == 0 );
        }
        return $canCapture && $result ;
    }

    public function parentCanCapture()
    {
        return parent::canCapture();
    }
}