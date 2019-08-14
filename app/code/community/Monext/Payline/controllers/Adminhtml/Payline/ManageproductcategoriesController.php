<?php

/**
 * This controller manage mapping between Payline and store product categories
 */
class Monext_Payline_Adminhtml_Payline_ManageproductcategoriesController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Manage Payline Product Categories'));
        $this->loadLayout();
        $this->_setActiveMenu('system');
        $this->renderLayout();
    }

    public function assignAction()
    {
    	Mage::getSingleton('core/session')->setData('rowCatToAssign',$this->getRequest()->getParam('id'));
    	$this->loadLayout()
    	->renderLayout();
    }

    public function unassignAction()
    {
    	$rowId = $this->getRequest()->getParam('id');
    	$model = Mage::getModel('payline/productcategories')->load($rowId);
    	$data = array('payline_category_id' => -1, 'payline_category_label' => '');
    	$model->addData($data);
    	try {
    		$model->setId($rowId)->save();
    	} catch (Exception $e){
    		echo $e->getMessage();
    	}
    	$this->_redirect('*/*/');
    }

    public function updateAction()
    {
        $collection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addAttributeToFilter('level',array('gt'=>1));

        $collection->getSelect()
           ->joinLeft(array('plncat'=>$collection->getTable('payline_product_categories')), 'plncat.store_category_id=e.entity_id')
           ->where('plncat.store_category_id is null');


        foreach ($collection as $category) {
            $pc = Mage::getModel('payline/productcategories')
                    ->setStoreCategoryId($category->getId())
                    ->setStoreCategoryLabel(Mage::helper('payline/category')->getCategoryFullpathName($category->getId()))
                    ->setPaylineCategoryId(-1)
                    ->save();
        }

        $this->_redirect('*/*/');
    }

    /**
    * Save status assignment to state
    */
    public function assignPostAction()
    {
    	$data = $this->getRequest()->getPost();
    	if ($data) {
    		$paylineCategoryId  = $this->getRequest()->getParam('paylinecat');
    		$rowId = Mage::getSingleton('core/session')->getData('rowCatToAssign');

			$data = array('payline_category_id' => $paylineCategoryId, 'payline_category_label' => Mage::getModel('payline/datasource_paylineproductcategories')->getLabelbyId($paylineCategoryId));
			$model = Mage::getModel('payline/productcategories')->load($rowId);
			$model->addData($data);
			try {
				$model->setId($rowId)->save();
			} catch (Exception $e){
				echo $e->getMessage();
			}
    		$this->_redirect('*/*/index');
    		return;
    	}
    	$this->_redirect('*/*/');
    }

    public function resetAction()
    {
    	$pcCol = Mage::getModel('payline/productcategories')->getCollection();

    	// delete current mapping between Payline and store categories
    	foreach ($pcCol as $pcitem) {
    		$pcitem->delete();
    	}

    	$categories = Mage::helper('payline/category')->getAllCategoriesWithFullpathName();
    	foreach ($categories as $categoryId=>$categoryPath) {
    		$pc = Mage::getModel('payline/productcategories')
            		->setStoreCategoryId($categoryId)
            		->setStoreCategoryLabel($categoryPath)
            		->setPaylineCategoryId(-1)
            		->save();
    	}
    	$this->_redirect('*/*');
    }

    /**
     * Order grid
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('payline/adminhtml_manageproductcategories_grid')->toHtml()
        );
    }
}
