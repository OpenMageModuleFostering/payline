<?php

/**
 * Payline contracts resource model 
 */

class Monext_Payline_Model_Mysql4_Contract extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct() 
    {
        $this->_init('payline/contract', 'id');
    }
	
	/**
	 * set primary = 0 and secondary = 0 for contracts that are not in $pointOfSell
	 * @param type $pointOfSell 
	 */
	public function removePrimaryAndSecondaryNotIn($pointOfSell)
	{
		$connection = $this->_getWriteAdapter();	
		$connection->beginTransaction();
		$fields = array();
		$fields['is_primary'] = 0;
		$fields['is_secondary'] = 0;
		$where = $connection->quoteInto('point_of_sell != ?', $pointOfSell);
		$connection->update($this->getTable('payline/contract'), $fields, $where);
		$connection->commit();
	}

    // Use fallback history pattern
    public function updateContractWalletList($ids, $optionToSet, $website_code, $store_code)
    {
        if(!is_array($ids)) {
            $ids = array($ids);
        }

        $pointOfSell        = $this->getPointOfSell($ids);
        $otherContracts     = $this->getContractsNotIn($pointOfSell);
        $storeIds           = array();
        $websiteId          = null;
        $isDefaultLevel     = false;
        $isWebsiteLevel     = false;
        $isStoreViewLevel   = false;
        $connection         = $this->_getWriteAdapter();

        // set store & website code
        if(!$store_code) {
            if($website_code) {
                $isWebsiteLevel = true;
                $website        = Mage::getModel('core/website')->load($website_code);
                $websiteId      = $website->getId();
                $storeIds       = $website->getStoreIds();
            } else {
                $isDefaultLevel = true;
            }
        } else {
            $isStoreViewLevel   = true;
            $storeIds           = Mage::getModel('core/store')->load($store_code)->getId();
            $storeIds           = array($storeIds);
        }

        $connection->beginTransaction();

        // process update
        if($isDefaultLevel) {
            // default level override son's options
            $conditions     = array();
            $conditions[]   = $connection->quoteInto('contract_id in (?)', $ids);
            $connection->delete($this->getTable('payline/contract_status'),$conditions);

            $where  = $connection->quoteInto('id in (?)', $ids);
            $fields = array( 'is_included_wallet_list' => $optionToSet );
            $connection->update($this->getTable('payline/contract'), $fields, $where);

            // wallet subscription can be set for all point of sell. Uncomment below to avoid that.
//            $this->resetContractWallet($connection, 0, $otherContracts, $websiteId, $storeIds);

            $count = Mage::getModel('payline/contract')->getCollection()->addFieldToFilter('is_primary',1)->getSize();
        } else {
            $contractStatusRModel =  Mage::getResourceModel('payline/contract_status');

            $conditions = 'contract_id in ('.implode(',',$ids).') AND (';
            if($isWebsiteLevel) $conditions .= 'website_id = '. $websiteId . ' OR ';
            $conditions .= 'store_id in (' . implode(',',$storeIds) . '))';

            // temporarily stock deleted rows to avoid is_primary and is_secondary data lost
            $deletedRows = $contractStatusRModel->queryContractStatus($ids, $storeIds, $websiteId);

            $connection->delete($this->getTable('payline/contract_status'),$conditions);

            $fields['is_primary']   = 0;
            $fields['is_secondary'] = 0;
            foreach ($ids as $id) {
                if($isWebsiteLevel) {
                    $data = array(
                        'contract_id'               => $id,
                        'website_id'                => $websiteId,
                        'store_id'                  => null,
                        'is_primary'                => $fields['is_primary'],
                        'is_secondary'              => $fields['is_secondary'],
                        'is_included_wallet_list'   => $optionToSet
                    );
                    // time to restore deleted info (if needed)
                    $backup = $contractStatusRModel->getMatchingRowByKeys( $deletedRows, $data );
                    if( $backup ) {
                        $data['is_primary']     = $backup['is_primary'];
                        $data['is_secondary']   = $backup['is_secondary'];
                    }
                    $connection->insert($this->getTable('payline/contract_status'),$data);
                }
                foreach ($storeIds as $storeId) {
                    $data = array(
                        'contract_id'               => $id,
                        'website_id'                => null,
                        'store_id'                  => $storeId,
                        'is_primary'                => $fields['is_primary'],
                        'is_secondary'              => $fields['is_secondary'],
                        'is_included_wallet_list'   => $optionToSet
                    );
                    $backup = $contractStatusRModel->getMatchingRowByKeys( $deletedRows, $data );
                    if( $backup ) {
                        $data['is_primary']     = $backup['is_primary'];
                        $data['is_secondary']   = $backup['is_secondary'];
                    }
                    $connection->insert($this->getTable('payline/contract_status'),$data);
                }
            }

            // wallet subscription can be set for all point of sell. Uncomment below to avoid that.
//            $this->resetContractWallet($connection, ($isWebsiteLevel ? 2 : 3), $otherContracts, $websiteId, $storeIds);

            if($isWebsiteLevel) {
                $count= Mage::getModel('payline/contract_status')->getCollection()
                    ->addFieldToFilter('is_primary',1)
                    ->addFieldToFilter('store_id',$storeIds)
                    ->getSize();
            } else {
                $count = Mage::getModel('payline/contract')->getCollection()->addFilterStatus(true,$storeId)->getSize();
            }
        }

        $connection->commit();

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('payline')->__('Contracts modified successfully'));

    } // end updateContractWalletList()

    /**
     * Reset contract wallet list for contracts that are not in $pointOfSell
     *
     * @param type $connection
     * @param type $level
     * @param type $ids
     * @param type $websiteId
     * @param type $storeIds
     */
    public function resetContractWallet($connection,$level,$ids,$websiteId,$storeIds) {
        $fields                             = array();
        $fields['is_primary']               = 0;
        $fields['is_secondary']             = 0;
        $fields['is_included_wallet_list']  = 0;

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
            $connection->delete($this->getTable('payline/contract_status'),$conditions);

            foreach ($ids as $id) {
                if($level == 2) {
                    $data = array(
                        'contract_id' => $id,
                        'website_id' => $websiteId,
                        'is_primary' => $fields['is_primary'],
                        'is_secondary' => $fields['is_secondary']
                    );
                    $connection->insert($this->getTable('payline/contract_status'),$data);
                }
                foreach ($storeIds as $storeId) {
                    $data = array(
                        'contract_id' => $id,
                        'store_id' => $storeId,
                        'is_primary' => $fields['is_primary'],
                        'is_secondary' => $fields['is_secondary']
                    );
                    $connection->insert($this->getTable('payline/contract_status'),$data);
                }
            } // end foreach( $ids
        } // end else $level
    }


    /**
     * Get the point of sell of contracts
     * @param array $contract_ids
     * @return string
     */
    public function getPointOfSell($contract_ids) {
        $read = $this->_getReadAdapter();

        $select = $read->select()
            ->distinct()
            ->from($this->getTable('payline/contract'),array('point_of_sell'))
            ->where('id in (?)', $contract_ids);

        $result = $select->query();
        $row = $result->fetchAll();
        return $row[0]['point_of_sell'];
    }

    /**
     * Get contract ids of contracts not int $pointOfSell
     * @param string $pointOfSell
     * @return array
     */
    public function getContractsNotIn($pointOfSell) {
        $read = $this->_getReadAdapter();

        $select = $read->select()
            ->distinct()
            ->from($this->getTable('payline/contract'),array('id'))
            ->where('point_of_sell != ?', $pointOfSell);

        $result = $select->query();
        $row = $result->fetchAll();
        $res = array();
        foreach($row as $r) {
            $res[] = $r['id'];
        }
        return $res;
    }

} //end class
?>
