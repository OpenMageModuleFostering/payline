<?php
/**
 * Class used as a datasource to display available billing occurences
 */
class Monext_Payline_Model_Datasource_Billingoccurrences
{
    public function toOptionArray()
    {
        return array(
        array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('2')),
        array('value' => 3, 'label'=>Mage::helper('adminhtml')->__('3')),
        array('value' => 4, 'label'=>Mage::helper('adminhtml')->__('4')),
        array('value' => 5, 'label'=>Mage::helper('adminhtml')->__('5')),
        array('value' => 6, 'label'=>Mage::helper('adminhtml')->__('6')),
        array('value' => 7, 'label'=>Mage::helper('adminhtml')->__('7')),
        array('value' => 8, 'label'=>Mage::helper('adminhtml')->__('8')),
        array('value' => 9, 'label'=>Mage::helper('adminhtml')->__('9')),
        array('value' => 10, 'label'=>Mage::helper('adminhtml')->__('10'))
        );
    }
}
