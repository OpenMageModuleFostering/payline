<?php
/** Add is_included_wallet_list column to payline_contract & payline_contract_status tables.
 *
 * Used to know if the contract is a secure one and should be used in 3DS cinematic. */

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$connection->addColumn( $this->getTable('payline/contract'), "is_secure",
    'tinyint(1) NOT NULL default 0' );

$connection->addColumn( $this->getTable('payline/contract_status'), "is_secure",
    'tinyint(1) NOT NULL default 0' );

$connection->addColumn( $this->getTable('sales/quote_payment'), "card_token_pan",
        'varchar(255)' );

$connection->addColumn( $this->getTable('sales/order_payment'), "card_token_pan",
        'varchar(255)' );


$installer->endSetup();