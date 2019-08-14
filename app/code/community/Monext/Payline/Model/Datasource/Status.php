<?php
class Monext_Payline_Model_Datasource_Status extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
    public function toOptionArray()
    {
        $options = array();
        if (class_exists('Mage_Sales_Model_Mysql4_Order_Status_Collection')) {
            $collection = Mage::getResourceModel('sales/order_status_collection')
                ->orderByLabel();
            foreach ($collection as $status) {
                $options[] = array(
                   'value' => $status->getStatus(),
                   'label' => $status->getStoreLabel()
                );
            }
        } else {
            $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
            foreach ($statuses as $code=>$label) {
                $options[] = array(
                   'value' => $code,
                   'label' => $label
                );
            }
        }
        
        return $options;
    }
}