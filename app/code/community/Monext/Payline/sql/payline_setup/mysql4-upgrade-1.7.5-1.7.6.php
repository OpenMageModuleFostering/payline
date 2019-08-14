<?php
/** Add is_included_wallet_list column to payline_contract & payline_contract_status tables.
 *
 * Used to know if the contract can be included in contractNumberWalletList params when $paylineSDK->doWebPayment() and
 * $paylineSDK->createWebWallet() are called. */

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$connection->addColumn( $this->getTable('payline/contract'), "is_included_wallet_list",
    'tinyint(1) NOT NULL default 0' );
$connection->addColumn( $this->getTable('payline/contract_status'), "is_included_wallet_list",
    'tinyint(1) NOT NULL default 0' );

$installer->endSetup();