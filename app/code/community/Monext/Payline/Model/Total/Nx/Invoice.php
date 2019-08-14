<?php

class Monext_Payline_Model_Total_Nx_Invoice
    extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    // Collect the totals for the invoice
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();		
		$orderId = $order->getId();
		$fee = Mage::getModel('payline/fees')->getCollection()
							->addFieldtoFilter('order_id',$orderId)->getFirstItem();
		
		if($fee->getId() && ($invoice->getId() == $fee->getInvoiceId())) {
			$myTotal = $fee->getAmount();
			$baseMyTotal = $fee->getBaseAmount();

			$invoice->setGrandTotal($invoice->getGrandTotal() + $myTotal);
			$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseMyTotal);
		}
        return $this;
    }
}
