<?php

/**
 * Payline contracts status resource model 
 */

class Monext_Payline_Model_Mysql4_Contract_Status extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct() 
    {
        $this->_init('payline/contract_status', 'id');
    }

	/**
	 * Update contract status by scope
	 * @param type $ids
	 * @param type $status
	 * @param type $website
	 * @param type $store
	 * @return type 
	 */
	public function updateContractStatus($ids,$status,$website_code,$store_code)
	{
		if(!is_array($ids)) {
			$ids = array($ids);
		}
		
		$pointOfSell = $this->getPointOfSell($ids);
		$otherContracts = $this->getContractsNotIn($pointOfSell);
		$storeIds = array();
		$websiteId = null;
		$isDefaultLevel = false;
		$isWebsiteLevel = false;
		$isStoreViewLevel= false;
		
		if(!$store_code) {
			if($website_code) {
				$isWebsiteLevel = true;
				$website = Mage::app()->getWebsite($website_code);
				$websiteId = $website->getId();
				$storeIds = $website->getStoreIds();
			} else {
				$isDefaultLevel = true;
			}
		} else {
			$isStoreViewLevel = true;
			$storeIds = array(Mage::app()->getStore($store_code)->getId());
		}
		$connection = $this->_getWriteAdapter();
		$connection->beginTransaction();
		$fields = array();
		switch($status) {
			case 0:
				$fields['is_primary'] = 1; $fields['is_secondary'] = 0;		
				break;
			case 1:
				$fields['is_primary'] = 0; $fields['is_secondary'] = 1;
				break;
			case 2:
				$fields['is_primary'] = 1; $fields['is_secondary'] = 1;		
				break;
			case 3:
				$fields['is_primary'] = 0; $fields['is_secondary'] = 0;		
				break;
			default :
				$fields['is_primary'] = 0; $fields['is_secondary'] = 0;		
		}
		
		if($isDefaultLevel) {
			$conditions = array();
			$conditions[] = $connection->quoteInto('contract_id in (?)', $ids);
			$connection->delete($this->getTable('payline/contract_status'),$conditions);
			
			$where = $connection->quoteInto('id in (?)', $ids);
			$connection->update($this->getTable('payline/contract'), $fields, $where);
			
			$this->resetContractStatus($connection, 0, $otherContracts, $websiteId, $storeIds);
			
			$count = Mage::getModel('payline/contract')->getCollection()->addFieldToFilter('is_primary',1)->getSize();
		} else  {
			$conditions = 'contract_id in ('.implode(',',$ids).') AND (';
			if($isWebsiteLevel) $conditions .= 'website_id = '. $websiteId . ' OR ';
			$conditions .= 'store_id in (' . implode(',',$storeIds) . '))';
            $deletedRows = $this->queryContractStatus($ids, $storeIds, $websiteId);
			$connection->delete($this->getTable('payline/contract_status'),$conditions);

			foreach ($ids as $id) {
				if($isWebsiteLevel) {
					$data = array(
							'contract_id'   => $id,
							'website_id'    => $websiteId,
                            'store_id'      => null,
							'is_primary'    => $fields['is_primary'],
							'is_secondary'  => $fields['is_secondary']
						);
                    $backup = $this->getMatchingRowByKeys( $deletedRows, $data );
                    if( $backup ) {
                        $data['is_included_wallet_list'] = $backup['is_included_wallet_list'];
                    }
					$connection->insert($this->getTable('payline/contract_status'),$data);
				}
				foreach ($storeIds as $storeId) {
					$data = array(
						'contract_id'   => $id,
						'store_id'      => $storeId,
                        'website_id'    => null,
						'is_primary'    => $fields['is_primary'],
						'is_secondary'  => $fields['is_secondary']
					);
                    $backup = $this->getMatchingRowByKeys( $deletedRows, $data );
                    if( $backup ) {
                        $data['is_included_wallet_list'] = $backup['is_included_wallet_list'];
                    }
					$connection->insert($this->getTable('payline/contract_status'),$data);
				}
			} 
			
			$this->resetContractStatus($connection, ($isWebsiteLevel ? 2 : 3), $otherContracts, $websiteId, $storeIds);
			
			if($isWebsiteLevel) {
				$count= Mage::getModel('payline/contract_status')->getCollection()
										->addFieldToFilter('is_primary',1)
										->addFieldToFilter('store_id',$storeIds)
										->getSize();	
			} else {
				$count = Mage::getModel('payline/contract')->getCollection()->addFilterStatus(true,$storeId)->getSize();
			}
		}	

		//at least one contract must be primary
		if(!$count) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('payline')->__('At leat one contract must be primary'));
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('payline')->__('Please set a primary contract beforefor this point of sell'));
			return;
		}
		
		$connection->commit();	
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('payline')->__('Contracts modified successfully'));
	}
	
	/**
	 * Reset contract status for contracts that are not in $pointOfSell
	 * 
	 * @param type $connection
	 * @param type $level
	 * @param type $ids
	 * @param type $websiteId
	 * @param type $storeIds 
	 */
	public function resetContractStatus($connection,$level,$ids,$websiteId,$storeIds) {
		$fields = array();
		$fields['is_primary'] = 0;
		$fields['is_secondary'] = 0;

		if($level == 0) {
			$conditions = array();
			$conditions[] = $connection->quoteInto('contract_id in (?)', $ids);
			$connection->delete($this->getTable('payline/contract_status'),$conditions);
			
			$where = $connection->quoteInto('id in (?)', $ids);
			$connection->update($this->getTable('payline/contract'), $fields, $where);
		} else  {
			$conditions = 'contract_id in ('.implode(',',$ids).') AND (';
			if($level == 2) $conditions .= 'website_id = '. $websiteId . ' OR ';
			$conditions .= 'store_id in (' . implode(',',$storeIds) . '))';
            $deletedRows = $this->queryContractStatus($ids, $storeIds, $websiteId);

            $connection->delete($this->getTable('payline/contract_status'),$conditions);

			foreach ($ids as $id) {
				if($level == 2) {
					$data = array(
							'contract_id' => $id,
							'website_id' => $websiteId,
                            'store_id' => null,
							'is_primary' => $fields['is_primary'],
							'is_secondary' => $fields['is_secondary']
						);
                    $backup = $this->getMatchingRowByKeys( $deletedRows, $data );
                    if( $backup ) {
                        $data['is_included_wallet_list']   = $backup['is_included_wallet_list'];
                    }
					$connection->insert($this->getTable('payline/contract_status'),$data);
				}
				foreach ($storeIds as $storeId) {
					$data = array(
						'contract_id' => $id,
						'store_id' => $storeId,
                        'website_id' => null,
						'is_primary' => $fields['is_primary'],
						'is_secondary' => $fields['is_secondary']
					);
                    $backup = $this->getMatchingRowByKeys( $deletedRows, $data );
                    if( $backup ) {
                        $data['is_included_wallet_list']   = $backup['is_included_wallet_list'];
                    }
					$connection->insert($this->getTable('payline/contract_status'),$data);
				}
			} 
		}
	}
	
	
	/**
	 * Get contract ids of contracts not int $pointOfSell
	 * @param string $pointOfSell
	 * @return array 
	 */
	public function getContractsNotIn($pointOfSell) {
	    return Mage::getResourceModel('payline/contract')->getContractsNotIn( $pointOfSell );
	}
	
	
	/**
	 * Get the point of sell of contracts
	 * @param array $contract_ids
	 * @return string 
	 */
	public function getPointOfSell($contract_ids) {
        return Mage::getResourceModel('payline/contract')->getPointOfSell( $contract_ids );
	}

    /**
     * Return contract_status rows by contract ids, store ids and website id
     * @param $contractIds {array} contain n contract_status.contract_id
     * @param $storeIds {array} contain n contact_status.store_id
     * @param $websiteId {int} website id
     * @return {array} Return an array of rows returned by the query
     */
    public function queryContractStatus($contractIds, $storeIds, $websiteId)
    {
        $read   = $this->_getReadAdapter();
        $select = $read
            ->select()
//            ->distinct()
            ->from( $this->getTable('payline/contract_status') );

        $condition = 'contract_id IN ('. implode(',',$contractIds) .') AND (';
        if( isset( $websiteId ) ) { $condition .= 'website_id='.$websiteId.' OR '; }
        $condition .= 'store_id IN ('. implode(',', $storeIds) . ') )';
        $select->where( $condition );
        return $select->query()->fetchAll();
    }

    /**
     * Return the matching array $matchMe in $rows.
     *The match is done by checking the contract_status db table keys
     * @param $rows
     * @param $matchMe
     * @return array The matching array or false
     */
    public function getMatchingRowByKeys($rows, $matchMe)
    {
        $matchCounter = 0;
        foreach( $rows as $row ) {
            if( $row['contract_id'] == $matchMe['contract_id'] ) {
                $matchCounter++;
                $matchCounter = $row['store_id'] == $matchMe['store_id'] ? $matchCounter + 1 : $matchCounter;
                $matchCounter = $row['website_id'] == $matchMe['website_id'] ? $matchCounter + 1 : $matchCounter;
            }

            if( $matchCounter == 3 ) {return $row; }
            else { $matchCounter = 0; }
        }
        // no match
        return false;
    }

}
