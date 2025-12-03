<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Service;

use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class CategoryProcessor
{
    private const ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE = 'allowed_customer_groups';

    /**
     * @var array<int, bool>
     */
    private array $updatedProductIds = [];

    /**
     * @param LoggerInterface $logger
     * @param ProductCollectionFactory $productCollectionFactory
     * @param AllowedCustomerGroupsService $allowedCustomerGroupsService
     * @param ConfigInterface $ondemandConfig
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected ProductCollectionFactory $productCollectionFactory,
        protected AllowedCustomerGroupsService $allowedCustomerGroupsService,
        protected ConfigInterface $ondemandConfig
    ) {}

    public function process(int $categoryId): void
    {
        try {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('category_ids');
            $productCollection->addAttributeToSelect(self::ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE);
            $productCollection->addCategoriesFilter(['in' => [$categoryId]]);
            $productCollection->addStoreFilter(0);

            foreach ($productCollection as $product) {
                if (isset($this->updatedProductIds[$product->getId()])) {
                    continue;
                }

                $categoryIds = $product->getCategoryIds();
                $ondemandB2bCategory = $this->ondemandConfig->getB2bPrintProductsCategory();
                if ($categoryIds && in_array($ondemandB2bCategory, $categoryIds)) {
                    $allowedValue = '-1';
                } else {
                    $allowed = $this->allowedCustomerGroupsService->getAllowedCustomerGroupsFromCategories($categoryIds);
                    $allowedValue = implode(',', $allowed);
                }

                if ($product->getData(self::ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE) === $allowedValue) {
                    $this->updatedProductIds[$product->getId()] = true;
                    continue;
                }

                $this->allowedCustomerGroupsService->updateAttributes(
                    [$product->getId()],
                    $allowedValue
                );
                $this->updatedProductIds[$product->getId()] = true;
            }
        } catch (LocalizedException $e) {
            $this->logger->critical(
                sprintf(__METHOD__ . ':' . __LINE__ . ' Error processing category ID %d: %s', $categoryId, $e->getMessage()),
                ['exception' => $e]
            );
        }
    }
}

