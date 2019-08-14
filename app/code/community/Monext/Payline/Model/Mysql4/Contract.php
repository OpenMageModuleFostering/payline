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
    public function updateContractSecureList($ids, $optionToSet, $website_code, $store_code)
    {
        if(!is_array($ids)) {
            $ids = array($ids);
        }

        $contractOptions= array();
        foreach($ids as $contractId) {
            $contractOptions[$contractId] = array('is_secure'=>$optionToSet);
        }


        $this->updateOptionsContractList($contractOptions, $website_code, $store_code);
    }

    // Use fallback history pattern
    public function updateContractWalletList($ids, $optionToSet, $website_code, $store_code)
    {
        if(!is_array($ids)) {
            $ids = array($ids);
        }

        $contractOptions= array();
        foreach($ids as $contractId) {
            $contractOptions[$contractId] = array('is_included_wallet_list'=>$optionToSet);
        }

        $this->updateOptionsContractList($contractOptions, $website_code, $store_code);
    }

    // Use fallback history pattern
    public function updateOptionsContractList($contractOptions, $website_code, $store_code)
    {

        $ids = array_keys($contractOptions);

        $pointOfSell        = $this->getPointOfSell($ids);
        $otherContracts     = $this->getContractsNotIn($pointOfSell);
        $storeIds           = array();
        $websiteId          = null;
        $isDefaultLevel     = false;
        $isWebsiteLevel     = false;
        $isStoreViewLevel   = false;
        $connection         = $this->_getWriteAdapter();


        $keepOptionList=array('is_included_wallet_list','is_secure','is_primary','is_secondary');

        // set store & website code
        if(!$store_code) {
            if($website_code) {
                $isWebsiteLevel = true;
                $website        = Mage::app()->getWebsite($website_code);
                $websiteId      = $website->getId();
                $storeIds       = $website->getStoreIds();
            } else {
                $isDefaultLevel = true;
            }
        } else {
            $isStoreViewLevel   = true;
            $storeIds           = array(Mage::app()->getStore($store_code)->getId());
        }

        $connection->beginTransaction();

        // process update
        if($isDefaultLevel) {
            // default level override son's options
            $conditions     = array();
            $conditions[]   = $connection->quoteInto('contract_id in (?)', $ids);
            $connection->delete($this->getTable('payline/contract_status'),$conditions);

            //Update contracts
            foreach ($contractOptions as $id=>$optionToSet) {
                $fields = $optionToSet;
                $where  = $connection->quoteInto('id in (?)', $id);
                $connection->update($this->getTable('payline/contract'), $optionToSet, $where);
            }

            //Unset other options
            $optionToUnsset = array_fill_keys(array_keys(current($contractOptions)), 0);
            $connection->update($this->getTable('payline/contract'), $optionToUnsset, $connection->quoteInto('id not in (?)', array_keys($contractOptions)));

            $count = Mage::getModel('payline/contract')->getCollection()->addFieldToFilter('is_primary',1)->getSize();
        } else {
            $contractStatusRModel =  Mage::getResourceModel('payline/contract_status');

            $conditionContract = 'contract_id in ('.implode(',',$ids).')';
            $conditionLevel= '(';
            if($isWebsiteLevel) $conditionLevel .= 'website_id = '. $websiteId . ' OR ';
            $conditionLevel .= 'store_id in (' . implode(',',$storeIds) . '))';

            // temporarily stock deleted rows to avoid is_primary and is_secondary data lost
            $deletedRows = $contractStatusRModel->queryContractStatus($ids, $storeIds, $websiteId);

            $connection->delete($this->getTable('payline/contract_status'), $conditionContract . ' AND ' . $conditionLevel);

            //Unset other options
            $optionToUnsset = array_fill_keys(array_keys(current($contractOptions)), 0);
            $connection->update($this->getTable('payline/contract_status'), $optionToUnsset, $connection->quoteInto('contract_id not in (?)', array_keys($contractOptions)) . ' AND ' . $conditionLevel);

            $fields=array();
            $fields['is_primary']   = 0;
            $fields['is_secondary'] = 0;
            $fields['is_secure'] = 0;
            $fields['is_included_wallet_list'] = 0;

            foreach ($contractOptions as $id=>$optionToSet) {
                if($isWebsiteLevel) {
                    $data = array(
                            'contract_id'               => $id,
                            'website_id'                => $websiteId,
                            'store_id'                  => null,
                    );

                    $data=array_merge($data, $fields, $optionToSet);
                    // time to restore deleted info (if needed)
                    $backup = $contractStatusRModel->getMatchingRowByKeys( $deletedRows, $data );
                    foreach($keepOptionList as $fieldToBackup) {
                        if(!array_key_exists($fieldToBackup, $optionToSet)) {
                            $data[$fieldToBackup]      = $backup[$fieldToBackup];
                        }
                    }

                    $connection->insert($this->getTable('payline/contract_status'),$data);
                }
                foreach ($storeIds as $storeId) {
                    $data = array(
                            'contract_id'               => $id,
                            'website_id'                => null,
                            'store_id'                  => $storeId,
                    );
                    $data=array_merge($data, $fields, $optionToSet);
                    // time to restore deleted info (if needed)
                    $backup = $contractStatusRModel->getMatchingRowByKeys( $deletedRows, $data );

                    foreach($keepOptionList as $fieldToBackup) {
                        if(!array_key_exists($fieldToBackup, $optionToSet)) {
                            $data[$fieldToBackup]      = $backup[$fieldToBackup];
                        }
                    }
                    $connection->insert($this->getTable('payline/contract_status'),$data);
                }
            }

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
