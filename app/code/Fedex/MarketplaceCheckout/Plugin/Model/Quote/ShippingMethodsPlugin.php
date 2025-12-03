<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Plugin\Model\Quote;

use Fedex\MarketplaceCheckout\Model\CartInfoGrouper;
use Fedex\MarketplaceCheckout\Api\FedexRateApiDataInterface;
use Fedex\MarketplaceCheckout\Model\ShippingMethodBuilder;
use Fedex\MarketplaceCheckout\Model\ShippingTypeHandler;
use Fedex\MarketplaceCheckout\Helper\BuildDeliveryDate;
use Fedex\MarketplaceCheckout\Model\CompanyDeliveryOptionsService;
use Fedex\MarketplaceCheckout\Model\Config;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingConstants;
use Fedex\MarketplaceCheckout\Model\CustomSampleBox;
use Fedex\MarketplaceCheckout\Model\DTO\DeliveryDTO;
use Fedex\MarketplaceCheckout\Model\DTO\FedexRateResponseDTO;
use Fedex\MarketplaceCheckout\Model\DTO\FreightRequestDTO;
use Fedex\MarketplaceCheckout\Model\DTO\MarketplaceDTO;
use Fedex\MarketplaceCheckout\Model\DTO\PriceDTO;
use Fedex\MarketplaceCheckout\Model\DTO\RatesAndTransitRequestDTO;
use Fedex\MarketplaceCheckout\Model\DTO\SelectionDTO;
use Fedex\MarketplaceCheckout\Model\DTO\ShippingDateDTO;
use Fedex\MarketplaceCheckout\Model\DTO\ShippingDetailsDTO;
use Fedex\MarketplaceCheckout\Model\DTO\ShippingMethodDTO;
use Fedex\MarketplaceCheckout\Model\FreightCheckoutPricing;
use Fedex\MarketplaceCheckout\Model\Offers;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use Fedex\MarketplaceCheckout\Model\RatesAndTransitPricing;
use Fedex\MarketplaceCheckout\Model\ShipDate;
use Fedex\MarketplaceCheckout\Model\ShippingAccountResolver;
use Fedex\MarketplaceCheckout\Model\ShippingMethods;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Fedex\Model\Carrier;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ShippingMethodManagementInterface;
use Mirakl\Connector\Helper\Quote as MiraklQuoteHelper;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;
use Fedex\MarketplaceCheckout\Model\Freight;

class ShippingMethodsPlugin
{
    private QuoteItem $miraklQuoteItem;
    private array $offerAddress = [];
    private ShippingFeeType $selectedShippingType;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteHelper $quoteHelper
     * @param QuoteSynchronizer $quoteSynchronizer
     * @param Data $data
     * @param ShopManagementInterface $shopManagement
     * @param OfferCollector $offerCollector
     * @param BuildDeliveryDate $buildDeliveryDate
     * @param TimezoneInterface $timezone
     * @param MiraklQuoteHelper $miraklQuoteHelper
     * @param RequestInterface $request
     * @param PackagingCheckoutPricing $packagingCheckoutPricing
     * @param FreightCheckoutPricing $freightCheckoutPricing
     * @param NonCustomizableProduct $nonCustomizableProduct
     * @param RatesAndTransitPricing $ratesAndTransitPricing
     * @param Offers $offers
     * @param Freight $freight
     * @param CustomSampleBox $customSampleBox
     * @param Config $config
     * @param ShippingAccountResolver $shippingAccountResolver
     * @param CompanyDeliveryOptionsService $companyDeliveryOptionsService
     * @param ShipDate $shipDate
     * @param ShippingMethodBuilder $shippingMethodBuilder
     * @param ShippingTypeHandler $shippingTypeHandler
     * @param CartInfoGrouper $cartInfoGrouper
     */
    public function __construct(
        private CartRepositoryInterface        $quoteRepository,
        private QuoteHelper                    $quoteHelper,
        private QuoteSynchronizer              $quoteSynchronizer,
        private Data                           $data,
        private ShopManagementInterface        $shopManagement,
        protected OfferCollector               $offerCollector,
        private BuildDeliveryDate              $buildDeliveryDate,
        private TimezoneInterface              $timezone,
        private MiraklQuoteHelper              $miraklQuoteHelper,
        private RequestInterface               $request,
        private PackagingCheckoutPricing       $packagingCheckoutPricing,
        private FreightCheckoutPricing         $freightCheckoutPricing,
        private NonCustomizableProduct         $nonCustomizableProduct,
        private RatesAndTransitPricing         $ratesAndTransitPricing,
        private Offers                         $offers,
        private Freight                        $freight,
        private CustomSampleBox                $customSampleBox,
        private Config                         $config,
        private ShippingAccountResolver        $shippingAccountResolver,
        private CompanyDeliveryOptionsService  $companyDeliveryOptionsService,
        private ShipDate                       $shipDate,
        private ShippingMethodBuilder $shippingMethodBuilder,
        private ShippingTypeHandler   $shippingTypeHandler,
        private CartInfoGrouper       $cartInfoGrouper
    ) {
    }

