<?php
$installer=$this;
$installer->startSetup();

// create table to storeweb payment token
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('payline_product_categories')};
CREATE TABLE {$this->getTable('payline_product_categories')} (
  `id` int(10) unsigned NOT NULL auto_increment,
  `store_category_id` int(10) NOT NULL,
  `store_category_label` varchar(255),
  `payline_category_id` int(10),
  `payline_category_label` varchar(255),	
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='mapping between store and Payline product categories';

");	

$installer->endSetup();