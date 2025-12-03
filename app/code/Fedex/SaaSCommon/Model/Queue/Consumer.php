<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Queue;

use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Fedex\SaaSCommon\Model\Service\CategoryProcessor;
use Fedex\SaaSCommon\Model\Service\CustomerGroupProcessor;
use Fedex\SaaSCommon\Model\Service\ProductProcessor;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Group;
use Psr\Log\LoggerInterface;

class Consumer
{
    /**
     * @param LoggerInterface $logger
     * @param ProductProcessor $productProcessor
     * @param CategoryProcessor $categoryProcessor
     * @param CustomerGroupProcessor $customerGroupProcessor
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected ProductProcessor $productProcessor,
        protected CategoryProcessor $categoryProcessor,
        protected CustomerGroupProcessor $customerGroupProcessor
    ) {
    }

    /**
     * Process entity message from the queue.
     *
     * @param AllowedCustomerGroupsRequestInterface $request
     */
    public function process(AllowedCustomerGroupsRequestInterface $request): void
    {
        $entityId = $request->getEntityId();
        $entityType = $request->getEntityType();

        if (!$entityId || !$entityType) {
            return;
        }

        try {
            if ($entityType === Product::ENTITY) {
                $this->productProcessor->process($entityId);
                return;
            }

            if ($entityType === Category::ENTITY) {
                $this->categoryProcessor->process($entityId);
                return;
            }

            if ($entityType === Group::ENTITY) {
                $this->customerGroupProcessor->process($entityId);
                return;
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(__METHOD__ . ':' . __LINE__ . ' Error processing entity ID %d: %s', $entityId, $e->getMessage()),
                ['exception' => $e]
            );
        }
    }
}
