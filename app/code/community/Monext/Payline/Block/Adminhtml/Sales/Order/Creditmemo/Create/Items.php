<?php

/**
 * This class corrects an unexpected behavour on Magento EE 1.9
 * (looks like invoice MUST be captured online to do a refund)
 */
class Monext_Payline_Block_Adminhtml_Sales_Order_Creditmemo_Create_Items extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Create_Items{
    /**
     * Prepare child blocks
     *
     * @return Monext_Payline_Block_Adminhtml_Sales_Order_Creditmemo_Create_Items
     */
    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('creditmemo_item_container'),'".$this->getUpdateUrl()."')";
        $this->setChild(
            'update_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                'label'     => Mage::helper('sales')->__('Update Qty\'s'),
                'class'     => 'update-button',
                'onclick'   => $onclick,
            ))
        );
        $isNotMagento1_3=version_compare(Mage::getVersion(), '1.4', 'ge');
        if ($this->getCreditmemo()->canRefund()) {
            //No transaction for an invoice
            if ($this->getCreditmemo()->getInvoice()){// && $this->getCreditmemo()->getInvoice()->getTransactionId()) {
                $this->setChild(
                    'submit_button',
                    $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                        'label'     => Mage::helper('sales')->__('Refund'),
                        'class'     => 'save submit-button',
                        'onclick'   => $isNotMagento1_3?'disableElements(\'submit-button\');submitCreditMemo()':'editForm.submit();',
                    ))
                );
            }
            $this->setChild(
                'submit_offline',
                $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                    'label'     => Mage::helper('sales')->__('Refund offline'),
                    'class'     => 'save submit-button',
                    'onclick'   => $isNotMagento1_3?'disableElements(\'submit-button\');submitCreditMemoOffline()':'editForm.submit();',
                ))
            );

        }
        else {
            $this->setChild(
                'submit_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                    'label'     => Mage::helper('sales')->__('Refund Offline'),
                    'class'     => 'save submit-button',
                    'onclick'   => $isNotMagento1_3?'disableElements(\'submit-button\');editForm.submit()':'editForm.submit();',
                ))
            );
        }

        return $this;
    }
}