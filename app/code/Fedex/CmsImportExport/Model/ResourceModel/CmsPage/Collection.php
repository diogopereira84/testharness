<?php
namespace Fedex\CmsImportExport\Model\ResourceModel\CmsPage;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Fedex\CmsImportExport\Model\CmsPage as Model;
use Fedex\CmsImportExport\Model\ResourceModel\CmsPage as ResourceModel;

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
        private \Magento\Store\Model\StoreManagerInterface $storeManager,
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
            ['secondTable' => $this->getTable('cms_page_store')],
            'main_table.page_id = secondTable.row_id',
            ['row_id','store_id']
        )->joinLeft(
            ['thirdTable' => $this->getTable('sequence_cms_page')],
            'main_table.page_id = thirdTable.sequence_value',
            ['sequence_value']
        )->joinLeft(
            ['fourthTable' => $this->getTable('store')],
            'secondTable.store_id = fourthTable.store_id',
            ['store_id','code','website_id','group_id','name','sort_order']
        );
    }
}
