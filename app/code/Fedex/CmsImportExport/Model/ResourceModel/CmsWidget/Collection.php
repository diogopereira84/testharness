<?php
namespace Fedex\CmsImportExport\Model\ResourceModel\CmsWidget;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Fedex\CmsImportExport\Model\CmsWidget as Model;
use Fedex\CmsImportExport\Model\ResourceModel\CmsWidget as ResourceModel;

/**
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model

     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param AdapterInterface $connection
     * @param AbstractDb $resource
     */

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        private \Magento\Store\Model\StoreManagerInterface|StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->_init(Model::class, ResourceModel::class);
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Define main table
     *
     * @return null
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['secondTable' => $this->getTable('widget_instance_page')],
            'main_table.instance_id = secondTable.instance_id',
            ['*']
        )->joinLeft(
            ['thirdTable' => $this->getTable('theme')],
            'main_table.theme_id = thirdTable.theme_id',
            ['*']
        )->joinLeft(
            ['fourthTable' => $this->getTable('store')],
            'main_table.store_ids = fourthTable.store_id',
            ['*']
        );
    }
}
