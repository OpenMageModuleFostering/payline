<?php

/**
 * Block to display payline nx payment fees on order and invoice
 */

class Monext_Payline_Block_Adminhtml_Sales_Order_Total_Nxfees extends Mage_Core_Block_Abstract
{
    public function initTotals()
    {
        $parent = $this->getParentBlock();
		
		$entity = $parent->getSource();
        $orderId = $entity->getOrderId(); //invoice
		$order = false;
		if(!$orderId) { 
			$orderId = $entity->getId(); //order
			$order = true;
		}
		
		$fee = Mage::getModel('payline/fees')->getCollection()
						->addFieldtoFilter('order_id',$orderId)->getFirstItem();

        if ($fee->getId() && 
			($order || (!$order && $fee->getInvoiceId()==$entity->getId())))
        {
            $total = new Varien_Object(array(
				'code'  => 'payline_nx',
				'value' => $fee->getAmount(),
				'base_value' => $fee->getBaseAmount(),
				'label' => Mage::helper('payline')->__('Payline fees'),
				'field' => 'payline_nx'
            ));
            $parent->addTotal($total, 'payline_nx');
        }
		
        return $this;
    }
}