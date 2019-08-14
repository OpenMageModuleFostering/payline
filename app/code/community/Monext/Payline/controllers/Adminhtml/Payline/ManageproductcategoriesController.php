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
    	
    	$scCol = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active');
    	
    	// add entry for each active store category
    	$arrayCat = array();
    	foreach ($scCol as $scitem) {
    		$data = $scitem->getData();
    		$arrayCat[$data['entity_id']] = $data['name'];
    	}
    	foreach ($scCol as $scitem) {
    		$data = $scitem->getData();
    		$path = explode('/',$data['path']);
    		$lab = '';
    		$size = sizeof($path);
    		$idRootCats = array(1,2); // identifiants des catégories "Root Catalog" et "Root Catalog/Default Category/"
    		for($i=0;$i<$size;$i++){
    			if($size>2 && in_array($path[$i], $idRootCats)){ // on supprime "Root Catalog/Default Category/" du chemin des autres sous-catégories
    				$lab .= '/';
    			}else{
    				$lab .= $arrayCat[$path[$i]].'/';
    			}
    		}
    		$pc = Mage::getModel('payline/productcategories')
    		->setStoreCategoryId($data['entity_id'])
    		->setStoreCategoryLabel(trim($lab))
    		->setPaylineCategoryId(-1);
    		$pc->save();
    	}
    	$this->_redirect('*/*');
    }
}
