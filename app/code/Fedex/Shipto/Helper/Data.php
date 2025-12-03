<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipto\Helper;

use Fedex\Header\Helper\Data as HeaderData;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use \Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Checkout\Model\CartFactory;
use Fedex\Email\Helper\SendEmail;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\Country;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\QuoteFactory;


/**
 * Data Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private const EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION = 'explorers_restricted_and_recommended_production';
    private const COUNTRYCODE = 'US';
    private const TYPE = 'nearestAddress';
    private const ADDRESSCLASSIFICATION = 'BUSINESS';
    private const UNIT = 'MILES';
    private const MATCH_TYPE = 'ALL';
    private const SORT_BY = 'DISTANCE';
        /**
         * @var Session
         */
        protected $_customerSession;

        /**
         * @var CartFactory
         */
        protected $cartFactory;

        /**
         * @var OrderRepositoryInterface
         */
        protected $_orderRepository;

        /**
         * @var InvoiceService
         */
        protected $_invoiceService;

        /**
         * @var Transaction
         */
        protected $_transaction;

        protected $isRestrictedRecommendedToggle;

        /**
         * Data Constructor
         *
         * @param Context $context
         * @param Session $customerSession
         * @param CustomerRepositoryInterface $customerRepository
         * @param QuoteFactory $quoteFactory
         * @param SendEmail $mail
         * @param PunchoutHelper $punchoutHelper
         * @param CompanyRepositoryInterface $companyRepository
         * @param CartRepositoryInterface $quoteRepository
         * @param OrderRepositoryInterface $orderRepository,
         * @param InvoiceService $invoiceService,
         * @param Transaction $transaction
         * @param Curl $curl
         * @param Region $region
         * @param Country $country
         * @param ResourceConnection $resourceConnection
         * @param ToggleConfig $toggleConfig
         * @param LoggerInterface $logger
         * @param HeaderData $headerData
         */
    public function __construct(
        Context $context,
        Session $customerSession,
        protected CustomerRepositoryInterface $customerRepository,
        private QuoteFactory $quoteFactory,
        protected SendEmail $mail,
        protected PunchoutHelper $punchoutHelper,
        private DeliveryHelper $deliveryHelper,
        protected CompanyRepositoryInterface $companyRepository,
        protected CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        protected Curl $curl,
        private Region $region,
        private Country $country,
        private ResourceConnection $resourceConnection,
        private ToggleConfig $toggleConfig,
        protected LoggerInterface $logger,
        protected HeaderData $headerData
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->_orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }

        /**
         * Get Assigned Company
         * @param $customer
         * @return object $company
         */
    public function getAssignedCompany($customer = null)
    {
        $companyId = $this->_customerSession->getCustomerCompany();
        if ((int) $companyId == 0) {
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            $companyId = $companyAttributes->getCompanyId();
        }
        return $this->companyRepository->get((int) $companyId);
    }

        /**
         * Send  email on Quote creation
         * @param int $quoteId
         * @param string $rejectionReason
         * @return boolean|string $result
         */
    public function sendOrderFailureNotification($quoteId, $rejectionReason)
    {
        if ($quoteId > 0 || $rejectionReason != "") {
            $mailStatus = false;
            $token_data['access_token'] = "";
            $token_data['auth_token'] = "";
            $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
            $tazToken = $this->punchoutHelper->getTazToken();
            if ($tazToken && !empty($gatewayToken)) {
                $token_data['access_token'] = $tazToken;
                $token_data['auth_token'] = $gatewayToken;
            }

            $quoteDetails = $this->quoteRepository->get($quoteId);
            if ($quoteDetails->getFailureEmailStatus()) {
                return true;
            }
            $customer = $this->customerRepository->getById($quoteDetails->getCustomerId());

            if ($this->getIsOrderRejectNotificationEnable($customer)) {
                $name = trim($quoteDetails->getCustomerFirstname() . ' ' . $quoteDetails->getCustomerLastname());

                $email = $quoteDetails->getCustomerEmail();
                $templateId = 'ePro_order_rejected';
                //Fix for total in rejection email not correct
                $grandTotal = number_format(
                    (float) ($quoteDetails->getSubtotal() - $quoteDetails->getDiscount()), 2, '.', '');
                $templateD = [
                                 'order' => [
                                     'primaryContact' => [
                                         'firstLastName' => $name ? $name : '', // Customer name
                                     ],
                                     'gtn' => (string) $quoteId, // order no.  for quote.
                                     'productionCostAmount' => $grandTotal ? $grandTotal : 0,
                                                        //quote sub total price excluding shipping & taxes.
                                     'rejectionReason' => $rejectionReason,
                                 ],
                                 'producingCompany' => [
                                     'name' => 'Fedex Office',
                                     'customerRelationsPhone' => '1.800.GoFedEx 1.800.463.3339',
                                 ],
                                 'user' => [
                                     'emailaddr' => '[mailto: ' . $email . ' ] ' . $email,
                                 ],
                                 'channel' => 'Print On Demand',
                             ];
                $templateData = json_encode($templateD);

                $mailStatus = $this->sendEmailIfNameEmailNotEmpty($name, $email,
                $templateId, $templateData, $token_data, $quoteId, $quoteDetails);

                $this->logger->info(__METHOD__.':'.__LINE__.': mail status '.$mailStatus);
                return $mailStatus;
            }
        }
        return false;
    }

    /**
     * Send email if name and email are not empty
     * @param string $name
     * @param string $email
     * @param int $templateId
     * @param array $templateData
     * @param string $token_data
     * @param int $quoteId
     * @param array $quoteDetails
     * @return boolean $mailStatus
     */
    public function sendEmailIfNameEmailNotEmpty($name, $email, $templateId,
         $templateData, $token_data, $quoteId, $quoteDetails){
        $mailStatus = false;
        if (!empty($name && $email)) {
            $customerData = ['name' => $name, 'email' => $email];
            $mailStatus = $this->mail->sendMail($customerData, $templateId, $templateData, $token_data);
                $connection  = $this->resourceConnection->getConnection();
                $tableName   = $connection->getTableName('quote');
                $data = ["failure_email_status" => 1];
                $where = ['entity_id = ?' => (int)$quoteId];
                $connection->update($tableName, $data, $where);
            

            $this->logger->error(__METHOD__.':'.__LINE__.': missing shipping item for '.$quoteId);
        }
        return $mailStatus;
    }

        /**
         * Identify whether quote rejection notification mail enable/disable
         * @param $customer
         * @return boolean
         */
    public function getIsOrderRejectNotificationEnable($customer)
    {
        $company = $this->getAssignedCompany($customer);
        return $company->getIsOrderReject();
    }

    /**
     * Create OrderInvoice Automatically
     * @param $orderId
     * @return array
     */
    public function createInvoice($orderId)
    {
        $responseArray = [];
        if ($orderId == "" || $orderId == null) {
            $responseArray['error'] = 1;
            $responseArray['message'] = "Invalid order number";
            $this->logger->error(__METHOD__.':'.__LINE__.': Invalid order number '. $orderId);
            return $responseArray;
        }
        $order = $this->_orderRepository->get($orderId);
        if ($order->canInvoice()) {
            try {
                $invoice = $this->_invoiceService->prepareInvoice($order);

                if ($order->getCustomTaxAmount()) {
                    $invoice->setTaxAmount($order->getCustomTaxAmount());
                    $invoice->setBaseTaxAmount($order->getCustomTaxAmount());
                    $invoice->setBaseGrandTotal($order->getBaseGrandTotal());
                    $invoice->setGrandTotal($order->getGrandTotal());
                }

                $invoice->register();
                $invoice->save();

                $transactionSave = $this->_transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                //send notification code
                $order->addStatusHistoryComment(
                    __('Manual invoice #%1.', $invoice->getId())
                )
                ->setIsCustomerNotified(true)
                ->save();
                $responseArray['error'] = 0;
                $responseArray['message'] = 'Manual invoice created successfully';
                $this->logger->info(__METHOD__.':'.__LINE__.': Manual invoice created successfully '.$orderId);
            } catch (\Exception $e) {
                $responseArray['error'] = 1;
                $responseArray['message'] = $e->getMessage();
                $this->logger->error(__METHOD__.':'.__LINE__.':orderId: '.$orderId.' '.$e->getMessage());
            }
        } else {
            $responseArray['error'] = 1;
            $responseArray['message'] = "Invoice already created for this order";
            $this->logger->info(__METHOD__.':'.__LINE__.': Invoice already created for this order '.$orderId);
        }
        return $responseArray;
    }

     /**
      * Get All Locations By Zipcode, city or state
      *
      * @param array $data
      * @param boolean $isCalledFromEnhancedProfile
      * @return array
      */
    public function getAllLocationsByZip($data, $isCalledFromEnhancedProfile = true)
    {
        $responseArray = $storeNumbers = $modifiedLocation = [];
        $radius = null;
        $zipCode = null;
        $city = null;
        $stateCode = null;
        $storeCode = null;
        $radius = !empty($data['radius']) ? $data['radius'] : null;
        $zipCode = !empty($data["zipcode"]) ? $data["zipcode"] : null;
        $city = !empty($data["city"]) ? $data["city"] : null;
        $stateCode = !empty($data['stateCode']) ? $data['stateCode'] : null;
        $isStoreSearchEnabled = false;
        $storesCount = 0;

        // Feature toggle Explorers E-394577 - Restricted And Recommended production locations
        $this->isRestrictedRecommendedToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION) && !$isCalledFromEnhancedProfile ? true :false;

        // B-1913634 :: Chekcking for store numbers search
        if (isset($data['isStoreSearchEnabled']) && $data['isStoreSearchEnabled'] == "true") {
            $isStoreSearchEnabled = true;
            $storeNumbers = explode(' ', trim($zipCode));
            $storeNumbers = $this->validateStoreNumbers($storeNumbers, $storesCount);
            $storesCount = count($storeNumbers);
        }
        $url = $this->getAllLocationUrl();
        $headers = $this->getHeaders();
        if ($url != "") {
            // Checking if searched for store numbers
            if ($isStoreSearchEnabled == true && count($storeNumbers) > 0) {
                $j = 0;
                for ($i=0; $i<$storesCount; $i++) {
                    $storeCode = $storeNumbers[$i];
                    $postfields = $this->getPostFields($isStoreSearchEnabled, $stateCode, $city, $radius, $storeCode);
                    $response = $this->fetchAllLocationsWithCurl($url, $headers, $postfields, $isStoreSearchEnabled);
                    if(!empty($response)) {
                        $responseArray[$j] = $response['locations'][0];
                        $modifiedLocation[$storeCode] = $responseArray[$j];
                        $j++;
                    }
                }
                $this->mergeSessionLocations($modifiedLocation);
            } elseif ($isStoreSearchEnabled == false) {
                $postfields = $this->getPostFields($isStoreSearchEnabled, $stateCode, $city, $radius, $zipCode);
                $responseArray = $this->fetchAllLocationsWithCurl($url, $headers, $postfields, $zipCode, $city);
                if ($this->isRestrictedRecommendedToggle && !empty($responseArray)) {
                    $responseArray = $responseArray['locations'];
                }
            }
        } else {
            $responseArray['error'] = 1;
            $responseArray['message'] = "Location API URL is missing. Please check configuration setting";
            $this->logger->error(__METHOD__.':'.__LINE__.
                ': Location API URL is missing. Check configuration setting '.$city);
        }

        if (isset($responseArray['error'])) {
            return $responseArray;
        }
        $isCommercial = $this->deliveryHelper->isCommercialCustomer();
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_217639') && $isCommercial) {
            $includePremium = $this->getHcToggle($isCommercial); // true = keep premium locations
            if ($this->isRestrictedRecommendedToggle) {
                if (is_array($responseArray)) {
                    $responseArray = $this->filterPremiumLocations($responseArray, $includePremium);
                }
            } else {
                // Structured response with locations key
                if (isset($responseArray['locations']) && is_array($responseArray['locations'])) {
                    $responseArray['locations'] = $this->filterPremiumLocations(
                        $responseArray['locations'],
                        $includePremium
                    );
                } elseif (is_array($responseArray)) {
                    // Fallback if structure differs
                    $responseArray = $this->filterPremiumLocations($responseArray, $includePremium);
                }
            }
        }

        return $responseArray;
    }

    /**
     * Retrive curl response
     *
     * @param array $curlResponse
     * @param boolean $isStoreSearchEnabled
     * @param string $zipCode
     * @param string $city
     * @return array $responseArray
     */
    public function retrieveCurlResponse($curlResponse, $isStoreSearchEnabled, $zipCode = null, $city = null)
    {
        $responseArray = [];
        $modifiedLocation = [];

        //B-1913634 :: Retrieve Curl Response with new Endpoints if toggle Enabled
        if ($this->isRestrictedRecommendedToggle && isset($curlResponse['output']['search']) && $curlResponse['output']['search'] != "") {
            if(!$isStoreSearchEnabled) {
                foreach ($curlResponse['output']['search'] as $key => $value) {
                    $locationId = $value['officeLocationId'];
                    $modifiedLocation[$locationId] = $value;
                }
                $this->mergeSessionLocations($modifiedLocation);
            }
            $responseArray['success'] = 1;
            $responseArray['locations'] = $curlResponse['output']['search'];
            $this->logger->info(__METHOD__.':'.__LINE__. ': Location found with store number : '.$locationId);
        } elseif (isset($curlResponse['output']['locations']) && $curlResponse['output']['locations'] != "") {
            foreach ($curlResponse['output']['locations'] as $key => $value) {
                $locationId = $value['Id'];
                $modifiedLocation[$locationId] = $value;
            }
            $this->mergeSessionLocations($modifiedLocation);
            $responseArray['success'] = 1;
            $responseArray['locations'] = $curlResponse['output']['locations'];
            $this->logger->info(__METHOD__.':'.__LINE__. ': Location found with city : '.$city);
        } else {
            $responseArray['error'] = 1;
            $responseArray['message'] = "unknown error occur.";
            $this->logger->error(__METHOD__.':'.__LINE__.': unknown error '.$city);
        }
        return $responseArray;
    }

    /**
     * Get all location url
     *
     * @return string
     */
    public function getAllLocationUrl()
    {
            // B-1913634 :: L6 Location Endpoint  Url
        if ($this->isRestrictedRecommendedToggle) {
            return $this->scopeConfig->getValue('fedex/general/all_location_print_hub_api_url', ScopeInterface::SCOPE_STORE);
        } else {
            return $this->scopeConfig->getValue('fedex/general/all_location_api_url', ScopeInterface::SCOPE_STORE);
        }
    }

    /**
     * validate Store Numbers
     *
     * @param array $validStoreNumbers
     * @return array storeNumbers
     */
    public function validateStoreNumbers($storeNumbers)
    {
        $validStoreNumbers = [];
        $storesCount = 0;
        if (isset($storeNumbers)) {
            $storesCount = count($storeNumbers);
            for ($i=0; $i<$storesCount; $i++) {
                $storeCode = $storeNumbers[$i];
                if (preg_match('/^\d{3,4}$/', $storeCode)) {
                    // If the store code has 3 digits, prepend zero
                    if (strlen($storeCode) == 3) {
                        $storeCode = '0' . $storeCode;
                    }
                    $validStoreNumbers[$i] = $storeCode;
                }
            }
        }
        if (count($validStoreNumbers) === $storesCount) {
            return $validStoreNumbers;
        } else {
            return [];
        }
    }
    /**
     * Get address by locationId
     *
     * B-1084159 | Save pickup address in DB from location Id
     * @param string $locationId
     * @return array
     */
    public function getAddressByLocationId($locationId, $hoursOfOperation = false)
    {
        $url = $this->getAllLocationUrl();
        $responseArray = [];
        try {

            if (empty($locationId)) {
                $responseArray['error'] = 1;
                $responseArray['message'] = "Invalid Location Id";
                $this->logger->error(__METHOD__.':'.__LINE__.': Invalid location Id '.$locationId);
                return $responseArray;
            }

            if ($url != "") {
                $url = $url . '/' . $locationId;
                $gateWayToken=$this->punchoutHelper->getAuthGatewayToken();
                $authHeaderVal = "Authorization: Bearer ";
                if ($this->toggleConfig->getToggleConfigValue('E352723_use_clientId_header')) {
                    $authHeaderVal =  "client_id: ";
                }
                $authorization = $authHeaderVal.$gateWayToken;
                $headers = ["Content-Type: application/json", "Accept-Language: json" , $authorization ];
                $this->curl->setOptions(
                    [
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_ENCODING => ''
                    ]
                );

                $this->curl->get($url);
                $output = $this->curl->getBody();
                $curlResponse = json_decode($output, true);

                if (isset($curlResponse['successful'])
                             && $curlResponse['successful'] == 1
                                 && isset($curlResponse['output']['location'])) {
                    $outputLocation = $curlResponse['output']['location'];

                    if (isset($outputLocation['services'])) {
                        unset($outputLocation['services']);
                    }

                    if (isset($outputLocation['hoursOfOperation']) && $hoursOfOperation == false) {
                        unset($outputLocation['hoursOfOperation']);
                    }

                    $responseArray['success'] = 1;
                    $responseArray['address'] = json_encode($outputLocation);
                    $this->logger->info(__METHOD__.':'.__LINE__.
                    ': Location found with Location Id : '.$locationId);
                } else {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    ' Error response from curl request while getting address by location id.');
                    $responseArray['error'] = 1;
                    $responseArray['message'] = "unknown error occur.";
                }
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Location API URL is missing.');
                $responseArray['error'] = 1;
                $responseArray['message'] = "Location API URL is missing. Please check configuration setting";
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $responseArray['error'] = 1;
            $responseArray['message'] = $e->getMessage();
        }

        return $responseArray;
    }

    /**
     * @inheritDoc
     * B-1082825 | Display pickup location from DB on orders and quotes
     * Format address from array to html
     */
    public function formatAddress($addressArray)
    {
        $address = null;
        $countryCode = null;

        if (isset($addressArray['address']['countryCode'])) {
            $countryCode = $addressArray['address']['countryCode'];
        }

        if (isset($addressArray['name']) && $addressArray['name']) {
            $address = $address . $addressArray['name'] . '<br>';
        }

        if (isset($addressArray['address']['address1'])) {
            $address = $address . $addressArray['address']['address1'] . ' ';
        }

        if (isset($addressArray['address']['address2'])) {
            $address = $address . $addressArray['address']['address2'] . '<br>';
        }

        if (isset($addressArray['address']['city'])) {
            $address = $address . $addressArray['address']['city'] . ', ';
        }

        if (isset($addressArray['address']['stateOrProvinceCode'])) {
            $stateCode = $addressArray['address']['stateOrProvinceCode'];
            $stateName = null;
            if ($stateCode && $countryCode) {
                $stateName = $this->region->loadByCode($stateCode, $countryCode)->getName();
            }
            $address = $address . $stateName . ', ';
        }

        if (isset($addressArray['address']['postalCode'])) {
            $address = $address . $addressArray['address']['postalCode'] . '<br>';
        }

        if ($countryCode) {
            $countryName = $this->country->loadByCode($countryCode)->getName();
            $address = $address . $countryName . '<br>';
        }

        if (isset($addressArray['phone']) && $addressArray['phone']) {
            $address = $address . 'T: <a href="tel:'.$addressArray['phone'].'">'. $addressArray['phone'] .'</a>';
        }
        return $address;
    }
    /**
     * Get Post Fields
     *
     * @param boolean $isStoreSearchEnabled
     * @param string $stateCode
     * @param string $city
     * @param string $radius
     * @param string $zipCodeOrStoreCode
     * @return array
     */
    public function getPostFields($isStoreSearchEnabled, $stateCode, $city, $radius, $zipCodeOrStoreCode)
    {
        $jsonData = [];
        if ($this->isRestrictedRecommendedToggle) {
            if ($isStoreSearchEnabled) {
                $jsonData = [
                    'locationSearchRequest' => [
                        'officeLocationId' => $zipCodeOrStoreCode,
                        'address' => [
                            'countryCode' => self::COUNTRYCODE,
                            'addressClassification' => self::ADDRESSCLASSIFICATION
                        ],
                        'searchRadius' => [
                            'value' => $radius,
                            'unit' => self::UNIT
                        ]
                    ]
                ];
            } else {
                    $jsonData = [
                        'locationSearchRequest' => [
                            'address' => [
                                'city' => $city,
                                'stateOrProvinceCode' => $stateCode,
                                'postalCode' => $zipCodeOrStoreCode,
                                'countryCode' => self::COUNTRYCODE,
                                'addressClassification' => self::ADDRESSCLASSIFICATION
                            ],
                            'searchRadius' => [
                                'value' => $radius,
                                'unit' => self::UNIT
                            ],
                            'service' => [
                                'matchType' => self::MATCH_TYPE,
                                'serviceTypes' => null
                            ],
                            'include' => [
                                'printHubOnly' => false
                            ],
                            'resultOptions' => [
                                'resultCount' => 0,
                                'sort' => self::SORT_BY
                            ]
                        ]
                    ];
            }
        } else {
            $jsonData = [
                    'input' => [
                        'locationsFilters' => [ 0 => [
                            'address' => [
                                'countryCode' => self::COUNTRYCODE,
                                'postalCode' => $zipCodeOrStoreCode,
                                'stateOrProvinceCode' => $stateCode,
                                'city' => $city,
                            ],
                            'radius' => [
                                'unit' => 'mile',
                                'value' => $radius,
                            ],
                            'type' => self::TYPE,
                        ],
                    ],
                ]
              ];
        }
            return $jsonData;
    }
    /**
     * Get Headers
     *
     * @return array
     */
    public function getHeaders()
    {
        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $authorization = $authHeaderVal.$gateWayToken;
        $accessToken = $this->punchoutHelper->getTazToken();
        if ($this->isRestrictedRecommendedToggle) {
            return ["Content-Type: application/json", "Accept-Language: json" , $authorization,"Cookie: Bearer=". $accessToken];
        } else {
            return ["Content-Type: application/json", "Accept-Language: json" , $authorization];
        }
    }

    /**
     * Fetch All Locations With Curl
     *
     * @param string $url
     * @param array $headers
     * @param array $postfields
     * @param string $zipCode
     * @param string $city
     * @param boolean $isStoreSearchEnabled
     * @return array
     */
    public function fetchAllLocationsWithCurl($url, $headers, $postfields, $zipCode = null, $city = null, $isStoreSearchEnabled = false)
    {
        $responseArray = [];
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($postfields),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => ''
            ]
        );

        $this->curl->post($url, json_encode($postfields));
        $output = $this->curl->getBody();
        $curlResponse = json_decode($output, true);
        //B-1913634 :: Retrieve Curl Response On Toggle
        $curlResponseArray = $this->retrieveCurlResponse($curlResponse, $isStoreSearchEnabled, $zipCode, $city);
        if(!isset($curlResponseArray['error'])) {
            $responseArray = $curlResponseArray;
        }
        return $responseArray;
    }

    //merge locations to session
    public function mergeSessionLocations($modifiedLocation)
    {
        if(!empty($this->_customerSession->getAllLocations())){
        $sessiondata=json_decode($this->_customerSession->getAllLocations(),true);
        $modifiedLocation=$modifiedLocation+$sessiondata;
        $this->_customerSession->setAllLocations(json_encode($modifiedLocation));
        }
        else
        {
            $this->_customerSession->setAllLocations(json_encode($modifiedLocation));
        }
    }

    /**
     * Get HC Toggle for Commercial Customer
     *
     * @param bool $isCommercialCustomer
     * @return bool
     */
    public function getHcToggle($isCommercialCustomer)
    {
        if ($isCommercialCustomer) {
            try {
                $customer = $this->_customerSession->getCustomer();
                if (!$customer) {
                    return true;
                }
                $company = $this->getAssignedCompany($customer);
                return (bool)$company->getHcToggle();
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error fetching HC toggle: ' . $e->getMessage());
                return true;
            }
        }

        return true;
    }

    /**
     * Filter out premium (hotel convention) locations when includePremium flag is false.
     *
     * @param array $locations
     * @param bool $includePremium
     * @return array
     */
    private function filterPremiumLocations(array $locations, bool $includePremium): array
    {
        if ($includePremium || empty($locations)) {
            return $locations;
        }
        return array_values(array_filter($locations, function ($loc) {
            $format = $loc['locationFormat'] ?? $loc['locationFormate'] ?? null;
            if ($format === null) {
                return true;
            }
            return strtoupper($format) !== 'HOTEL_CONVENTION';
        }));
    }
}
