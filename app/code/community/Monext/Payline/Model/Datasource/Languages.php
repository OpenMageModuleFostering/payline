 <?php
/**
 * Class used as a datasource to display available languages for the payment pages
 * 
 * Language values follows ISO 639-1
 */
class Monext_Payline_Model_Datasource_Languages
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'', 'label'=>Mage::helper('payline')->__('Based on browser')),
            array('value' => 'fr', 'label'=>Mage::helper('payline')->__('French')),
            array('value' => 'eng', 'label'=>Mage::helper('payline')->__('English')),
            array('value' => 'spa', 'label'=>Mage::helper('payline')->__('Spanish')),
            array('value' => 'pt', 'label'=>Mage::helper('payline')->__('Portuguese')),
            array('value' => 'it', 'label'=>Mage::helper('payline')->__('Italian')),
            array('value' => 'de', 'label'=>Mage::helper('payline')->__('German')),
            array('value' => 'nl', 'label'=>Mage::helper('payline')->__('Flemish')),
            array('value' => 'fi', 'label'=>Mage::helper('payline')->__('Finn')),
        );
    }
}
