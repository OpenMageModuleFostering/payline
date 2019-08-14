<?php
class Monext_Payline_Block_Wallet extends Mage_Payment_Block_Form {
    protected $_walletData;
    
    protected function _construct() {
        parent::_construct();
        
        $this->setTemplate('payline/wallet/form.phtml');
    }
    
    public function getWalletData(){
        if (!empty($this->_walletData)){
            return $this->_walletData;
        }
        $wallet=$this->getMethod()->getWalletData();
        $data=array(
            $this->__('Card type')=>$wallet['card']['type'],
            $this->__('Number')=>$wallet['card']['number'],
            $this->__('Exp. date')=>$wallet['card']['expirationDate']
        );
        $this->_walletData=$data;
        return $data;
    }
}