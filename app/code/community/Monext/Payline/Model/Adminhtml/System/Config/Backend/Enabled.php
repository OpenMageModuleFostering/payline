<?php

class Monext_Payline_Model_Adminhtml_System_Config_Backend_Enabled extends Mage_Core_Model_Config_Data
{
    /**
     * Check settings after save
     */
    protected function _afterSave()
    {
        if (Mage::getStoreConfig($this->getPath())!=$this->getFieldsetDataValue(basename($this->getPath()))) {

            $change = Mage::registry('payline_config_disable_payments');
            if (!empty($change)) {
                Mage::unregister('payline_config_disable_payments');
            }

            if(!$this->getFieldsetDataValue(basename($this->getPath()))) {
                Mage::register('payline_config_disable_payments', true);
            }
        }
    }
}
