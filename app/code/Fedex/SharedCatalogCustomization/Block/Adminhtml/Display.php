<?php
namespace Fedex\SharedCatalogCustomization\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\CollectionFactory as
CleanupCollectionFactory;

class Display extends \Magento\Backend\Block\Template
{

    /**
     * Display constructor param
     *
     * @param Context $context
     * @param CollectionFactory $catalogSyncQueueProcessCollectionFactory
     * @param CleanupCollectionFactory $cleanupCollectionFactory
     * @param UrlInterface $urlBuilder
     * @param ResourceConnection $resourceConnection
     * @param Data $data
     *
     * @return void
     */
    public function __construct(
        Context  $context,
        protected CollectionFactory $catalogSyncQueueProcessCollectionFactory,
        protected CleanupCollectionFactory $cleanupCollectionFactory,
        protected UrlInterface $urlBuilder,
        private ResourceConnection $resourceConnection,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     *  Get Queue Items status
     *
     * @return array $templateVars
     */
    public function getCatalogSyncDetails(): array
    {
        $templateVars = [];

        $id = $this->getRequest()->getParam('id');
        $newActionTypeCount = 0;
        $updateActionTypeCount = 0;
        $deleteActionTypeCount = 0;
        $failedCount = 0;

        $newActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
            ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $id])
            ->addFieldToFilter('catalog_type', ['eq' => 'product'])
            ->addFieldToFilter('status', ['eq' => 'completed'])
            ->addFieldToFilter('action_type', ['eq' => 'new'])
            ->getSize();

        $updateActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
            ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $id])
            ->addFieldToFilter('catalog_type', ['eq' => 'product'])
            ->addFieldToFilter('status', ['eq' => 'completed'])
            ->addFieldToFilter('action_type', ['eq' => 'update'])
            ->getSize();

        $deleteActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
            ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $id])
            ->addFieldToFilter('catalog_type', ['eq' => 'product'])
            ->addFieldToFilter('status', ['eq' => 'completed'])
            ->addFieldToFilter('action_type', ['eq' => 'delete'])
            ->getSize();

        $failedCount = $this->catalogSyncQueueProcessCollectionFactory->create()
            ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $id])
            ->addFieldToFilter('catalog_type', ['eq' => 'product'])
            ->addFieldToFilter('status', ['eq' => 'failed'])
            ->getSize();

        $templateVars = [
            'back_url' => $this->getBackButtonUrl(),
            'newItem' => $newActionTypeCount,
            'updateItem' => $updateActionTypeCount,
            'deleteItem' => $deleteActionTypeCount,
            'failedItem' => $failedCount,
        ];
        
        return $templateVars;
    }

    /**
     *  Get Queue Items status
     *
     * @return array $templateVars
     */
    public function getCatalogSyncQueueDetails(): array
    {
        $templateVars = [];

        $id = $this->getRequest()->getParam('id');
        $newActionTypeCount = 0;
        $updateActionTypeCount = 0;
        $deleteActionTypeCount = 0;
        $failedCount = 0;

        $newActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
            ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $id])
            ->addFieldToFilter('catalog_type', ['eq' => 'product'])
            ->addFieldToFilter('status', ['eq' => 'completed'])
            ->addFieldToFilter('action_type', ['eq' => 'new'])
            ->getSize();

        $updateActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
            ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $id])
            ->addFieldToFilter('catalog_type', ['eq' => 'product'])
            ->addFieldToFilter('status', ['eq' => 'completed'])
            ->addFieldToFilter('action_type', ['eq' => 'update'])
            ->getSize();

        $deleteActionTypeCount = $this->cleanupCollectionFactory->create()
            ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $id])
            ->addFieldToFilter('catalog_type', ['eq' => 'product'])
            ->addFieldToFilter('status', ['eq' => 'completed'])
            ->getSize();

        $connection = $this->resourceConnection->getConnection();

        $failedCollectionQuery = 'SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="failed") UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="failed")';

        $failedCount = count($connection->fetchCol($failedCollectionQuery));

        $templateVars = [
            'back_url' => $this->getBackButtonUrl(),
            'newItem' => $newActionTypeCount,
            'updateItem' => $updateActionTypeCount,
            'deleteItem' => $deleteActionTypeCount,
            'failedItem' => $failedCount,
        ];
        
        return $templateVars;
    }

    /**
     * Get Back button Url
     *
     * @return String $backUrl
     */
    public function getBackButtonUrl()
    {
        $backUrl = $this->urlBuilder->getUrl('shared_catalog_customization/grid/index');
        return $backUrl;
    }
}
