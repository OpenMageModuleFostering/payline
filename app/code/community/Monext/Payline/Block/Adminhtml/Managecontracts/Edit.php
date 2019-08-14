<?php

class Monext_Payline_Block_Adminhtml_Managecontracts_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'payline';
        $this->_controller = 'adminhtml_managecontracts';

        parent::__construct();

        $this->setData('form_action_url', Mage::getUrl('*/payline_managecontracts/save'));

        $this->_updateButton('save', 'label', Mage::helper('payline')->__('Save contract'));
        $this->_removeButton('delete');
    }
    
    /**
     * Get edit form container header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('payline')->__('Edit contract');
    }
}
