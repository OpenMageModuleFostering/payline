<?php
class Monext_Payline_Block_Logo extends Mage_Core_Block_Template{
    protected function isPaylineAvailable(){
        if (
            Mage::getStoreConfig('payment/PaylineCPT/active') ||
            Mage::getStoreConfig('payment/PaylineNX/active') ||
            Mage::getStoreConfig('payment/PaylineDIRECT/active') ||
            Mage::getStoreConfig('payment/PaylineWALLET/active'))
        {
            return true;
        }else{
            return false;
        }
    }
}
