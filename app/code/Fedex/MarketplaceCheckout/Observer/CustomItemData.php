<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Observer;

use DOMException;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ItemRepository;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrderOffer;
use Mirakl\MMP\FrontOperator\Domain\Order\CustomerShippingAddressFactory;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Magento\Framework\Xml\Generator;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;

class CustomItemData implements ObserverInterface
{
    /** @var string */
    private const SHIPPING_RATE_OPTION_KEY = 'shipping_rate_option';

    /** @var string */
    private const FEDEX_SHIPPING_RATES_VALUE = 'fedex-shipping-rates';

    /** @var string */
    private const FEDEX_RATES = 'FEDEX_RATES';

    /**
     * @param ItemRepository $itemRepository
     * @param CustomerShippingAddressFactory $customerShippingAddressFactory
     * @param CountryFactory $countryFactory
     * @param CollectionFactory $collectionFactory
     * @param Data $helper
     * @param HandleMktCheckout $handleMktCheckout
     * @param OfferHelper $offerHelper
     * @param ShopRepositoryInterface $shopRepository
     * @param MarketplaceRatesHelper $marketplaceRatesHelper
     * @param Generator $xmlGenerator
     * @param LoggerInterface $logger
     * @param PackagingCheckoutPricing $packagingCheckoutPricing
     */
    public function __construct(
        private ItemRepository                      $itemRepository,
        private CustomerShippingAddressFactory      $customerShippingAddressFactory,
        private CountryFactory                      $countryFactory,
        private CollectionFactory                   $collectionFactory,
        private Data                                $helper,
        readonly private HandleMktCheckout          $handleMktCheckout,
        readonly private OfferHelper                $offerHelper,
        readonly private ShopRepositoryInterface    $shopRepository,
        private MarketplaceRatesHelper              $marketplaceRatesHelper,
        private Generator                           $xmlGenerator,
        private LoggerInterface             $logger,
        private PackagingCheckoutPricing $packagingCheckoutPricing
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $createOrder = $observer->getEvent()->getCreateOrder();
        $order = $observer->getEvent()->getOrder();
        $additionalAttributesData = json_decode((string) $order->getPayment()->getProductLineDetails());
        $offerItems = $createOrder->getOffers()->getItems();
        $offerLeadTimes = $this->getOfferLeadTimes($offerItems);
        foreach ($offerItems as $offer) {
            $orderLineId = $offer->getOrderLineId();
            if (isset($offerLeadTimes[$offer->getId()]) && !is_null($offerLeadTimes[$offer->getId()])) {
                $offer->setData('leadtime_to_ship', $offerLeadTimes[$offer->getId()]);
            }
            /** @var Item $orderItem */
            $orderItem = $this->itemRepository->get($orderLineId);

            $offerPrice = round((float)$offer->getPrice(), 2);
            $offer->setPrice($offerPrice);
            $couponCode = '';
                if (!empty($additionalAttributesData)) {
                    foreach ($additionalAttributesData as $additionalAttribute) {
                        if ($orderItem->getQuoteItemId() == $additionalAttribute->instanceId
                            && (float)$additionalAttribute->productDiscountAmount > 0) {
                            foreach ($additionalAttribute->productLineDetails as $attribute) {
                                $offer->setPrice(round((float)$attribute->detailPrice, 2));
                                $discountedUnitPrice = (float)$attribute->detailPrice / (int)$attribute->unitQuantity;
                                $offer->setOfferPrice(round($discountedUnitPrice, 4));
                                $couponCode = $order->getCouponCode() ?? '';
                            }
                        }
                    }
                }

            $this->setCustomDataToOffer($orderItem, $offer, $createOrder, $couponCode, $offerPrice);
        }
    }

