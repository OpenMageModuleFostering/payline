 <?php
/**
 * Class used as a datasource to display target Payline environment
 */

class Monext_Payline_Model_Datasource_Datatemplate
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'redirect', 'label'=> Mage::helper('payline')->__('Redirection')),
            array('value' => 'lightbox', 'label'=> Mage::helper('payline')->__('Lightbox')),
            array('value' => 'tab', 'label'=> Mage::helper('payline')->__('Embedded tabs')),
            array('value' => 'column', 'label'=> Mage::helper('payline')->__('Embedded columns'))
        );
    }
}
