<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceProduct\Model\Shop;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Service\PackagingItemService;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;

class PackagingCheckoutPricing
{
    public const REFACTOR_PACKAGING_DATA = 'tiger_team_B_2576271';

    public const TIGER_D227568           = 'tiger_d227568';

    /**
     * @param Curl $curlClient
     * @param Json $jsonSerializer
     * @param QuoteHelper $quoteHelper
     * @param Session $session
     * @param LoggerInterface $logger
     * @param ShopRepositoryInterface $shopRepository
     * @param Data $helper
     * @param CacheInterface $cache
     * @param ToggleConfig $toggleConfig
     * @param PackagingItemService $packagingItemService
     * @param SellerPackagingInputCollector $inputCollector
     */
    public function __construct(
        private readonly Curl           $curlClient,
        private readonly Json           $jsonSerializer,
        private QuoteHelper             $quoteHelper,
        private Session                 $session,
        private LoggerInterface         $logger,
        private ShopRepositoryInterface $shopRepository,
        private Data                    $helper,
        private CacheInterface          $cache,
        private readonly ToggleConfig $toggleConfig,
        private readonly PackagingItemService $packagingItemService
    ) {
    }

    /**
     * Get packaging details for custom boxes
     *
     * This method sends a POST request to the Custom Boxes API to retrieve packaging
     * details based on the provided item specifications.
     *
     * @param array $items An array of items, each containing specifications for a custom box.
     *                     Each item should be an associative array with the following keys:
     *                     - code: string (e.g., "ELTE")
     *                     - length: string (in inches, e.g., "6")
     *                     - width: string (in inches, e.g., "6")
     *                     - depth: string (in inches, e.g., "2")
     *                     - boardStrength: string (e.g., "32")
     *                     - quantity: int (e.g., 4000)
     *
     * @param Shop $seller Will be used to get the seller API endpoint.
     *
     * @return mixed The API response as an associative array containing packaging details.
     *               The structure of the return array is as follows:
     *               [
     *                 'packaging' => [
     *                   'quantity' => int,
     *                   'shape' => [
     *                     'length' => float,
     *                     'width' => float,
     *                     'depth' => float,
     *                     'volume' => float,
     *                     'area' => float
     *                   ],
     *                   'totalWeight' => float,
     *                   'weight' => float,
     *                   'freightClass' => float,
     *                   'type' => string
     *                 ]
     *               ]
     *
     * @throws \Exception If Seller API endpoint not found.
     * @throws \Exception If the items array is empty.
     * @throws \Exception If the API request fails or returns a non-200 status code.
     *
     * @example
     * $items = [
     *     [
     *         "code" => "ELTE",
     *         "length" => "6",
     *         "width" => "6",
     *         "depth" => "2",
     *         "boardStrength" => "32",
     *         "quantity" => 4000
     *     ]
     * ];
     *
     * $packagingDetails = $customBoxesApiClient->getPackagingDetails($items);
     * $palletQuantity = $packagingDetails['packaging']['quantity'];
     * $palletVolume = $packagingDetails['packaging']['shape']['volume'];
     * $palletWeight = $packagingDetails['packaging']['totalWeight'];
     * $palletType = $packagingDetails['packaging']['type'];
     */
    public function getPackagingDetails(array $items, Shop $seller): array
    {
        try {
            $this->validateParams($items, $seller);

            $requestBody = ['items' => $items];
            $jsonBody = $this->jsonSerializer->serialize($requestBody);

            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
              ];

            $this->curlClient->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . 'Packaging API request start'
            );
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                'Packaging API request payload: ' . $jsonBody
            );

            $this->curlClient->post($this->getSellerPackageApiEndpoint($seller), $jsonBody);

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . 'Packaging API request end'
            );

            $response = $this->curlClient->getBody();

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                'Packaging API response payload: ' . $response
            );

            if ($this->curlClient->getStatus() === 200) {
                return $this->jsonSerializer->unserialize($response);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ': ' . $e->getMessage());
        }

        return [];
    }

    /**
     * @param Shop $seller
     * @return string|null
     */
    public function getSellerPackageApiEndpoint(Shop $seller): ?string
    {
        return $seller->getSellerPackageApiEndpoint();
    }

    protected function validateParams(array $items, Shop $seller): void
    {
        if (empty($items)) {
            throw new \Exception('Items array for Packaging API is empty.');
        }

        if (!$this->getSellerPackageApiEndpoint($seller)) {
            throw new \Exception('Seller API endpoint not found.');
        }
    }

    /**
     * Get packaging items for a quote or an order
     *
     * @param bool $save
     * @param Order|null $order
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPackagingItems(bool $save = false, Order $order = null): array
    {
        if ($this->toggleConfig->getToggleConfigValue(self::REFACTOR_PACKAGING_DATA)) {
            return $this->packagingItemService->getPackagingItems($save, $order);
        }

        $result = $shops = [];

        if (!$this->helper->isFreightShippingEnabled()) {
            return $result;
        }

        // Use order if provided, else fallback to quote
        $entity = $order ?: $this->session->getQuote();

        // For quote only, check Mirakl + cache logic
        if ($entity instanceof Quote) {
            if (!$this->quoteHelper->isMiraklQuote($entity)) {
                return $result;
            }

            if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D227568)) {
                $itemFingerprint = sha1(json_encode(array_map(function ($item) {
                    return [
                        'sku'       => $item->getSku(),
                        'qty'       => (float)$item->getQty(),
                        'offer_id'  => $item->getData('mirakl_offer_id')
                    ];
                }, $entity->getAllItems()), JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES));

                $cacheKey = 'freight_packaging_response_' . $entity->getId() . '_' . $itemFingerprint;

                if (!$save) {
                    $data = $this->cache->load($cacheKey);
                    if ($data) {
                        return $this->jsonSerializer->unserialize($data);
                    }
                }
            } else {
                $cacheKey = 'freight_packaging_response_' . $entity->getId();
                if (!$save) {
                    $data = $this->cache->load($cacheKey);
                    if ($data) {
                        return $this->jsonSerializer->unserialize($data);
                    }
                }
            }
        }

        $marketPlaceItems = array_filter($entity->getAllItems(), function ($item) {
            return $item->getData('mirakl_offer_id');
        });

        if (empty($marketPlaceItems)) {
            return $result;
        }

        foreach ($marketPlaceItems as $item) {
            $punchoutEnabled = false;

            if (!is_null($item->getAdditionalData())) {
                $additionalInfo = json_decode($item->getAdditionalData());

                if (isset($additionalInfo->punchout_enabled)) {
                    $punchoutEnabled = (bool)$additionalInfo->punchout_enabled;
                }

                if ($punchoutEnabled) {
                    $shopId = $item->getMiraklShopId();
                    $shop = $this->shopRepository->getById((int)$shopId);
                    $shopShippingInfo = $shop->getShippingRateOption();

                    if ($shopShippingInfo['freight_enabled']) {
                        $packageUrl = $this->getSellerPackageApiEndpoint($shop);

                        if (!empty($packageUrl) && isset($additionalInfo->packaging_data)) {
                            $packagingData = $additionalInfo->packaging_data;
                            if (!empty($packagingData)) {
                                $data = [
                                    'seller' => $shop,
                                    'packagingData' => $packagingData
                                ];
                                $shops[$shopId][] = $data;
                            }
                        }
                    }
                }
            }
        }

        foreach ($shops as $shopItems) {
            $package = [];
            $seller = null;

            foreach ($shopItems as $data) {
                $package[] = $data['packagingData'];
                $seller = $data['seller'];
            }

            if ($seller) {
                $packingInfo = $this->getPackagingDetails($package, $seller);
                $result[$seller->getId()][] = $packingInfo;
            }
        }

        if ($entity instanceof Quote && !$save) {
            if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D227568)) {
                $this->cache->save($this->jsonSerializer->serialize($result), $cacheKey);
            } else {
                $cacheKey = 'freight_packaging_response_' . $entity->getId();
                $this->cache->save($this->jsonSerializer->serialize($result), $cacheKey);
            }
        }

        return $result;
    }




    /**
     * @param $shopId
     * @param $packaging
     * @return mixed|null
     */
    public function findSellerRecord($shopId, $packaging)
    {
        foreach ($packaging as $seller => $row) {
            if ($seller == $shopId) {
                return $row;
            }
        }
        return null;
    }
}
