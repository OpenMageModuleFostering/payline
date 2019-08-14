<?php
require_once(Mage::getBaseDir() . '/app/code/community/Monext/Payline/lib/paylineSDK.php');

class Monext_Payline_Model_Adminhtml_System_Config_Source_Environment
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
        	//array('value' => paylineSDK::ENV_DEV, 'label'=>paylineSDK::ENV_DEV),
            array('value' => paylineSDK::ENV_HOMO, 'label'=>paylineSDK::ENV_HOMO),
            array('value' => paylineSDK::ENV_PROD, 'label'=>paylineSDK::ENV_PROD)
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            paylineSDK::ENV_HOMO => Mage::helper('adminhtml')->__(paylineSDK::ENV_HOMO),
            paylineSDK::ENV_PROD => Mage::helper('adminhtml')->__(paylineSDK::ENV_PROD),
        );
    }

}
