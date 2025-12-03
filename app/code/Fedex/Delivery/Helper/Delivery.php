<?php
/**
 * Fedex
 * Copyright (C) 2021 Fedex <info@fedex.com>
 *
 * PHP version 7
 *
 * @category  Fedex
 * @package   Fedex_Code
 * @author    Fedex <info@edex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU (GPL-3.0)
 * @link      http://fedex.com
 */
declare (strict_types = 1);

namespace Fedex\Delivery\Helper;

use Fedex\ComputerRental\Model\CRdataModel;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Header\Helper\Data;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfigInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\InBranch\Model\InBranchValidation;
use Fedex\Delivery\Helper\ShippingDataHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\CartFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\OrderApprovalB2b\ViewModel\ReviewOrderViewModel;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Fedex\Company\Helper\Data As CompanyHelper;
use Fedex\Delivery\Helper\Data As DeliveryHelper;

/**
 * Delivery Helper
 *
 * @author    Fedex <info@edex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU (GPL-3.0)
 * @link      http://fedex.com
 */
class Delivery extends AbstractHelper
{
    public $mapping;
    public const CATALOG_REFERENCE = 'catalogReference';
    public const OUTPUT = 'output';
    public const SERVICE_TYPE = 'serviceType';
    public const ESTIMATED_DELIVERY_LOCAL_TIME = 'estimatedDeliveryLocalTime';
    public const ESTIMATED_DELIVERY_DURATION = 'estimatedDeliveryDuration';
    public const SERVICE_DESCRIPTION = 'serviceDescription';
    public const CURRENCY = 'currency';
    public const PRODUCT_ASSOCIATIONS = 'productAssociations';
    public const ADDRESS_INFORMATION = 'addressInformation';
    public const SHIPPING_METHOD_CODE = 'shipping_method_code';
    public const PICKUP = 'PICKUP';
    public const GROUND_US = 'GROUND_US';
    public const FEDEX_HOME_DELIVERY = 'FEDEX_HOME_DELIVERY';
    public const EOD_TEXT = 'End of Day';
    public const VALUE = 'value';
    private const RECOMMENDED_LOCATION_ALL_LOCATIONS = 'recommended_location_all_locations';
    private const RECOMMENDED_STORES_ALL_LOCATION = 'recommended_stores_all_location';
    private const IS_RECOMMENDED_STORE = 'is_recommended_store';

