<?php
class Monext_Payline_Block_Checkout_Head extends Mage_Core_Block_Template
{
    /**
     * Adding JS scripts and styles to block
     *
     * @throws Mage_Core_Exception
     * @return Mage_Adminhtml_Block_Widget_Form_Container
     */
    protected function _prepareLayout()
    {
        if (!Mage::helper('payline')->disableOnepagePaymentStep()) {
            return $this;
        }

        $blockHead = $this->getLayout()->getBlock('head');

        return parent::_prepareLayout();
    }
}
