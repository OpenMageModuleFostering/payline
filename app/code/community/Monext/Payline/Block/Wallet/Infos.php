<?php
/** Display wallet payment info (usually in the checkout progress bar 
 */ 
class Monext_Payline_Block_Wallet_Infos extends Mage_Payment_Block_Info{
 	/**
     * Prepare credit card related payment info
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $wallet=$this->getInfo()->getMethodInstance()->getWalletData();
        $data=array();
        if (!empty($wallet)){
            $cardNumber = substr($wallet['card']['number'], -4);
            $expMonth = substr($wallet['card']['expirationDate'], 0, 2);
            $expYear = substr($wallet['card']['expirationDate'], -2);
            $cardType = $wallet['card']['type'];
            $cardType = strtolower($cardType);
            $img = '<img src="'.$this->getSkinUrl('images/'.$cardType.'.gif').'" />';
            $data=array(
                $this->__('Card type') => $img,
                $this->__('Number') => sprintf('xxxx-%s', $cardNumber),
                $this->__('Exp date') => $expMonth.'/'.$expYear
            );
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}