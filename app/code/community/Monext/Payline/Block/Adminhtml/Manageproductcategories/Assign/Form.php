<?php
/**
 * Assign store product category to Payline product category
 */
class Monext_Payline_Block_Adminhtml_Manageproductcategories_Assign_Form extends Mage_Adminhtml_Block_Widget_Form
{
	
	private $_storeCatLabel;
	
    /**
     * Prepare form fields
     *
     * @return Monext_Payline_Block_Adminhtml_Manageproductcategories_Assign_Form
     */
    protected function _prepareForm()
    {
    	$rowId = Mage::getSingleton('core/session')->getData('rowCatToAssign');
    	$assignment = Mage::getModel('payline/productcategories')->load($rowId)->getData();
    	$this->_storeCatLabel = $assignment['store_category_label'];
    	
        $form   = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/payline_manageproductcategories/assignPost'),
            'method'    => 'post'
        ));
        
        $form->setUseContainer(true);
        $this->setForm($form);

        $fieldset   = $form->addFieldset('base_fieldset', array(
            'legend'	=> Mage::helper('payline')->__('Chose a category for').' '.$this->_storeCatLabel,
            'class'		=> 'fieldset-wide'
        ));

        $paylinecat = Mage::getModel('payline/datasource_paylineproductcategories')->toOptionArray();

        $fieldset->addField('paylinecat', 'select',
            array(
                'name'      => 'paylinecat',
                'label'     => Mage::helper('payline')->__('Payline category'),
                'class'     => 'required-entry',
                'values'    => $paylinecat,
                'required'  => true,
            )
        );

        return parent::_prepareForm();
    }
    
    /**
     * Retrieve text for header element
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('payline')->__('Assign Payline product category to').' '.$this->_storeCatLabel;
    }
    
    /**
     * 
     * Add submit button
     */
    public function getButtonsHtml()
    {
    	$addButtonData = array(
            'label'     => Mage::helper('payline')->__('Save assignment'),
            'onclick'   => 'edit_form.submit()',
            'class'     => 'add',
        );
        return $this->getLayout()->createBlock('adminhtml/widget_button')->setData($addButtonData)->toHtml();
    }
}
