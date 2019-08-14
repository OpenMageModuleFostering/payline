 <?php
/**
 * Class used as a datasource to display target Payline environment
 */
class Monext_Payline_Model_Datasource_Walletsecurity
{
    public function toOptionArray()
    {
        return array(
            array('value' => Monext_Payline_Helper_Data::WALLET_NONE, 'label'=>Mage::helper('payline')->__('- none -')),
            // array('value' => Monext_Payline_Helper_Data::WALLET_CVV, 'label'=>Mage::helper('payline')->__('CVV')),
            array('value' => Monext_Payline_Helper_Data::WALLET_3DS, 'label'=>Mage::helper('payline')->__('3DS'))
            // array('value' => Monext_Payline_Helper_Data::WALLET_BOTH, 'label'=>Mage::helper('payline')->__('CVV + 3DS'))
        );
    }
}
