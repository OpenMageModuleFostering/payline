<?php
class Monext_Payline_Model_Datasource_Statusrowempty extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
    protected $_options;

    public function toOptionArray()
    {
        if ($this->_options === null) {
            $options = array();
            $options[] = array(
                'value' => '',
                'label' => 'None'
            );
            if (class_exists('Mage_Sales_Model_Mysql4_Order_Status_Collection')) {
                $collection = Mage::getResourceModel('sales/order_status_collection')
                    ->orderByLabel();
                $statusValue = '';
                foreach ($collection as $status) {
                    $statusValue = $status->getStatus();
                    if( $statusValue === 'complete' || $statusValue === 'closed' ) {
                            continue;
                    }
                    $options[] = array(
                        'value' => $statusValue,
                        'label' => $status->getStoreLabel()
                    );
                }
            } else {
                $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
                foreach ($statuses as $code=>$label) {
                    if( $code === 'complete' || $code === 'closed' ) {
                            continue;
                    }
                    $options[] = array(
                        'value' => $code,
                        'label' => $label
                    );
                }
            }

            $this->_options = $options;
        }

        return $this->_options;
    }
}