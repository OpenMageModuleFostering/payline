<?php 

/**
 * Class used as a datasource to display available states for canceled/refused orders
 */
class Monext_Payline_Model_Datasource_Status_Canceled extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
    protected $_stateStatuses = array(
        Mage_Sales_Model_Order::STATE_CANCELED,
        Mage_Sales_Model_Order::STATE_HOLDED,
    );
}