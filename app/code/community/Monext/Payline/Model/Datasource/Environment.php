 <?php
/**
 * Class used as a datasource to display target Payline environment
 */
require_once(Mage::getBaseDir() . '/app/code/community/Monext/Payline/lib/paylineSDK.php');
class Monext_Payline_Model_Datasource_Environment
{
    public function toOptionArray()
    {
        return array(
        	//array('value' => paylineSDK::ENV_DEV, 'label'=>paylineSDK::ENV_DEV),
            array('value' => paylineSDK::ENV_HOMO, 'label'=>paylineSDK::ENV_HOMO),
            array('value' => paylineSDK::ENV_PROD, 'label'=>paylineSDK::ENV_PROD)
        );
    }
}
