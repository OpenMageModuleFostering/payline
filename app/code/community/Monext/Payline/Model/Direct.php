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

    public function assignData($data)
    {
        $_SESSION['payline_ccdata'] = $data;
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear());
        return $this;
    }
    
    /**
     * Prepare info instance for save
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        $info->setCcNumber(null)
            ->setCcCid(null);
        return $this;
    }

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('payline/index/direct');
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