    /**
     * @param Item $orderItem
     * @param CreateOrderOffer $offer
     * @param MiraklObject $createOrder
     * @param string $couponCode
     * @param float $originalPrice
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException|DOMException
     */
    public function setCustomDataToOffer(Item $orderItem, CreateOrderOffer $offer, MiraklObject $createOrder, string $couponCode = '', float $originalPrice = 0): void
    {
        $additionalFields = [];
        $format = null;
        $shop = null;

        $additionalData = $orderItem->getAdditionalData();
        if ($additionalData) {
            $additionalData = json_decode($additionalData, true);

            if (isset($additionalData['quantity'])) {
                $offer->setQuantity((int)$additionalData['quantity']);
            }

            if (isset($additionalData['supplierPartAuxiliaryID'])) {
                $additionalFields[] = [
                    "code" => "supplier-part-auxiliary-id",
                    "value" => $additionalData['supplierPartAuxiliaryID']
                ];
            }
            if (isset($additionalData['supplierPartID'])) {
                $additionalFields[] = [
                    "code" => "supplier-part-id",
                    "value" => $additionalData['supplierPartID']
                ];
            }
            if (isset($additionalData['features'])) {
                $featureFormatted = [];
                foreach ($additionalData['features'] as $feature) {
                    $featureFormatted[] = $feature["name"] . ': ' . $feature["choice"]["name"];
                }
                if (count($featureFormatted)) {
                    $additionalFields[] = [
                        "code" => "additional-details",
                        "value" => implode('; ', $featureFormatted)
                    ];
                }
            }
            if (isset($additionalData['variantDetails'])) {
                $variantQuantities = [];
                foreach ($additionalData['variantDetails'] as $variantDetails) {
                    if(isset($variantDetails['VariantID'])){
                        $variantQuantities[] = [
                            'variant_id' => (int)$variantDetails['VariantID'],
                            'quantity' => (int)$variantDetails['Quantity']
                        ];
                    }
                }
                if (count($variantQuantities)) {
                    $additionalFields[] = [
                        "code" => "variant-quantities",
                        "value" => json_encode($variantQuantities)
                    ];
                }
            }
            if (isset($additionalData['mirakl_shipping_data'])) {
                $miraklShippingData = $additionalData['mirakl_shipping_data'];
                if ($miraklShippingData) {
                    $miraklShippingData = $this->unsetAdditionalDataFields($miraklShippingData);
                    if(isset($additionalData['mirakl_shipping_data']['address'])) {
                        $miraklShippingData['address'] = $additionalData['mirakl_shipping_data']['address'];
                    }
                    if (!empty($couponCode)) {
                        $miraklShippingData['coupon_code'] = $couponCode;
                    }
                    $miraklShippingData['original_offer_price'] = $originalPrice;

                    if ($this->marketplaceRatesHelper->isFreightShippingEnabled()
                        && !empty($additionalData['freight_data'])) {

                        $packagingData = $additionalData['freight_data'];
                        $miraklShippingData['packaging_data'] = $packagingData;

                    } elseif ($this->marketplaceRatesHelper->isd2255568toggleEnabled()
                        && empty($additionalData['freight_data'])) {

                        $order = $orderItem->getOrder();
                        $freightInfo = $this->packagingCheckoutPricing->getPackagingItems(false,$order);

                        if ($this->isFreightQuoteItem($additionalData, $freightInfo)) {
                            $sellerPackage = $this->packagingCheckoutPricing
                                ->findSellerRecord($orderItem->getMiraklShopId(), $freightInfo);

                            if ($sellerPackage) {
                                $packaging = [];
                                foreach ($sellerPackage as $item) {
                                    $packaging = $item['packaging'] ?? [];
                                    break;
                                }
                                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Packaging Data for orderItem '.$orderItem->getId());
                                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Packaging Data'.print_r($packaging,true));

                                $miraklShippingData['packaging_data'] = $packaging;
                            }
                        }
                    } else {
                        $miraklShippingData['packaging_data'] = [];
                    }

                    if (isset($miraklShippingData["seller_id"])) {
                        try {
                            $shop = $this->shopRepository->getById((int) $miraklShippingData['seller_id']);
                        } catch (NoSuchEntityException $e) {
                            $this->logger->warning("Shop not found for seller_id: " . $miraklShippingData['seller_id']);
                        }
                    }

                    if ($shop) {
                        $format = $shop->getShippingInfoFormat();
                        if ($this->isCustomerShipmentAccountEnabled($shop) && $this->isCustomerShippingAccountGloballyEnabled()) {
                            $this->injectFedexShippingData($miraklShippingData, $additionalData['mirakl_shipping_data']);
                        } else {
                            foreach (['fedexShipAccountNumber', 'fedexShipReferenceId'] as $key) {
                                unset($miraklShippingData[$key]);
                            }
                        }
                    }

                    if ($this->helper->isEssendantToggleEnabled() && strcasecmp($format, 'N/A') !== 0) {
                        $miraklShippingData = ($format === 'XML')
                            ? $this->generateXML($miraklShippingData)
                            : json_encode($miraklShippingData);

                        $additionalFields[] = [
                            'code' => ($format === 'XML') ? 'shipping-additional-data-xml' : 'shipping-additional-data',
                            'value' => $miraklShippingData
                        ];
                    }
                }
            }
            // Set shipping info to Mirakl order for 1P Pickup + MP shipping
            if (isset($additionalData['mirakl_shipping_data'])) {
                $customerShippingAddress = $createOrder->getCustomer()->getShippingAddress();

                if (isset($additionalData['mirakl_shipping_data']['address'])) {
                    $shippingAddress = $additionalData['mirakl_shipping_data']['address'];

                    $country = $this->countryFactory->create()->loadByCode($shippingAddress['countryId']);

                    $regionCode = $shippingAddress['region'];
                    $regionName = '';
                    if (!empty($regionCode)) {
                        $region = $this->collectionFactory->create()
                            ->addRegionCodeFilter($regionCode)
                            ->addCountryFilter($shippingAddress['countryId'])
                            ->getFirstItem()
                            ->toArray();

                        if (count($region) > 0) {
                            $regionName = $region['name'];
                        }
                    }

                    $street2 = '';
                    if (count($shippingAddress['street']) > 1) {
                        $street2 = $shippingAddress['street'][1];
                    }

                    $customerShippingAddress
                        ->setFirstname(!empty($shippingAddress['altFirstName']) ? $shippingAddress['altFirstName'] : $shippingAddress['firstname'])
                        ->setLastname(!empty($shippingAddress['altLastName']) ? $shippingAddress['altLastName'] : $shippingAddress['lastname'])
                        ->setCity($shippingAddress['city'])
                        ->setCountry($country->getName())
                        ->setCountryIsoCode($country->getData('iso3_code'))
                        ->setStreet1($shippingAddress['street'][0])
                        ->setZipCode($shippingAddress['postcode'])
                        ->setPhone(!empty($shippingAddress['altPhoneNumber']) ? $shippingAddress['altPhoneNumber'] : $shippingAddress['telephone'])
                        ->setCompany($shippingAddress['company'])
                        ->setState($regionName);

                    if (!empty($street2)) {
                        $customerShippingAddress->setStreet2($street2);
                    }
                }

                $additionalData['mirakl_shipping_data'] = $this->unsetAdditionalDataFields($additionalData['mirakl_shipping_data']);
                 if(!$this->helper->isEssendantToggleEnabled()){
                     $customerShippingAddress->setAdditionalInfo(json_encode($additionalData['mirakl_shipping_data'], JSON_PRETTY_PRINT));

                 }
                $createOrder->getCustomer()->setShippingAddress($customerShippingAddress);
            }
        }

        if (count($additionalFields)) {
            $offer->setOrderLineAdditionalFields($additionalFields);
        }

        if ($this->helper->getD194958() && !$offer->getShippingTypeCode()) {
            $offerData = $this->offerHelper->getOfferById($offer->getId());
            if ($this->shippingOptionHasFedexShippingRates((int)($offerData->getShopId() ?? 0))) {
                $offer->setShippingTypeCode(self::FEDEX_RATES);
            }
        }
    }

