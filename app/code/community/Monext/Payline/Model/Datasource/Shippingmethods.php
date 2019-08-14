<?php
/**
 * Class used as a datasource to display available shipping methods
 * Only the 3 basic (no user configuration during checkout process) Magento methods are available
 */
class Monext_Payline_Model_Datasource_Shippingmethods
{
    public function toOptionArray(){
        $availableMethods=array();
        $availableMethods[]=array('value'=>'', 'label'=>Mage::helper('payline')->__('- none -'));
        if (Mage::getStoreConfig('carriers/flatrate/active')){
            $availableMethods[]=array('value'=>'flatrate_flatrate', 'label'=>Mage::helper('payline')->__('Flat Rate'));
        }
        if (Mage::getStoreConfig('carriers/tablerate/active')){
            $availableMethods[]=array('value'=>'tablerate_bestway', 'label'=>Mage::helper('payline')->__('Table Rate'));
        }
        if (Mage::getStoreConfig('carriers/freeshipping/active')){
            $availableMethods[]=array('value'=>'freeshipping_freeshipping', 'label'=>Mage::helper('payline')->__('Free Shipping'));
        }
        return $availableMethods;
    }
}