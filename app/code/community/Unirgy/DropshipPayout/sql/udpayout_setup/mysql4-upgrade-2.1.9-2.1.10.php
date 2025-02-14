<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($this->getTable('udpayout/payout'), 'total_payment', 'decimal(12,4)');
$conn->addColumn($this->getTable('udpayout/payout'), 'total_invoice', 'decimal(12,4)');
$conn->addColumn($this->getTable('udpayout/payout'), 'payment_due', 'decimal(12,4)');
$conn->addColumn($this->getTable('udpayout/payout'), 'invoice_due', 'decimal(12,4)');
$conn->addColumn($this->getTable('udpayout/payout'), 'payment_paid', 'decimal(12,4)');
$conn->addColumn($this->getTable('udpayout/payout'), 'invoice_paid', 'decimal(12,4)');

$installer->endSetup();
