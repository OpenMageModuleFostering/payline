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
        $transport = new Varien_Object($transport);
        $data = array();
        if ($this->getInfo()->getCcType()) {
            $contract = $this->_getContract($this->getInfo());
            $ccType = strtolower($contract->getContractType());

            // Force to the frontend area
            $currentArea = Mage::getDesign()->getArea();
            Mage::getDesign()->setArea(Mage_Core_Model_Design_Package::DEFAULT_AREA);

            // The images are only in the rontend skin directory
            $img = '<img src="'.$this->getSkinUrl('images/monext/'.$ccType.'.gif').'" />';

            // Un-Force the area
            Mage::getDesign()->setArea($currentArea);

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

    /**
     * Get the contract
     * @return Monext_Payline_Model_Contract
     */
    protected function _getContract(Varien_Object $info)
    {
        return Mage::helper('payline/payment')->getContractByData($info);
    }
}