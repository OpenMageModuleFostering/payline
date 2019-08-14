<?php
class Monext_Payline_Block_Adminhtml_Manageproductcategories extends Mage_Adminhtml_Block_Widget_Container
{

    /**
     * Set template
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('payline/productcategories.phtml');
    }

    /**
     * Prepare button and grid
     *
     * @return Monext_Payline_Block_Adminhtml_Manageproductcategories
     */
    protected function _prepareLayout()
    {
        $this->_removeButton('add');

        $this->_addButton('update', array(
                                        'label' => Mage::helper('payline')->__('Insert unassigned categories'),
                                        'onclick' => "setLocation('" . $this->getUrl('*/*/update') . "')"));

        $this->_addButton('reset', array(
                                        'label' => Mage::helper('payline')->__('Reset all assignments'),
                                        'onclick' => "setLocation('" . $this->getUrl('*/*/reset') . "')"));

        $this->setChild('grid', $this->getLayout()
            ->createBlock('payline/adminhtml_manageproductcategories_grid', 'productcategories.grid'));

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
        if (! Mage::app()->isSingleStoreMode()) {
            return false;
        }
        return true;
    }
}