    /**
     * Around estimate to handle MKT shipping methods.
     *
     * @param ShippingMethodManagementInterface $subject
     * @param \Closure $proceed
     * @param string $cartId
     * @param AddressInterface $address
     * @return  ShippingMethodInterface[]|mixed
     */
    public function aroundEstimateByExtendedAddress(
        ShippingMethodManagementInterface $subject,
        \Closure                          $proceed,
                                          $cartId,
        AddressInterface                  $address
    )
    {
        if (!$this->config->isShippingManagementRefactorEnabled()) {
            return $proceed($cartId, $address);
        }

        if (!$this->shippingAccountResolver->isAddressValid($address)) {
            return [];
        }

        $quote = $this->quoteRepository->getActive($cartId);

        if (!$this->quoteHelper->isMiraklQuote($quote)) {
            return $proceed($cartId, $address);
        }
        // Retrieve default shipping methods
        $shippingMethods = $proceed($cartId, $address);

        if ($quote->isVirtual() || !$quote->getItemsCount()) {
            return [];
        }

        $shippingAddress = $quote->getShippingAddress();
        $error = [];

        if ($shippingMethods || $this->shippingAccountResolver->isPickup($this->request) || $this->quoteHelper->isFullMiraklQuote($quote)) {
            $this->getMarketplaceShippingMethods($quote, $shippingMethods, $error, $shippingAddress);
        }

        if ($error) {
            return $error;
        }

        return $shippingMethods;
    }

    /**
     * Around estimate by address ID case.
     *
     * @param ShippingMethodManagementInterface $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param int $addressId
     * @return  array
     */
    public function aroundEstimateByAddressId(
        ShippingMethodManagementInterface $subject,
        \Closure                          $proceed,
                                          $cartId,
                                          $addressId
    ) {
        if (!$this->config->isShippingManagementRefactorEnabled()) {
            return $proceed($cartId, $addressId);
        }

        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        if ($quote->isVirtual() || !$quote->getItemsCount()) {
            return [];
        }

        // Retrieve default shipping methods
        $shippingMethods = $proceed($cartId, $addressId);
        $error = [];

        if ($shippingMethods || $this->shippingAccountResolver->isPickup($this->request) || $this->miraklQuoteHelper->isFullMiraklQuote($quote)) {
            $this->getMarketplaceShippingMethods($quote, $shippingMethods, $error);
        }

        if ($error) {
            return $error;
        }

        return $shippingMethods;
    }

