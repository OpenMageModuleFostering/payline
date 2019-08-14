<?php

class Monext_Payline_Block_Adminhtml_Managecontracts extends Mage_Adminhtml_Block_Widget_Container
{
	
	/**
     * Set template
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('payline/contracts.phtml');
    }
	
	/**
     * Prepare button and grid
     *
     * @return Monext_Payline_Block_Adminhtml_Managecontracts
     */
    protected function _prepareLayout()
    {
		$this->_removeButton('add');
		
		$this->_addButton('import_contract', array( 
			'label' => Mage::helper('payline')->__('Import contracts'),  
			'onclick'   => "setLocation('".$this->getUrl('*/*/import')."')",
			'class' => 'add')
		);

        $this->setChild('grid', $this->getLayout()->createBlock('payline/adminhtml_managecontracts_grid', 'contract.grid'));
        return parent::_prepareLayout();
    }
	
	
	/**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }

    /**
     * Check whether it is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        if (!Mage::app()->isSingleStoreMode()) {
               return false;
        }
        return true;
    }
}
