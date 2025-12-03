<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Plugin\Model\Quote;

use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\Delivery\Helper\Data as RetailHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Api\FedexRateApiDataInterface;
use Fedex\MarketplaceCheckout\Helper\BuildDeliveryDate;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceCheckout\Model\Config;
use Fedex\MarketplaceCheckout\Model\FreightCheckoutPricing;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Quote\Model\ResourceModel\Quote\AddressFactory as QuoteAddressResourceFactory;
use Magento\Quote\Model\ShippingMethodManagementInterface;
use Mirakl\Connector\Helper\Quote as MiraklQuoteHelper;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollection;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\FrontendDemo\Helper\Quote\Item as QuoteItemHelper;
use Mirakl\FrontendDemo\Model\Quote\Updater as QuoteUpdater;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;

class ShippingMethodManagementPlugin
{
    private const XPATH_ENABLE_MKT_SELFREG_SITE = 'environment_toggle_configuration/environment_toggle/tiger_tk_410245';

    private const WEIGHT_OZ_UNIT = 'oz.';

    private const ORIGIN_ADDRESS_STATES = 'origin_address_states';

    private const ORIGIN_ADDRESS_REFERENCE = 'origin_address_reference';

    private const ORIGIN_SHOP_CITY = 'origin_shop_city';

    private const CITY = 'city';

    private const ORIGIN_CITY = 'origin_city';

    private const STATE_OR_PROVINCE = 'stateOrProvinceCode';

    private const ORIGIN_STATE = 'origin_state';

    private const ORIGIN_SHOP_STATE = 'origin_shop_state';

    private const POSTAL_CODE = 'postalCode';

    private const ORIGIN_ZIPCODE = 'origin_zipcode';

    private const ORIGIN_SHOP_ZIPCODE = 'origin_shop_zipcode';

    private const ALL = 'ALL';

    /**
     * Rates option chosen by Shop.
     */
    private const FEDEX_SHIPPING_RATES_CONFIGURATION = 'fedex-shipping-rates';
    public const EOD_TEXT = 'End of Day';
    public const GROUND_US = 'FedEx Ground US®';
    public const GROUND_US_NO_MARK = 'FedEx Ground US';
    public const ESTIMATED_DELIVERY_LOCAL_TIME = 'estimatedDeliveryLocalTime';
    private const MIRAKL_SHIPPING_RATES_CONFIGURATION = 'mirakl-shipping-rates';

    /**
     * @var float
     */
    private $totalCartWeight;

    private QuoteItem $miraklQuoteItem;

    private $addressElected = [];

    private $addressCounted = [];

    private $originCombinedOffers = null;
    private $productionDays = [];

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteHelper $quoteHelper
     * @param QuoteItemHelper $quoteItemHelper
     * @param QuoteUpdater $quoteUpdater
     * @param QuoteAddressResourceFactory $quoteAddressResourceFactory
     * @param QuoteSynchronizer $quoteSynchronizer
     * @param Data $data
     * @param ShopManagementInterface $shopManagement
     * @param OfferCollector $offerCollector
     * @param BuildDeliveryDate $buildDeliveryDate
     * @param CollectionFactory $collectionFactory
     * @param ToggleConfig $toggleConfig
     * @param TimezoneInterface $timezone
     * @param MiraklQuoteHelper $miraklQuoteHelper
     * @param RequestInterface $request
     * @param OfferCollection $offerCollectionFactory
     * @param RetailHelper $retailHelper
     * @param CheckoutConfig $checkoutConfig
     * @param MarketplaceHelper $marketplaceHelper
     * @param ShopRepositoryInterface $shopRepository
     * @param PackagingCheckoutPricing $packagingCheckoutPricing
     * @param FreightCheckoutPricing $freightCheckoutPricing
     * @param NonCustomizableProduct $nonCustomizableProduct
     */
    public function __construct(
        private CartRepositoryInterface $quoteRepository,
        private QuoteHelper $quoteHelper,
        private QuoteItemHelper $quoteItemHelper,
        private QuoteUpdater $quoteUpdater,
        private QuoteAddressResourceFactory $quoteAddressResourceFactory,
        private QuoteSynchronizer $quoteSynchronizer,
        private Data $data,
        private ShopManagementInterface $shopManagement,
        protected OfferCollector $offerCollector,
        private BuildDeliveryDate $buildDeliveryDate,
        private CollectionFactory $collectionFactory,
        Private ToggleConfig $toggleConfig,
        private TimezoneInterface $timezone,
        private MiraklQuoteHelper $miraklQuoteHelper,
        private RequestInterface $request,
        private OfferCollection $offerCollectionFactory,
        private RetailHelper $retailHelper,
        private CheckoutConfig $checkoutConfig,
        private MarketplaceHelper $marketplaceHelper,
        private ShopRepositoryInterface $shopRepository,
        private PackagingCheckoutPricing $packagingCheckoutPricing,
        private FreightCheckoutPricing $freightCheckoutPricing,
        private NonCustomizableProduct $nonCustomizableProduct,
        private Config  $config
    ) {
    }