    /**
     * @param array $additionalInfo
     * @return mixed[]
     */
    private function unsetAdditionalDataFields(array $additionalInfo): array
    {
        $keysToDelete = [
            'address',
            'carrier_title',
            'base_amount',
            'available',
            'price_incl_tax',
            'price_excl_tax',
            'title',
            'selected',
            'selected_code',
            'item_id',
            'marketplace',
            'reference_id',
            'productionLocation'
        ];

        foreach ($keysToDelete as $key) {
            unset($additionalInfo[$key]);
        }
        return $additionalInfo;
    }

    /**
     * Calculate max lead time per seller
     * @param array $offerItems
     * @return array
     */
    private function getOfferLeadTimes(array $offerItems): array
    {
        $leadTimes = [];
        $leadTimeShop = [];
        $offerLeadTimes = [];
        foreach ($offerItems as $offer) {
            $offerData = $this->offerHelper->getOfferById($offer->getId());
            $leadTimes[] = [
                'offer_id' => (int)$offerData->getId(),
                'shop_id' => $offerData->getShopId(),
                'leadtime_to_ship' => $offerData->getLeadtimeToShip(),
            ];
            if (isset($leadTimeShop[$offerData->getShopId()])) {
                if ($leadTimeShop[$offerData->getShopId()] < $offerData->getLeadtimeToShip()) {
                    $leadTimeShop[$offerData->getShopId()] = $offerData->getLeadtimeToShip();
                }
            } else {
                $leadTimeShop[$offerData->getShopId()] = $offerData->getLeadtimeToShip();
            }
        }
        foreach ($leadTimes as $leadTime) {
            $offerLeadTimes[$leadTime['offer_id']] = $leadTimeShop[$leadTime['shop_id']];
        }
        return $offerLeadTimes;
    }

