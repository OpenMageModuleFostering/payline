<?php

/**
 * Payline contracts collection
 */

class Monext_Payline_Model_Mysql4_Contract_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct() {
        $this->_init('payline/contract');
    }

	public function addFilterStatus($primary = false,$storeId = null)
	{
		if($storeId === null) return $this;

		$this->getSelect()
				->joinLeft(
					array('status'=>$this->getTable('payline/contract_status')),
					'`main_table`.`id`=`status`.`contract_id` AND `status`.`store_id`='.$storeId,
					array());

		if($primary) {
			$this->getSelect()->where('status.is_primary = 1 or (status.is_primary is null and main_table.is_primary = 1)');
		} else {
			$this->getSelect()->where('status.is_secondary = 1 or (status.is_secondary is null and main_table.is_secondary = 1)');
		}

		$this->getSelect()->reset(Zend_Db_Select::COLUMNS)
				->columns('id', 'main_table')
				->columns('name', 'main_table')
				->columns('number', 'main_table')
				->columns('contract_type', 'main_table')
				->columns('is_secure', 'main_table')
				->columns('is_included_wallet_list', 'main_table')
				->distinct();

		return $this;
	}

	/**
	 * Add a secure filter to retrieve 3DS contract
	 *
	 * @param bool $secure
	 * @param int $storeId
	 *
	 * @return Monext_Payline_Model_Mysql4_Contract_Collection
	 */
	public function addFilterSecure($secure = false,$storeId = null)
	{
	    if($storeId === null) {
	        $storeId = Mage::app()->getStore()->getId();
	    }

	    $this->getSelect()
	    ->joinLeft(
	            array('secure'=>$this->getTable('payline/contract_status')),
	            '`main_table`.`id`=`secure`.`contract_id` AND `secure`.`store_id`='.$storeId,
	            array());

	    if($secure) {
	        $this->getSelect()->where('secure.is_secure = 1 or (secure.is_secure is null and main_table.is_secure = 1)');
	    } else {
	        $this->getSelect()->where('secure.is_secure = 0 or (secure.is_secure is null and main_table.is_secure = 0)');
	    }

	    $this->getSelect()->reset(Zend_Db_Select::COLUMNS)
	    ->columns('id', 'main_table')
	    ->columns('name', 'main_table')
	    ->columns('number', 'main_table')
	    ->columns('contract_type', 'main_table')
	    ->columns('is_secure', 'main_table')
	    ->columns('is_included_wallet_list', 'main_table')
	    ->distinct();

	    return $this;
	}

	public function addStoreFilter($storeId = null)
	{
		if($storeId === null) return $this;

		$this->getSelect()
				->joinLeft(
					array('status'=>$this->getTable('payline/contract_status')),
					'`main_table`.`id`=`status`.`contract_id` AND `status`.`store_id`='.$storeId,
					array())
				->reset(Zend_Db_Select::COLUMNS)
				->columns('id', 'main_table')
				->columns('name', 'main_table')
				->columns('number', 'main_table')
				->columns('point_of_sell', 'main_table')
				->columns(array('is_primary' => new Zend_Db_Expr('IFNULL(status.is_primary,main_table.is_primary)')), 'status')
				->columns(array('is_secondary' => new Zend_Db_Expr('IFNULL(status.is_secondary,main_table.is_secondary)')), 'status')
				->columns(array('is_secure' => new Zend_Db_Expr('IFNULL(status.is_secure,main_table.is_secure)')), 'status')
				->columns(array('is_included_wallet_list' => new Zend_Db_Expr('IFNULL(status.is_included_wallet_list,main_table.is_included_wallet_list)')), 'status');

		return $this;
	}

	public function addWebsiteFilter($websiteId = null)
	{
		if($websiteId === null) return $this;

		$this->getSelect()
				->joinLeft(
					array('status'=>$this->getTable('payline/contract_status')),
					'`main_table`.`id`=`status`.`contract_id` AND `status`.`website_id`='.$websiteId,
					array())
				->reset(Zend_Db_Select::COLUMNS)
				->columns('id', 'main_table')
				->columns('name', 'main_table')
				->columns('number', 'main_table')
				->columns('point_of_sell', 'main_table')
				->columns(array('is_primary' => new Zend_Db_Expr('IFNULL(status.is_primary,main_table.is_primary)')), 'status')
				->columns(array('is_secondary' => new Zend_Db_Expr('IFNULL(status.is_secondary,main_table.is_secondary)')), 'status')
				->columns(array('is_secure' => new Zend_Db_Expr('IFNULL(status.is_secure,main_table.is_secure)')), 'status')
				->columns(array('is_included_wallet_list' => new Zend_Db_Expr('IFNULL(status.is_included_wallet_list,main_table.is_included_wallet_list)')), 'status');

		return $this;
	}

	/**
	 * Convert items array to hash for select options
	 *
	 * return items hash
	 * array($value => $label)
	 *
	 * @param   string $valueField
	 * @param   string $labelField
	 * @return  array
	 */
	public function toOptionHashByContractTypes()
	{
		return parent::_toOptionHash('id', 'contract_type');
	}
}