    /**
     * Delivery construct
     *
     * @param Context $context context
     * @param RegionFactory $regionFactory regionfactory
     * @param CompanyHelper $companyHelper companyHelper
     * @param CartFactory $cartFactory cartfactory
     * @param LoggerInterface $logger logger
     * @param DeliveryHelper $retailHelper retailHelper
     * @param Curl $curl curl
     * @param SdeHelper $sdeHelper sdeHelper
     * @param ToggleConfig $toggleConfig
     * @param CartDataHelper $cartDataHelper
     * @param QuoteHelper $quoteHelper
     * @param Data $data
     * @param PunchoutHelper $punchoutHelper
     * @param ShippingDataHelper $shippingDataHelper
     * @param ScopeConfigInterface $configInterface
     * @param RequestInterface $requestObj
     * @param InBranchValidation $inBranchValidation
     * @param CRdataModel $crData
     * @param ReviewOrderViewModel $reviewOrderViewModel
     * @param ProductionLocationFactory $productionLocationFactory
     */
    public function __construct(
        protected Context $context,
        protected RegionFactory $regionFactory,
        protected CompanyHelper $companyHelper,
        protected CartFactory $cartFactory,
        protected LoggerInterface $logger,
        protected DeliveryHelper $retailHelper,
        private Curl $curl,
        protected SdeHelper $sdeHelper,
        private ToggleConfig $toggleConfig,
        protected CartDataHelper $cartDataHelper,
        protected QuoteHelper $quoteHelper,
        protected Data $data,
        protected PunchoutHelper $punchoutHelper,
        protected ShippingDataHelper $shippingDataHelper,
        protected ScopeConfigInterface $configInterface,
        private RequestInterface $requestObj,
        private InBranchValidation $inBranchValidation,
        private readonly CRdataModel $crData,
        protected ReviewOrderViewModel $reviewOrderViewModel,
        protected ProductionLocationFactory $productionLocationFactory,
        private readonly ProductBundleConfigInterface $productBundleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Get Delivery Options
     *
     * @param array $config config
     *
     * @return array deliveryOptions
     */
    public function getDeliveryOptions($config)
    {
        $logHeader = 'File: ' . static::class . ' Method: ' . __METHOD__;
        $setupURL = $config['delivery_api'];
        $deliveryOption = [];
        $regionId = $config['region_id'];
        $shipperRegionCode = '';
        try {
            $quote = $this->cartFactory->create()->getQuote();
            //Load Quote obj for Order Approval flow
            if ($this->reviewOrderViewModel->isOrderApprovalB2bEnabled() &&
            !empty($this->reviewOrderViewModel->getPendingOrderQuoteId())) {
                $penidngOrderQuoteId = $this->reviewOrderViewModel->getPendingOrderQuoteId();
                $quote = $this->reviewOrderViewModel->getQuoteObj($penidngOrderQuoteId);
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ .
                    'Activating Pending Order Approval Quote with Quote Id : '.
                    $quote->getId()
                );
            }

            $shippingAddress = $quote->getShippingAddress();
            $addressClassification = "BUSINESS";
            $requestData = $this->requestObj->getContent();
            $requestDataDecoded = json_decode((string)$requestData, true);
            if (!empty($requestDataDecoded['address'])) {
                foreach ($requestDataDecoded['address']['custom_attributes'] as $attribute) {
                    if ($this->toggleConfig->getToggleConfigValue('tiger_d213977')) {
                        if ($attribute['attribute_code'] === 'residence_shipping' && ($attribute['value'] === true || $attribute['value'] === 1)) {
                            $addressClassification = "HOME";
                            break;
                        }
                    } else {
                        if ($attribute['attribute_code'] === 'residence_shipping' && ($attribute['value'] === true)) {
                            $addressClassification = "HOME";
                            break;
                        }
                    }
                }
            } else {
                if ($this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix')) {
                    $requestPostData = $this->requestObj->getPost('data');
                    $isResidenceShipping = $shippingAddress->getData('is_residence_shipping');
                    if ($isResidenceShipping) {
                        $addressClassification = "HOME";
                    } elseif (!empty($requestPostData)) {
                        $requestPostDataDecoded = json_decode((string)$requestPostData, true);
                        if (!empty($requestPostDataDecoded) && !empty($requestPostDataDecoded['addressInformation']) && !empty($requestPostDataDecoded['addressInformation']['shipping_address']) && !empty($requestPostDataDecoded['addressInformation']['shipping_address']['customAttributes'])) {
                            $customAttributesArray = $requestPostDataDecoded['addressInformation']['shipping_address']['customAttributes'];
                            foreach ($customAttributesArray as $customAttribute) {
                                if ($this->toggleConfig->getToggleConfigValue('tiger_d213977')) {
                                    if ($customAttribute['attribute_code'] === 'residence_shipping' && ($customAttribute['value'] === true || $customAttribute['value'] === 1)) {
                                        $addressClassification = "HOME";
                                        break;
                                    }
                                } else {
                                    if ($customAttribute['attribute_code'] === 'residence_shipping' && $customAttribute['value'] === true) {
                                        $addressClassification = "HOME";
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $company = $this->companyHelper->getCustomerCompany();
            $companyId = $company ? $company->getId() : false;
            if ($companyId != null && $companyId > 0) {
                $customerRepo = $this->companyHelper->getCustomerCompany((int)$companyId);
                $companyLoginType = $customerRepo->getStorefrontLoginMethodOption();
                if ($customerRepo->getRecipientAddressFromPo() && $companyLoginType == 'commercial_store_epro') {
                    $addressClassification = "BUSINESS";
                }
            }

            if ($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
            } else {
                $items = $quote->getAllVisibleItems();
            }
            $product = [];
            $productAssociations = [];
            if (is_numeric($regionId) && !empty($config['postcode'])
                && !empty($config['country_id'])
            ) {
                $productionLocationId = $restrictedIds = null;
                $street = $config['street'];

                    $isEproUser = $this->inBranchValidation->isInBranchUser();
                    if ($isEproUser) {
                        $locationNumber = $this->inBranchValidation->getAllowedInBranchLocation();
                        if ($locationNumber && $locationNumber != '') {
                            $restrictedIds = [$locationNumber];
                            $productionLocationId = $locationNumber;
                        }
                    }
                    $requestData = $this->requestObj->getContent();
                    $requestDataDecoded = json_decode((string)$requestData, true);
                    if (empty($productionLocationId) && is_array($requestDataDecoded) && isset($requestDataDecoded['productionLocation'])) {
                        $productionLocationId = $requestDataDecoded['productionLocation'];
                    }
                    if (isset($config['street']) && $config['street'] == "n/a") {
                        $street = null;
                    }

                if (!$this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix') && $this->toggleConfig->getToggleConfigValue('explorers_d196997_fix')) {
                    $this->cartDataHelper->setAddressClassification($addressClassification);
                }

                $shipperRegion = $this->regionFactory->create()->load($regionId);
                if ($shipperRegion->getId()) {
                    $isFullMiraklQuote = $this->quoteHelper->isFullMiraklQuote($quote);
                    $productData = $this->setProductDataAndProductAssociations($items, $isFullMiraklQuote);
                    $product = $productData['product'];
                    $productAssociations = $productData[self::PRODUCT_ASSOCIATIONS];
                    $shipperRegionCode = $shipperRegion->getCode();
                    $fedExAccountNumber = $this->getFedExAccountNumber($quote);
                    $fedexShippingAccountNumber = $quote->getData('fedex_ship_account_number');
                    $data = [
                        'deliveryOptionsRequest' => [
                            'fedExAccountNumber' => $fedExAccountNumber,
                            'site' => $config['site'],
                            'products' => $product,
                            'deliveries' => [
                                0 => [
                                    'deliveryReference' => 'default',
                                    'address' => [
                                        'streetLines' => [
                                            0 => $street,
                                            1 => '',
                                        ],
                                        'city' => $config['city'],
                                        'stateOrProvinceCode' => $shipperRegionCode,
                                        'postalCode' => $config['postcode'],
                                        'countryCode' => $config['country_id'],
                                        'addressClassification' => $addressClassification,
                                    ],
                                    'holdUntilDate' => null,
                                    'requestedDeliveryTypes' => [
                                        'requestedShipment' => [
                                            'productionLocationId' => $productionLocationId,
                                            'fedExAccountNumber' => $fedexShippingAccountNumber,
                                        ],
                                    ],
                                    self::PRODUCT_ASSOCIATIONS => $productAssociations,
                                ],
                            ],
                        ],
                    ];

                    if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_194022') &&
                        $this->inBranchValidation->isInBranchProductWithContentAssociationsEmpty($items)
                    ) {
                        $data['deliveryOptionsRequest']['validateContent'] = false;
                    }

                    //B-1309424: Include Signature Options in Delivery Options Payload
                    $shipmentSpecialServices = $this->retailHelper->getRateRequestShipmentSpecialServices();
                    if (!empty($shipmentSpecialServices)) {
                        $data['deliveryOptionsRequest']['deliveries'][0]
                        ['requestedDeliveryTypes']['requestedShipment']['specialServices'] = $shipmentSpecialServices;
                    }

                    // D-192068 :: Commercial B2B Approval - Ship Orders don't route to customer selected production location
                    $toggleD192068FixEnabled = $this->toggleConfig->getToggleConfigValue('explorers_D192068_fix');
                    if (!empty($restrictedIds)) {
                        $data['deliveryOptionsRequest']['deliveries'][0]['routingRestrictions']['allowedProductionLocations'] = $restrictedIds;
                    } elseif ($toggleD192068FixEnabled && !$productionLocationId) {
                        $restrictedIds = $this->getRestrictedLocations();
                        if (!empty($restrictedIds)) {
                            $data['deliveryOptionsRequest']['deliveries'][0]['routingRestrictions']['allowedProductionLocations'] = $restrictedIds;
                        }
                    }
                    $isRetailCustomer = $this->crData->isRetailCustomer();
                    if($isRetailCustomer) {
                        $locationNumber = $this->crData->getStoreCodeFromSession();
                        if ($locationNumber && $locationNumber != '') {
                            $data['deliveryOptionsRequest']['deliveries'][0]['routingRestrictions']['allowedProductionLocations'] = [$locationNumber];
                        }
                    }
                    //E-442091

                    $authHeaderVal = $this->data->getAuthHeaderValue();
                    $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();

                    $dataString = json_encode($data);

                    if ($this->toggleConfig->getToggleConfigValue('d207891_toggle')){
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Delivery API  Request:');
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    }

                    $headers = [
                        "Content-Type: application/json",
                        "Accept: application/json",
                        "Accept-Language: json",
                        "Content-Length: " . strlen($dataString),
                        $authHeaderVal . $gateWayToken,
                        "Cookie: Bearer=" . $config['access_token']
                    ];

                    $isDeliveryApiMockEnabled = $this->isDeliveryApiMockEnabled();
                    if($isDeliveryApiMockEnabled) {
                        $headers = [
                            "Content-Type: application/json",
                            "Accept: application/json",
                            "Accept-Language: json",
                            "Content-Length: " . strlen($dataString),
                            $authHeaderVal . $gateWayToken,
                            "Cookie: Bearer=" . $config['access_token'],
                            "postalCode: ".$config['postcode']
                        ];
                    }
                    $this->curl->setOptions(
                        [
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => $dataString,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_ENCODING => '',
                        ]
                    );

                    $this->curl->post($setupURL, $dataString);
                    $output = $this->curl->getBody();
                    $outputData = json_decode($output, true);
                    if (isset($outputData['errors']) || !isset($outputData[self::OUTPUT])) {
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Delivery API  Request:');
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Delivery API  response:');
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                    }
                    $this->logger->info(
                        $logHeader . ' Line:' . __LINE__ . ' Delivery API response detail for Quote Id : ' .
                        $quote->getId() . json_encode($outputData)
                    );

                    $deliveryOption = $this->getDeliveryOptionsData($output);
                }
            }
            return $deliveryOption;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' Not able to return delivery options. ' . $e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param object $items
     * @param bool $isFullMiraklQuote
     * @return array
     */
    public function setProductDataAndProductAssociations($items, bool $isFullMiraklQuote = false)
    {
        $product = [];
        $productAssociations = [];

        foreach ($items as $item) {
            if ($item->getMiraklOfferId() || $item->getProductType() === Type::TYPE_BUNDLE) {
                continue;
            }
            $additionalOption = $item->getOptionByCode(
                'info_buyRequest'
            );
            $additionalOptions = is_null($additionalOption->getValue()) ? '' : $additionalOption->getValue();
            $productJson = (array) json_decode(
                $additionalOptions
            )->external_prod[0];
            if (isset($productJson[self::CATALOG_REFERENCE])) {
                $productJson[
                    self::CATALOG_REFERENCE
                ] = (array) $productJson[
                    self::CATALOG_REFERENCE
                ];
            }
            if (isset($productJson['preview_url'])) {
                unset($productJson['preview_url']);
            }
            if (isset($productJson['fxo_product'])) {
                unset($productJson['fxo_product']);
            }

            $productJson['instanceId'] = $item->getItemId();

            $productJson['qty'] = $item->getQty();
            $product[] = $productJson;
            $productAssociations[] = [
                'id' => $productJson['instanceId'],
                'quantity' => $item->getQty(),
            ];
        }
        return [
            'product' => $product,
            self::PRODUCT_ASSOCIATIONS => $productAssociations
        ];
    }

    /**
     * Get FedEx Account Number
     */
    public function getFedExAccountNumber($quote)
    {
        if ($this->retailHelper->isCommercialCustomer() && $quote->getData('fedex_account_number')) {
            //B-1275215: Get fedex account number saved in quote
            return  $this->cartDataHelper->decryptData(
                $quote->getData('fedex_account_number')
            );
        } else {
            return null;
        }
    }

    /**
     * Get FedEx Shipping Account Number
     */
    public function getFedexShippingAccountNumber()
    {
        if ($this->sdeHelper->getIsSdeStore()) {
            return $this->companyHelper->getCustomerCompany() ? trim((string)$this->companyHelper->getCustomerCompany()
                ->getShippingAccountNumber()) : null;
        } else {
            return null;
        }
    }

    /**
     * Get Delivery Options Data
     *
     * @param $output
     *
     * @return array $deliveryOption
     */
    public function getDeliveryOptionsData($output)
    {
        $deliveryOption = [];
        if ($output !== false) {
            // It will check return store & that allow delivery options
            $company = $this->companyHelper->getCustomerCompany();
            $companyId = $company ? $company->getId() : false;
            if ($this->toggleConfig->getToggleConfigValue('xmen_shipping_methods_business_configurable')
            && !$companyId) {
                $allowedDeliveryOptionsData = $this->shippingDataHelper->getRetailOnePShippingMethods();
            } else {
                $allowedDeliveryOptionsData = $this->allowedDeliveryOptionsStore();
            }
            $allowStore = $allowedDeliveryOptionsData["allowStore"];
            $allowedDeliveryOptions = $allowedDeliveryOptionsData["allowedDeliveryOptions"];

            if (array_key_exists('GROUND_US', $allowedDeliveryOptions) &&
                $this->toggleConfig->getToggleConfigValue('d_190493_show_home_delivery_for_comercial_delivery_options')
            ) {
                $allowedDeliveryOptions['FEDEX_HOME_DELIVERY'] = count($allowedDeliveryOptions);
            }
            $output = json_decode($output, true);
            $recievedOption = isset($output[self::OUTPUT]['deliveryOptions'][0]['shipmentOptions']) ?
            $output[self::OUTPUT]['deliveryOptions'][0]['shipmentOptions'] : [];
            if (count($recievedOption)) {
                foreach ($recievedOption as $shipping) {
                    if (
                    $allowStore &&
                    isset($shipping[self::SERVICE_TYPE]) &&
                    !empty($allowedDeliveryOptions) &&
                    !array_key_exists($shipping[self::SERVICE_TYPE], $allowedDeliveryOptions)) {
                        continue;
                    }
                    $shippingData = $this->getShippingData($shipping);
                    $description = $shippingData['description'];
                    $currency = $shippingData[self::CURRENCY];
                    $estimatedDeliveryDuration = $shippingData[self::ESTIMATED_DELIVERY_DURATION];
                    $estimatedDeliveryLocalTime = $shippingData[self::ESTIMATED_DELIVERY_LOCAL_TIME];

                    $deliveryOption[] = [
                        self::SERVICE_TYPE => $shipping[self::SERVICE_TYPE],
                        self::SERVICE_DESCRIPTION => $description,
                        self::CURRENCY => $currency,
                        'estimatedShipmentRate' => $shipping['estimatedShipmentRate'],
                        'estimatedShipDate' => $shipping['estimatedShipDate'],
                        self::ESTIMATED_DELIVERY_DURATION => $estimatedDeliveryDuration,
                        'priceable' => $shipping['priceable'],
                        'productionLocationId' => $shippingData['productionLocation'],
                        self::ESTIMATED_DELIVERY_LOCAL_TIME => $estimatedDeliveryLocalTime
                    ];
                }
            }
        }

        return $deliveryOption;
    }

    /**
     * It will check return store & that allow delivery options
     * @return array
     */
    public function allowedDeliveryOptionsStore() {
        $allowStore = $this->cartDataHelper->isCommercialCustomer();
        if ($allowStore) {
            $allowedDeliveryOptions = $this->retailHelper->getAllowedDeliveryOptions();
        }
        return [
            "allowStore" => $allowStore,
            "allowedDeliveryOptions" => !empty($allowedDeliveryOptions) ? $allowedDeliveryOptions: []
        ];
    }

    /**
     * Get Shipping Data
     * @param array $shipping
     * @return array
     */
    public function getShippingData($shipping)
    {
        $locationId = null;
        $expectedDate = !empty($shipping[self::ESTIMATED_DELIVERY_LOCAL_TIME]) ?
        date("l, F d g:i a", strtotime($shipping[self::ESTIMATED_DELIVERY_LOCAL_TIME])) : '';
        $estimatedDeliveryLocalTime = $expectedDate;
        $estimatedDeliveryDuration = isset($shipping[self::ESTIMATED_DELIVERY_DURATION]) ?
        $shipping[self::ESTIMATED_DELIVERY_DURATION] : '';
        //D-104312: delivery options not showing in sensitive work flow
        $shippingDescription = isset($shipping[self::SERVICE_DESCRIPTION])
            ? $shipping[self::SERVICE_DESCRIPTION]
            : $this->getShippingServiceTitle($shipping[self::SERVICE_TYPE]);

        $description = strpos($shippingDescription, 'FedEx') !== false
            ? $shippingDescription
            : "FedEx " . str_replace("Fedex", "", $shippingDescription);

        $currency = 'USD';
        if (isset($shipping[self::CURRENCY])) {
            $currency = $shipping[self::CURRENCY];
        }

        // D-192068 :: Commercial B2B Approval - Ship Orders don't route to customer selected production location
        // Else to be removed added for tetsing
        $toggleD192068FixEnabled = $this->toggleConfig->getToggleConfigValue('explorers_D192068_fix');
        if (
            !$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') &&
            $toggleD192068FixEnabled && isset($shipping['productionLocation']['id'])
        ) {
            if(!$this->retailHelper->isCommercialCustomer()) {//this should handle retail flow
                $locationId = null;
            } elseif (empty($this->getRestrictedLocations()))  {//this should handle scenario if no restricted store is there, should work for retail also but handling retail explicitly also
                $locationId = null;
            } else {
                $locationId = $shipping['productionLocation']['id'];
            }
        }

        if (
            $this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') &&
            $this->retailHelper->isCommercialCustomer() &&
            isset($shipping['productionLocation']) &&
            isset($shipping['productionLocation']['id'])
        ) {
            $locationId = $shipping['productionLocation']['id'];
        }

        return [
            self::ESTIMATED_DELIVERY_LOCAL_TIME => $estimatedDeliveryLocalTime,
            self::ESTIMATED_DELIVERY_DURATION => $estimatedDeliveryDuration,
            'description' => $description,
            self::CURRENCY => $currency,
            'productionLocation' => $locationId
        ];
    }

    /**
     * Get shipping service title based on service type
     *
     * @param string $serviceType
     * @return string|null
     */
    public function getShippingServiceTitle($serviceType)
    {
        $serviceTitle = null;
        $availableServices = $this->getAvailableShippingServices();
        if (isset($availableServices[$serviceType])) {
            $serviceTitle = $availableServices[$serviceType];
        }

        return $serviceTitle;
    }

    /**
     * Get all available Fedex shipping services
     *
     * @return array
     */
    public function getAvailableShippingServices()
    {
        return [
            'GROUND_US' => 'Ground US',
            'LOCAL_DELIVERY_AM'=> 'FedEx Local Delivery',
            'LOCAL_DELIVERY_PM' => 'FedEx Local Delivery',
            'EXPRESS_SAVER' => 'Express Saver',
            'TWO_DAY' => '2 Day',
            'STANDARD_OVERNIGHT' => 'Standard Overnight',
            'PRIORITY_OVERNIGHT' => 'Priority Overnight',
            'FIRST_OVERNIGHT' => 'First Overnight',
        ];
    }

    /**
     * Is It pickup for PickupData
     */
    public function isItPickup($pickupData)
    {
        return isset($pickupData) && isset($pickupData[self::ADDRESS_INFORMATION][self::SHIPPING_METHOD_CODE]) &&
        $pickupData[self::ADDRESS_INFORMATION][self::SHIPPING_METHOD_CODE] == self::PICKUP;
    }

    /**
     * Get Expected Date
     *
     * @param array $deliveryOption
     * @return string|null
     */
    public function getExpectedDate($deliveryOption)
    {
        $expectedDate = null;
        if (!empty($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME])
        && ($deliveryOption[self::SERVICE_TYPE] == static::GROUND_US
        || $deliveryOption[self::SERVICE_TYPE] == static::FEDEX_HOME_DELIVERY)
        ) {
            $deliveryDate = date('l, F d', strtotime($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME]));
            $expectedDate = $deliveryDate . ' ' . static::EOD_TEXT;
        } elseif (($deliveryOption[self::SERVICE_TYPE] == static::GROUND_US
        || $deliveryOption[self::SERVICE_TYPE] == static::FEDEX_HOME_DELIVERY)
        && isset($deliveryOption[self::ESTIMATED_DELIVERY_DURATION]['unit'])
        ) {
            $unit = $deliveryOption[self::ESTIMATED_DELIVERY_DURATION]['unit'];
            $expectedDate = !empty($deliveryOption[self::ESTIMATED_DELIVERY_DURATION][self::VALUE])
                ? $deliveryOption[self::ESTIMATED_DELIVERY_DURATION][self::VALUE] . ' ' . $this->mapping[$unit] : '';
        } else {
            $expectedDate = (!empty($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME]))
                ? $deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME] : '';
        }

        return $expectedDate;
    }

    /**
     * Get Expected Date with new date format
     * @param array $deliveryOption
     * @return String
     */
    public function getExpectedDateFormat($deliveryOption)
    {
        $expectedDate = null;

        if (!empty($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME])
        && ($deliveryOption[self::SERVICE_TYPE] == static::GROUND_US ||
        $deliveryOption[self::SERVICE_TYPE] == static::FEDEX_HOME_DELIVERY)) {

            $deliveryDate = date('l, F d', strtotime($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME]));
            $expectedDate = $deliveryDate . ' ' . static::EOD_TEXT;
        } elseif (($deliveryOption[self::SERVICE_TYPE] == static::GROUND_US ||
        $deliveryOption[self::SERVICE_TYPE] == static::FEDEX_HOME_DELIVERY) &&
        isset($deliveryOption[self::ESTIMATED_DELIVERY_DURATION]['unit'])) {

            $unit = $deliveryOption[self::ESTIMATED_DELIVERY_DURATION]['unit'];
            $expectedDate = !empty($deliveryOption[self::ESTIMATED_DELIVERY_DURATION][self::VALUE])
            ? $deliveryOption[self::ESTIMATED_DELIVERY_DURATION][self::VALUE] . ' ' . $this->mapping[$unit] : '';
        } else {
            $expectedDate = (!empty($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME])) ?
            date("l, F j", strtotime($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME])) .', '
            .strtolower(date("g:ia", strtotime($deliveryOption[self::ESTIMATED_DELIVERY_LOCAL_TIME]))): '';
        }

        return $expectedDate;
    }

