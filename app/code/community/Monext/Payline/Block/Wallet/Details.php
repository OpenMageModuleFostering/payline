<?php

class Monext_Payline_Block_Wallet_Details extends Mage_Core_Block_Template
{

    public $wallet = array();
    public $showShippingDetails;

    public function _construct($flag = null)
    {
        if ($flag === null) {
            $flag = (bool) Mage::getStoreConfig('payment/PaylineWALLET/update_personal_details');
        }
        $this->showShippingDetails = $flag;

        parent::_construct();
        $this->setTemplate('payline/wallet/details.phtml');
    }

}
