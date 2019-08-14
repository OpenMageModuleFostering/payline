<?php
/* @var $this Monext_Payline_Model_Resource_Eav_Mysql4_Setup */
$installer=$this;
$installer->startSetup();
//Create the wallet id if doesn't exists
$attribute = $installer->getAttribute('customer', 'wallet_id');
if (empty($attribute['attribute_id'])) {

    $installer->addAttribute('customer', 'wallet_id',
        array(
            'type'              => 'varchar',
            'backend'           => '',
            'frontend'          => '',
            'label'             => 'Payline User ID',
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
            'unique'            => true,
            'position'          => 1 
        )
    );
}

//Create default static CMS blocks for registration to one click payment
$connection=$installer->getConnection();
if (!$connection->fetchOne("SELECT block_id FROM ".$installer->getTable('cms_block')." WHERE `identifier`='payline_register-oneclick_catalog'")) {
    $connection->insert($installer->getTable('cms/block'), array(
        'title'             => 'Register to one click checkout',
        'identifier'        => 'payline_register-oneclick_catalog',
        'content'           => "<p>Save time by registering now to our one click checkout service</p>",
        'creation_time'     => now(),
        'update_time'       => now(),
        'is_active'         => 1,
    ));
    
    $connection->insert($installer->getTable('cms/block_store'), array(
        'block_id'   => $connection->lastInsertId(),
        'store_id'  => 0
    ));
}
if (!$connection->fetchOne("SELECT block_id FROM ".$installer->getTable('cms_block')." WHERE `identifier`='payline_register-oneclick_customeraccount'")) {
    $connection->insert($installer->getTable('cms/block'), array(
        'title'             => 'Register to one click checkout',
        'identifier'        => 'payline_register-oneclick_customeraccount',
        'content'           => "<p>Save time by registering now to our one click checkout service</p>",
        'creation_time'     => now(),
        'update_time'       => now(),
        'is_active'         => 1,
    ));
    
    $connection->insert($installer->getTable('cms/block_store'), array(
        'block_id'   => $connection->lastInsertId(),
        'store_id'  => 0
    ));
}
$installer->endSetup();