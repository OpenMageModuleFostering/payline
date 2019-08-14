<?php

class Monext_Payline_Block_Adminhtml_Manageproductcategories_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('paylineManageProductCategoriesGrid');
		$this->setUseAjax(true);
		$this->setDefaultLimit(50);
    }
	
	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
	

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('payline/productcategories')->getCollection();
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
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {		
        $this->addColumn('storecat', array(
            'header'    => Mage::helper('payline')->__('Store category'),
            'index'     => 'store_category_label',
			'filter' => false,
            'type'		=> 'text'
        ));
		
        $this->addColumn('paylinecat', array(
            'header'    => Mage::helper('payline')->__('Payline category'),
            'index'     => 'payline_category_label',
			'filter' => false,
            'type'		=> 'text'
        ));
		
        $this->addColumn('action',
             array(
             	'header'    => Mage::helper('sales')->__('Action'),
             	'width'     => '50px',
             	'type'      => 'action',
             	'getter'     => 'getId',
             	'actions'   => array(
             		array(
             			'caption' => Mage::helper('payline')->__('Assign'),
             			'url'     => array('base'=>'*/*/assign'),
             			'field'   => 'id',
             			'data-column' => 'action',
             		),
        			array(
        				'caption' => Mage::helper('payline')->__('Unassign'),
                     	'url'     => array('base'=>'*/*/unassign'),
                     	'field'   => 'id',
                     	'data-column' => 'action',
        			)
             	),
             	'filter'    => false,
             	'sortable'  => false,
             	'index'     => 'store_category_id',
             	'is_system' => true,
             )
        );
        
        $this->setDefaultSort('storecat', 'desc');
        $this->setDefaultDir('ASC');

        return parent::_prepareColumns();
    }
	
	public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
