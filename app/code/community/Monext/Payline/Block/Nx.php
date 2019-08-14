<?php
class Monext_Payline_Block_Nx extends Mage_Payment_Block_Form {
    protected function _construct() {
        parent::_construct();
        
        $this->setTemplate('payline/Payline.phtml');
        $redirectMsg=Mage::getStoreConfig('payment/PaylineNX/redirect_message');
        $this->setRedirectMessage($redirectMsg);
        $this->setBannerSrc($this->getSkinUrl('images/monext/payline-logo.png'));
    }
}
