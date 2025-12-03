<?php

namespace Fedex\CatalogMigration\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class Subscriber implements SubscriberInterface
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Subscriber constructor.
     * @param CatalogMigrationHelper $catalogMigrationHelper
     * @param CategoryProcessor $categoryProcessor
     * @param LoggerInterface $logger
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected CatalogMigrationHelper $catalogMigrationHelper,
        protected CategoryProcessor $categoryProcessor,
        protected LoggerInterface $logger,
        protected CategoryFactory $categoryFactory,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function processMessage(MessageInterface $message)
    {
        $migrationCatalogData = $message->getMessage();

        $migrationCatalogDataDecoded = json_decode($migrationCatalogData, true);
        $lastMigrationProcessId = $migrationCatalogDataDecoded['lastMigrationProcessId'];
        try {
            $this->catalogMigrationHelper->updateCatalogMigrationQueueStatus(
                $lastMigrationProcessId,
                static::STATUS_PROCESSING
            );

            $categoryPath = $migrationCatalogDataDecoded['category_path'];
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Category Path under migration subscriber: ' . $categoryPath);
            $categoryIds = $this->categoryProcessor->upsertCategories($categoryPath, ',');

            // update is anchor property with category
            $this->updateCategory($categoryIds, $lastMigrationProcessId);

            $this->catalogMigrationHelper->createProductCreateUpdateQueue($migrationCatalogDataDecoded, $categoryIds);

            // Update status to completed.
            $this->catalogMigrationHelper->updateCatalogMigrationQueueStatus(
                $lastMigrationProcessId,
                static::STATUS_COMPLETED
            );
        } catch (\Exception $e) {
            $this->catalogMigrationHelper->updateCatalogMigrationQueueStatus(
                $lastMigrationProcessId,
                static::STATUS_FAILED
            );
            $this->logger->error(
                __METHOD__.':'.__LINE__.
                ' Error with migration process queue creation with sync queue id ' .
                $lastMigrationProcessId .' is: ' . var_export($e->getMessage(), true)
                . ' for category path: ' .  $categoryPath
            );
        }
    }

    /**
     * Update Category
     * @param array $categoryIds
     * @param int $lastMigrationProcessId
     */
    public function updateCategory($categoryIds, $lastMigrationProcessId)
    {
        try {

            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryFactory->create()->load($categoryId);
                $category->setCustomAttributes([
                    'is_anchor' => 0
                ]);
                $this->categoryRepositoryInterface->save($category);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.
                ' Error with category update with sync queue id ' .
                $lastMigrationProcessId .' is: ' . var_export($e->getMessage(), true));
        }
    }
}
