<?php
/* @var $this Monext_Payline_Model_Resource_Eav_Mysql4_Setup */
$installer=$this;
$installer->startSetup();
//Create the wallet contract number if doesn't exists
$attribute = $installer->getAttribute('customer', 'wallet_contract_number');
if (empty($attribute['attribute_id'])) {

    $installer->addAttribute('customer', 'wallet_contract_number',
        array(
            'type'              => 'varchar',
            'backend'           => '',
            'frontend'          => '',
            'label'             => 'Contract number used to create wallet',
            'input'             => 'text',
            'class'             => '',
            'source'            => '',
            'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
            'visible'           => false,
            'required'          => false,
            'user_defined'      => false,
            'default'           => '',
            'searchable'        => false,
            'filterable'        => false,
            'comparable'        => false,
            'visible_on_front'  => false,
            'visible_in_advanced_search' => false,
            'unique'            => false,
            'position'          => 1 
        )
    );
}

//Create payline contracts table

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('payline_contract')};
CREATE TABLE {$this->getTable('payline_contract')} (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `number` varchar(255) NOT NULL default '',
  `point_of_sell` varchar(255) NOT NULL default '',
  `contract_type` varchar(100) NOT NULL default '',
  `is_primary` tinyint(1) NOT NULL default 0,
  `is_secondary` tinyint(1) NOT NULL default 0,
  `contract_date` datetime NOT NULL default '0000-00-00 00:00:00',	
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='payline contracts';

");

// create payline contracts status by store

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('payline_contract_status')};
create table {$this->getTable('payline_contract_status')} (
	`id` int(10) unsigned NOT NULL auto_increment, 
	`contract_id` int(10) unsigned not null, 
	`store_id` smallint(5) unsigned,
	`website_id` smallint(5) unsigned,
	`is_primary` tinyint(1) NOT NULL default 0, 
	`is_secondary` tinyint(1) NOT NULL default 0, 
	primary key (`id`), 
	foreign key (`contract_id`) references {$this->getTable('payline_contract')}(id) on delete cascade, 
	foreign key (`store_id`) references {$this->getTable('core_store')}(store_id),
	foreign key (`website_id`) references {$this->getTable('core_website')}(website_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='payline contract status by store';
");
	
//create table to store payline nx fees
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('payline_nx_fees')};
create table {$this->getTable('payline_nx_fees')} (
	`id` int(10) unsigned NOT NULL auto_increment, 
	`quote_id` int(10) unsigned, 
	`order_id` int(10) unsigned,
	`invoice_id` int(10) unsigned,
	`amount` decimal(12,4),
	`base_amount` decimal(12,4), 
	primary key (`id`), 
	foreign key (`order_id`) references {$this->getTable('sales_flat_order')}(entity_id), 
	foreign key (`quote_id`) references {$this->getTable('sales_flat_quote')}(entity_id),
	foreign key (`invoice_id`) references {$this->getTable('sales_flat_invoice')}(entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='payline nx fees';
");	


$installer->endSetup();