    /**
     * Around estimate to handle MKT shipping methods.
     *
     * @param   ShippingMethodManagementInterface   $subject
     * @param   \Closure                            $proceed
     * @param   string                              $cartId
     * @param   AddressInterface                    $address
     * @return  ShippingMethodInterface[]|mixed
     */
    public function aroundEstimateByExtendedAddress(
        ShippingMethodManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        AddressInterface $address
    ) {
        // Switch to new shipping management plugin if toggle is enabled
        if ($this->config->isShippingManagementRefactorEnabled()) {
            return $proceed($cartId, $address);
        }

        if (!$this->isAddressValid($address)) {
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

        if ($shippingMethods || $this->isPickup() || $this->quoteHelper->isFullMiraklQuote($quote)) {
            $this->handleMiraklShippingTypesForAllSellers($quote, $shippingMethods, $error, $shippingAddress);
        }

        if ($error) {
            return $error;
        }

        return $shippingMethods;
    }

    public function isAddressValid(AddressInterface $address) {
        if(!$address->getPostcode()
            || !$address->getCity()
            || !$address->getRegionCode()
            || !$address->getStreet()) {
            return false;
        }
        return true;
    }

    /**
     * Around estimate by address ID case.
     *
     * @param   ShippingMethodManagementInterface   $subject
     * @param   \Closure                            $proceed
     * @param   int                                 $cartId
     * @param   int                                 $addressId
     * @return  array
     */
    public function aroundEstimateByAddressId(
        ShippingMethodManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        $addressId
    ) {
        // Switch to new shipping management plugin if toggle is enabled
        if ($this->config->isShippingManagementRefactorEnabled()) {
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

        if ($shippingMethods || $this->isPickup() || $this->miraklQuoteHelper->isFullMiraklQuote($quote)) {
            $this->handleMiraklShippingTypesForAllSellers($quote, $shippingMethods, $error);
        }

        if ($error) {
            return $error;
        }

        return $shippingMethods;
    }

    /**
     * Handle MKT shipping structure.
     * @param Quote $quote
     * @param mixed[] $shippingMethods
     * @param mixed[] $error
     * @param $shippingAddress
     * @return void
     */
    private function handleMiraklShippingTypes(Quote $quote, array &$shippingMethods, array &$error, $shippingAddress = null)
    {
        if ($this->quoteHelper->isMiraklQuote($quote) && !empty($shippingAddress)) {

            $businessDays = [];
            foreach ($this->getItemsWithOfferCombined($quote) as $item) {
                if (!$item->getMiraklOfferId()) {
                    continue;
                }
                $itemWeight = $this->getLbsWeight($item);
                $this->totalCartWeight += ($itemWeight * $item->getQty());

                $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                if (isset($additionalData['business_days'])) {
                    $businessDays[] = (int) $additionalData['business_days'];
                }

                if (!isset($this->miraklQuoteItem)) {
                    /** @var ShippingFeeType $selectedShippingType */
                    $selectedShippingType = $this->getItemSelectedShippingType($item);

                    $shopData = $this->shopManagement->getShopByProduct($item->getProduct());
                    $shopShippingInfo = $shopData->getShippingRateOption();
                    $this->originCombinedOffers = $shopShippingInfo['origin_combined_offers'];
                    $this->miraklQuoteItem = $item;
                }

                $offers = $this->getFilteredOffers($item->getSku(), $shopData->getId());
                $regionCode = $shippingAddress->getRegionCode();

                foreach ($offers as $offer) {
                    $itemQty = $item->getQty();
                    $offerStates = explode(',', trim($offer->getAdditionalInfo()[self::ORIGIN_ADDRESS_STATES]));
                    $offerOriginReference = $offer->getAdditionalInfo()[self::ORIGIN_ADDRESS_REFERENCE];

                    if (in_array($regionCode, $offerStates) || trim($offer->getAdditionalInfo()[self::ORIGIN_ADDRESS_STATES]) == self::ALL) {
                        if (isset($this->addressCounted[$offer->getId()][$offerOriginReference])) {
                            $itemQty = $itemQty + $this->addressCounted[$offer->getId()][$offerOriginReference];
                        }

                        $this->addressCounted[$offer->getId()][self::CITY] = isset($offer->getAdditionalInfo()[self::ORIGIN_CITY])
                            ? $offer->getAdditionalInfo()[self::ORIGIN_CITY]
                            : $shopShippingInfo[self::ORIGIN_SHOP_CITY];

                        $this->addressCounted[$offer->getId()][self::STATE_OR_PROVINCE] = isset($offer->getAdditionalInfo()[self::ORIGIN_STATE])
                            ? $offer->getAdditionalInfo()[self::ORIGIN_STATE]
                            : $shopShippingInfo[self::ORIGIN_SHOP_STATE];

                        $this->addressCounted[$offer->getId()][self::POSTAL_CODE] = isset($offer->getAdditionalInfo()[self::ORIGIN_ZIPCODE])
                            ? $offer->getAdditionalInfo()[self::ORIGIN_ZIPCODE]
                            : $shopShippingInfo[self::ORIGIN_SHOP_ZIPCODE];

                        $this->addressCounted[$offer->getId()][$offerOriginReference] = $itemQty;
                    }
                }
            }

            if ($shopShippingInfo['shipping_rate_option'] == self::FEDEX_SHIPPING_RATES_CONFIGURATION) {
                // Get current date in CST timeformat
                $currentDateTime = $this->timezone->formatDateTime(
                    $this->timezone->date(),
                    null,
                    null,
                    null,
                    $shopData->getTimezone(),
                    'yyyy-MM-dd\'T\'HH:mm:ss'
                );

                // Build Ship Date logic
                $shipDate = $this->buildDeliveryDate->getAllowedDeliveryDate(
                    $currentDateTime,
                    max($businessDays),
                    $shopShippingInfo['shipping_cut_off_time'],
                    $shopShippingInfo['shipping_seller_holidays'],
                    (int)$shopShippingInfo['additional_processing_days'],
                    $shopData->getTimezone()
                );

                $shippingAccountNumber = $shopShippingInfo['shipping_account_number'];
                $marketPlaceShippingAccountNumber = $this->getFedExAccountNumber();
                if ($this->marketplaceHelper->isCustomerShippingAccount3PEnabled()
                    && $this->marketplaceHelper->isVendorSpecificCustomerShippingAccountEnabled()
                    && $shopShippingInfo['customer_shipping_account_enabled']
                    && !empty($marketPlaceShippingAccountNumber)) {
                    $shippingAccountNumber = $marketPlaceShippingAccountNumber;
                }

                $dataFromRates = $this->createDataForFedexRatesApiEnhancement(
                    date("Y-m-d", $shipDate),
                    $shopData,
                    $shippingAddress,
                    $shippingAccountNumber,
                    $this->marketplaceHelper->isCustomerShippingAccount3PEnabled()
                );

                if ($this->checkoutConfig->isSelfRegCustomer()
                    && $this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_MKT_SELFREG_SITE)) {
                    $deliveryMethods = $this->getCompanyAllowedShippingMethods();
                } else {
                    $deliveryMethods = json_decode($shop['shop']->getData('shipping_methods'), true);
                }

                if (!empty($dataFromRates)) {
                    if (!isset($dataFromRates['errors'])) {
                        foreach ($dataFromRates as $ratesFromRatesFedexApi) {
                            // If we don't get the shipping delivery date, we just don't show it
                            if
                            (isset(
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

                            $methodTitle = $ratesFromRatesFedexApi
                            [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                            [FedexRateApiDataInterface::DESCRIPTION];
                            $carrierCode = 'marketplace_' . $ratesFromRatesFedexApi
                                [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                [FedexRateApiDataInterface::SERVICE_TYPE];
                            $shippingMethods[] = [
                                'carrier_code' => $carrierCode,
                                'method_code' => $ratesFromRatesFedexApi
                                [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                [FedexRateApiDataInterface::SERVICE_TYPE],
                                'carrier_title' => ucwords(\Magento\Fedex\Model\Carrier::CODE),
                                'method_title' => $this->data->handleMethodTitle($methodTitle),
                                'amount' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                [0]['totalNetFedExCharge'],
                                'base_amount' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                [0]['totalNetFedExCharge'],
                                'available' => true,
                                'price_incl_tax' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                [0]['totalNetFedExCharge'],
                                'price_excl_tax' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                [0]['totalNetFedExCharge'],
                                'offer_id' => $item->getData('mirakl_offer_id'),
                                'title' => $item->getData('mirakl_shop_name'),
                                'selected' => $carrierCode . '_' . $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                    ['serviceType'],
                                'selected_code' => $ratesFromRatesFedexApi
                                [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                [FedexRateApiDataInterface::SERVICE_TYPE],
                                'item_id' => $item->getId(),
                                'shipping_type_label' => $ratesFromRatesFedexApi
                                [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                [FedexRateApiDataInterface::DESCRIPTION],
                                'deliveryDate' => date(
                                    "l, F j, g:ia",
                                    strtotime(
                                        $ratesFromRatesFedexApi
                                        [FedexRateApiDataInterface::OPERATIONAL_DETAIL]
                                        [FedexRateApiDataInterface::DELIVERY_DATA]
                                    )
                                ),
                                'deliveryDateText' => date(
                                    "l, F j, g:ia",
                                    strtotime(
                                        $ratesFromRatesFedexApi
                                        [FedexRateApiDataInterface::OPERATIONAL_DETAIL]
                                        [FedexRateApiDataInterface::DELIVERY_DATA]
                                    )
                                ),
                                'marketplace' => true,
                            ];
                        }
                    }
                } else {
                    $shippingMethods = [];
                }
            } else {
                foreach ($this->getItemShippingTypes($item, $shippingAddress) as $i => $shippingType) {
                    /** @var ShippingFeeType $shippingType */
                    $carrierCode = 'marketplace_' . $item->getMiraklOfferId();
                    $shippingMethods[] = [
                        'carrier_code' => $carrierCode,
                        'method_code' => $shippingType->getCode(),
                        'carrier_title' => $item->getData('mirakl_shop_name'),
                        'method_title' => $this->data->handleMethodTitle($shippingType->getLabel()),
                        'amount' => $shippingType->getData('total_shipping_price_incl_tax'),
                        'base_amount' => $shippingType->getData('total_shipping_price_incl_tax'),
                        'available' => true,
                        'price_incl_tax' => $shippingType->getData('price_incl_tax'),
                        'price_excl_tax' => $shippingType->getData('price_excl_tax'),
                        'offer_id' => $item->getData('mirakl_offer_id'),
                        'title' => $item->getData('mirakl_shop_name'),
                        'selected' => $carrierCode . '_' . $selectedShippingType->getCode(),
                        'selected_code' => $selectedShippingType->getCode(),
                        'item_id' => $item->getId(),
                        'shipping_type_label' => $shippingType->getLabel(),
                        'marketplace' => true,
                    ];
                }
            }

            if (isset($dataFromRates['errors'])) {
                $shippingMethods = [];
                $error = $dataFromRates;
            } else {
                $baseAmounts = [];
                $originalKeys = [];

                foreach ($shippingMethods as $key => $method) {
                    $baseAmounts[$key] = is_object($method) ? $method->getBaseAmount() : $method['base_amount'];
                    $originalKeys[$key] = $key;
                }
                array_multisort($baseAmounts, $originalKeys, $shippingMethods);
            }
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
    private function handleMiraklShippingTypesForAllSellers(Quote $quote, array &$shippingMethods, array &$error, $shippingAddress = null)
    {
        if ($this->quoteHelper->isMiraklQuote($quote) && !empty($shippingAddress)) {

            //Filter quote by Marketplace items only
            $marketPlaceItems = array_filter($quote->getAllItems(), function ($item) {
                return $item->getData('mirakl_offer_id');
            });

            // Declare Variables
            $result = $shops = [];


            // Build array of Marketplace items grouped by shop_id
            foreach ($marketPlaceItems as $item) {
                $shopId = $item->getMiraklShopId();
                if (array_key_exists($shopId, $result)) {
                    array_push($result[$shopId], $item);
                } else {
                    $result[$shopId] = [$item];
                }
            }

            // Build summary information about each seller
            foreach ($result as $shopId => $items) {
                $this->totalCartWeight = 0;
                $businessDays = [];

                foreach ($items as $item) {
                    $itemWeight = $this->getLbsWeight($item);
                    $this->totalCartWeight += ($itemWeight * $item->getQty());

                    $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                    if (isset($additionalData['business_days'])) {
                        $businessDays[] = (int)$additionalData['business_days'];
                    }
                }
                $shopData = $this->shopRepository->getById($shopId);
                $shopInfo = [
                    'shop' => $shopData,
                    'weight' => $this->totalCartWeight,
                    'business_days' => $businessDays,
                    'items' => $items
                ];
                $shops[$shopId] = $shopInfo;
            }


            foreach ($this->quoteSynchronizer->getGroupedItems($quote) as $item) {
                if (!$item->getMiraklOfferId()) {
                    continue;
                }
                /** @var ShippingFeeType $selectedShippingType */
                $selectedShippingType = $this->getItemSelectedShippingType($item);

                $shopData = $this->shopManagement->getShopByProduct($item->getProduct());
                $shopShippingInfo = $shopData->getShippingRateOption();
                $this->originCombinedOffers = $shopShippingInfo['origin_combined_offers'];
                $this->miraklQuoteItem = $item;

                $offers = $this->getFilteredOffers($item->getSku(), $shopData->getId());

                $regionCode = $shippingAddress->getRegionCode();

                foreach ($offers as $offer) {
                    $itemQty = $item->getQty();
                    $offerStates = explode(',', trim($offer->getAdditionalInfo()[self::ORIGIN_ADDRESS_STATES]));
                    $offerOriginReference = $offer->getAdditionalInfo()[self::ORIGIN_ADDRESS_REFERENCE];

                    if (in_array($regionCode, $offerStates) || trim($offer->getAdditionalInfo()[self::ORIGIN_ADDRESS_STATES]) == self::ALL) {
                        if (isset($this->addressCounted[$offer->getId()][$offerOriginReference])) {
                            $itemQty = $itemQty + $this->addressCounted[$offer->getId()][$offerOriginReference];
                        }

                        $this->addressCounted[$offer->getId()][self::CITY] = isset($offer->getAdditionalInfo()[self::ORIGIN_CITY])
                            ? $offer->getAdditionalInfo()[self::ORIGIN_CITY]
                            : $shopShippingInfo[self::ORIGIN_SHOP_CITY];

                        $this->addressCounted[$offer->getId()][self::STATE_OR_PROVINCE] = isset($offer->getAdditionalInfo()[self::ORIGIN_STATE])
                            ? $offer->getAdditionalInfo()[self::ORIGIN_STATE]
                            : $shopShippingInfo[self::ORIGIN_SHOP_STATE];

                        $this->addressCounted[$offer->getId()][self::POSTAL_CODE] = isset($offer->getAdditionalInfo()[self::ORIGIN_ZIPCODE])
                            ? $offer->getAdditionalInfo()[self::ORIGIN_ZIPCODE]
                            : $shopShippingInfo[self::ORIGIN_SHOP_ZIPCODE];

                        $this->addressCounted[$offer->getId()][$offerOriginReference] = $itemQty;
                    }
                }
            }

            $hasOnlySamplePackProduct = false;
            $freightInfo = [];
            if ($this->data->isFreightShippingEnabled()) {
                // Check if cart only contains sample widget product to calculate free shipping

                if($this->nonCustomizableProduct->isMktCbbEnabled()){
                    if ($this->quoteHelper->isMiraklQuote($quote)) {
                        $hasOnlySamplePackProduct =  $this->checkCBBSampleProductInCartByAllShops($shops);
                    }
                }else{
                    if ($this->quoteHelper->isMiraklQuote($quote) && count($shops) == 1) {
                        foreach ($shops as $shop) {
                            if (count($shop['items']) == 1) {
                                foreach ($shop['items'] as $item) {
                                    $punchoutEnabled = false;
                                    if (!is_null($item->getAdditionalData())) {
                                        $additionalInfo = json_decode($item->getAdditionalData());
                                        if (isset($additionalInfo->punchout_enabled)) {
                                            $punchoutEnabled = (bool)$additionalInfo->punchout_enabled;
                                        }
                                        if (!$punchoutEnabled) {
                                            $hasOnlySamplePackProduct = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$hasOnlySamplePackProduct) {
                    $freightInfo = $this->packagingCheckoutPricing->getPackagingItems();
                }
            }

            foreach ($shops as $shop) {
                $boxType = '';
                $totalPackageCount = 0;
                $timeZone = $shop['shop']->getTimezone();
                $shopShippingInfo = $shop['shop']->getShippingRateOption();

                // Get current date in CST timeformat
                $currentDateTime = $this->timezone->formatDateTime(
                    $this->timezone->date(),
                    null,
                    null,
                    null,
                    $timeZone,
                    'yyyy-MM-dd\'T\'HH:mm:ss'
                );

                if ($this->data->isFreightShippingEnabled() && $shopShippingInfo['freight_enabled']
                    && $freightInfo && !$hasOnlySamplePackProduct) {
                    $sellerPackage = $this->packagingCheckoutPricing->findSellerRecord($shop['shop']->getId(), $freightInfo);
                    if ($sellerPackage) {
                        foreach ($sellerPackage as $item) {
                            $packaging = $item['packaging'] ?? [];
                            if (isset($packaging['type']) && $packaging['type'] == 'pallet') {
                                $shipDate = $this->buildDeliveryDate->getAllowedDeliveryDate(
                                    $currentDateTime,
                                    max($shop['business_days']),
                                    $shopShippingInfo['shipping_cut_off_time'],
                                    $shopShippingInfo['shipping_seller_holidays'],
                                    (int)$shopShippingInfo['additional_processing_days'],
                                    $timeZone
                                );

                                $shippingAccountNumber = $shopShippingInfo['shipping_account_number'];
                                $marketPlaceShippingAccountNumber = $this->getFedExAccountNumber();
                                if ($this->marketplaceHelper->isCustomerShippingAccount3PEnabled()
                                    && $this->marketplaceHelper->isVendorSpecificCustomerShippingAccountEnabled()
                                    && $shopShippingInfo['customer_shipping_account_enabled']
                                    && !empty($marketPlaceShippingAccountNumber)) {
                                    $shippingAccountNumber = $marketPlaceShippingAccountNumber;
                                }

                                $residentialValue = false;
                                $requestData = $this->request->getContent();
                                $requestDataDecoded = json_decode((string)$requestData, true);
                                if (!empty($requestDataDecoded['address']['custom_attributes'])) {
                                    foreach ($requestDataDecoded['address']['custom_attributes'] as $attribute) {
                                        if ($attribute['attribute_code'] === 'residence_shipping') {
                                            $residenceShippingValue = $attribute['value'];
                                            if ($this->toggleConfig->getToggleConfigValue('tiger_d213977')) {
                                                if ($residenceShippingValue === true || $residenceShippingValue === 1) {
                                                    $residentialValue = true;
                                                    break;
                                                }
                                            } else {
                                                if ($residenceShippingValue === true) {
                                                    $residentialValue = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }

                                $packaging['specialServices'] = $this->isLoadingDockSelected();

                                $dataFromRates = $this->freightCheckoutPricing->execute(
                                    $shopShippingInfo,
                                    $shippingAddress,
                                    date("Y-m-d", $shipDate),
                                    $packaging,
                                    $shippingAccountNumber,
                                    $residentialValue
                                );

                                if ($this->checkoutConfig->isSelfRegCustomer()
                                    && $this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_MKT_SELFREG_SITE)) {
                                    $deliveryMethods = $this->getCompanyAllowedShippingMethods();
                                } else {
                                    $deliveryMethods = json_decode($shop['shop']['shipping_methods'], true);
                                }

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

                                            $deliveryDate = date("l, F j, g:ia",
                                                strtotime(
                                                    $ratesFromRatesFedexApi
                                                    [FedexRateApiDataInterface::OPERATIONAL_DETAIL]
                                                    [FedexRateApiDataInterface::DELIVERY_DATA]
                                                )
                                            );

                                            $methodTitle = $deliveryMethod[0]['shipping_method_label'];
                                            $deliveryDateText = $this->addEndOfDayTextInDeliveryDate($methodTitle, $deliveryDate);
                                            $carrierCode = 'marketplace_' . $ratesFromRatesFedexApi
                                                [FedexRateApiDataInterface::SERVICE_TYPE];

                                            $item = $shop['items'][0];
                                            $liftGateAmount = 0;
                                            if (isset($ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                                [0][FedexRateApiDataInterface::SHIPMENT_RATE_DETAIL][FedexRateApiDataInterface::SURCHARGES])) {
                                                $surcharges = $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                                [0][FedexRateApiDataInterface::SHIPMENT_RATE_DETAIL][FedexRateApiDataInterface::SURCHARGES];
                                                foreach ($surcharges as $surcharge) {
                                                    if ($surcharge['type'] == 'LIFTGATE_DELIVERY') {
                                                        $liftGateAmount = $surcharge['amount'];
                                                    }
                                                }
                                            }
                                            $shippingMethods[] = [
                                                'carrier_code' => $carrierCode,
                                                'method_code' => $ratesFromRatesFedexApi
                                                [FedexRateApiDataInterface::SERVICE_TYPE], //TODO
                                                'carrier_title' => ucwords(\Magento\Fedex\Model\Carrier::CODE),
                                                'method_title' => $this->data->handleMethodTitle($methodTitle),
                                                'amount' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                                [0]['totalNetFedExCharge'],
                                                'base_amount' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                                [0]['totalNetFedExCharge'],
                                                'available' => true,
                                                'price_incl_tax' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                                [0]['totalNetFedExCharge'],
                                                'price_excl_tax' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                                [0]['totalNetFedExCharge'],
                                                'offer_id' => $item->getData('mirakl_offer_id'),
                                                'title' => $item->getData('mirakl_shop_name'),
                                                'selected' => $carrierCode . '_' . $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_TYPE],
                                                'selected_code' => $ratesFromRatesFedexApi
                                                [FedexRateApiDataInterface::SERVICE_TYPE],
                                                'item_id' => $item->getId(),
                                                'shipping_type_label' => $methodTitle,
                                                'deliveryDate' => $deliveryDate,
                                                'deliveryDateText' => $deliveryDateText,
                                                'marketplace' => true,
                                                'seller_id' => $item->getData('mirakl_shop_id'),
                                                'seller_name' => $shop['shop']->getSellerAltName(),
                                                'surcharge_amount' => number_format(((float)$liftGateAmount), 2)
                                            ];
                                        }
                                    }
                                }
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

                /**
                 * Default to Fedex Transit Rates/Mirakl Shipping if anyone of the conditions is met:
                 * 1. Freight Shipping is disabled
                 * 2. Seller does not have Freight Shipping enabled
                 * 3. Cart only contains Sample Pack Product
                 * 4. Packaging Type is of Box type
                 */
                if (!$this->data->isFreightShippingEnabled() || !$shopShippingInfo['freight_enabled']
                    || $hasOnlySamplePackProduct || $boxType == FreightCheckoutPricing::BOX_TYPE) {


                    // Set Sample Widget Product to use Mirakl Shipping
                    if($this->nonCustomizableProduct->isMktCbbEnabled()){
                        if ($hasOnlySamplePackProduct && $this->checkCBBSampleProductInCartByShop($shop)) {
                            $shopShippingInfo['shipping_rate_option'] = self::MIRAKL_SHIPPING_RATES_CONFIGURATION;
                        }
                    }else{
                        if ($hasOnlySamplePackProduct) {
                            $shopShippingInfo['shipping_rate_option'] = self::MIRAKL_SHIPPING_RATES_CONFIGURATION;
                        }
                    }

                    // Fedex Shipping rates
                    if ($shopShippingInfo['shipping_rate_option'] == self::FEDEX_SHIPPING_RATES_CONFIGURATION) {
                        // Build Ship Date logic
                        $shipDate = $this->buildDeliveryDate->getAllowedDeliveryDate(
                            $currentDateTime,
                            max($shop['business_days']),
                            $shopShippingInfo['shipping_cut_off_time'],
                            $shopShippingInfo['shipping_seller_holidays'],
                            (int)$shopShippingInfo['additional_processing_days'],
                            $timeZone
                        );

                        $shippingAccountNumber = $shopShippingInfo['shipping_account_number'];
                        $marketPlaceShippingAccountNumber = $this->getFedExAccountNumber();
                        if ($this->marketplaceHelper->isCustomerShippingAccount3PEnabled()
                            && $this->marketplaceHelper->isVendorSpecificCustomerShippingAccountEnabled()
                            && $shopShippingInfo['customer_shipping_account_enabled']
                            && !empty($marketPlaceShippingAccountNumber)) {
                            $shippingAccountNumber = $marketPlaceShippingAccountNumber;
                        }

                        $dataFromRates = $this->createDataForFedexRatesApiEnhancement(
                            date("Y-m-d", $shipDate),
                            $shop['shop'],
                            $shippingAddress,
                            $shippingAccountNumber,
                            $this->marketplaceHelper->isCustomerShippingAccount3PEnabled(),
                            $shop['weight'],
                            $totalPackageCount
                        );


                        if ($this->checkoutConfig->isSelfRegCustomer()
                            && $this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_MKT_SELFREG_SITE)) {
                            $deliveryMethods = $this->getCompanyAllowedShippingMethods();
                        } else {
                            $deliveryMethods = json_decode($shop['shop']->getData('shipping_methods'), true);
                        }

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
                                    $methodTitle = $ratesFromRatesFedexApi
                                    [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                    [FedexRateApiDataInterface::DESCRIPTION];
                                    $deliveryDateText = $this->addEndOfDayTextInDeliveryDate($methodTitle, $deliveryDate);
                                    $carrierCode = 'marketplace_' . $ratesFromRatesFedexApi
                                        [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                        [FedexRateApiDataInterface::SERVICE_TYPE];

                                    $item = $shop['items'][0];
                                    $shippingMethods[] = [
                                        'carrier_code' => $carrierCode,
                                        'method_code' => $ratesFromRatesFedexApi
                                        [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                        [FedexRateApiDataInterface::SERVICE_TYPE],
                                        'carrier_title' => ucwords(\Magento\Fedex\Model\Carrier::CODE),
                                        'method_title' => $this->data->handleMethodTitle($methodTitle),
                                        'amount' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                        [0]['totalNetFedExCharge'],
                                        'base_amount' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                        [0]['totalNetFedExCharge'],
                                        'available' => true,
                                        'price_incl_tax' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                        [0]['totalNetFedExCharge'],
                                        'price_excl_tax' => $ratesFromRatesFedexApi[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS]
                                        [0]['totalNetFedExCharge'],
                                        'offer_id' => $item->getData('mirakl_offer_id'),
                                        'title' => $item->getData('mirakl_shop_name'),
                                        'selected' => $carrierCode . '_' . $ratesFromRatesFedexApi[FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                            ['serviceType'],
                                        'selected_code' => $ratesFromRatesFedexApi
                                        [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                        [FedexRateApiDataInterface::SERVICE_TYPE],
                                        'item_id' => $item->getId(),
                                        'shipping_type_label' => $ratesFromRatesFedexApi
                                        [FedexRateApiDataInterface::SERVICE_DESCRIPTION]
                                        [FedexRateApiDataInterface::DESCRIPTION],
                                        'deliveryDate' => $deliveryDate,
                                        'deliveryDateText' => $deliveryDateText,
                                        'marketplace' => true,
                                        'seller_id' => $item->getData('mirakl_shop_id'),
                                        'seller_name' => $shop['shop']->getSellerAltName()
                                    ];
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
                                foreach ($this->getItemShippingTypes($item, $shippingAddress) as $i => $shippingType) {
                                    /** @var ShippingFeeType $shippingType */
                                    $shopId = $item->getData('mirakl_shop_id');

                                    $deliveryDate = date_format($shippingType->getDeliveryTime()->getLatestDeliveryDate(), "l, F j");

                                    $carrierCode = 'marketplace_' . $item->getMiraklOfferId();
                                    $shippingMethods[] = [
                                        'carrier_code' => $carrierCode,
                                        'method_code' => $shippingType->getCode(),
                                        'carrier_title' => $item->getData('mirakl_shop_name'),
                                        'method_title' => $this->data->handleMethodTitle($shippingType->getLabel()),
                                        'amount' => $shippingType->getData('total_shipping_price_incl_tax'),
                                        'base_amount' => $shippingType->getData('total_shipping_price_incl_tax'),
                                        'available' => true,
                                        'price_incl_tax' => $shippingType->getData('price_incl_tax'),
                                        'price_excl_tax' => $shippingType->getData('price_excl_tax'),
                                        'offer_id' => $item->getData('mirakl_offer_id'),
                                        'title' => $item->getData('mirakl_shop_name'),
                                        'selected' => $carrierCode . '_' . $selectedShippingType->getCode(),
                                        'selected_code' => $selectedShippingType->getCode(),
                                        'item_id' => $item->getId(),
                                        'shipping_type_label' => $shippingType->getLabel(),
                                        'deliveryDate' => $deliveryDate,
                                        'deliveryDateText' => $deliveryDate,
                                        'marketplace' => true,
                                        'seller_id' => $shopId,
                                        'seller_name' => $shop['shop']->getSellerAltName()
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            if (isset($dataFromRates['errors'])) {
                $shippingMethods = [];
                $error = $dataFromRates;
            } else {
                $baseAmounts = [];
                $originalKeys = [];

                foreach ($shippingMethods as $key => $method) {
                    $baseAmounts[$key] = is_object($method) ? $method->getBaseAmount() : $method['base_amount'];
                    $originalKeys[$key] = $key;
                }
                array_multisort($baseAmounts, $originalKeys, $shippingMethods);
            }
        }
    }

    /**
     * @return mixed|void
     */
    public function getCompanyAllowedShippingMethods(): array
    {
        $customer = $this->retailHelper->getCustomer();
        $company  = $this->retailHelper->getAssignedCompany($customer);
        if ($company && $company->getAllowedDeliveryOptions() != '') {
            $deliveryOptions = json_decode($company->getAllowedDeliveryOptions());

            $shippingMethodMapping = [
                'TWO_DAY'           => 'FEDEX_2_DAY',
                'EXPRESS_SAVER'     => 'FEDEX_EXPRESS_SAVER',
                'GROUND_US'         => 'FEDEX_GROUND',
                'LOCAL_DELIVERY_AM' => 'FEDEX_2_DAY_AM',
                'LOCAL_DELIVERY_PM' => 'FEDEX_2_DAY_PM',
            ];

            $deliveryOptions = array_map(function ($method) use ($shippingMethodMapping) {
                $mappedMethod = $shippingMethodMapping[$method] ?? $method;
                return ['shipping_method_name' => strtoupper($mappedMethod)];
            }, $deliveryOptions);

            return $deliveryOptions;
        }
    }

    /**
     * Get filtered offers by sky and shopId.
     *
     * @param $productSku
     * @param $shopId
     * @return mixed
     */
    public function getFilteredOffers($productSku, $shopId)
    {
        /** @var OfferCollection $offerCollection */
        $offerCollection = $this->offerCollectionFactory->create();

        $filteredCollection = $offerCollection
            ->addFieldToFilter('product_sku', $productSku)
            ->addFieldToFilter('shop_id', $shopId);

        return $filteredCollection->getItems();
    }

    /**
     * Return address elected based on qty by region.
     *
     * @return mixed|null
     */
    public function getElectedAddress()
    {
        $sums = [];
        $maxArray = null;
        $maxValue = PHP_INT_MIN;

        foreach ($this->addressCounted as $array) {
            foreach ($array as $key => $value) {
                if ($key !== self::CITY && $key !== self::STATE_OR_PROVINCE && $key !== self::POSTAL_CODE) {
                    if (!isset($sums[$key])) {
                        $sums[$key] = 0;
                    }

                    $sums[$key] += $value;

                    if ($sums[$key] > $maxValue) {
                        $maxValue = $sums[$key];
                        $maxArray = $array;
                    }
                }
            }
        }

        return $maxArray;
    }

    /**
     * Convert weight from oz to lbs if nececessary.
     *
     * @param QuoteItem $item
     * @return float|int|void
     */
    public function getLbsWeight(QuoteItem $item)
    {
        $conversionFactor = 1 / 16;
        $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
        if (isset($additionalData['weight_unit']) && $additionalData['weight_unit'] == self::WEIGHT_OZ_UNIT) {
            return $item->getWeight() * $conversionFactor;
        }
        return $item->getWeight();
    }

    /**
     * Get Rates from Fedex API
     * @param $shipDate
     * @param $shopData
     * @param $shippingAddress
     * @param $shipAccountNumber
     * @param bool $customerShippingAccount3PEnabled
     * @param float $weight
     * @param int $totalPackageCount
     * @return string
     */
    protected function createDataForFedexRatesApiEnhancement($shipDate, $shopData, $shippingAddress, $shipAccountNumber, bool $customerShippingAccount3PEnabled, float $weight = 0, int $totalPackageCount = 0)
    {
        $shopArrayData = $shopData->getData();
        $offerAddress = $this->getElectedAddress();
        $regionCode = '';
        $regionName = $offerAddress['stateOrProvinceCode'] ?? $shopArrayData["additional_info"]['contact_info']['state'];

        if (!empty($regionName)) {
            $region = $this->collectionFactory->create()
                ->addRegionNameFilter($regionName)
                ->addCountryCodeFilter($shopArrayData["additional_info"]['contact_info']['country'])
                ->getFirstItem()
                ->toArray();

            if (count($region) > 0) {
                $regionCode = $region['code'];
            }
        }

        $residentialValue = false;
        $requestData = $this->request->getContent();
        $requestDataDecoded = json_decode((string)$requestData, true);
        if (!empty($requestDataDecoded['address']['custom_attributes'])) {
            foreach ($requestDataDecoded['address']['custom_attributes'] as $attribute) {
                if ($attribute['attribute_code'] === 'residence_shipping') {
                    $residenceShippingValue = $attribute['value'];
                    if ($this->toggleConfig->getToggleConfigValue('tiger_d213977')) {
                        if ($residenceShippingValue === true || $residenceShippingValue === 1) {
                            $residentialValue = true;
                            break;
                        }
                    } else {
                        if ($residenceShippingValue === true) {
                            $residentialValue = true;
                            break;
                        }
                    }
                }
            }
        }

        $shippingMKtData = [
            "rateRequestControlParameters" => [
                "rateSortOrder" => "SERVICENAMETRADITIONAL",
                "returnTransitTimes" => true
            ],
            "requestedShipment" => [
                "shipDateStamp" => $shipDate,
                "shipper" => [
                    "address" => [
                        "city" => $offerAddress['city'] ?? $shopArrayData["additional_info"]['contact_info']['city'],
                        "stateOrProvinceCode" => $regionCode,
                        "postalCode" => $offerAddress['postalCode'] ?? $shopArrayData["additional_info"]['contact_info']['zip_code'],
                        "countryCode" => strtoupper($shopArrayData["additional_info"]["shipping_zones"][0])
                    ]
                ],
                "recipient" => [
                    "address" => [
                        "city" => $shippingAddress->getCity(),
                        "stateOrProvinceCode" => $shippingAddress->getRegionCode(),
                        "postalCode" => $shippingAddress->getPostcode(),
                        "countryCode" => $shippingAddress->getCountryId(),
                        "residential" => $residentialValue
                    ]
                ],
                "pickupType" => "DROPOFF_AT_FEDEX_LOCATION",
                "rateRequestType" => [
                    !empty($shipAccountNumber) ? "ACCOUNT" : "LIST"
                ],
                "requestedPackageLineItems" => [
                    [
                        "weight" => [
                            "units" => "LB",
                            "value" => $weight == 0 ? $this->totalCartWeight : $weight
                        ]
                    ]
                ],
                "preferredCurrency" => "USD"
            ],
            "carrierCodes" => [
                "FDXE",
                "FDXG"
            ],
            "returnLocalizedDateTime" => true
        ];

        $shippingMKtData["accountNumber"]["value"] = $shipAccountNumber;

        if ($totalPackageCount > 0) {
            $shippingMKtData['requestedShipment']['totalPackageCount'] = $totalPackageCount;
        }

        return $this->data->getResponseFromFedexRatesAPI($customerShippingAccount3PEnabled, $shipAccountNumber, json_encode($shippingMKtData));
    }

    /**
     * Get Rates from Fedex API
     *
     * @param string $shipDate
     * @param ShopInterface $shopData
     * @param Quote\Address|null $shippingAddress
     * @param string $shipAccountNumber
     * @return string
     */
    protected function createDataForFedexRatesApi($shipDate, $shopData, $shippingAddress, $shipAccountNumber, bool $customerShippingAccount3PEnabled)
    {
        $shopArrayData = $shopData->getData();
        $regionCode = '';
        $regionName = $shopArrayData["additional_info"]['contact_info']['state'];
        if (!empty($regionName)) {
            $region = $this->collectionFactory->create()
                ->addRegionNameFilter($regionName)
                ->getFirstItem()
                ->toArray();

            if (count($region) > 0) {
                $regionCode = $region['code'];
            }
        }


        $shippingMKtData = [
            "rateRequestControlParameters" => [
                "rateSortOrder" => "SERVICENAMETRADITIONAL",
                "returnTransitTimes" => true
            ],
            "requestedShipment" => [
                "shipDateStamp" => $shipDate,
                "shipper" => [
                    "accountNumber" => [
                        "key" => $shipAccountNumber
                    ],
                    "address" => [
                        "city" =>  $shopArrayData["additional_info"]['contact_info']['city'],
                        "stateOrProvinceCode" => $regionCode,
                        "postalCode" => $shopArrayData["additional_info"]['contact_info']['zip_code'],
                        "countryCode" => strtoupper($shopArrayData["additional_info"]["shipping_zones"][0])
                    ]
                ],
                "recipient" => [
                    "address" => [
                        "city" => $shippingAddress->getCity(),
                        "stateOrProvinceCode" =>  $shippingAddress->getRegionCode(),
                        "postalCode" => $shippingAddress->getPostcode(),
                        "countryCode" => $shippingAddress->getCountryId(),
                        "residential" => $shippingAddress->getCompany() ? false : true
                    ]
                ],
                "pickupType" => "DROPOFF_AT_FEDEX_LOCATION",
                "rateRequestType" => [
                    !empty($shipAccountNumber) ? "ACCOUNT" : "LIST"
                ],
                "requestedPackageLineItems" => [
                    [
                        "weight" => [
                            "units" => "LB",
                            "value" => $this->totalCartWeight
                        ]
                    ]
                ],
                "preferredCurrency" => "USD"
            ],
            "carrierCodes" => [
                "FDXE",
                "FDXG"
            ],
            "returnLocalizedDateTime" => true
        ];

        return $this->data->getResponseFromFedexRatesAPI($customerShippingAccount3PEnabled, $shipAccountNumber, json_encode($shippingMKtData));
    }

    /**
     * Get selected item shipping type.
     *
     * @param   QuoteItem   $item
     * @return  ShippingFeeType
     */
    private function getItemSelectedShippingType(QuoteItem $item)
    {
        if ($shippingTypeCode = $item->getMiraklShippingType()) {
            return $this->quoteUpdater->getItemShippingTypeByCode($item, $shippingTypeCode);
        }

        return $this->quoteUpdater->getItemSelectedShippingType($item);
    }

    /**
     * Return item shipping type.
     *
     * @param   QuoteItem           $item
     * @param   Quote\Address|null  $shippingAddress
     * @return  ShippingFeeTypeCollection
     */
    private function getItemShippingTypes(QuoteItem $item, $shippingAddress = null)
    {
        return $this->quoteItemHelper->getItemShippingTypes($item, $shippingAddress);
    }

    /**
     * @return bool
     */
    private function isPickup(): bool
    {
        $requestData = json_decode($this->request->getContent() ?? '{}', true);
        return $requestData['isPickup'] ?? false;
    }

    /**
     * @return false|mixed
     */
    private function getFedExAccountNumber()
    {
        $requestData = json_decode($this->request->getContent() ?? '{}', true);
        return $requestData['fedEx_account_number'] ?? '';
    }

    /**
     * @return false|mixed
     */
    private function isLoadingDockSelected()
    {
        $requestData = json_decode($this->request->getContent() ?? '{}', true);
        return $requestData['hasLiftGate'] ?? '';
    }

    /**
     * Get items with offers combined.
     *
     * @param CartInterface $quote
     * @return array
     */
    public function getItemsWithOfferCombined(CartInterface $quote): array
    {
        $quoteItemsWithOffer = [];

        /** @var QuoteItem $quoteItem */
        foreach ($this->offerCollector->getQuoteItems($quote) as $quoteItem) {

            if ($quoteItem->isDeleted() || $quoteItem->getParentItemId()) {
                continue;
            }

            /** @var QuoteItemOption $offerCustomOption */
            $offerCustomOption = $quoteItem->getProduct()->getCustomOption('mirakl_offer');

            if (!$offerCustomOption) {
                continue;
            }

            $offerData = $offerCustomOption->getValue();
            $offer = json_decode($offerData, true);
            
            // Merge items if they have the same offer id
            if (isset($quoteItemsWithOffer[$offer['offer_id']])) {
                $existingOffer = $quoteItemsWithOffer[$offer['offer_id']];
                $quoteItem->setQty($quoteItem->getQty() + $existingOffer->getQty());
            }

            $quoteItemsWithOffer[$quoteItem->getData('mirakl_shop_id')] = $quoteItem;
            $shopId = $quoteItem->getData('mirakl_shop_id');
            $this->productionDays[$shopId][] = max(explode(',', $quoteItem->getProduct()->getProductionDays()));
        }
        return $quoteItemsWithOffer;
    }

    /**
     * @param string $label
     * @param string $deliveryDate
     * @return string
     */
    private function addEndOfDayTextInDeliveryDate(string $label, string $deliveryDate): string
    {
        if (stripos($label, self::GROUND_US_NO_MARK) !== false) {
            $deliveryDate = date('l, F d', strtotime($deliveryDate));
            $deliveryDate = $deliveryDate . ', ' . static::EOD_TEXT;
        }
        return $deliveryDate;
    }

    /**
     * @param $shops
     * @param bool $isAllShops
     * @return bool
     */
    private function checkCBBSampleProductInCartByAllShops($shops, bool $isAllShops = true): bool
    {
        $hasOnlySamplePackProduct = false;
        $hasOnlySamplePackProductData = [];
        if ($isAllShops) {
            foreach ($shops as $shop) {
                $hasOnlySamplePackProductData[] = $this->checkCBBSampleProductInCartByShop($shop);
            }
            if (in_array(true, $hasOnlySamplePackProductData)) {
                $hasOnlySamplePackProduct = true;
            }
        } else {
            $hasOnlySamplePackProduct = $this->checkCBBSampleProductInCartByShop($shops);
        }
        return $hasOnlySamplePackProduct;
    }

    /**
     * Helper method to check if a sample product exists in a single shop.
     *
     * @param $shop - Shop data to check.
     * @return bool
     */
    private function checkCBBSampleProductInCartByShop($shop): bool
    {
        $hasOnlySamplePackProduct = false;
        if (count($shop['items']) == 1) {
            foreach ($shop['items'] as $item) {
                $punchoutEnabled = false;
                if (!is_null($item->getAdditionalData())) {
                    $additionalInfo = json_decode($item->getAdditionalData());
                    if (isset($additionalInfo->punchout_enabled)) {
                        $punchoutEnabled = (bool)$additionalInfo->punchout_enabled;
                    }
                    if (!$punchoutEnabled) {
                        $offers = $this->getOfferItemsByOfferId($item->getData('mirakl_offer_id'));
                        foreach ($offers as $offer) {
                            $offerData = $offer->getAdditionalInfo();
                            $hasOnlySamplePackProduct =
                                (isset($offerData['force_mirakl_shipping_options']) && $offerData['force_mirakl_shipping_options'] == 'true');
                        }
                    }
                }
            }
        }
        return $hasOnlySamplePackProduct;
    }

    /**
     * @param $offerId
     * @return \Magento\Framework\DataObject[]
     */
    public function getOfferItemsByOfferId($offerId){
        $offerCollection = $this->offerCollectionFactory->create();
        return $offerCollection
            ->addFieldToFilter('offer_id', $offerId)->getItems();
    }
}