    private function processGroupedItems(Quote $quote, $shippingAddress): void
    {
        foreach ($this->quoteSynchronizer->getGroupedItems($quote) as $item) {
            if (!$item->getMiraklOfferId()) {
                continue;
            }
            /** @var ShippingFeeType $selectedShippingType */
            $this->selectedShippingType = $this->shippingTypeHandler->getItemSelectedShippingType($item);

            $shopData = $this->shopManagement->getShopByProduct($item->getProduct());
            $shopShippingInfo = $shopData->getShippingRateOption();
            $this->miraklQuoteItem = $item;

            $offers = $this->offers->getFilteredOffers($item->getSku(), (int)$shopData->getId());

            $regionCode = $shippingAddress->getRegionCode();

            $this->offerAddress = $this->offers->getOfferAddresses($offers, $item, $regionCode, $shopShippingInfo);
        }
    }

    /**
     * Handle MKT shipping structure.
     * @param Quote $quote
     * @param mixed[] $shippingMethods
     * @param mixed[] $error
     * @param $shippingAddress
     * @return void
     * @throws NoSuchEntityException
     */
    private function getMarketplaceShippingMethods(Quote $quote, array &$shippingMethods, array &$error, $shippingAddress = null)
    {
        if (!$this->quoteHelper->isMiraklQuote($quote) || empty($shippingAddress)) {
            return [];
        }

        // Get cart relevant information grouped by seller
        $shops = $this->cartInfoGrouper->getMarketplaceCartInfoGroupedBySeller($quote);

        $this->processGroupedItems($quote, $shippingAddress);

        // Check if the cart has only sample pack products
        $hasOnlySamplePackProduct = $this->customSampleBox->isOnlySampleBoxProductInCart($quote, $shops, $this->data->isFreightShippingEnabled(), $this->nonCustomizableProduct->isMktCbbEnabled(), $this->quoteHelper->isMiraklQuote($quote));

        // Get shipping methods
        $this->populateShippingMethods($shops, $hasOnlySamplePackProduct, $shippingAddress, $shippingMethods);

        // Format shipping methods before returning to Frontend
        $this->formatShippingMethods($shippingMethods);
    }

    private function populateShippingMethods(array $shops, bool $hasOnlySamplePackProduct, $shippingAddress, &$shippingMethods): void
    {
        // If Freight products exist in cart
        $freightInfo = [];
        if (!$hasOnlySamplePackProduct) {
            $freightInfo = $this->packagingCheckoutPricing->getPackagingItems();
        }

        foreach ($shops as $shop) {
            $boxType = '';
            $totalPackageCount = 0;
            $timeZone = $shop['shop']->getTimezone();
            $shopShippingInfo = $shop['shop']->getShippingRateOption();
            $currentDateTime = $this->shipDate->getCurrentDateTime($timeZone);

            // If cart has Freight items
            if ($this->freight->isFreightShipping($shopShippingInfo, $freightInfo, $hasOnlySamplePackProduct)) {
                $sellerPackage = $this->packagingCheckoutPricing->findSellerRecord($shop['shop']->getId(), $freightInfo);
                if ($sellerPackage) {
                    foreach ($sellerPackage as $item) {
                        $packaging = $item['packaging'] ?? [];
                        if (isset($packaging['type']) && $packaging['type'] == 'pallet') {
                            $this->processFreightShippingMethods($shippingMethods, $packaging, $currentDateTime, $shop, $shopShippingInfo, $timeZone, $shippingAddress);
                        } else {
                            if (isset($packaging['weight'])) {
                                $shop['weight'] = $packaging['weight'];
                            }
                            if (isset($packaging['quantity'])) {
                                $totalPackageCount = $packaging['quantity'];
                            }
                            $boxType = FreightCheckoutPricing::BOX_TYPE;
                        }
                    }
                }
            }

            // If cart does not have Freight items
            if ($this->isStandardShipping($shopShippingInfo, $boxType, $hasOnlySamplePackProduct)) {
                $this->processStandardShippingOptions($shippingMethods, $hasOnlySamplePackProduct, $shop, $currentDateTime, $timeZone, $shippingAddress, $totalPackageCount, $this->selectedShippingType, $shopShippingInfo);
            }
        }
    }

