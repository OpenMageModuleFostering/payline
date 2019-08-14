<?php
/**
 * Class used as a datasource to display available events to trigger payment capture
 */
class Monext_Payline_Model_Datasource_Capturepaymentoptions extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{

    public function toOptionArray()
    {
        $options    = array();
        $options[]  = array(
            'value' => 'invoice',
            'label' => 'When Invoice is created'
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
        return $options;
    }
}