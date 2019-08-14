<?php

/**
 * Payline product categories collection 
 */

class Monext_Payline_Model_Mysql4_Productcategories_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct() {
        $this->_init('payline/productcategories');
    }
    
    public function getAssignedPaylineCatId($storeCatId){
    	$assignmentData = $this->getData();
    	$continue = true;
    	$n=0;
    	$paylineCatId = null;
    	while($continue){
    		if($assignmentData[$n]['store_category_id'] == $storeCatId){
    			$paylineCatId = $assignmentData[$n]['payline_category_id'];
    			$continue = false;
    		}
    		$n++;
    	}
    	return $paylineCatId;
    }
}
