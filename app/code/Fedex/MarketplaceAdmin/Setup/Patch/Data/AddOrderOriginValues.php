<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceAdmin
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Setup\Patch\Data;

use Magento\Framework\DB\Select;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Exception;
use Psr\Log\LoggerInterface;

class AddOrderOriginValues implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param OrderGridCollection $orderCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly OrderGridCollection      $orderCollectionFactory,
        private readonly LoggerInterface          $logger
    ) {
    }

    /**
     * Update flag column based on order origin.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $queryBase = $this->createBaseQueryCollection();
        try {
            $this->updateMarketplaceOrder($queryBase);
            $queryBase->reset('having');
            $this->updateMixedOrder($queryBase);
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Create base query collection.
     *
     * @return Select
     */
    public function createBaseQueryCollection(): Select
    {
        /** @var OrderGridCollection $collection */
        $select = clone $this->orderCollectionFactory->getSelect();
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->columns('main_table.entity_id');
        $columns = [
            'count_magento' => new \Zend_Db_Expr('SUM(IF(items.mirakl_offer_id IS NULL, 1, 0))'),
            'count_mirakl'  => new \Zend_Db_Expr('SUM(IF(items.mirakl_offer_id IS NOT NULL, 1, 0))'),
        ];
        $select->join(
            ['items' => $this->orderCollectionFactory->getTable('sales_order_item')],
            'main_table.entity_id = items.order_id AND items.parent_item_id IS NULL',
            $columns
        );
        $select->group('items.order_id');

        return $select;
    }

    /**
     * Set marketplace value into flag column based on order origin.
     *
     * @param $select
     * @return void
     */
    public function updateMarketplaceOrder($select): void
    {
        $select->having('count_magento = 0');
        $orderIds = $this->orderCollectionFactory->getConnection()->fetchCol($select);
        $this->orderCollectionFactory->getConnection()->update(
            $this->orderCollectionFactory->getTable('sales_order_grid'),
            ['flag' => 'marketplace'],
            ['entity_id IN (?)' => $orderIds]
        );
    }

    /**
     * Set mixed value into flag column based on order origin.
     *
     * @param $select
     * @return void
     */
    public function updateMixedOrder($select): void
    {
        $select->having('count_magento > 0 AND count_mirakl > 0');
        $orderIds = $this->orderCollectionFactory->getConnection()->fetchCol($select);
        $this->orderCollectionFactory->getConnection()->update(
            $this->orderCollectionFactory->getTable('sales_order_grid'),
            ['flag' => 'mixed'],
            ['entity_id IN (?)' => $orderIds]
        );
    }

    /**
     * @inheritdoc
     */
    public function revert(): void
    {
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
