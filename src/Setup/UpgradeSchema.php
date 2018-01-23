<?php

namespace Mediact\Smile\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Add column to the customer and order table for Smile.io
     * synchronisation status
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        /** @var string $table */
        $tableName = $setup->getTable('customer_entity');
        $setup->getConnection()->addColumn(
            $tableName,
            'smileio_synchronised_at',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true,
                'default' => null,
                'comment' => 'Synchronised with Smile.io'
            ]
        );

        /** @var string $table */
        $tableName = $setup->getTable('sales_order');
        $setup->getConnection()->addColumn(
            $tableName,
            'smileio_synchronised_at',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true,
                'default' => null,
                'comment' => 'Synchronised with Smile.io'
            ]
        );

        $setup->endSetup();
    }
}
