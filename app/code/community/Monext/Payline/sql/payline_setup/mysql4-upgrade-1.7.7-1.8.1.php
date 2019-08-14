<?php
$installer=$this;
$installer->startSetup();

// create table to storeweb payment token
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('payline_token')};
CREATE TABLE {$this->getTable('payline_token')} (
  `id` int(10) unsigned NOT NULL auto_increment,
  `order_id` varchar(50) NOT NULL default '',
  `token` varchar(255) NOT NULL default '',
  `result_code` varchar(5) NOT NULL default '',
  `transaction_id` varchar(50),
  `status` tinyint(1) NOT NULL default 0,
  `date_create` datetime,
  `date_update` datetime,	
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='payline web tokens';

");	

$installer->endSetup();