    /**
     * @param int $miraklShopId
     * @return bool
     */
    private function shippingOptionHasFedexShippingRates(int $miraklShopId): bool
    {
        if ($miraklShopId === 0) {
            return false;
        }

        try {
            $shop = $this->shopRepository->getById($miraklShopId);
            $shippingRateOption = $shop->getShippingRateOption() ?? [];

            return isset($shippingRateOption[self::SHIPPING_RATE_OPTION_KEY])
                && $shippingRateOption[self::SHIPPING_RATE_OPTION_KEY] === self::FEDEX_SHIPPING_RATES_VALUE;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param array $miraklShippingData
     * @return string
     * @throws DOMException
     */
    private function generateXML(array $miraklShippingData): string
    {
        $xml = trim($this->xmlGenerator->arrayToXml($this->helper->adjustArrayForXml($miraklShippingData))->__toString());
        $formattedXml = preg_replace('/<\?xml.*?\?>\s*/', '', $xml);
        return $formattedXml;
    }
    /**
     * @param array $additionalData
     * @param array $freightInfo
     * @return bool
     */
    private function isFreightQuoteItem(array $additionalData, array $freightInfo): bool
    {
        return isset($additionalData['punchout_enabled'])
            && (bool)$additionalData['punchout_enabled']
            && isset($additionalData['packaging_data'])
            && !empty($additionalData['packaging_data'])
            && $freightInfo;
    }

    /**
     * @param ShopInterface $shop
     * @return bool
     */
    private function isCustomerShipmentAccountEnabled(ShopInterface $shop): bool
    {
        $fields = $shop->getAdditionalInfo()?->getAdditionalFieldValues() ?? [];

        foreach ($fields as $field) {
            if ($field['code'] === 'enable-customer-shipment-account') {
                return  $field['value'] === "true";
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isCustomerShippingAccountGloballyEnabled(): bool
    {
        return $this->helper->isCustomerShippingAccount3PEnabled()
            && $this->helper->isVendorSpecificCustomerShippingAccountEnabled();
    }

    /**
     * @param array $target
     * @param array $source
     * @return void
     */
    private function injectFedexShippingData(array &$target, array $source): void
    {
        foreach (['fedexShipAccountNumber', 'fedexShipReferenceId'] as $key) {
            if (!empty($source[$key])) {
                $target[$key] = $source[$key];
                continue;
            }
            unset($target[$key]);
        }
    }
}
