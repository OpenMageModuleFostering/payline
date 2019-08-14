<?php

class Monext_Payline_Block_Info_Direct extends Mage_Payment_Block_Info_Cc
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payline/payment/info/monext.phtml');
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = new Varien_Object($transport);;
        $data = array();
        if ($ccType = $this->getInfo()->getCcType()) {
            $ccType = strtolower($ccType);
            $img = '<img src="'.$this->getSkinUrl('images/'.$ccType.'.gif').'" />';
            $data[Mage::helper('payline')->__('Credit Card Type')] = $img;
        }
        if ($this->getInfo()->getCcLast4()) {
            $data[Mage::helper('payment')->__('Number')] = sprintf('xxxx-%s', $this->getInfo()->getCcLast4());
        }
        $year = $this->getInfo()->getCcExpYear();
        $month = $this->getInfo()->getCcExpMonth();
        if ($year && $month) {
            $data[Mage::helper('payline')->__('Exp date')] =  $this->_formatCardDate($year, $month);
        }
        $this->_paymentSpecificInformation = $transport;
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}