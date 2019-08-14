<?php
class Monext_Payline_Model_Datasource_Status_Invoice extends Monext_Payline_Model_Datasource_Status
{
    public function toOptionArray()
    {

        if ($this->_options === null) {
            $options = array();
            $options[] = array(
               'value' => '',
               'label' => Mage::helper('adminhtml')->__('No')
            );
            $options[] = array(
               'value' => 'return',
               'label' => Mage::helper('payline')->__('Back to the shop')
            );
            if (class_exists('Mage_Sales_Model_Mysql4_Order_Status_Collection')) {
                $collection = Mage::getResourceModel('sales/order_status_collection')
                    ->orderByLabel();
                foreach ($collection as $status) {
                    $options[] = array(
                       'value' => $status->getStatus(),
                       'label' => Mage::helper('payline')->__("When order status is '%s'", $status->getStoreLabel())
                    );
                }
            } else {
                $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
                foreach ($statuses as $code=>$label) {
                    $options[] = array(
                       'value' => $code,
                       'label' => Mage::helper('payline')->__("When order status is '%s'", $label)
                    );
                }
            }

            $this->_options = $options;
        }

        return $this->_options;
    }
}