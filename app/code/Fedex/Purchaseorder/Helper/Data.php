<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Purchaseorder\Helper;

use Fedex\B2b\Model\NegotiableQuoteManagement;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\Purchaseorder\Helper\PurchaseOrderHelper;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreFactory;

/**
 * PO Data helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_configInterface;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptorInterface;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_tokenModelFactory;

    /**
     * @var \Magento\Integration\Model\Oauth\TokenFactory
     */
    protected $_countryFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $_collectionFactory;

   /**
    * @var \Magento\Sales\Model\OrderFactory $orderFactory
    */
   protected $orderFactory;

   protected $updateStr = "UPDATE";

   protected $setStatus = "` SET `status`= '";

   protected $indexRest = "index.php/rest/";

   protected $contentType = "Content-Type: application/json";

   protected $acceptEncoding = "Accept-Encoding: *";
    private mixed $baseUrl;
    private \Magento\Framework\App\ResourceConnection $_connection;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $configInterface,
        \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\App\ResourceConnection $connection,
        protected LoggerInterface $logger,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $collectionFactory,
        private \Magento\NegotiableQuote\Model\ResourceModel\QuoteGridInterface $quoteGrid,
        private NegotiableQuoteManagement $negotiableQuoteManagement,
        private \Fedex\Shipto\Helper\Data $shiptoHelper,
        protected \Magento\Integration\Model\AdminTokenService $adminTokenService,
        protected \Magento\Quote\Model\QuoteRepository $quoteRepository,
        private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        protected ToggleConfig $toggleConfig,
        private \Fedex\Purchaseorder\Helper\PurchaseOrderHelper $purchaseOrderHelper,
        private QuoteFactory $quotefactory,
        private StoreFactory $storefactory
    ) {
        parent::__construct($context);
        $this->_timezone = $timezone;
        $this->_date = $date;
        $this->_storeManager = $storeManager;
        $this->_configInterface = $configInterface;
        $this->_encryptorInterface = $encryptorInterface;
        $this->_tokenModelFactory = $tokenModelFactory;
        $this->_countryFactory = $countryFactory;
        $this->_connection = $connection;
        $this->_collectionFactory = $collectionFactory;
        $this->baseUrl = $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get PO number from PO Approval cXML
     *
     * @param array $poXml
     *
     * @return string $poNum
     */
    public function getPoNumber($poXml = [])
    {
        $poNum = '';
        if (!empty($poXml)) {
            foreach ($poXml as $key => $val) {
                if ($key == 'Request') {
                    foreach ($val['OrderRequest'] as $rt => $rd) {
                        if ($rt == 'OrderRequestHeader') {
                            $poNum = $rd['@attributes']['orderID'];
                        }
                    }
                }
            }
        }
        return $poNum;
    }

    /**
     * Get Action type from cXML
     *
     * @param array $poXml
     *
     * return string $type
     */
    public function getActionType($poXml = [])
    {
        $type = '';
        if (!empty($poXml)) {
            $type = $poXml['Request']['OrderRequest']['OrderRequestHeader']['@attributes']['type'];
        }
        return $type;
    }

    /**
     * Get Unique number
     * @codeCoverageIgnore
     * @param Int $lenght
     *
     * @return string
     */
    public function uniqidReal($lenght = 16)
    {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' No cryptographically secure random
            function available.');
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }

    /**
     * Get admin token for API's Authentications.
     *
     * @return string
     *
     * Atul || B-885040 || Code Refactor - Removed curl and called model method directly
     */
    public function getAdminToken()
    {
       $dataString = $this->getApiUserCredentials();

       $username = $dataString['username'];
       $password = $dataString['password'];
       return $this->adminTokenService->createAdminAccessToken($username, $password);
    }

    /**
     * Get API's Credentials of admin
     *
     * @return array
     */
    public function getApiUserCredentials()
    {
        $username = $this->_configInterface->getValue("fedex/authentication/username");
        $password = $this->_configInterface->getValue("fedex/authentication/password");
        $stringPassword = $this->_encryptorInterface->decrypt($password);
        return array(
            'username' => $username,
            'password' => $stringPassword,
        );
    }

    /**
     * Get Customer Token
     *
     * @param Int $customerId
     *
     * @return string
     */
    public function getCustomerToken($customerId = '')
    {
        $customerToken = $this->_tokenModelFactory->create();
        return $customerToken->createCustomerToken($customerId)->getToken();
    }

    /**
     * Adjust quote approval request
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $poNumber
     * @param Int    $customerId
     * @param array  $product
     * @param array  $association
     * @param array  $companyDetails
     * @param array  $shippingAddress
     * @param array  $billingAddress
     * @param array  $availableOption
     * @param string $gatewayToken
     * @param string $accessToken
     * @param string $tokenType
     * @param string $pickUpIdLocation
     *
     * @return array
     */
    // @codeCoverageIgnoreStart
    public function adjustQuote(
        $quote,
        $poNumber = '',
        $customerId = '',
        $product = [],
        $association = [],
        $companyDetails = [],
        $shippingAddress = [],
        $billingAddress = [],
        $availableOption = [],
        $gatewayToken = '',
        $accessToken = '',
        $tokenType = '',
        $pickUpIdLocation = ''
    )
    {
        $quoteId = $quote->getId();
        $token = $this->getAdminToken();
        $errorMessage = [];
        $customerToken = $this->getCustomerToken($customerId);
        $legacySiteName = $companyDetails['legacy_site_name'];
        //B-1598911-Order Approval time, get store Id from quote instead of company
        $quotecoll = $this->quotefactory->create()->load($quoteId);
        $storeId = $quotecoll->getStoreId();
        $storecoll = $this->storefactory->create()->load($storeId);
        $storeCode = $storecoll->getCode();
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' store id'.$storeId);
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' storeCode New'.$storeCode);

        if ($token && $customerToken) {

            $quoteAddressEmail = $shippingAddress['email'];

            // D-85695 - Validate shipping information during quote to order conversion
            // B-1299552 - Cleanup Toggle Feature - fix_missing_shipping_info_afterQuoteToOrder
            $this->verifyAndUpdateShippingInfo($quote, 'adjustQuote in place 1' ,$quoteAddressEmail);

            header("Content-Type: text/xml; charset=utf-8");
            $approvalResponse = $this->approveQuoteFromAdmin($quoteId, $token, $storeCode);

            if ($approvalResponse['error'] == 0) {

                // D-85695 - Validate shipping information during quote to order conversion
                // B-1299552 - Cleanup Toggle Feature - fix_missing_shipping_info_afterQuoteToOrder
                $this->verifyAndUpdateShippingInfo(
                    $quote,
                    'In adjustQuote after approveQuoteFromAdmin',
                    $quoteAddressEmail
                );

                $refreshResponse = $this->refreshCart($quoteId, $token);
                if ($refreshResponse['error'] == 0) {

                    // D-85695 - Validate shipping information during quote to order conversion
                    // B-1299552 - Cleanup Toggle Feature - fix_missing_shipping_info_afterQuoteToOrder
                    $this->verifyAndUpdateShippingInfo($quote, 'In adjustQuote after refreshCart',$quoteAddressEmail);

                    $shippingInfo = $this->shippingInformation(
                        $quoteId,
                        $customerToken,
                        $product,
                        $association,
                        $legacySiteName,
                        $shippingAddress,
                        $billingAddress,
                        $availableOption,
                        $gatewayToken,
                        $accessToken,
                        $tokenType,
                        $pickUpIdLocation,
                        $storeCode
                    );
                    if ($shippingInfo['error'] == 0) {

                        // D-85695 - Validate shipping information during quote to order conversion
                        // B-1299552 - Cleanup Toggle Feature - fix_missing_shipping_info_afterQuoteToOrder
                        $this->verifyAndUpdateShippingInfo(
                            $quote,
                            'In adjustQuote after shippingInformation',
                            $quoteAddressEmail
                        );

                        return $this->placeOrder(
                            $quote,
                            $poNumber,
                            $customerToken,
                            $product,
                            $association,
                            $legacySiteName,
                            $shippingAddress,
                            $billingAddress,
                            $availableOption,
                            $gatewayToken,
                            $accessToken,
                            $tokenType,
                            $pickUpIdLocation,
                            $storeCode
                        );
                    } else {
                        $this->logger->error(__METHOD__.':'.__LINE__.' Shipping Infomration
                        Magento API Error for the Quote ID: '.$quoteId);
                        $errorMessage = ['error' => 1, 'msg' => $shippingInfo['msg'], 'order_id' => ''];
                    }
                } else {
                    $this->logger->error(__METHOD__.':'.__LINE__.' Refresh Cart Magento API
                    Error: Unable to submit order for the Quote ID: '.$quoteId);
                    $errorMessage = ['error' => 1, 'msg' => 'Unable to submit order', 'order_id' => ''];
                }
            } else {
                //Code for revert back negotiable quote
                $this->changeNegotiableQuoteStatus($quoteId);
                $this->logger->error(__METHOD__.':'.__LINE__.' Approve Quote From Admin Magento
                API Error: Unable to submit order for the Quote ID: '.$quoteId);
                $errorMessage = ['error' => 1, 'msg' => 'Unable to submit order.
                Please try again after sometime', 'order_id' => ''];
            }
        } else {
            $this->logger->error(__METHOD__.':'.__LINE__.' Magento API Token Error for the Quote ID: '.$quoteId);
            $errorMessage = ['error' => 1, 'msg' => 'Unable to submit order: internal token error', 'order_id' => ''];
        }
        return $errorMessage;
    }

    /**
     * Call Quote Approval API
     *
     * @param Int    $quoteId
     * @param string $token
     * @param String $storeCode
     * @return array
     *
     * Atul || B-885040 || Code Refactor - Instead of curl, Model method can
     * not be called directly, due to restrictions, related to magento user accessibility
     */
    public function approveQuoteFromAdmin($quoteId = '', $token = '', $storeCode = '')
    {
        $data = ["quoteId" => $quoteId, "comment" => "Quote approved"];
        $dataString = json_encode($data);
        $setupURL = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_WEB) . $this->indexRest.$storeCode.
            "/V1/negotiableQuote/submitToCustomer";
        $headers = array($this->contentType,
        "Accept: application/json", "Accept-Language: json",
        "Content-Length: " . strlen($dataString),
        "Authorization: Bearer $token",
        $this->acceptEncoding);
        $ch = curl_init($setupURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        $submitToCustomerResponse = curl_exec($ch);
        if ($submitToCustomerResponse === false) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . curl_error($ch));
            return ['error' => 1, 'msg' => curl_error($ch)];
        } else {
            $submitToCustomerInfo = curl_getinfo($ch);
            curl_close($ch);
            if ($submitToCustomerInfo['http_code'] == 200) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Approve Quote from admin API
                success for the Quote ID: ' . $quoteId);
                return ['error' => 0, 'msg' => 'submit order'];
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Negotiable Quote Submission failed - submitToCustomer() Response: ' . $submitToCustomerResponse);
                return ['error' => 1, 'msg' => 'Unable to submit order'];
            }
        }
    }

    // @codeCoverageIgnoreEnd

    /**
     * Refersh cart before payemnt
     *
     * @param Int    $quoteId
     * @param string $token
     *
     * @return array
     *
     * Atul || B-885040 || Code Refactor - Removed curl and called model method directly
     */
    public function refreshCart($quoteId = '', $token = '')
    {
        $quoteObj = $this->quoteRepository->get($quoteId);
		if ($quoteObj->getId()) {
			$this->logger->info(__METHOD__ . ':' . __LINE__ . ' Refresh Quote API success for the Quote ID: ' . $quoteId);
			return ['error' => 0, 'msg' => ''];
		} else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Unable to refersh the cart.');
			return ['error' => 1, 'msg' => 'Unable to refersh the cart'];
		}
    }

    /**
     * Create Order
     *
     * @param Object $quote
     * @param string $poNumber
     * @param string $customerToken
     * @param array  $product
     * @param array  $association
     * @param string $legacySiteName
     * @param array  $shippingAddress
     * @param array  $billingAddress
     * @param array  $availableOption
     * @param string $gatewayToken
     * @param string $accessToken
     * @param string $tokenType
     * @param string $pickUpIdLocation
     *
     * @return array
     *
     * Atul || B-885040 || Code Refactor - Instead of curl, Model method can
     * not be called directly, due to restrictions, related to magento user accessibility
     */

    // @codeCoverageIgnoreStart
    public function placeOrder(
        $quote,
        $poNumber = '',
        $customerToken = '',
        $product = [],
        $association = [],
        $legacySiteName = '',
        $shippingAddress = [],
        $billingAddress = [],
        $availableOption = [],
        $gatewayToken = '',
        $accessToken = '',
        $tokenType = '',
        $pickUpIdLocation = '',
        $storeCode = ''
    )
    {
        $quoteId = $quote->getId();
        $shippingCarrierCode = 'fedexshipping';
        $shippingMethodCode = $availableOption['serviceType'];
        $data = array(
            "paymentMethod" => ["po_number" => $poNumber, 'method' => 'purchaseorder'],
            'site_name' => $legacySiteName,
            'product' => $product,
            'association' => $association,
            'gatewayToken' => trim($gatewayToken),
            'access_token' => trim($accessToken),
            'token_type' => trim($tokenType),
            'pickUpIdLocation' => $pickUpIdLocation,
            'reloadOptions' => $availableOption['reload'],
            'addressInformation' => array(
                'shipping_address' => array(
                    'region' => $shippingAddress['region'],
                    'region_id' => $shippingAddress['region_id'],
                    'country_id' => $shippingAddress['countryCode'],
                    'street' => $shippingAddress['street'],
                    'postcode' => $shippingAddress['postcode'],
                    'city' => $shippingAddress['city'],
                    'telephone' => $shippingAddress['telephone'],
                    'firstname' => $shippingAddress['firstname'],
                    'lastname' => $shippingAddress['lastname'],
                ),
                'billing_address' => array(
                    'region' => $billingAddress['region'],
                    'region_id' => $billingAddress['region_id'],
                    'region_code' => $billingAddress['region_code'],
                    'country_id' => $billingAddress['countryCode'],
                    'street' => $billingAddress['street'],
                    'postcode' => $billingAddress['postcode'],
                    'city' => $billingAddress['city'],
                    'firstname' => $billingAddress['firstname'],
                    'lastname' => $billingAddress['lastname'],
                    'email' => $billingAddress['email'],
                    'telephone' => $billingAddress['telephone'],
                ),
                'shipping_carrier_code' => $shippingCarrierCode,
                'shipping_method_code' => $shippingMethodCode,
            ),
        );
        $setupURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) .
        $this->indexRest.$storeCode."/V1/negotiable-carts/" . $quoteId . "/payment-information";
        $dataString = json_encode($data);
        $headers = array($this->contentType,
        "Accept: application/json",
        "Accept-Language: json",
        "Authorization: Bearer $customerToken",
        $this->acceptEncoding);
        $ch2 = curl_init($setupURL);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_ENCODING, '');
        $createOrder = curl_exec($ch2);
        if ($createOrder === false) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . curl_error($ch2));
            return ['error' => 1, 'msg' => curl_error($ch2)];
        } else {
            $arrayData = json_decode($createOrder, true);
            $createOrderinfo = curl_getinfo($ch2);
            curl_close($ch2);
            if ($createOrderinfo['http_code'] == 200) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Payment Information API success
                for the Quote ID: ' . $quoteId);
                $location = $this->getLocationID($quote);
                $this->updateQuoteGridData($quoteId);

                // B-1084159 | Save pickup address in DB from location Id
                $pickupAddress = $quote->getShippingAddress()->getPickupAddress();
                if ($pickupAddress && $arrayData) {
			        $this->updatePickupLocationAddress($arrayData, $pickupAddress);
                }
                $quoteAddressEmail = $shippingAddress['email'];
                $this->verifyAndUpdateShippingInfo($quote, 'adjustQuote in place 1' ,$quoteAddressEmail);
                $this->shiptoHelper->createInvoice($arrayData);
                return ['error' => 0, 'msg' => 'success', 'order_id' => $arrayData, 'location_id' => $location];
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $arrayData['message']);
                return ['error' => 1, 'msg' => $arrayData['message'], 'order_id' => ''];
            }
        }
    }

    /**
     * Shipping Method Refresh
     *
     * @param Int    $quoteId
     * @param string $customerToken
     * @param array  $product
     * @param array  $association
     * @param string $legacySiteName
     * @param array  $shippingAddress
     * @param array  $billingAddress
     * @param array  $availableOption
     * @param string $gatewayToken
     * @param string $accessToken
     * @param string $tokenType
     * @param string $pickUpIdLocation
     * @param string $storeCode
     *
     * @return array
     *
     * Atul || B-885040 || Code Refactor - Instead of curl, Model method can
     * not be called directly, due to restrictions, related to magento user accessibility
     */
    public function shippingInformation(
        $quoteId = '',
        $customerToken = '',
        $product = [],
        $association = [],
        $legacySiteName = '',
        $shippingAddress = [],
        $billingAddress = [],
        $availableOption = [],
        $gatewayToken = '',
        $accessToken = '',
        $tokenType = '',
        $pickUpIdLocation = '',
        $storeCode = ''
    )
    {
        $shippingCarrierCode = 'fedexshipping';
        $shippingMethodCode = $availableOption['serviceType'];
        $data = array(
            'site_name' => $legacySiteName,
            'product' => $product,
            'association' => $association,
            'gatewayToken' => trim($gatewayToken),
            'access_token' => trim($accessToken),
            'token_type' => trim($tokenType),
            'pickUpIdLocation' => $pickUpIdLocation,
            'reloadOptions' => $availableOption['reload'],
            'addressInformation' => array(
                'shipping_address' => array(
                    'region' => $shippingAddress['region'],
                    'region_id' => $shippingAddress['region_id'],
                    'country_id' => $shippingAddress['countryCode'],
                    'street' => $shippingAddress['street'],
                    'postcode' => $shippingAddress['postcode'],
                    'city' => $shippingAddress['city'],
                    'telephone' => $shippingAddress['telephone'],
                    'firstname' => $shippingAddress['firstname'],
                    'lastname' => $shippingAddress['lastname'],
                ),
                'billing_address' => array(
                    'region' => $billingAddress['region'],
                    'region_id' => $billingAddress['region_id'],
                    'region_code' => $billingAddress['region_code'],
                    'country_id' => $billingAddress['countryCode'],
                    'street' => $billingAddress['street'],
                    'postcode' => $billingAddress['postcode'],
                    'city' => $billingAddress['city'],
                    'firstname' => $billingAddress['firstname'],
                    'lastname' => $billingAddress['lastname'],
                    'email' => $billingAddress['email'],
                    'telephone' => $billingAddress['telephone'],
                ),
                'shipping_carrier_code' => $shippingCarrierCode,
                'shipping_method_code' => $shippingMethodCode,
            ),
        );
        $setupURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) .
        $this->indexRest.$storeCode."/V1/negotiable-carts/" . $quoteId . "/shipping-information";
        $dataString = json_encode($data);
        $headers = array($this->contentType,
        "Authorization: Bearer $customerToken",
        $this->acceptEncoding);
        $ch2 = curl_init($setupURL);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        $shippingInfo = curl_exec($ch2);
        if ($shippingInfo === false) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . curl_error($ch2));
            return ['error' => 1, 'msg' => curl_error($ch2)];
        } else {
            $arrayData = json_decode($shippingInfo, true);
            $info = curl_getinfo($ch2);
            curl_close($ch2);
            if ($info['http_code'] == 200) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Shipping Infomration API
                success for the Quote ID: ' . $quoteId);
                return ['error' => 0, 'msg' => 'success'];
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $arrayData['message']);
                return ['error' => 1, 'msg' => $arrayData['message']];
            }
        }
    }

    // @codeCoverageIgnoreEnd

    /**
     * Send Error Massage Back To ERP
     *
     * @param string $message
     *
     * @return string
     */
    public function sendError($message = '')
    {
        $payload = uniqid() . '.' . $this->uniqidReal();
        $timestamp = $this->_timezone->date($this->_date->gmtDate())->format('Y-m-d H:i:sP');
        return '<?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.2.021/cXML.dtd">
        <cXML payloadID="' . $payload . '@c0022326.prod.cloud.fedex.com"
        timestamp="' . $timestamp . '" xml:lang="en-us">
        <Response><Status code="500" text="Internal Server Error">' . $message . '</Status></Response></cXML>';
    }

    /**
     * Send Success Message To ERP
     *
     * @param string $message
     *
     * @return string
     */
    public function sendSuccess($message = '')
    {
        $payload = uniqid() . '.' . $this->uniqidReal();
        $timestamp = $this->_timezone->date($this->_date->gmtDate())->format('Y-m-d H:i:sP');
        return '<?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.2.021/cXML.dtd">
        <cXML xml:lang="en-US" payloadID="' . $payload . '@c0022326.prod.cloud.fedex.com"
        timestamp="' . $timestamp . '">
        <Response><Status code="200" text="Ok">' . $message . '</Status></Response></cXML>';
    }

    /**
     * Get Refreshed LocationID
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     *
     * @return Boolean|String
     */
    public function getLocationID($quote)
    {
        $addressData = $quote->getShippingAddress();
        if ($addressData['shipping_method'] == 'fedexshipping_PICKUP') {
            $pickLocationId = $addressData['shipping_description'];
        } else {
            $pickLocationId = 0;
        }
        return $pickLocationId;
    }

    /**
     * Get Country ID from DB
     *
     * @param String $countryName
     *
     * @return Int $countryId
     */
    public function getCountryCode($countryName = '')
    {
        $countryName = trim($countryName);
        $countryCollection = $this->_countryFactory->create()->getCollection();
        foreach ($countryCollection as $country) {
            if ($countryName == $country->getName()) {
                $countryId = $country->getCountryId();
                break;
            }
        }

        return $countryId;
    }

    /**
     * update Quote updated date in quote grid DB
     *
     * @param Int $quoteId
     */
    public function updateQuoteGridData($quoteId)
    {
        $updatedDate = $this->_date->gmtDate();
        $connection = $this->_connection->getConnection();
        $table = $connection->getTableName('negotiable_quote_grid');
        $query = $this->updateStr." `" . $table . "` SET `updated_at`= '".$updatedDate."' WHERE entity_id = $quoteId ";
        $connection->query($query);
    }

    /**
     * update pickup address in order table DB
     * B-1084159 | Save pickup address in DB from location Id
     *
     * @param int $orderId
     * @param String $pickupAddress
     */
    public function updatePickupLocationAddress($orderId, $pickupAddress)
    {
	    $orderObj = $this->orderRepository->get($orderId);
	    if ($orderObj) {
		    $orderObj->setPickupAddress($pickupAddress)->save();
	    }
    }

    /**
     * Change quote status
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     */
    public function changeQuoteStatus($quote)
    {
        $quoteId = $quote->getId();
        $customerId = $quote->getCustomer()->getId();
        $statusOrdered = NegotiableQuoteInterface::STATUS_ORDERED;

        $connection = $this->_connection->getConnection();
        $table1 = $connection->getTableName('negotiable_quote_grid');
        $query1 = $this->updateStr." `" . $table1 . $this->setStatus .$statusOrdered."' WHERE entity_id = $quoteId";
        $connection->query($query1);

        $table2 = $connection->getTableName('negotiable_quote');
        $query2 = $this->updateStr." `" . $table2 . $this->setStatus .$statusOrdered."' WHERE quote_id = $quoteId";
        $connection->query($query2);

        $logData = ["status" => ["old_value" => NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
        "new_value" => $statusOrdered]];
        $logData = json_encode($logData);
        $table3 = $connection->getTableName('negotiable_quote_history');
        $query3 = "INSERT INTO `" . $table3 . "` (`quote_id`, `is_seller`, `author_id`,
        `is_draft`, `status`, `log_data`)
        VALUES (" .$quoteId. ", '0', '".$customerId."', '0', 'updated', '" .$logData. "')";
        $connection->query($query3);
    }

    /**
     * Change quote status
     *
     * @param $quoteId
     */
    public function changeNegotiableQuoteStatus($quoteId)
    {
        $statusCreated = NegotiableQuoteInterface::STATUS_CREATED;

        $connection = $this->_connection->getConnection();
        $table1 = $connection->getTableName('negotiable_quote_grid');
        $query1 = $this->updateStr." `" . $table1 . $this->setStatus .$statusCreated."' WHERE entity_id = $quoteId";
        $connection->query($query1);

        $table2 = $connection->getTableName('negotiable_quote');
        $query2 = $this->updateStr." `" . $table2 . $this->setStatus .
        $statusCreated."',`snapshot`='' WHERE quote_id = $quoteId";
        $connection->query($query2);

    }

    /**
     * Return region Data for region name
     * @param string $region
     * @return string[]
     */
    public function getRegionCode(string $region): array
    {
        $regionData = [];
        $regionCollectionFactory = $this->_collectionFactory->create();
        $regionCollection = $regionCollectionFactory->addRegionNameFilter($region);
        if ($regionCollection->getSize()) {
            $regionData = $regionCollection->getFirstItem()->toArray();
        }
        return $regionData;
    }

    /**
     * Change quote status to delete
     *
     * @param Object $quote
     */
    public function changeQuoteStatusatdelete($quote)
    {
        try {
            $quoteId = $quote->getId();
            $this->quoteGrid->refreshValue(
                'entity_id',
                $quoteId,
                'status',
                NegotiableQuoteInterface::STATUS_CLOSED
            );
            $this->negotiableQuoteManagement->closed($quoteId);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid/Missing Quote Id.');
            return $this->sendError('Invalid/Missing Quote Id');
        }
    }

    /**
     * Validate shipping address fields length
     *
     * @param array $shippingAddress
     * @return string $streetValidation
     */
    public function validateShipToFieldsLength($shippingAddress)
    {
        $validationResult = [];
        $validationResultArray = [];

        if (!empty($shippingAddress['companyName']) && strlen($shippingAddress['companyName']) > 100) {
            $validationResultArray[] = 'Company';
        }
        if (is_array($shippingAddress['street'])) {
            $streetLineCount = 1;
            foreach ($shippingAddress['street'] as $street) {
                if (!empty($street) && strlen($street) > 70) {
                    $validationResult['street'][$streetLineCount] = 'Address Line' . $streetLineCount;
                }
                $streetLineCount++;
            }
            if (isset($validationResult['street'])) {
                $validationResultArray[] = implode("-", $validationResult['street']);
            }
        }
        if (!empty($shippingAddress['email']) && strlen($shippingAddress['email']) > 150) {
            $validationResultArray[] = 'Email';
        }

        return implode(",", $validationResultArray);
    }

	/**
     * Update shipping method if not exist
     * @codeCoverageIgnore
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $location
     */
    public function verifyAndUpdateShippingInfo($quote, $location = null,$quoteAddressEmail = null)
    {
        if ($quoteAddressEmail != null &&
            $quote->getBillingAddress()->getShippingMethod() == "fedexshipping_PICKUP"
        ) {
            $connection = $this->_connection->getConnection();
            $quoteAddress = $connection->getTableName('quote_address');
            $quoteId = $quote->getId();
            $query = $this->updateStr." `" . $quoteAddress . "` SET `email`= '$quoteAddressEmail' WHERE quote_id =" .
            $quoteId;
            $connection->query($query);
        }

		if (!empty($quote) && $quote->getBillingAddress()) {
			$shipMethodInShipping = $quote->getShippingAddress()->getShippingMethod();
			$shipMethodInBilling = $quote->getBillingAddress()->getShippingMethod();

			if (!empty($shipMethodInBilling) && empty($shipMethodInShipping)) {
				$shipAddrssId = $quote->getShippingAddress()->getId();
				$quoteId = $quote->getId();

				$shipDescrInBilling = $quote->getBillingAddress()->getShippingDescription();

				$quote->getShippingAddress()->setShippingMethod($shipMethodInBilling)
                ->setShippingDescription($shipDescrInBilling)->save();
				$this->logger->info(__METHOD__ . ':' . __LINE__ . ' Shipping Information
                updated for quote Id : ' . $quoteId . ' and location is : ' . $location);
			}
		}
	}

	/**
     * Update snapshot for quote in negotiable quote
     * @codeCoverageIgnore
     * D-85695 - Validate shipping information during quote to order conversion
     * @param int $quoteId
     */
    public function updateSnapshotForQuote($quoteId)
    {
		if (!empty($quoteId)) {
			$connection = $this->_connection->getConnection();
			$negQuote = $connection->getTableName('negotiable_quote');
			$query = $this->updateStr." `" . $negQuote . "` SET `status`= 'created', `snapshot`= NULL,
            `notifications` = NULL WHERE quote_id =" . $quoteId;
			$connection->query($query);
			$this->logger->info(__METHOD__ . ':' . __LINE__ . ' Snapshot and Status updated for quote Id : ' . $quoteId);
		}
	}
    /**
     * B-1285259  PO failure issue
     *
     * @param string $deliverTo
     */
    public function getNameFromXML($deliverTo)
    {
        $deliverTo = trim($deliverTo);
        if (substr_count($deliverTo, "/") == 1) {
            $explodedName = explode("/", $deliverTo);
            if (!empty($explodedName) && count($explodedName) == 2) {
                $deliverTo = trim($explodedName[0]);
            }
        } elseif (substr_count($deliverTo, ",") == 1) {
            $explodedName = explode(",", $deliverTo);
            if (!empty($explodedName) && count($explodedName) == 2) {
                $returnName['firstname'] = trim($explodedName[1]);
                $returnName['lastname'] = trim($explodedName[0]);
                return $returnName;
            }
        }
        $deliverTo = preg_replace("/[^a-zA-Z0-9]+/", " ", trim($deliverTo));
        $deliverTo = trim($deliverTo);
        $nameArray = explode(" ", $deliverTo);
        $lname = end($nameArray);
        if (strlen($lname) < 2 && substr_count($deliverTo, " ") > 1) {
            $explodedName = explode(" ", $deliverTo);
            if (!empty($explodedName) && count($explodedName) >= 2) {
                $returnName['firstname'] = trim($explodedName[0]);
                unset($explodedName[0]);
                $returnName['lastname'] = trim(implode(" ", $explodedName));
                return $returnName;
            }
        }
        array_pop($nameArray);
        $fname = implode(" ", $nameArray);
        if ($fname == "") {
            $fname = $lname;
        }
        $returnName['firstname'] = $fname;
        $returnName['lastname'] = $lname;
        return $returnName;
    }
}
