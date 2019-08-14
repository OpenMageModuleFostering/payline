<?php

class Monext_Payline_Model_Adminhtml_System_Config_Backend_Common extends Mage_Core_Model_Config_Data
{
    /**
     * Check settings after save
     */
    protected function _afterSave()
    {
        if (Mage::getStoreConfig($this->getPath())!=$this->getFieldsetDataValue(basename($this->getPath()))) {

            $change = Mage::registry('payline_config_change');
            if (empty($change)) {
                $change = array();
            } else {
                Mage::unregister('payline_config_change');
            }

            $change[$this->getPath()] = array('before'=>Mage::getStoreConfig($this->getPath()),
                                               'after'=>$this->getFieldsetDataValue(basename($this->getPath())),
                                               'scope_id'=>$this->getScopeId(),
                                               'scope'=>$this->getScope()
                    );

            Mage::register('payline_config_change', $change);
        }
    }
}