    private function formatShippingMethods(&$shippingMethods): void
    {
        $baseAmounts = [];
        $originalKeys = [];

        foreach ($shippingMethods as $key => $method) {
            $baseAmounts[$key] = is_object($method) ? $method->getBaseAmount() : $method['base_amount'];
            $originalKeys[$key] = $key;
        }
        array_multisort($baseAmounts, $originalKeys, $shippingMethods);
    }

    private function isStandardShipping(array $shopShippingInfo, string $boxType, bool $hasOnlySamplePackProduct): bool
    {
        return !$this->data->isFreightShippingEnabled()
            || !$shopShippingInfo['freight_enabled']
            || $hasOnlySamplePackProduct
            || $boxType == FreightCheckoutPricing::BOX_TYPE;
    }

    private function processFreightShippingMethods(array &$shippingMethods, array $packaging, string $currentDateTime, array $shop, array $shopShippingInfo, string $timeZone, \Magento\Quote\Model\Quote\Address $shippingAddress): array
    {
        $dto = new ShippingDateDTO(
            $currentDateTime,
            max($shop['business_days']),
            $shopShippingInfo['shipping_cut_off_time'],
            $shopShippingInfo['shipping_seller_holidays'],
            (int)$shopShippingInfo['additional_processing_days'],
            $timeZone);
        $shipDate = $this->shipDate->getShipDate($dto);

        // Get shipping account number
        $shippingAccountNumber = $this->shippingAccountResolver->resolveShippingAccountNumber($shopShippingInfo, $this->request);

        // Check if the address is residential/commercial
        $residentialValue = $this->shippingAccountResolver->isShippingToResidence($this->request->getContent());

        // Set special services for freight shipping
        $dto = new FreightRequestDTO($this->request->getContent());
        $packaging['specialServices'] = $this->freight->isLoadingDockSelected($dto);

        // Get rates from Freight API
        $dataFromRates = $this->freightCheckoutPricing->execute(
            $shopShippingInfo,
            $shippingAddress,
            date("Y-m-d", $shipDate),
            $packaging,
            $shippingAccountNumber,
            $residentialValue
        );

        // Get delivery methods configured codes
        $deliveryMethods = $this->companyDeliveryOptionsService->getAllowedDeliveryMethods($shop);

        if (!empty($dataFromRates)) {
            if (!isset($dataFromRates['errors'])) {
                foreach ($dataFromRates as $ratesFromRatesFedexApi) {

                    if (isset(
                        $ratesFromRatesFedexApi
                        [FedexRateApiDataInterface::OPERATIONAL_DETAIL]
                        [FedexRateApiDataInterface::DELIVERY_DATA]
                    )) {

                        // Check if delivery mapping exists for the ship method
                        $shippingMethodCode = $ratesFromRatesFedexApi
                        [FedexRateApiDataInterface::SERVICE_TYPE];
                        $deliveryMethod = array_filter($deliveryMethods, function ($var) use ($shippingMethodCode) {
                            return ($var['shipping_method_name'] == $shippingMethodCode);
                        });
                        $deliveryMethod = array_values($deliveryMethod);
                        if (empty($deliveryMethod)) {
                            continue;
                        }
                    } else {
                        continue;
                    }

                    // Get delivery date from API response
                    $deliveryDate = date("l, F j, g:ia",
                        strtotime(
                            $ratesFromRatesFedexApi
                            [FedexRateApiDataInterface::OPERATIONAL_DETAIL]
                            [FedexRateApiDataInterface::DELIVERY_DATA]
                        )
                    );

                    $methodTitle = $deliveryMethod[0]['shipping_method_label'];
                    $deliveryDateText = $this->buildDeliveryDate->formatDeliveryDateWithEodTextIfGroundShipping($methodTitle, $deliveryDate);
                    $carrierCode = 'marketplace_' . $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_TYPE];

                    $item = $shop['items'][0];
                    // Get surcharge amount for liftgate delivery from freight API
                    $dto = new FedexRateResponseDTO($ratesFromRatesFedexApi);
                    $freightSurcharge = $this->freight->getFreightSurchargeAmount($dto);

                    $shippingData = new ShippingMethodDTO(

                        new ShippingDetailsDTO(
                            $carrierCode,
                            $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_TYPE],
                            ucwords(Carrier::CODE),
                            $methodTitle,
                            $methodTitle),

                        new PriceDTO(
                            $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                            $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                            $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                            $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                            $freightSurcharge),

                        new MarketplaceDTO(
                            $item->getData('mirakl_offer_id'),
                            $item->getData('mirakl_shop_name'),
                            $item->getData('mirakl_shop_id'),
                            $shop['shop']->getSellerAltName()),

                        new SelectionDTO(
                            $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_TYPE],
                            $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_TYPE]),

                        new DeliveryDTO(
                            $deliveryDate,
                            $deliveryDateText),

                        $item->getId()
                    );

