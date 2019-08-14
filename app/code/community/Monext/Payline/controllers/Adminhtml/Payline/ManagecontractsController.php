<?php

/**
 * This controller manage primary and secondary payline contracts in back office
 */
class Monext_Payline_Adminhtml_Payline_ManagecontractsController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->_title($this->__('Manage Payline Contracts'));
        $this->loadLayout();
        $this->_setActiveMenu('system');
        $this->renderLayout();
    }

    public function importAction()
    {
        $updatedAt = new Zend_Date();
        $updatedAt = $updatedAt->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $paylineSDK = Mage::helper('payline')->initPayline('CPT');
        try {
            $result = $paylineSDK->getMerchantSettings(array('version' => Monext_Payline_Helper_Data::VERSION));
        } catch (Exception $e) {
            Mage::logException($e);
            $msg    = Mage::helper('payline')->__('Error during import');
            Mage::getSingleton('adminhtml/session')->addError($msg);
            $msgLog = 'Unknown PAYLINE ERROR (payline unreachable?)';
            Mage::helper('payline/logger')->log('[importAction] ' . $this->order->getIncrementId() . $msgLog);
            $this->_redirect('*/*');
            return;
        }

        if (is_string($result)) { //error message from web service
            $msgError = $result;
            if (stristr('Authorization Required', $result)) {
                $msgError .= '. ' . Mage::helper('payline')->__('Please check your authentication params (merchant Id / access key / production) in System > Configuration');
            } elseif (stristr('could not connect to host', $result)) {
                $msgError .= '. ' . Mage::helper('payline')->__('Please check your proxy params (Proxy host / port / login / password) in System > Configuration');
            }

            Mage::getSingleton('adminhtml/session')->addError($msgError);
            Mage::helper('payline/logger')->log('[importAction] ' . $result);
            $this->_redirect('*/*');
            return;
        }

        $nbContracts     = Mage::getModel('payline/contract')->getCollection()->getSize();
        $first           = true;
        $listPointOfSell = array();
        if (isset($result['listPointOfSell']['pointOfSell'])) {
            $listPointOfSell = $result['listPointOfSell']['pointOfSell'];
        }
        foreach ($listPointOfSell as $key => $pointOfSell) {
            if (is_object($pointOfSell)) {
                $contracts        = $pointOfSell->contracts->contract;
                $pointOfSellLabel = $pointOfSell->label;
            } else { //if only one point of sell, we parse an array
                if ($key == 'contracts') {
                    $contracts = $pointOfSell['contract'];
                } elseif ($key == 'label') {
                    $pointOfSellLabel = $pointOfSell;
                    continue;
                } else {
                    continue;
                }
            }

            if (!is_array($contracts)) {
                $contracts = array($contracts);
            }

            if (is_array($contracts) && isset($contracts['contractNumber'])) {
                $contracts = array((object) $contracts);
            }

            foreach ($contracts as $contract) {
                $myContract = Mage::getModel('payline/contract')
                        ->getCollection()
                        ->addFieldToFilter('number', $contract->contractNumber)
                        ->getFirstItem();

                if ($myContract->getId()) { //contract exists, update
                    $myContract->setName($contract->label)
                        ->setPointOfSell($pointOfSellLabel)
                        ->setContractDate($updatedAt)
                        ->setContractType($contract->cardType)
                        ->save();
                } else {
                    $new_contract = Mage::getModel('payline/contract')
                        ->setName($contract->label)
                        ->setNumber($contract->contractNumber)
                        ->setPointOfSell($pointOfSellLabel)
                        ->setContractType($contract->cardType)
                        ->setContractDate($updatedAt);

                    if ($first && !$nbContracts) { //force to have a primary contract
                        $new_contract->setIsPrimary(1);
                        $first = false;
                    }

                    $new_contract->save();
                }
            }
        }

        //delete contracts not updated
        $toDelete = Mage::getModel('payline/contract')->getCollection()
                ->addFieldToFilter('contract_date', array('neq' => $updatedAt));
        foreach ($toDelete as $contract) {
            $contract->delete();
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('payline')->__('Contracts imported successfully. Don\'t forget to define primary and secondary contracts!'));
        $this->_redirect('*/*');
    }

    /**
     * can't create new contract, forward to edit action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            try {
                $postData = $this->getRequest()->getPost();

                // Update existing contract
                if (array_key_exists('id', $postData)) {
                    $contract = Mage::getModel('payline/contract')->load($postData['id']);

                    $is_primary   = (isset($postData['is_primary']) ? $postData['is_primary'] : 0);
                    $is_secondary = (isset($postData['is_secondary']) ? $postData['is_secondary'] : 0);

                    $count = Mage::getModel('payline/contract')->getCollection()
                        ->addFieldToFilter('is_primary', 1)
                        ->addFieldToFilter('id', array('neq' => $contract->getId()))
                        ->getSize();
                    //at least one contract must be primary
                    if (!$is_primary && !$count) {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('payline')->__('At leat one contract must be primary'));
                        $this->_redirect('*/*');
                        return;
                    }

                    //primary and secondary contracts must ne defined in only one point of sell
                    //because it's impossible to send to webservice contracts that are not in the same point of sell
                    //Mage::getResourceModel('payline/contract')->removePrimaryAndSecondaryNotIn($contract->getPointOfSell());

                    $contract->setIsPrimary($is_primary)
                        ->setIsSecondary($is_secondary)
                        ->save();
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('payline')->__('Contract saved successfully'));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }

        $this->_redirect('*/*/');
    }

    public function editAction()
    {
        $contract = Mage::getModel('payline/contract')->load($this->getRequest()->getParam('id', false));

        //checks if the item has been loaded correctly
        if (!$contract->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('payline')->__("Required contract does not exist"));
            $this->_redirect('*/*/index');
            return;
        }

        // Register model to use later in blocks
        Mage::register('current_contract', $contract);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function massStatusAction()
    {
        $contractIds = (array) $this->getRequest()->getParam('contract');
        $status      = (int) $this->getRequest()->getParam('status');
        $store       = $this->getRequest()->getParam('store', 0);
        $website     = $this->getRequest()->getParam('website', 0);

        Mage::getResourceModel('payline/contract_status')->updateContractStatus($contractIds, $status, $website, $store);

        $this->_redirect('*/*/', array('website' => $website, 'store' => $store));
    }

    public function massWalletAction()
    {
        $contractIds = (array) $this->getRequest()->getParam('contract');
        $subscribe   = (int) $this->getRequest()->getParam('walletList');
        $store       = $this->getRequest()->getParam('store', 0);
        $website     = $this->getRequest()->getParam('website', 0);

        Mage::getResourceModel('payline/contract')->updateContractWalletList(
            $contractIds, $subscribe, $website, $store
        );

        $this->_redirect('*/*/', array('website' => $website, 'store' => $store));
    }

    /**
     * contracts grid for AJAX request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

}
