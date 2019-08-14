<?php
class Monext_Payline_Model_Session extends Mage_Core_Model_Session_Abstract
{
    /**
     * Class constructor. Initialize checkout session namespace
     */
    public function __construct()
    {
        $this->init('payline');
    }

    public function switchToSecureContract()
    {
        $currentCcType = $this->getCcType();

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
            $this->setCcType($contract->getId());
            return true;
        } else {
            return false;
        }
    }
}
