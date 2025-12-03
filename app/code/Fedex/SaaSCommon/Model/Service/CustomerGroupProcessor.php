<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
class CustomerGroupProcessor
{
    public function __construct(
        protected LoggerInterface $logger,
        protected AllowedCustomerGroupsService $allowedCustomerGroupsService,
        protected CategoryProcessor $categoryProcessor
    ) {}

    public function process(int $customerGroupId): void
    {
        try {
            $categoryIds = $this->allowedCustomerGroupsService->getAllowedCategoriesFromCustomerGroup($customerGroupId);

            if (empty($categoryIds)) {
                return;
            }

            foreach ($categoryIds as $categoryId) {
                $this->categoryProcessor->process((int)$categoryId);
            }
        } catch (LocalizedException $e) {
            $this->logger->critical(
                sprintf(__METHOD__ . ':' . __LINE__ . ' Error processing customer group ID %d: %s', $customerGroupId, $e->getMessage()),
                ['exception' => $e]
            );
        }
    }
}

