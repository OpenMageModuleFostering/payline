<?php

class Monext_Payline_Block_Adminhtml_Managecontracts_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('paylineManageContractsGrid');
		$this->setUseAjax(true);
        //$this->setVarNameFilter('contract_filter');
		$this->setDefaultLimit(50);
    }
	
	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
	

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('payline/contract')->getCollection();
		$store   = $this->getRequest()->getParam('store', '');
		$website = $this->getRequest()->getParam('website', '');
		if($store) {
			$storeId = Mage::getModel('core/store')->load($store)->getId();
			$collection->addStoreFilter($storeId);
		} elseif ($website) {
			$websiteId = Mage::getModel('core/website')->load($website)->getId();
			$collection->addWebsiteFilter($websiteId);
		}
		
        $this->setCollection($collection);		
		
		$temp = clone($collection);
		foreach($temp as $c) {
			if($c->getIsPrimary()) {
				$this->setDefaultFilter(array('point_of_sell'=>$c->getPointOfSell()));
				break;
			}
		}

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {		
        $this->addColumn('name', array(
            'header'    => Mage::helper('payline')->__('Name'),
            'index'     => 'name',
			'filter' => false,
            'type'		=> 'text'
        ));
		
        $this->addColumn('number', array(
            'header'    => Mage::helper('payline')->__('Number'),
            'index'     => 'number',
			'filter' => false,
            'type'		=> 'text'
        ));

		$this->addColumn('point_of_sell', array(
			'header'    => Mage::helper('payline')->__('Point Of Sell'),
			'index'     => 'point_of_sell',
			'type'		=> 'text',
            'filter'    => false
        ));
		 
		 $this->addColumn('is_primary', array(
            'header'    => Mage::helper('payline')->__('Primary'),
            'index'     => 'is_primary',
            'type'    => 'options',
			 'filter' => false,
			'options' => array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-'))
        ));
		 
		 $this->addColumn('is_secondary', array(
            'header'    => Mage::helper('payline')->__('Secondary'),
            'index'     => 'is_secondary',
            'type'    => 'options',
			 'filter' => false,
			'options' => array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-'))
        ));

        $this->addColumn('is_included_wallet_list', array(
            'header'    => Mage::helper('payline')->__('Wallet'),
            'index'     => 'is_included_wallet_list',
            'type'    => 'options',
            'filter' => false,
            'options' => array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-'))
        ));
        $this->setDefaultSort('point_of_sell', 'desc');
        $this->setDefaultDir('ASC');

        return parent::_prepareColumns();
    }
    
 
	
	protected function _preparePointOfSell() {
		$pointOfSells = Mage::getModel('payline/contract')->getCollection()
							->getSelect()
							->reset(Zend_Db_Select::COLUMNS)
							->columns('point_of_sell', 'main_table')
							->distinct();
		$result = $pointOfSells->query()->fetchAll();

		$res = array();
		foreach ($result as $p) {
			$res[$p['point_of_sell']] = $p['point_of_sell'];
		}
		
		return $res;		
	}
	
	protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('contract');

        $this->getMassactionBlock()->addItem('walletList', array(
            'label' => Mage::helper('payline')->__('Subscribe to wallet'),
            'url'   => $this->getUrl('*/*/massWallet', array('_current'=>true)),
            'additional' => array(
                'visibility'    => array(
                    'name'      => 'walletList',
                    'type'      => 'select',
                    'class'     => 'required-entry',
                    'label'     => Mage::helper('payline')->__('Set'),
                    'values'    => array(
                        1 => 'On',
                        0 => 'Off'
                    )
                )
            )
        ) );

        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('payline')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('catalog')->__('Status'),
                         'values' => array(0 => Mage::helper('payline')->__('Primary'), 
										   1 => Mage::helper('payline')->__('Secondary'),
										   2 => Mage::helper('payline')->__('Primary') . ' & ' . Mage::helper('payline')->__('Secondary'),
										   3 => Mage::helper('payline')->__('Nothing'))
                     )
             )
        ));

       

        return $this;
    }
	
	public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
	
	public function getRowUrl($row)
    {
		
	}

}
