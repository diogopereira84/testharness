<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Service;

use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ProductProcessor
{
    private const ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE = 'allowed_customer_groups';

    /**
     * @var array<int, bool>
     */
    private array $updatedProductIds = [];

    public function __construct(
        protected LoggerInterface $logger,
        protected ProductRepositoryInterface $productRepository,
        protected AllowedCustomerGroupsService $allowedCustomerGroupsService,
        protected ConfigInterface $ondemandConfig
    ) {}

    public function process(int $productId): void
    {
        if (isset($this->updatedProductIds[$productId])) {
            return;
        }

        try {
            $product = $this->productRepository->getById($productId, false, 0, true);
            $categoryIds = $product->getCategoryIds();
            $ondemandB2bCategory = $this->ondemandConfig->getB2bPrintProductsCategory();
            if ($categoryIds && in_array($ondemandB2bCategory, $categoryIds)) {
                $allowedValue = '-1';
            } else {
                $allowed = $this->allowedCustomerGroupsService->getAllowedCustomerGroupsFromCategories($categoryIds);
                $allowedValue = implode(',', $allowed);
            }

            if ($product->getData(self::ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE) === $allowedValue) {
                $this->updatedProductIds[$productId] = true;
                return;
            }

            $this->allowedCustomerGroupsService->updateAttributes(
                [$productId],
                $allowedValue
            );
            $this->updatedProductIds[$productId] = true;
        } catch (LocalizedException $e) {
            $this->logger->critical(
                sprintf(__METHOD__ . ':' . __LINE__ . ' Error processing product ID %d: %s', $productId, $e->getMessage()),
                ['exception' => $e]
            );
        }
    }
}
