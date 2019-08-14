<?php

/**
 * Payline product categories model 
 */

class Monext_Payline_Model_Productcategories extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('payline/productcategories');
    }
    
    public function getStoreCategoryId(){
    	return $this->_data['store_category_id'];
    }
    
	public function getPaylineCategoryId(){
    	return $this->_data['payline_category_id'];
    }
}
