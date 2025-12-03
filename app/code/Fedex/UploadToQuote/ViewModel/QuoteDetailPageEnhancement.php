<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\Helper\LocationApiHelper;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Fedex\Shipto\Helper\Data as ShiptoData;

/**
 * QuoteDetailPageEnhancement ViewModel
 */
class QuoteDetailPageEnhancement implements ArgumentInterface
{
    public const CREATED = "created";
    public const PRODUCTION = "production";
    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param CartRepositoryInterface $quoteRepository
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param RegionFactory $regionFactory
     * @param AdminConfigHelper $configHelper
     * @param LocationApiHelper $apiHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param ProducingAddressFactory $producingAddressFactory
     * @param ShiptoData $shiptoData
     */
    public function __construct(
        private RequestInterface $request,
        private CartRepositoryInterface $quoteRepository,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private RegionFactory $regionFactory,
        private AdminConfigHelper $configHelper,
        private LocationApiHelper $apiHelper,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private OrderRepositoryInterface $orderRepository,
        private NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        private ProducingAddressFactory $producingAddressFactory,
        private ShiptoData $shiptoData
    )
    {
    }

    /**
     * Get Quote ID
     *
     * @return int|null
     */
    public function getQuoteId(): ?int
    {
        return (int)$this->request->getParam('quote_id') ?: null;
    }

    /**
     * Get Created By Location ID
     *
     * @return string|null
     */
    public function getCreatedByLocationId()
    {
        $quoteId = $this->getQuoteId();
        if (!$quoteId) {
            return null;
        }

        try {
            $quote = $this->quoteRepository->get($quoteId);
            if ($quote->getQuoteMgntLocationCode()){
                $data = [
                    "zipcode" => $quote->getQuoteMgntLocationCode(),
                    "isStoreSearchEnabled" => true,
                    "is_restricted_product_location_toggle" => '1'
                ];
                $shiptoDataValue = $this->shiptoData->getAllLocationsByZip($data, false);
                if (is_array($shiptoDataValue) && count($shiptoDataValue) > 0) {
                    $locationValue = $shiptoDataValue[0];
                    if (isset($locationValue['locationId']) && $locationValue['locationId'] != "") {
                        return $locationValue['locationId'];
                    }
                }
            } else{
                return $this->getIntegrationLocationId($quoteId);
            }
        } catch (\Exception $e) {
            return $this->getFallbackLocationId($quoteId, self::CREATED);
        }
    }

    /**
     * Get Producing Location ID
     *
     * @return string|null
     */
    public function getProducingLocationId(): ?string
    {
        $quoteId = $this->getQuoteId();
        if (!$quoteId) {
            return null;
        }

        $quoteData = $this->negotiableQuoteRepository->getById($quoteId);
        if ($quoteData->getStatus() === 'ordered') {
            return $this->getFallbackLocationId($quoteId, self::PRODUCTION);
        }

        return null;
    }

    /**
     * Get quote repository
     *
     * @return string|null
     */
    public function getResponsibleLocationId(): ?string
    {
        $quoteId = $this->getQuoteId();
        if (!$quoteId) {
            return null;
        }
        $quoteData = $this->negotiableQuoteRepository->getById($quoteId);
        if ($quoteData->getStatus() === 'ordered') {
            $order = $this->getOrderByQuoteId($quoteId);
            if (!$order) {
                return null;
            }
            $orderId = $order->getId();

            $orderProducingAddress = $this->producingAddressFactory->create()->load($orderId, 'order_id');
            if (!$orderProducingAddress->getId()) {
                return null;
            }
            $additionalData = json_decode($orderProducingAddress->getAdditionalData(), true);
            return $additionalData['responsible_location_id'] ?? null;
        }
      return null;
    }

    /**
     * Check if the quote is an EPRO quote
     *
     * @return bool
     */
    public function isEproQuote(): bool
    {
        $quoteId = $this->getQuoteId();
        if (!$quoteId) {
            return false;
        }

        $quote = $this->quoteRepository->get($quoteId);
        return (bool)$quote->getIsEproQuote();
    }

    /**
     * Get order by quote ID
     *
     * @param int $quoteId
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    public function getOrderByQuoteId(int $quoteId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('quote_id', $quoteId, 'eq')
            ->create();

        $orderList = $this->orderRepository->getList($searchCriteria);
        $orders = $orderList->getItems();
        return !empty($orders) ? reset($orders) : null;
    }

    /**
     * Get integration location ID
     *
     * @param int $quoteId
     * @return string|null
     */
    private function getIntegrationLocationId(int $quoteId): ?string
    {
        $integration = $this->cartIntegrationRepository->getByQuoteId($quoteId);
        return $integration ? $integration->getLocationId() : null;
    }

    /**
     * Fallback to determine location ID
     *
     * @param int $quoteId
     * @return string|null
     */
    private function getFallbackLocationId(int $quoteId, $type): ?string
    {
        $quote = $this->quoteRepository->get($quoteId);
        $billingAddress = $quote->getBillingAddress();
        $createdByLocationId = $quote->getCreatedByLocationId();
        if(!empty($createdByLocationId && $type==self::CREATED)){
            $regionCode = $billingAddress && $billingAddress->getRegionId()
            ? $this->regionFactory->create()->load($billingAddress->getRegionId())->getCode()
            : "";
            $stateCode = $createdByLocationId ?? $regionCode;
        }else{
            $stateCode = $billingAddress && $billingAddress->getRegionId()
            ? $this->regionFactory->create()->load($billingAddress->getRegionId())->getCode()
            : "";
        }
        $countryCode = $billingAddress ? $billingAddress->getCountryId() : '';

        $postData = [
            'stateCode' => $stateCode,
            'countryCode' => $countryCode,
        ];
        $apiUrl = $this->configHelper->getUploadToQuoteConfigValue('location_search_api_url');
        $locationResp = $this->apiHelper->getHubCenterCodeByState($postData, $apiUrl);

        return !empty($locationResp['output']['search'][0]['locationId'])
            ? $locationResp['output']['search'][0]['locationId']
            : null;
    }
}
