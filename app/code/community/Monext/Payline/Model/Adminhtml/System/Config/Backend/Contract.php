<?php

class Monext_Payline_Model_Adminhtml_System_Config_Backend_Contract extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    protected $_eventPrefix = 'payline_config_backend_contract';


    protected function _afterLoad()
    {
    	if (!is_array($this->getValue())) {
    		$value = $this->getValue();

    		$collection = Mage::getModel('payline/contract')->getCollection();
    		$store   = Mage::app()->getRequest()->getParam('store', '');
    		$website = Mage::app()->getRequest()->getParam('website', '');
    		if($store) {
    		    $storeId = Mage::getModel('core/store')->load($store)->getId();
    		    $collection->addStoreFilter($storeId);
    		} elseif ($website) {
    		    $websiteId = Mage::getModel('core/website')->load($website)->getId();
    		    $collection->addWebsiteFilter($websiteId);
    		}

    		$contracts = $collection->toArray();

    		$values = $contracts['items'];

    		$this->setValue($values);
    	}
    }

    protected function _beforeSave()
    {
        $contract_lists = Mage::app()->getRequest()->getParam('contract_list');

        $defaultValues=array('is_primary'=>0,'is_secondary'=>0,'is_secure'=>0,'is_included_wallet_list'=>0);
        foreach ($contract_lists as $contactId=>$contractValues) {
            $contract_lists[$contactId] = array_merge($defaultValues, $contractValues);
        }

        $this->setUnserializedValue($contract_lists);
        $this->setValue($contract_lists);

        parent::_beforeSave();
    }

    protected function _afterSave()
    {
        if (Mage::getStoreConfig($this->getPath())!=$this->getValue()) {
            $contract_lists = $this->getUnserializedValue();

            $store   = Mage::app()->getRequest()->getParam('store', '');
            $website = Mage::app()->getRequest()->getParam('website', '');

            Mage::getResourceModel('payline/contract')->updateOptionsContractList(
                    $contract_lists, $website, $store
            );
        }

        parent::_afterSave();
    }
}
