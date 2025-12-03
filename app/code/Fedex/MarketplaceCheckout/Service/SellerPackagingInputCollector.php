<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Service;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use stdClass;

class SellerPackagingInputCollector
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly QuoteHelper $quoteHelper
    ) {}

    /**
     * @param $entity
     * @return array
     * @throws NoSuchEntityException
     */
    public function collect($entity): array
    {
        $shops = [];
        $shopCache = [];

        if ($entity instanceof Quote && !$this->quoteHelper->isMiraklQuote($entity)) {
            return $shops;
        }

        $items = $this->getItemswithOfferId($entity);

        foreach ($items as $item) {
            if (!$this->isPunchoutEnabled($item)) {
                continue;
            }

            $shopId = (int) $item->getMiraklShopId();

            if (!isset($shopCache[$shopId])) {
                $shopCache[$shopId] = $this->shopRepository->getById($shopId);
            }

            $shop = $shopCache[$shopId];

            if (!$this->isFreightEnabled($shop)) {
                continue;
            }
            $packagingData = $this->getPackagingData($item);

            if ($shop->getSellerPackageApiEndpoint() && !empty($packagingData)) {
                $shops[$shopId][] = [
                    'seller' => $shop,
                    'packagingData' => $packagingData
                ];
            }
        }

        return $shops;
    }

    /**
     * @param $entity
     * @return array
     */
   public function  getItemswithOfferId($entity): array
   {
       return  array_filter($entity->getAllItems(),fn($item) => $item->getData('mirakl_offer_id'));
    }

    /**
     * @param $shop
     * @return bool
     */
    private function isFreightEnabled($shop): bool
    {
        $info = $shop->getShippingRateOption();
        return !empty($info['freight_enabled']);
    }

    /**
     * @param $item
     * @return bool
     */
    public function isPunchoutEnabled($item): bool
    {
        $additionalInfo = $this->decodeAdditionalData($item);
        return isset($additionalInfo->punchout_enabled) && (bool)$additionalInfo->punchout_enabled;
    }

    /**
     * @param $item
     * @return mixed
     */
    public function getPackagingData($item): mixed
    {
        $additionalInfo = $this->decodeAdditionalData($item);
        return $additionalInfo->packaging_data ?? null;
    }

    /**
     * @param $item
     * @return stdClass|null
     */
    private function decodeAdditionalData($item): ?stdClass
    {
        $data = $item->getAdditionalData();
        if (!$data) {
            return null;
        }

        $decoded = json_decode($data);
        return $decoded instanceof stdClass ? $decoded : null;
    }
}
