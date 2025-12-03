<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Delivery\Controller\Index;

use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\ComputerRental\Model\CRdataModel;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data;
use Fedex\Delivery\Helper\Delivery;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfigInterface;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\CartFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\InBranch\Model\InBranchValidation;
use Psr\Log\LoggerInterface;

class Getpickup extends \Magento\Framework\App\Action\Action
{
    private const ERRORS = 'errors';
    private const ESTIMATE_DELIVERY_LOCALTIME = 'estimatedDeliveryLocalTime';
    private const IS_RECOMMENDED = 'is_recommended';
    private const EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION = 'explorers_restricted_and_recommended_production';
    private const RECOMMENDED_STORES_ALL_LOCATION = 'recommended_stores_all_location';
    private const RECOMMENDED_LOCATION_ALL_LOCATIONS = 'recommended_location_all_locations';
    private const IS_RECOMMENDED_STORE = 'is_recommended_store';
    private const CATALOG_REFERENCE = 'catalogReference';
    private const GEOGRAPHICAL_LOCATION = 'geographical';
    private const ESTIMATE_PICKUP_LOCALTIME = 'estimatedPickupLocalTime';
    private const SGC_PROMISE_TIME_TOGGLE = 'sgc_promise_time_pickup_options';
    private const AVAILABLE_ORDER_PRIORITIES = 'availableOrderPriorities';
    private const ORDER_PRIORITY_TEXT = 'orderPriorityText';
    private const ORDER_PRIORITY = 'orderPriority';
    private const PREMIUM = 'PREMIUM';
    private const PRIORITY_PRINT_PICKUP = 'Priority Print Pickup';
    private const STANDARD_PICKUP = 'Standard Pickup';
    /**
     * @var $isRecommendedToggle
     */
    protected $isRecommendedToggle;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $configInterface
     * @param \Fedex\Delivery\Helper\Data $helper
     * @param CartFactory $cartFactory
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param \Fedex\Punchout\Helper\Data $gateTokenHelper
     * @param ProductionLocationFactory $productionLocationFactory
     * @param ToggleConfig $toggleConfig
     * @param CompanyRepositoryInterface $companyRepository
     * @param CartDataHelper $cartDataHelper
     * @param Curl $curl
     * @param JsonFactory $resultJsonFactory
     * @param QuoteHelper $quoteHelper
     * @param ConfigInterface $companyConfigInterface
     * @param Data $data
     * @param Delivery $deliveryHelper
     * @param InBranchValidation $inBranchValidation
     * @param CRdataModel $crData
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        protected \Magento\Framework\App\Config\ScopeConfigInterface $configInterface,
        protected \Fedex\Delivery\Helper\Data $helper,
        protected \Magento\Checkout\Model\CartFactory $cartFactory,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\App\RequestInterface $request,
        protected \Fedex\Punchout\Helper\Data $gateTokenHelper,
        protected \Fedex\Shipto\Model\ProductionLocationFactory $productionLocationFactory,
        protected \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig,
        protected \Magento\Company\Api\CompanyRepositoryInterface $companyRepository,
        protected CartDataHelper $cartDataHelper,
        protected Curl $curl,
        protected JsonFactory $resultJsonFactory,
        protected QuoteHelper $quoteHelper,
        protected ConfigInterface $companyConfigInterface,
        protected Data $data,
        protected Delivery $deliveryHelper,
        private InBranchValidation $inBranchValidation,
        private readonly CRdataModel $crData,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private readonly ProductBundleConfigInterface $productBundleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Execute view action of Pickup Address
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quote = $this->cartFactory->create()->getQuote();
        $resultJson = $this->resultJsonFactory->create();
        $this->isRecommendedToggle = false;
        $preferredLocationOnly = false;
        $recommendedIds = $restrictedIds = $allowedProductionLocations = [];
        $isRestrictedRecommendedToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION);
        $isOnsiteStoresRecommendedLocationToggle = $this->toggleConfig->getToggleConfigValue('explorers_onsite_stores_recommended_locations');

        try {
            $data = $this->request->getPostValue();
            $radius = !empty($data['radius']) ? $data['radius'] : null;
            $zipCode = !empty($data["zipcode"]) ? $data["zipcode"] : null;
            $city = !empty($data["city"]) ? $data["city"] : null;
            $stateCode = !empty($data['stateCode']) ? $data['stateCode'] : null;
            $isCalledForPickup = isset($data['isCalledForPickup']) ? $data['isCalledForPickup'] : false;
            $locationsLimit = 50;
            $isCommercialCustomer = $this->helper->isCommercialCustomer();
            //B-1913661 :: Get Restricted Or Recommended ID's For Company
            $restrictedOrRecommendedIds = $this->getRestrictedOrRecommendedLocations($isCommercialCustomer, $isCalledForPickup);
            if ($this->isRecommendedToggle) {
                $recommendedIds = $restrictedOrRecommendedIds;
            } else {
                $restrictedIds = $restrictedOrRecommendedIds;
            }

            if (!empty($restrictedIds) && empty($recommendedIds)) {
                $allowedProductionLocations = $restrictedIds;
                $preferredLocationOnly = true;
            }

            if ($isRestrictedRecommendedToggle && !empty($restrictedIds)) {
                $radius = null;
            }

            // B-2120177 - Onsite Stores Recommended Locations
            if($isOnsiteStoresRecommendedLocationToggle && $this->isRecommendedToggle && empty($restrictedIds)) {
                $allowedProductionLocations = $recommendedIds;
                $preferredLocationOnly = false;
            }

            $returnData = [];
            /** D-154224 - Checking if the quote has items, if not return a session expired error message */
            if (!$quote->hasItems()) {
                $this->logger->debug(__METHOD__ . ':' . __LINE__ . "Expired Session");
                $returnData = [
                    self::ERRORS => [
                        ['code' => 'SESSION_EXPIRED']
                    ],
                    'noLocation' => true
                ];
                return $resultJson->setData($returnData);
            }

            $productJson = $this->getProductJson($quote);
            $productAssociations = $this->getProductAssociations($quote);
            //Inbranch Implementation
            $isEproStore = $this->inBranchValidation->isInBranchUser();
            if ($isEproStore) {
                $locationNumber = $this->inBranchValidation->getAllowedInBranchLocation();
                if ($locationNumber && $locationNumber != '') {
                    $restrictedIds = [$locationNumber];
                    if ($this->toggleConfig->getToggleConfigValue('explorers_d_200361')) {
                        $allowedProductionLocations = $restrictedIds;
                    }
                }
            }
             //Inbranch Implementation

            /**
             * Check if current category id is for default category(2)
             * If yes set $companySite and $companyPaymentMethod to null
             */
            // B-1250149 : Magento Admin UI changes to group all the Customer account details
            $siteName = $this->getSiteName();
            $fedExAccountNumber = $this->getFedExAccountNo($quote);

            $data = [
                'deliveryOptionsRequest' => [
                    'fedExAccountNumber' => $fedExAccountNumber,
                    'site' => $siteName,
                    'products' => $productJson,
                    'deliveries' => [
                        0 => [
                            'deliveryReference' => 'default',
                            'address' => [
                                'streetLines' => [
                                    0 => null,
                                ],
                                'city' => $city,
                                'stateOrProvinceCode' => $stateCode,
                                'postalCode' => $zipCode,
                                'countryCode' => null,
                                'addressClassification' => null,
                            ],
                            'holdUntilDate' => null,
                            'routingRestrictions' => [
                                'allowedProductionLocations' => $allowedProductionLocations,
                            ],
                            'requestedDeliveryTypes' => [
                                'requestedPickup' => [
                                    'resultsRequested' => $locationsLimit,
                                    'excludePremiumLocations' => $this->getHcToggle($isCommercialCustomer),
                                    'preferredLocationOnly' => $preferredLocationOnly,
                                    'searchRadius' => [
                                        'value' => $radius,
                                        'unit' => 'MILES',
                                    ],
                                ],
                            ],
                            'productAssociations' => $productAssociations,
                        ],
                    ],
                ],
            ];

            $arrayData = $this->callDeliveryLocationApi($data);

            if (!empty($arrayData)) {
                $returnData = $this->validateDeliveryOptionResponse($arrayData, $recommendedIds);
            } else {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ .' No data being returned by delivery options api.'
                );

                $returnData = [self::ERRORS => "Error found no data", "noLocation" => true];
            }
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' No data being returned for pickup address
                . ' .$error->getMessage()
            );

            $returnData = [self::ERRORS => "Error found no data", "noLocation" => true];
        }

        return $resultJson->setData($returnData);
    }

    /**
     * Get Locations Data
     *
     * @param array|mixed $arrayData
     * @param array|mixed $recommendedIds
     * @return array|mixed $reArr
     */
    public function getLocationsData($arrayData, $recommendedIds)
    {
        $arraySortedPickup = $arrayData['output']['deliveryOptions'][0]['pickupOptions'] ?? [];
        $reArr = [];
        $isRestrictedRecommendedToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION);
        $isPromiseTimePickupEnabled = $this->toggleConfig->getToggleConfigValue(self::SGC_PROMISE_TIME_TOGGLE);
        foreach ($arraySortedPickup as $arraySortedPickupData) {
            $localTime = $arraySortedPickupData[self::ESTIMATE_DELIVERY_LOCALTIME];
            $estimatedDeliveryLocalTimeShow = $this->helper->updateDateTimeFormat($localTime);
            $locationID = $arraySortedPickupData['location']['id'];
            $isRecommended = false;
            $isCommercialCustomer = $this->helper->isCommercialCustomer();
            if($isCommercialCustomer) {
                $companyId = $this->helper->getAssignedCompany()->getId();
                 $customerRepo = $this->companyRepository->get((int) $companyId);
                 if ((!empty($recommendedIds) && in_array($locationID, $recommendedIds) && $customerRepo->getProductionLocationOption() != self::GEOGRAPHICAL_LOCATION) || !$isRestrictedRecommendedToggle) {
                    $isRecommended = true;
                }
            } else if ((!empty($recommendedIds) && in_array($locationID, $recommendedIds)) || !$isRestrictedRecommendedToggle) {
                $isRecommended = true;
            }

            if ($isPromiseTimePickupEnabled) {
                $priorityPrintOptions = $arraySortedPickupData[self::AVAILABLE_ORDER_PRIORITIES];

                for ($i = 0; $i < count($priorityPrintOptions); $i++) {
                    $priorityPrintTime = $priorityPrintOptions[$i][self::ESTIMATE_DELIVERY_LOCALTIME];
                    $priorityPrintOptions[$i][self::ESTIMATE_PICKUP_LOCALTIME] =
                        $this->helper->updateDateTimeFormat($priorityPrintTime);
                    $priorityPrintOptions[$i][self::ORDER_PRIORITY_TEXT] =
                        $priorityPrintOptions[$i][self::ORDER_PRIORITY] == self::PREMIUM ?
                        self::PRIORITY_PRINT_PICKUP : self::STANDARD_PICKUP;
                }

                $reArr[] = [
                    'estimatedDeliveryLocalTimeShow' => $estimatedDeliveryLocalTimeShow,
                    self::ESTIMATE_DELIVERY_LOCALTIME => $arraySortedPickupData[self::ESTIMATE_DELIVERY_LOCALTIME],
                    self::IS_RECOMMENDED => $isRecommended,
                    'location' => $arraySortedPickupData['location'],
                    'availableOrderPriorities' => $priorityPrintOptions
                ];
            } else {
                $reArr[] = [
                    'estimatedDeliveryLocalTimeShow' => $estimatedDeliveryLocalTimeShow,
                    self::ESTIMATE_DELIVERY_LOCALTIME => $arraySortedPickupData[self::ESTIMATE_DELIVERY_LOCALTIME],
                    self::IS_RECOMMENDED => $isRecommended,
                    'location' => $arraySortedPickupData['location'],
                ];
            }
        }
        return $this->getSortedLocations($reArr);
    }

    /**
     * Get Site Name
     */
    public function getSiteName()
    {
        $siteName = null;
        if ($this->helper->isCommercialCustomer()) {
            $siteName = $this->helper->getCompanySite();
        }
        return $siteName;
    }

    /**
     * Get FedEx Account No
     */
    public function getFedExAccountNo($quote)
    {
        $fedExAccountNumber = null;
        if ($this->helper->isCommercialCustomer() && $quote->getData('fedex_account_number')) {
            //B-1275215: Get fedex account number from quote
            $fedExAccountNumber = $this->cartDataHelper->decryptData($quote->getData("fedex_account_number"));
        }

        return $fedExAccountNumber ;
    }

    /**
     * Get Recommended Locations
     *
     * @param boolean $isCommercialCustomer
     * @param boolean $isCalledForPickup
     * @return array|mixed $restrictedOrRecommendedIds
     */
    public function getRestrictedOrRecommendedLocations($isCommercialCustomer, $isCalledForPickup)
    {
        $restrictedOrRecommendedIds = [];
        $storesLocations = [];
        $isRestrictedRecommendedToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION);
        // Explorers E-394577 - Restricted And Recommended production locations
        if ($isCommercialCustomer) {
            $companyId = $this->helper->getAssignedCompany()->getId();
            $customerRepo = $this->companyRepository->get((int) $companyId);
            if ($isRestrictedRecommendedToggle && ($customerRepo->getAllowProductionLocation() == 1 || $isCalledForPickup)) {
                $prodLocationModel = $this->productionLocationFactory->create();
                if ($customerRepo->getProductionLocationOption() == self::RECOMMENDED_STORES_ALL_LOCATION) {
                    // B-1913661 || get recommended location ids for company
                    $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
                    ->addFieldToFilter(self::IS_RECOMMENDED_STORE, true);
                    $this->isRecommendedToggle = true;
                } elseif($customerRepo->getProductionLocationOption() == self::RECOMMENDED_LOCATION_ALL_LOCATIONS) {
                    // B-1913661 || get restricted location ids for company
                    $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
                    ->addFieldToFilter(self::IS_RECOMMENDED_STORE, false);
                } else {
                    $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId);
                    $this->isRecommendedToggle = true;
                }
                if ($storesLocations->getSize()) {
                    foreach ($storesLocations as $storesLocation) {
                        $restrictedOrRecommendedIds[] = $storesLocation->getData('location_id');
                    }
                }

            } elseif ($customerRepo->getAllowProductionLocation() == 1 &&
                $customerRepo->getProductionLocationOption() == self::RECOMMENDED_LOCATION_ALL_LOCATIONS) {
                $prodLocationModel = $this->productionLocationFactory->create();
                $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
                ->addFieldToFilter(self::IS_RECOMMENDED_STORE, false);
                if ($storesLocations->getSize()) {
                    foreach ($storesLocations as $storesLocation) {
                        $restrictedOrRecommendedIds[] = $storesLocation->getData('location_id');
                    }
                }

            }
        }
        $isRetailCustomer = $this->crData->isRetailCustomer();
        if($isRetailCustomer){
            $locationNumber = $this->crData->getStoreCodeFromSession();
            if ($locationNumber && $locationNumber != '') {
                $restrictedOrRecommendedIds[] = $locationNumber;
            }
        }
        //E-442091

        return $restrictedOrRecommendedIds;
    }

    /**
     * Get HC Toggle from Company
     */
    public function getHcToggle($isCommercialCustomer)
    {
        if ($isCommercialCustomer) {
            $companyId = $this->helper->getAssignedCompany()->getId();
            $company = $this->companyRepository->get((int)$companyId);

            return !$company->getHcToggle();
        }

        return false;
    }

    /**
     * Get Product Json
     */
    public function getProductJson($quote)
    {
        $items = $quote->getAllItems();
        $i = 0;
        $product = [];
        foreach ($items as $item) {
            if ($item->getMiraklOfferId() || $item->getProductType() == Type::TYPE_BUNDLE) {
                continue;
            }
            $additionalOption = $item->getOptionByCode('info_buyRequest');
            $additionalOptions = $additionalOption->getValue();
            $productJson = [];

            if (empty(json_decode($additionalOptions)->external_prod[0])) {
                continue;
            }

            $productJson = (array) json_decode($additionalOptions)->external_prod[0];

            if (isset($productJson[self::CATALOG_REFERENCE])) {
                $productJson[self::CATALOG_REFERENCE] = (array) $productJson[self::CATALOG_REFERENCE];
            }
            if (isset($productJson['preview_url'])) {
                unset($productJson['preview_url']);
            }
            if (isset($productJson['fxo_product'])) {
                unset($productJson['fxo_product']);
            }

            if (!empty($productJson)) {
                $productJson['instanceId'] = $item->getItemId();
            }

            $productJson['qty'] = $item->getQty();
            $product[] = $productJson;
            $i++;
        }

        return $product;
    }

    /**
     * Get Product Association
     */
    public function getProductAssociations($quote)
    {
        $isEssendantToggleEnabled =
            $this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
        if($isEssendantToggleEnabled){
            if ($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
            } else {
                $items = $quote->getAllVisibleItems();
            }
        }else{
            $items = $quote->getAllItems();
        }
        $productAssociations = [];
        foreach ($items as $item) {
            if ($item->getMiraklOfferId() || $item->getProductType() == Type::TYPE_BUNDLE) {
                continue;
            }
            $productAssociations[] = ['id' => $item->getItemId(), 'quantity' => $item->getQty()];
        }

        return $productAssociations;
    }

    /**
     * Call Delivery API
     */
    public function callDeliveryLocationApi($data)
    {
        $accessToken = $this->gateTokenHelper->getTazToken();
        $gateWayToken = $this->gateTokenHelper->getAuthGatewayToken();
        $setupURL = $this->configInterface->getValue("fedex/general/delivery_api_url");

        $dataString = json_encode($data);

        $authHeaderVal = $this->data->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "Content-Length: " . strlen($dataString),
            $authHeaderVal . $gateWayToken,
            "Cookie: Bearer=" . $accessToken,
        ];

        $isDeliveryApiMockEnabled = $this->deliveryHelper->isDeliveryApiMockEnabled();
        if($isDeliveryApiMockEnabled) {
            $setupURL = $this->deliveryHelper->getDeliveryMockApiUrl();
            $data = $this->request->getPostValue();
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Content-Length: " . strlen($dataString),
                $authHeaderVal . $gateWayToken,
                "Cookie: Bearer=" . $accessToken,
                "postalCode: ".$data['zipcode'],
            ];
        }

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_POSTFIELDS => $dataString,
            ]
        );
        $this->curl->get($setupURL);
        $output = $this->curl->getBody();
        $arrayData = json_decode($output, true);

        if (isset($arrayData[self::ERRORS]) || !isset($arrayData['output'])) {
            $outputLog = $this->addPostalCodeToLogDetails($output);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Pickup API Request:');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Pickup API response:');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $outputLog);
        }

        return $arrayData;
    }

    /**
     * Validating the Delivery Options Response
     */
    public function validateDeliveryOptionResponse($arrayData, $recommendedIds)
    {
        if (!array_key_exists(self::ERRORS, $arrayData)) {
            return $this->getLocationsData($arrayData, $recommendedIds);
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error(s) returned in delivery options api response.');
            return $arrayData;
        }
    }
    /**
     * Get Sorted Locations
     *
     * @param array|mixed $reArr
     * @return array|mixed $reArr
     */
    public function getSortedLocations($reArr)
    {
        if (!empty($reArr)) {
            usort($reArr, function ($recommendedArray, $notRecommendedArray) {
                if ($recommendedArray['is_recommended'] && !$notRecommendedArray['is_recommended']) {
                    return -1;
                } elseif (!$recommendedArray['is_recommended'] && $notRecommendedArray['is_recommended']) {
                    return 1;
                } else {
                    return 0;
                }
            });
        }
        return $reArr;
    }

    /**
     * @param string $output
     * @return string
     */
    private function addPostalCodeToLogDetails(string $output): string
    {
        $data = $this->request->getPostValue();
        $arrOutput = json_decode($output, true);
        $arrOutput['postalCode'] = $data['zipcode'] ?? '';
        return json_encode($arrOutput);
    }
}
