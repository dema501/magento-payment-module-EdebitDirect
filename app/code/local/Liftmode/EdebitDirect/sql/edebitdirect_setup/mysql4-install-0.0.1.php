<?php
/**
 *
 * @category   Mage
 * @package    Liftmode_EdebitDirect
 * @copyright  Copyright (c)  LiftMode (Synaptent LLC).
 */

$installer = $this;

//$installer->startSetup();

$salesFlatQuotePaymentTable = $installer->getTable('sales_flat_quote_payment');
$installer->getConnection()->addColumn(
    $salesFlatQuotePaymentTable,
    'routing_number',
    "VARCHAR(10)"
);

$installer->getConnection()->addColumn(
    $salesFlatQuotePaymentTable,
    'account_number',
    "VARCHAR(18)"
);

$salesFlatOrderPaymentTable = $installer->getTable('sales_flat_order_payment');
$installer->getConnection()->addColumn(
    $salesFlatOrderPaymentTable,
    'routing_number',
    "VARCHAR(10)"
);

$installer->getConnection()->addColumn(
    $salesFlatOrderPaymentTable,
    'account_number',
    "VARCHAR(18)"
);

$installer->endSetup();
