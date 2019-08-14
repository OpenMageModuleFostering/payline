<?php
class Monext_Payline_Block_Wallet_Sidebar extends Mage_Core_Block_Template{

    protected $_customer;

    /**
     * Get logged in customer
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * Define if the sidebar is displayed
     *
     * @return bool
     */
    public function getIsNeedToDisplaySideBar()
    {
        if (Mage::getStoreConfig('payment/PaylineWALLET/active')){
            if (Mage::getStoreConfig('payment/PaylineWALLET/payline_register-oneclick_catalog') != ''){
                $showCmsBlock=true;
            }else{
                $showCmsBlock=false;
            }
            if($this->isCustomerLoggedIn()){
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $customer=$this->getCustomer();
                if ($customer->getWalletId() && $quote !== null && $quote->hasItems()){
                    //if user doesn't have addresses, we don't display block
                    if ($this->getAddressesHtmlSelect()){
                        return true;
                    }
                }else{
                    //Customer doesn't have walletId, the CMS block will be shown if config is OK
                    return $showCmsBlock;
                }
            }else{
                //Customer isn't logged in, the CMS blcok will be shown if config is OK
                return $showCmsBlock;
            }
        }
        return false;
    }

    /**
     * If customer logged & registered to wallet, display form
     * Otherwise, display configured static CMS bloc
     * @return string html code
     */
    public function getContent(){
        $hasWallet = false;
        if($this->isCustomerLoggedIn()){
            $customer=$this->getCustomer();
            if ($walletId=$customer->getWalletId()){
                $hasWallet = true;
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                if ($quote !== null && $quote->hasItems()) {
                    $formBlock=$this->getLayout()->createBlock('core/template');
                    $formBlock->setTemplate('payline/wallet/sidebar/form.phtml');
                    $formBlock->setBillingAddresses($this->getAddressesHtmlSelect('billing'));
                    $formBlock->setShippingAddresses($this->getAddressesHtmlSelect('shipping'));

                    return $formBlock->toHtml();
                }
            }
        }
        $blockId=Mage::getStoreConfig('payment/PaylineWALLET/payline_register-oneclick_catalog');
        /* @var $cmsBlock Mage_Cms_Block_Block */
        $cmsBlock=$this->getLayout()->createBlock('cms/block')->setBlockId($blockId);
        /* @var $notLoggedBlock Mage_Core_Block_Template */
        $notLoggedBlock = $this->getLayout()->createBlock('core/template')
            ->setTemplate('payline/wallet/sidebar/notlogged.phtml')
            ->setHasWallet($hasWallet);
        $notLoggedBlock->append($cmsBlock);
        return $notLoggedBlock->toHtml();
    }

    /**
     * Return a html select with the customer addresses
     * Retrieve wether billing or shipping addresses, depending on the $type param
     * @param string $type
     */
    public function getAddressesHtmlSelect($type=null)
    {
        if ($this->getCustomer()!=null) {
            $options = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value'=>$address->getId(),
                    'label'=>$address->format('oneline')
                );
            }
            if (count($options)==0){
                return '';
            }
            if ($type=='billing') {
                $address = $this->getCustomer()->getPrimaryBillingAddress();
            } else {
                $address = $this->getCustomer()->getPrimaryShippingAddress();
            }

            $select = $this->getLayout()->createBlock('core/html_select')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setClass('address-select')
                ->setOptions($options);

            if ($address) {
                $select->setValue($address->getId());
            }

            return $select->getHtml();
        }
        return '';
    }
}
