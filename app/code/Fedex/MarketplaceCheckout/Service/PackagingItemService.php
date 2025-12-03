<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Service;

use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\MarketplaceRates\Helper\Data;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;

class PackagingItemService
{
    public function __construct(
        private readonly Session $session,
        private readonly Data $helper,
        private readonly CacheInterface $cache,
        private readonly Json $jsonSerializer,
        private readonly QuoteHelper $quoteHelper,
        private readonly PackagingApiClient $packagingApiClient,
        private readonly SellerPackagingInputCollector $inputCollector
    ) {}

    /**
     * @param bool $save
     * @param Order|null $order
     * @return array<int, array>
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPackagingItems(bool $save = false, ?Order $order = null): array
    {
        if (!$this->helper->isFreightShippingEnabled()) {
            return [];
        }

        $entity = $this->resolveEntity($order);

        if ($entity instanceof Quote && !$this->quoteHelper->isMiraklQuote($entity)) {
            return [];
        }

        $useCache = $this->shouldUseCache($entity, $save);
        $cacheKey = $this->getCacheKey((int)$entity->getId());

        if ($useCache) {
            $cached = $this->cache->load($cacheKey);
            if (!empty($cached) && $cached !== '[]') {
                return $this->jsonSerializer->unserialize($cached);
            }
        }

        $packagingResults = $this->buildPackagingRequests($entity);

        if ($useCache && !empty($packagingResults)) {
            $this->cache->save($this->jsonSerializer->serialize($packagingResults), $cacheKey);
        }

        return $packagingResults;
    }

    /**
     * @param Quote|Order $entity
     * @return array
     * @throws NoSuchEntityException
     */
    private function buildPackagingRequests(Quote|Order $entity): array
    {
        $result = [];
        $sellerPackagingGroups = $this->inputCollector->collect($entity);

        foreach ($sellerPackagingGroups as $shopId => $packagingGroup) {
            $payloads = [];
            $seller = null;

            foreach ($packagingGroup as $itemData) {
                $payloads[] = $itemData['packagingData'];
                $seller = $itemData['seller'];
            }

            if ($seller !== null) {
                $response = $this->packagingApiClient->requestPackaging($payloads, $seller);
                $result[$seller->getId()][] = $response;
            }
        }

        return $result;
    }

    /**
     * @param Order|null $order
     * @return Quote|Order
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function resolveEntity(?Order $order): Quote|Order
    {
        return $order ?: $this->session->getQuote();
    }

    /**
     * @param object $entity
     * @param bool $save
     * @return bool
     */
    private function shouldUseCache(object $entity, bool $save): bool
    {
        return $entity instanceof Quote && !$save;
    }

    /**
     * @param int $entityId
     * @return string
     */
    private function getCacheKey(int $entityId): string
    {
        return 'freight_packaging_response_' . $entityId;
    }
}