    /**
     * Is Delivery Mock Api Enabled
     */
    public function isDeliveryApiMockEnabled()
    {
        $isDeliveryApiMockEnabled = false;
        $isToggleEnabled = $this->configInterface->getValue("wiremock_service/selfreg_wiremock_group/delivery__option_api_wiremock_enable");
        $deliveryApiUrl = $this->configInterface->getValue("wiremock_service/selfreg_wiremock_group/delivery_option_api_wiremock_url");

        if($isToggleEnabled && $deliveryApiUrl) {
            $isDeliveryApiMockEnabled = true;
        }
        return $isDeliveryApiMockEnabled;
    }

    /**
     * Get Delivery Mock Api Url
     */
    public function getDeliveryMockApiUrl()
    {
        return $this->configInterface->getValue("wiremock_service/selfreg_wiremock_group/delivery_option_api_wiremock_url");
    }

    /**
     * Check if checkout quote priceable disable
     *
     * @param object $quote
     * @return boolean|bool
     */
    public function isCheckoutQuotePriceableDisable($quote)
    {
        return $this->cartDataHelper->checkQuotePriceableDisable($quote);
    }

    /**
     * Get Restricted Locations
     *
     * @return array|mixed $restrictedIds
     */
    public function getRestrictedLocations()
    {
        $restrictedIds = [];
        $storesLocations = [];
        $company = $this->companyHelper->getCustomerCompany();
        $companyId = $company ? $company->getId() : false;
        if ($companyId != null && $companyId > 0) {
            if ($company->getProductionLocationOption() == self::RECOMMENDED_LOCATION_ALL_LOCATIONS) {
                $prodLocationModel = $this->productionLocationFactory->create();
                $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
                    ->addFieldToFilter(self::IS_RECOMMENDED_STORE, false);
                if ($storesLocations->getSize()) {
                    foreach ($storesLocations as $storesLocation) {
                        $restrictedIds[] = $storesLocation->getData('location_id');
                    }
                }
            }
        }
        return $restrictedIds;
    }
}