                    // Build shipping methods from Freight API
                    $shippingMethods[] = $this->shippingMethodBuilder->createShippingMethod($shippingData);
                }
            }
        }
        return $shippingMethods;
    }

    public function processStandardShippingOptions(array &$shippingMethods, bool $hasOnlySamplePackProduct, array $shop, string $currentDateTime, string $timeZone, \Magento\Quote\Model\Quote\Address $shippingAddress, int $totalPackageCount, mixed $selectedShippingType, array $shopShippingInfo): array
    {
        // Set Sample Widget Product to use Mirakl Shipping
        if ($this->nonCustomizableProduct->isMktCbbEnabled()) {
            if ($hasOnlySamplePackProduct && $this->customSampleBox->hasSampleBoxInShop($shop)) {
                $shopShippingInfo['shipping_rate_option'] = ShippingConstants::MIRAKL_SHIPPING_RATES_CONFIGURATION;
            }
        } else {
            if ($hasOnlySamplePackProduct) {
                $shopShippingInfo['shipping_rate_option'] = ShippingConstants::MIRAKL_SHIPPING_RATES_CONFIGURATION;
            }
        }

        // Fedex Shipping rates
        if ($shopShippingInfo['shipping_rate_option'] == ShippingConstants::FEDEX_SHIPPING_RATES_CONFIGURATION) {
            // Build Ship Date logic

            $dto = new ShippingDateDTO(
                $currentDateTime,
                max($shop['business_days']),
                $shopShippingInfo['shipping_cut_off_time'],
                $shopShippingInfo['shipping_seller_holidays'],
                (int)$shopShippingInfo['additional_processing_days'],
                $timeZone);

            $shipDate = $this->shipDate->getShipDate($dto);

            $shippingAccountNumber = $this->shippingAccountResolver->resolveShippingAccountNumber($shopShippingInfo, $this->request);

            $ratesRequestData = new RatesAndTransitRequestDTO(
                $this->request->getContent(),
                date("Y-m-d", $shipDate),
                $shop['shop']->getData(),
                $shippingAddress->getData(),
                $this->offerAddress,
                $shippingAccountNumber,
                $shop['weight'],
                $totalPackageCount
            );

            $dataFromRates = $this->ratesAndTransitPricing->getRates($ratesRequestData);

            // Get delivery methods configured codes
            $deliveryMethods = $this->companyDeliveryOptionsService->getAllowedDeliveryMethods($shop);

            if (!empty($dataFromRates)) {
                if (!isset($dataFromRates['errors'])) {
                    foreach ($dataFromRates as $ratesFromRatesFedexApi) {
                        // If we don't get the shipping delivery date, we just don't show it
                        if (isset(
                            $ratesFromRatesFedexApi
                            [FedexRateApiDataInterface::OPERATIONAL_DETAIL]
                            [FedexRateApiDataInterface::DELIVERY_DATA]
                        )) {

                            // Check if delivery mapping exists for the ship method
                            $shippingMethodCode = $ratesFromRatesFedexApi
                            [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                            [FedexRateApiDataInterface::SERVICE_TYPE];
                            $deliveryMethod = array_filter($deliveryMethods, function ($var) use ($shippingMethodCode) {
                                return ($var['shipping_method_name'] == $shippingMethodCode);
                            });
                            $deliveryMethod = array_values($deliveryMethod);
                            if (empty($deliveryMethod)) {
                                continue;
                            }
                        } else {
                            continue;
                        }
                        $deliveryDate = date("l, F j, g:ia",
                            strtotime(
                                $ratesFromRatesFedexApi
                                [FedexRateApiDataInterface::OPERATIONAL_DETAIL]
                                [FedexRateApiDataInterface::DELIVERY_DATA]
                            )
                        );
                        $methodTitle = $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                        [FedexRateApiDataInterface::DESCRIPTION];
                        $deliveryDateText = $this->buildDeliveryDate->formatDeliveryDateWithEodTextIfGroundShipping($methodTitle, $deliveryDate);
                        $carrierCode = 'marketplace_' . $ratesFromRatesFedexApi
                            [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                            [FedexRateApiDataInterface::SERVICE_TYPE];

                        $item = $shop['items'][0];

                        $shippingData = new ShippingMethodDTO(

                            new ShippingDetailsDTO(
                                $carrierCode,
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_DESCRIPTION][FedexRateApiDataInterface::SERVICE_TYPE],
                                ucwords(Carrier::CODE),
                                $this->data->handleMethodTitle($methodTitle),
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_DESCRIPTION][FedexRateApiDataInterface::DESCRIPTION]),

                            new PriceDTO(
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]['totalNetFedExCharge'],
                                '0'),

                            new MarketplaceDTO(
                                $item->getData('mirakl_offer_id'),
                                $item->getData('mirakl_shop_name'),
                                $item->getData('mirakl_shop_id'),
                                $shop['shop']->getSellerAltName()),

                            new SelectionDTO(
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_DESCRIPTION]['serviceType'],
                                $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_DESCRIPTION][FedexRateApiDataInterface::SERVICE_TYPE]),

                            new DeliveryDTO(
                                $deliveryDate,
                                $deliveryDateText),

                            $item->getId()
                        );

                        $shippingMethods[] = $this->shippingMethodBuilder->createShippingMethod($shippingData);
                    }
                }
            } else {
                $shippingMethods = [];
            }
        } else {
            if (isset($dataFromRates['errors'])) {
                $shippingMethods = [];
            } else {
                foreach ($shop['items'] as $item) {
                    foreach ($this->shippingTypeHandler->getItemShippingTypes($item, $shippingAddress) as $i => $shippingType) {
                        /** @var ShippingFeeType $shippingType */
                        $shopId = $item->getData('mirakl_shop_id');

                        $deliveryDate = date_format($shippingType->getDeliveryTime()->getLatestDeliveryDate(), "l, F j");

                        $carrierCode = 'marketplace_' . $item->getMiraklOfferId();

                        $shippingData = new ShippingMethodDTO(
                            new ShippingDetailsDTO(
                                $carrierCode,
                                $shippingType->getCode(),
                                $item->getData('mirakl_shop_name'),
                                $this->data->handleMethodTitle($shippingType->getLabel()),
                                $shippingType->getLabel()),

                            new PriceDTO(
                                $shippingType->getData('total_shipping_price_incl_tax'),
                                $shippingType->getData('total_shipping_price_incl_tax'),
                                $shippingType->getData('price_incl_tax'),
                                $shippingType->getData('price_excl_tax'),
                                '0'),

                            new MarketplaceDTO(
                                $item->getData('mirakl_offer_id'),
                                $item->getData('mirakl_shop_name'),
                                $shopId,
                                $shop['shop']->getSellerAltName()),

                            new SelectionDTO(
                                $this->selectedShippingType->getCode(),
                                $this->selectedShippingType->getCode()),

                            new DeliveryDTO(
                                $deliveryDate,
                                $deliveryDate),

                            $item->getId()
                        );

                        $shippingMethods[] = $this->shippingMethodBuilder->createShippingMethod($shippingData);
                    }
                }
            }
        }

        return $shippingMethods;
    }

}