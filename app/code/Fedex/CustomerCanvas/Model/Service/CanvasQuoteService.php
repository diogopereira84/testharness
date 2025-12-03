<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Magento\Quote\Model\Quote;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Psr\Log\LoggerInterface;

class CanvasQuoteService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder $filterBuilder,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Check if the quote contains any product with 'is_customer_canvas' = 1
     *
     * @param Quote $quote
     * @return bool
     */
    public function quoteHasCanvasProduct(Quote $quote): bool
    {
        $productIds = array_map(
            fn($item) => $item->getProduct()->getId(),
            $quote->getAllVisibleItems()
        );

        if (empty($productIds)) {
            return false;
        }

        $idFilter = $this->filterBuilder
            ->setField('entity_id')
            ->setConditionType('in')
            ->setValue($productIds)
            ->create();

        $canvasFilter = $this->filterBuilder
            ->setField('is_customer_canvas')
            ->setConditionType('eq')
            ->setValue(1)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$idFilter, $canvasFilter])
            ->setPageSize(1)
            ->create();

        $result = $this->productRepository->getList($searchCriteria);

        return (bool) $result->getTotalCount();
    }

    /**
     * @param Quote $quote
     * @return string|null
     */
    public function getVendorOptionFromCanvasItem(Quote $quote): ?string
    {
        try {
            $canvasItem = null;

            foreach ($quote->getAllVisibleItems() as $item) {
                if ($this->isCanvasItem($item)) {
                    $canvasItem = $item;
                    break;
                }
            }

            if (!$canvasItem) {
                return null;
            }

            $infoBuyRequest = $canvasItem->getOptionByCode('info_buyRequest');
            if (!$infoBuyRequest || !$infoBuyRequest->getValue()) {
                return null;
            }

            $infoBuyRequestValue = json_decode($infoBuyRequest->getValue());

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ . ' Malformed JSON in info_buyRequest: ' . json_last_error_msg()
                );
                return null;
            }

            if (!is_object($infoBuyRequestValue)) {
                return null;
            }

            $productConfig = $infoBuyRequestValue->productConfig ?? null;
            if (!is_object($productConfig)) {
                return null;
            }

            return (is_object($productConfig->vendorOptions) && isset($productConfig->vendorOptions->userId))
                ? $productConfig->vendorOptions->userId
                : null;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Unexpected error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param Quote $quote
     * @param $newUserId
     * @return void
     */
    public function updateDyesubVendorOptions(Quote $quote, $newUserId)
    {
        try {
            $items = $quote->getAllItems();

            foreach ($items as $item) {
                try {
                    $product = $item->getProduct();

                    if ($product && $product->getData('is_customer_canvas')) {
                        $option = $item->getOptionByCode('info_buyRequest');

                        if ($option) {
                            $value = $option->getValue();
                            $decodedValue = json_decode($value);

                            if (isset($decodedValue->productConfig->vendorOptions->userId)) {
                                $decodedValue->productConfig->vendorOptions->userId = $newUserId;
                                $option->setValue(json_encode($decodedValue));
                                $option->save();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ .' Unexpected error: ' . $e->getMessage());
                }
            }

             $quote->save();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .' Unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * @param $item
     * @return bool
     */
    private function isCanvasItem($item): bool
    {
        try {
            return (bool) $item->getProduct()->getData('is_customer_canvas');
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Unexpected error: ' . $e->getMessage());
            return false;
        }
    }
}
