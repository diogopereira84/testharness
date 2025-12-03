<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Purchaseorder\Model;

use Exception;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\Config\Source\CredtiCardOptions;
use Fedex\Company\Model\Config\Source\FedExAccountOptions;
use Fedex\Company\Model\Config\Source\PaymentAcceptance;
use Fedex\Company\Model\Config\Source\PaymentOptions;
use Fedex\Email\Helper\Data as Emailhelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Punchout\Api\Data\ConfigInterface;
use Fedex\Purchaseorder\Api\PoInterface;
use Fedex\Purchaseorder\Helper\Data as Pohelper;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\Quote\Rewrite\Quote\Model\Quote;
use Fedex\ReorderInstance\Helper\ReorderInstanceHelper;
use Fedex\Shipto\Helper\Data as Shiptohelper;
use Fedex\SubmitOrderSidebar\Model\SubmitOrder as SubmitOrderModel;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Magento\Catalog\Model\Product\Type;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObjectFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Psr\Log\LoggerInterface;

/**
 * PO Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Po implements PoInterface
{
    public const TECH_TITANS_SPECIALS_CHARS_REMOVE_TOGGLE = 'tech_titans_specials_chars_remove';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_configInterface;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_cartRepositoryInterface;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Fedex\Punchout\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface
     */
    protected $_companyRepository;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Fedex\Purchaseorder\Helper\Data
     */
    protected $_poHelper;

    /**
     * @var \Fedex\Shipto\Helper\Data
     */
    protected $_shiptoHelper;

    /**
     * @var \Fedex\Email\Helper\Data
     */
    protected $_rejectHelper;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface
     */
    protected $_negotiableQuoteRepository;

    /**
     * @var Fedex\Purchaseorder\Model\Po
     */
    public $_baseUrl;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface
     */
    public $_companyMgmtRepository;

    protected $_region;

    const ATTRIBUTE = '@attributes';

    /**
     * Po Constructor
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param ScopeConfigInterface $configInterface
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param Order $order
     * @param PunchoutHelper $helper
     * @param CompanyRepositoryInterface $companyRepository
     * @param RegionFactory $regionFactory
     * @param Pohelper $poHelper
     * @param Shiptohelper $shiptoHelper
     * @param Region $region
     * @param LoggerInterface $logger
     * @param Emailhelper $rejectHelper
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param CompanyManagementInterface $companyMgmtRepository
     * @param ToggleConfig $toggleConfig
     * @param CompanyHelper $companyHelper
     * @param ReorderInstanceHelper $reorderInstanceHelper
     * @param OptimizeItemInstanceHelper $optimizeItemInstanceHelper
     * @param HeaderData $headerData
     * @param FXORateQuote $FXORateQuote
     * @param SubmitOrderBuilder $submitOrderBuilder
     * @param SubmitOrderModel $submitOrderModel
     * @param SubmitOrderDataArray $submitOrderDataArray
     * @param DataObjectFactory $dataObjectFactory
     * @param ConfigInterface $punchoutConfig
     * @param QuoteHelper $quoteHelper
     * @param ProductBundleConfig $productBundleConfig
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepositoryInterface,
        ScopeConfigInterface $configInterface,
        CartRepositoryInterface $cartRepositoryInterface,
        Order $order,
        PunchoutHelper $helper,
        CompanyRepositoryInterface $companyRepository,
        RegionFactory $regionFactory,
        Pohelper $poHelper,
        Shiptohelper $shiptoHelper,
        Region $region,
        protected LoggerInterface $logger,
        Emailhelper $rejectHelper,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        CompanyManagementInterface $companyMgmtRepository,
        protected ToggleConfig $toggleConfig,
        protected CompanyHelper $companyHelper,
        protected ReorderInstanceHelper $reorderInstanceHelper,
        protected OptimizeItemInstanceHelper $optimizeItemInstanceHelper,
        protected HeaderData $headerData,
        protected FXORateQuote $FXORateQuote,
        protected SubmitOrderBuilder $submitOrderBuilder,
        protected SubmitOrderModel $submitOrderModel,
        private SubmitOrderDataArray $submitOrderDataArray,
        private DataObjectFactory $dataObjectFactory,
        protected ConfigInterface $punchoutConfig,
        protected QuoteHelper $quoteHelper,
        protected readonly ProductBundleConfig $productBundleConfig
    ) {
        $this->_storeManager = $storeManager;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_configInterface = $configInterface;
        $this->_cartRepositoryInterface = $cartRepositoryInterface;
        $this->_order = $order;
        $this->_helper = $helper;
        $this->_companyRepository = $companyRepository;
        $this->_regionFactory = $regionFactory;
        $this->_poHelper = $poHelper;
        $this->_shiptoHelper = $shiptoHelper;
        $this->_region = $region;
        $this->_rejectHelper = $rejectHelper;
        $this->_negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->_baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        $this->_companyMgmtRepository = $companyMgmtRepository;
    }

    /**
     * Send PO Failer Notification to Customer
     *
     * @param int $quoteId
     * @param string $rejectionReason
     */

    public function sendFailureNotification($quoteId = '', $rejectionReason = '')
    {
        return $this->_shiptoHelper->sendOrderFailureNotification($quoteId, $rejectionReason);
    }

    public function getPoxmlCheckQuoteStatus($quoteStatus, $quote)
    {
        $sendErrorMessage = '';
        if ($quoteStatus != NegotiableQuoteInterface::STATUS_ORDERED &&
            ($quoteStatus == NegotiableQuoteInterface::STATUS_CREATED ||
                $quoteStatus == NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)) {
            $this->_poHelper->changeQuoteStatusatdelete($quote);
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Order is canceled');
            $sendErrorMessage = 'Order is canceled';
        } elseif ($quoteStatus == NegotiableQuoteInterface::STATUS_DECLINED ||
            $quoteStatus == NegotiableQuoteInterface::STATUS_CLOSED) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Order is already canceled');
            $sendErrorMessage = 'Order is already canceled.';
        } elseif ($quoteStatus == NegotiableQuoteInterface::STATUS_EXPIRED) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Order is already expired');
            $sendErrorMessage = 'Order is already expired.';
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Order is already processed and cannot be canceled.');
            $sendErrorMessage = 'Order is already processed and cannot be canceled.';
        }
        return $this->_poHelper->sendError(__($sendErrorMessage));
    }

    /**
     * Process PO approval cXML
     *
     * @param array $companyDetails
     * @param array $xmlArray
     *
     * @return string
     */
    public function getPoxml($companyDetails = [], $xmlArray = [])
    {
        $quoteData = $this->verifyQuoteDetails($xmlArray);
        if ($quoteData['available'] == 0 || empty($quoteData['quote_id'])) {
            if (isset($quoteData['message'])) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . $quoteData['message']);
                return $this->_poHelper->sendError($quoteData['message']);
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid/Missing Quote Id');
                return $this->_poHelper->sendError('Invalid/Missing Quote Id');
            }
        } else {
            $quoteStatus = $quoteData['status'];
        }
        // D-85695 - Validate shipping information during quote to order conversion
        // B-1299552 - Cleanup Toggle Feature - fix_missing_shipping_info_afterQuoteToOrder
        $quoteId = $quoteData['quote_id'];
        $this->validateSnapshot($quoteId);
        $rejectionReason = '';
        $returnPoXMl = '';
        // Identify which action to perform (reject/order submission)
        $actionType = $this->_poHelper->getActionType($xmlArray);
        if (empty($actionType)) {
            $rejectionReason = 'Misssing Action Type';
            $this->sendFailureNotification($quoteId, $rejectionReason);
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $rejectionReason);
            $returnPoXMl = $this->_poHelper->sendError(__($rejectionReason));
        } elseif ($actionType == 'new') {
            $returnPoXMl = $this->processOrder($xmlArray, $quoteId, $companyDetails, $quoteStatus);
        } elseif (strtolower($actionType) == 'delete') {
            $quote = $this->_cartRepositoryInterface->get($quoteId);
            $returnPoXMl = $this->getPoxmlCheckQuoteStatus($quoteStatus, $quote);
        } else {
            $rejectionReason = 'Action type not yet supported';
            $this->sendFailureNotification($quoteId, $rejectionReason);
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $rejectionReason);
            $returnPoXMl = $this->_poHelper->sendError(__($rejectionReason));
        }
        return $returnPoXMl;
    }

    public function processOrderQuoteStatusValidate($rejectionReason, $quoteStatus, $quoteId)
    {
        if ($quoteStatus == NegotiableQuoteInterface::STATUS_ORDERED) {
            $order_id = $this->_cartRepositoryInterface->get($quoteId)->getReservedOrderId();
            $externalID = $this->_order->loadByIncrementId($order_id)->getExtOrderId();

            if (!empty($order_id) && !empty($externalID)) {
                // Check if quote is already converted into Order successfully
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Order already accepted for the Quote ID: ' . $quoteId);
                $rejectionReason = 'Order already accepted : ' . $order_id;
                $this->sendFailureNotification($quoteId, $rejectionReason);

            }
        } elseif ($quoteStatus == NegotiableQuoteInterface::STATUS_CLOSED) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Order is already canceled.');
            $rejectionReason = 'Order is already canceled.';
        }
        return $rejectionReason;
    }

    public function processOrderPoNumberLengthValidate(
        $poNumber,
        $quoteId,
        $withCXML,
        $xmlArray,
        $rejectionReason
    ) {
        if (empty($poNumber)) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Po Number is Missing for the Quote ID: ' . $quoteId . $withCXML .
                print_r(json_encode($xmlArray), true));
            $rejectionReason = 'Po Number is Missing';
            $this->sendFailureNotification($quoteId, $rejectionReason);
        } elseif (strlen(trim($poNumber)) > 25) {
           $rejectionReason = __('PO Number must be less than 25 characters');
            $this->sendFailureNotification($quoteId, $rejectionReason);
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Quote id: ' . $quoteId . ' ' . $rejectionReason);
        }
        return $rejectionReason;
    }
    public function processOrderDeliveryOptionValidation(
        $rejectionReason,
        $availableOption,
        $quoteId,
        $withCXML,
        $xmlArray
    ) {
        if ($availableOption['available'] == 0) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Missing Shipping Options for the Quote ID: ' . $quoteId . $withCXML .
                print_r(json_encode($xmlArray), true));
            $rejectionReason = "Missing Shipping Options";
            $this->sendFailureNotification($quoteId, $rejectionReason);
        }
        return $rejectionReason;
    }

    public function processOrderShipingInfoValidation(
        $rejectionReason,
        $shipAddress,
        $quoteId,
        $xmlArray,
        $withCXML
    ) {
        if ($shipAddress['available'] == 0) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Shipping Address is Missing for the Quote ID: ' . $quoteId . '
            with cXML: ' . print_r(json_encode($xmlArray), true));
            $rejectionReason = "Shipping Address is Missing";
        } else {
            $shipValidation = $this->validateRequiredShippingInfo($shipAddress['address']);
            if ($shipValidation['available'] == 0) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Required shipping information is missing for the Quote ID: ' .
                    $quoteId . $withCXML . print_r(json_encode($xmlArray), true));
                $rejectionReason = $shipValidation['msg'];
                $this->sendFailureNotification($quoteId, $rejectionReason);
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                    ' Shipping address validation passed for the Quote ID: ' . $quoteId . '
                with cXML: ' . print_r(json_encode($xmlArray), true));
                $billingAddress = $shippingAddress = $shipAddress['address'];
            }
        }
        return $rejectionReason;
    }
    public function processOrderContactValidate(
        $rejectionReason,
        $contactDetail,
        $xmlArray,
        $quoteId
    ) {
        if ($contactDetail['available'] == 0) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Required contact detail is missing for the Quote ID: ' . $quoteId . '
            with cXML: ' . print_r(json_encode($xmlArray), true));
            $rejectionReason = 'Contact Details are Missing';
            $this->sendFailureNotification($quoteId, $rejectionReason);
            /* D-93054 */
        } else if ($contactDetail['available'] == 3) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $contactDetail['msg']);
            $rejectionReason = $contactDetail['msg'];
            $this->sendFailureNotification($quoteId, $rejectionReason);
        } else {
            $contactValidation = $this->validateRequiredContactInfo($contactDetail['contact']);
            if ($contactValidation['available'] == 0) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Validate contact detail is failed for the Quote ID: ' . $quoteId . '
                with cXML: ' . print_r(json_encode($xmlArray), true));
                $rejectionReason = $contactValidation['msg'];
                $this->sendFailureNotification($quoteId, $rejectionReason);
            }
        }
        return $rejectionReason;
    }

    /**
     * @param $dbItemDetails
     * @param Quote $quote
     * @param $poNumber
     * @param $companyDetails
     * @param $shippingAddress
     * @param $billingAddress
     * @param $availableOption
     * @param $pickUpIdLocation
     * @param $quoteId
     * @param $withCXML
     * @param $xmlArray
     * @param $contactDetails
     * @param $companyId
     * @return string
     */
    public function processOrderAfterDbItemValid(
        $dbItemDetails,
        $quote,
        $poNumber,
        $companyDetails,
        $shippingAddress,
        $billingAddress,
        $availableOption,
        $pickUpIdLocation,
        $quoteId,
        $withCXML,
        $xmlArray,
        $contactDetails,
        $companyId
    ) {
        $responseCXML = '';
        $productData = $this->getProductData($dbItemDetails);
        $createOrderResponse = $this->convertQuoteToOrder(
            $quote,
            $poNumber,
            $companyDetails,
            $productData['jsonData'],
            $productData['productAssociations'],
            $shippingAddress,
            $billingAddress,
            $availableOption,
            $pickUpIdLocation,
            $companyId
        );
        if ($createOrderResponse['error']) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Convert quote into Order failed for the Quote ID: ' . $quoteId . ' with message: '
                . $createOrderResponse['msg'] . $withCXML . print_r(json_encode($xmlArray), true));
            $rejectionReason = $createOrderResponse['msg'];
        } else {
            if ($this->punchoutConfig->getMigrateEproNewPlatformOrderCreationToggle($companyId)) {
                $logHeader = 'File: ' . self::class . ' Method: ' . __METHOD__ . ' Line:' . __LINE__;
                $orderNumber = $this->submitOrderBuilder->getGTNNumber($quote);
                try {
                    $shipmentId = $quote->getData('fxo_shipment_id');
                    $dataObject = $this->createDataObject(
                        $companyId,
                        $quote,
                        $shippingAddress,
                        $pickUpIdLocation,
                        $poNumber,
                        $orderNumber,
                        $shipmentId
                    );

                    if((empty($dataObject->getProductData())
                            || count($dataObject->getProductData()) !== count($productData['jsonData']))
                    || (empty($dataObject->getProductAssociations())
                            || count($dataObject->getProductAssociations()) !== count($productData['productAssociations']))) {
                        $result = $this->fixQuantity($quote);
                        $dataObject->setProductData($result['product'] ?? []);
                        $dataObject->setProductAssociations($result['productAssociations'] ?? []);
                    }

                    $orderData = $this->getOrderDetails($dataObject, $quote, $pickUpIdLocation);

                    $preferredPaymentMethod = $this->companyHelper->getPreferredPaymentMethodName($companyId);
                    $paymentData = $this->buildPaymentData($companyId, $preferredPaymentMethod, $poNumber);

                    $dataObjectForFujistu = $this->buildDataForFujitsu(
                        $quote,
                        $paymentData,
                        $dataObject,
                        $orderData,
                        $pickUpIdLocation,
                        $preferredPaymentMethod,
                        $orderNumber
                    );
                    $dataObjectForFujistu->setCompanyId($companyId);

                    $loadedOrder = $this->_order->load($createOrderResponse['order_id']);

                    $response = $this->submitOrderBuilder->callRateQuoteApiWithCommitAction(
                        $logHeader,
                        $dataObjectForFujistu,
                        $paymentData,
                        $loadedOrder
                    );
                    if (isset($response['error']) && $response['error']) {
                        if (isset($response['response']['errors'][0]['message'])) {
                            throw new Exception($response['response']['errors'][0]['message']);
                        }
                        throw new Exception($response['message'] ?? $response['msg'] ?? '');
                    }

                    // @codeCoverageIgnoreStart
                    $this->reorderInstanceHelper->pushOrderIdInQueue($createOrderResponse['order_id']);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ .
                        ' Order Submission success for the Quote ID: ' . $quoteId . $withCXML .
                        print_r(json_encode($xmlArray), true));

                    $responseCXML = $this->saveExternalOrdId(
                        $quote,
                        $orderNumber,
                        $createOrderResponse['order_id'],
                        $paymentData->paymentMethod,
                        $companyId
                    );

                    try {
                        $this->_poHelper->changeQuoteStatus($quote);
                        // Push quote id in queue to clean item instance from quote
                        $this->optimizeItemInstanceHelper->pushQuoteIdQueue($quoteId);
                    } catch (Exception$e) {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ .
                            ' Order Status change to Success failed for the Quote ID: ' . $quoteId);
                        $rejectionReason = 'Order Status change to Success failed';
                        $this->sendFailureNotification($quoteId, $rejectionReason);

                    }
                    return $responseCXML;
                    // @codeCoverageIgnoreEnd
                } catch (Exception $exception) {
                    $this->submitOrderModel->unsetOrderInProgress();
                    $this->logger->info(
                        $logHeader .
                        ' Problem when calling fujitsu rate quote API for Quote Id:' . $quoteId . ' $shipmentId => ' .
                        $shipmentId . ' GTN Number => ' . $orderNumber . ' Exception => ' . $exception
                    );
                    $rejectionReason = 'Problem when calling fujitsu rate quote API for Quote Id: ' . $quoteId .
                        ' Exception => ' . $exception->getMessage();
                    $this->sendFailureNotification($quoteId, $exception->getMessage());
                }
            } else {
                $orderSubmissionResponse = $this->constructOrderSubmissionAPIRequest(
                    $quote,
                    $dbItemDetails,
                    $shippingAddress,
                    $poNumber,
                    $contactDetails,
                    $companyId,
                    $availableOption,
                    $quoteId,
                    $createOrderResponse['location_id'],
                    $productData['jsonData'],
                    $productData['productAssociations'],
                    $createOrderResponse['order_id']
                );
                if ($orderSubmissionResponse['error'] == 1) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ .
                        ' Order Submission failed for the Quote ID: ' . $quoteId . $withCXML .
                        print_r(json_encode($xmlArray), true));
                    $rejectionReason = $orderSubmissionResponse['msg'];
                }
                // @codeCoverageIgnoreStart
                else {
                    $this->reorderInstanceHelper->pushOrderIdInQueue($createOrderResponse['order_id']);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ .
                        ' Order Submission success for the Quote ID: ' . $quoteId . $withCXML .
                        print_r(json_encode($xmlArray), true));

                    $responseCXML = $this->saveExternalOrdId(
                        $quote,
                        $orderSubmissionResponse['order_id'],
                        $createOrderResponse['order_id']
                    );

                    try {
                        $this->_poHelper->changeQuoteStatus($quote);
                        // Push quote id in queue to clean item instance from quote
                        $this->optimizeItemInstanceHelper->pushQuoteIdQueue($quoteId);
                    } catch (Exception$e) {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ .
                            ' Order Status change to Success failed for the Quote ID: ' . $quoteId);
                        $rejectionReason = 'Order Status change to Success failed';
                        $this->sendFailureNotification($quoteId, $rejectionReason);

                    }
                    return $responseCXML;
                    // @codeCoverageIgnoreEnd
                }
            }
        }
        return $this->_poHelper->sendError(__($rejectionReason));
    }
    public function processOrderValidLineItems(
        $validLineItems,
        $quoteId,
        $xmlArray,
        $withCXML,
        $rejectionReason
    ) {
        if ($validLineItems == 0) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ .
                " Database items validation with cXML items is failed for the Quote ID: "
                . $quoteId . $withCXML . print_r(json_encode($xmlArray), true));
                $rejectionReason = 'Items in the Purchase Order do not match items in the Cart';

            $this->sendFailureNotification($quoteId, $rejectionReason);
        }
        return $rejectionReason;
    }
    /**
     * Process Order
     *
     * @param array  $xmlArray | $companyDetails
     * @param  int    $quoteId
     * @param  String $quoteStatus
     *
     * @return mixed
     */
    public function processOrder($xmlArray = [], $quoteId = '', $companyDetails = [], $quoteStatus = '')
    {
        $withCXML = ' with cXML: ';
        $companyId = $companyDetails['company_id'];
        // get quote object
        $quote = $this->_cartRepositoryInterface->get($quoteId);
        $rejectionReason = '';
        $rejectionReason = $this->processOrderQuoteStatusValidate($rejectionReason, $quoteStatus, $quoteId);
        if ($rejectionReason) {
            return $this->_poHelper->sendError($rejectionReason);
        }
        $rejectionReason = '';
        $poNumber = $this->_poHelper->getPoNumber($xmlArray);

        $rejectionReason = $this->processOrderPoNumberLengthValidate(
            $poNumber, $quoteId, $withCXML, $xmlArray, $rejectionReason);

        if ($rejectionReason) {
            return $this->_poHelper->sendError($rejectionReason);
        }

        // D-85695 - Validate shipping information during quote to order conversion
        // B-1299552 - Cleanup Toggle Feature - fix_missing_shipping_info_afterQuoteToOrder
        $this->_poHelper->verifyAndUpdateShippingInfo($quote, 'before verifyShippingDetails');

        // Decide shippment/pickup from cxml, lookup in quote, otherwise throw error
        $shipAddress = $this->verifyShippingDetails($quote, $xmlArray);

        if (isset($shipAddress['error']) && isset($shipAddress['msg']) && $shipAddress['error'] == 1) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $shipAddress['msg']);
            $rejectionReason = $shipAddress['msg'];
            return $this->_poHelper->sendError(__($rejectionReason));
        }
        $pickUpIdLocation = isset($shipAddress['pickupLocationId']) ? $shipAddress['pickupLocationId'] : 0;

        $availableOption = $this->getDelieveryOption($quote, $xmlArray);
        $rejectionReason = '';
        $rejectionReason = $this->processOrderDeliveryOptionValidation(
            $rejectionReason, $availableOption, $quoteId, $withCXML, $xmlArray);

        if ($rejectionReason) {
            return $this->_poHelper->sendError($rejectionReason);
        }
        $rejectionReason = '';
        // @codeCoverageIgnoreStart
        $rejectionReason = $this->processOrderShipingInfoValidation(
            $rejectionReason, $shipAddress, $quoteId, $xmlArray, $withCXML);

        if ($rejectionReason) {
            return $this->_poHelper->sendError($rejectionReason);
        } else {
            $billingAddress = $shippingAddress = $shipAddress['address'];
        }

        $contactDetail = $this->getCustomerContactInfo($quote);
        $rejectionReason = '';
        $rejectionReason = $this->processOrderContactValidate(
            $rejectionReason, $contactDetail, $xmlArray, $quoteId);

        if ($rejectionReason) {
            return $this->_poHelper->sendError($rejectionReason);
        } else {
            $contactDetails = $contactDetail['contact'];
        }
        $dbItemDetails = $this->getDbQuoteDetails($quote);
        $validLineItems = $this->verifyQuoteLineItems($xmlArray, $dbItemDetails);
        $rejectionReason = '';

        $rejectionReason = $this->processOrderValidLineItems(
            $validLineItems,
            $quoteId,
            $xmlArray,
            $withCXML,
            $rejectionReason
        );
        if ($rejectionReason) {
            return $this->_poHelper->sendError(__($rejectionReason));
        }

        $this->logger->info(__METHOD__ . ":" . __LINE__ .
            " Line Items are validated for the Quote ID: " . $quoteId . $withCXML .
            print_r(json_encode($xmlArray), true));

        $rejectionReason = '';
        if (count($dbItemDetails) > 0) {
            return $this->processOrderAfterDbItemValid(
                $dbItemDetails,
                $quote,
                $poNumber,
                $companyDetails,
                $shippingAddress,
                $billingAddress,
                $availableOption,
                $pickUpIdLocation,
                $quoteId,
                $withCXML,
                $xmlArray,
                $contactDetails,
                $companyId
            );

        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Database items does not exist for the Quote ID: ' . $quoteId . $withCXML .
                print_r(json_encode($xmlArray), true));
            $rejectionReason = 'Invalid item(s)';
            $this->sendFailureNotification($quoteId, $rejectionReason);
        }
        if ($rejectionReason) {
            return $this->_poHelper->sendError(__($rejectionReason));
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Prepare Quote for converting into Order
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $poNumber
     * @param array  $companyDetails
     * @param array  $product
     * @param array  $association
     * @param array  $shippingAddress
     * @param array  $billingAddress
     * @param string $pickUpIdLocation
     * @param string|int $companyId
     *
     * @return array
     */
    public function convertQuoteToOrder(
        $quote,
        $poNumber = '',
        $companyDetails = [],
        $product = [],
        $association = [],
        $shippingAddress = [],
        $billingAddress = [],
        $availableOption = [],
        $pickUpIdLocation = '',
        $companyId = ''
    ) {
        $quoteId = $quote->getId();
        $order_id = $quote->getReservedOrderId();
        $orderdata = $this->_order->loadByIncrementId($order_id);
        $externalID = $orderdata->getExtOrderId();
        $real_id = $orderdata->getEntityId();
        $user_id = $quote->getCustomer()->getId();

        $order = $this->getExistingOrderFromQuoteId($quoteId);
        if ($this->punchoutConfig->getMigrateEproNewPlatformOrderCreationToggle($companyId) && $order && $order->getId()){
            $real_id = $order->getId();
        }
        if ($quote->getReservedOrderId() && empty($externalID)) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
                ' Order approval is pending for the Quote ID: ' . $quoteId);
            $locationId = $this->_poHelper->getLocationID($quote);

            $rejectionReason = 'Order approval pending';
            $this->sendFailureNotification($quoteId, $rejectionReason);
            return ['error' => 0, 'msg' => $rejectionReason, 'order_id' => $real_id, 'location_id' => $locationId];
        } else {
            $access_token = $this->_helper->getTazToken();
            $gatewayToken = $this->_helper->getAuthGatewayToken();
            if (!empty($gatewayToken) && $access_token) {
                return $this->_poHelper->adjustQuote(
                    $quote,
                    $poNumber,
                    $user_id,
                    $product,
                    $association,
                    $companyDetails,
                    $shippingAddress,
                    $billingAddress,
                    $availableOption,
                    $gatewayToken,
                    $access_token,
                    'Bearer',
                    $pickUpIdLocation
                );
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' API Token error for the quote ID: ' . $quoteId);
                $rejectionReason = 'Internal Token Error';
                $this->sendFailureNotification($quoteId, $rejectionReason);
                return ['error' => 1, 'msg' => $rejectionReason, 'order_id' => ''];
            }
        }
    }

    public function constructOrderSubmissionAPIRequestStepOne($companyId, $poNumber, $shippingAddress)
    {
        $fedExAccountNumber = null;
        $creditcard = null;
        $invoice = null;
        // B-1250149 : Magento Admin UI changes to group all the Customer account details
        $companyPaymentMethod = $this->companyHelper->getCompanyPaymentMethod($companyId, true);
        $poNumber = $this->filterPoNumber($poNumber);

        if ($companyPaymentMethod == PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS
            || $companyPaymentMethod == FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT) {
            $fedExAccountNumber = $this->companyHelper->getFedexAccountNumber($companyId);
        } else if ($companyPaymentMethod == PaymentAcceptance::LEGECY_FEDEX_ACCOUNT_NUMBER
            || $companyPaymentMethod == FedExAccountOptions::LEGACY_FEDEX_ACCOUNT) {
            // @codeCoverageIgnoreStart
            $fedExAccountNumber = null;
            $creditcard = null;
            $invoice = array('siteProvided' => true);
            // @codeCoverageIgnoreEnd
        } else if ($companyPaymentMethod == PaymentAcceptance::LEGECY_SITE_CREDIT_CARD
            || $companyPaymentMethod == CredtiCardOptions::LEGACY_SITE_CREDIT_CARD) {
            // @codeCoverageIgnoreStart
            $creditcard = array('siteProvided' => true);
            // @codeCoverageIgnoreEnd
        }

        $company = (isset($shippingAddress['companyName']) ? $shippingAddress['companyName'] : '');

        $addressClassification = "HOME";
        if ($company != null && $company != "") {
            $addressClassification = "BUSINESS";
        }

        return [
            'fedExAccountNumber' => $fedExAccountNumber,
            'creditcard' => $creditcard,
            'invoice' => $invoice,
            'company' => $company,
            'addressClassification' => $addressClassification,
            'poNumber' => $poNumber
        ];
    }

    /**
     * Construct Order Submission API
     *
     * @param object $quote
     * @param array  $dbItemDetails
     * @param array  $shippingAddress
     * @param string $poNumber
     * @param array  $contactDetails
     * @param int    $companyId
     * @param array  $availableOption
     * @param int    $quoteId
     * @param String $pickUpIdLocation
     * @param array  $jsonData
     * @param array  $productAssociations
     * @param String $magentoOrderId
     *
     * @return array
     */
    public function constructOrderSubmissionAPIRequest(
        $quote,
        $dbItemDetails = [],
        $shippingAddress = [],
        $poNumber = '',
        $contactDetails = [],
        $companyId = '',
        $availableOption = [],
        $quoteId = '',
        $pickUpIdLocation = '',
        $jsonData = [],
        $productAssociations = [],
        $magentoOrderId = ''
    ) {
        $companySite = $this->getCompanySite($companyId);

        $response = $this->constructOrderSubmissionAPIRequestStepOne($companyId, $poNumber, $shippingAddress);

        $fedExAccountNumber = $response['fedExAccountNumber'];
        $creditcard = $response['creditcard'];
        $invoice = $response['invoice'];
        $addressClassification = $response['addressClassification'];
        if (str_contains(strtolower((string)$availableOption['serviceType']), 'home')) {
            $addressClassification = "HOME";
        }
        $poNumber = $response['poNumber'];

        if ($pickUpIdLocation == 0) {
            $shipAccNo = $quote->getFedexShipAccountNumber() ?? null;
            // B-1004487 | Anuj | RT-ECVS- Send production location Id in Order Submission API
            $productionLocationId = $quote->getProductionLocationId() ?? null;
            $pickUpData = null;
            //Remove extra space and special characters from Cxml PO Number Failure Fix
            // new fix PO Number
            $poNum = $this->removeSpecialChars($poNumber);

            $shippingData = array(
                'address' => array(
                    'streetLines' => $shippingAddress['street'],
                    'city' => $shippingAddress['city'],
                    'stateOrProvinceCode' => $shippingAddress['region_code'],
                    'postalCode' => $shippingAddress['postcode'],
                    'countryCode' => $shippingAddress['countryCode'],
                    'addressClassification' => $addressClassification,
                ),
                'holdUntilDate' => null,
                'productionLocationId' => $productionLocationId,
                'serviceType' => $availableOption['serviceType'],
                'fedExAccountNumber' => $shipAccNo,
                'deliveryInstructions' => null,
                'poNumber' => $poNum,
            );
        } else {
            $pickUpData = array('location' => array(
                'id' => $pickUpIdLocation, //id
            ),
                'requestedPickupLocalTime' => null,
            );

            $shippingData = null;
        }
        $arrayRequest = [
            'orderSubmissionRequest' => [
                'fedExAccountNumber' => $fedExAccountNumber,
                'site' => $companySite,
                'products' => $jsonData,
                'recipients' => [
                    0 => [
                        'reference' => $quoteId,
                        'contact' => [
                            'contactId' => null,
                            'personName' => [
                                'firstName' => $shippingAddress['firstname'] ?
                                substr($shippingAddress['firstname'], 0, 30) :
                                substr(trim($contactDetails['fname']), 0, 30),
                                'lastName' => $shippingAddress['lastname'] ?
                                substr($shippingAddress['lastname'], 0, 30) :
                                substr(trim($contactDetails['lname']), 0, 30),
                            ],
                            'company' => [
                                'name' => (isset($shippingAddress['companyName']) &&
                                    !empty($shippingAddress['companyName'])) ?
                                $shippingAddress['companyName'] : null,
                            ],
                            'emailDetail' => [
                                'emailAddress' => $shippingAddress['email'] ?
                                $shippingAddress['email'] : $contactDetails['email'],
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => $shippingAddress['telephone'] ?
                                        $shippingAddress['telephone'] : $contactDetails['contact_number'],
                                        'extension' => $shippingAddress['phoneNumberExt'] ?
                                        $shippingAddress['phoneNumberExt'] : $contactDetails['contact_ext'],
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                        'attention' => null,
                        'pickUpDelivery' => $pickUpData,
                        'shipmentDelivery' => $shippingData,
                        'productAssociations' => $productAssociations,
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
                'orderContact' => [
                    'contact' => [
                        'contactId' => null,
                        'personName' => [
                            /* D-93054 */
                            'firstName' => substr(trim($contactDetails['fname']), 0, 30),
                            'lastName' => substr(trim($contactDetails['lname']), 0, 30),
                        ],
                        'company' => [
                            'name' => (isset($shippingAddress['companyName']) &&
                                !empty($shippingAddress['companyName'])) ?
                            $shippingAddress['companyName'] : null,
                        ],
                        'emailDetail' => [
                            'emailAddress' => $contactDetails['email'],
                        ],
                        'phoneNumberDetails' => [
                            0 => [
                                'phoneNumber' => [
                                    'number' => $contactDetails['contact_number'],
                                    'extension' => $contactDetails['contact_ext'],
                                ],
                                'usage' => 'PRIMARY',
                            ],
                        ],
                    ],
                    'attention' => null,
                ],
                'payments' => [
                    0 => [
                        'payAtLocation' => false,
                        'poNumber' => $poNumber,
                        'billingFields' => [
                            0 => [
                                'fieldName' => null,
                                'value' => null,
                            ],
                        ],
                        'invoice' => $invoice,
                        'creditCard' => $creditcard,
                    ],
                ],
                'notificationRegistration' => [
                    'webhook' => [
                        'url' => $this->_baseUrl . "rest/V1/orders/" . $magentoOrderId . "/status",
                        'auth' => null,
                    ],
                ],
            ],
        ];

        return $this->callOrderSubmissionApi($quoteId, $arrayRequest, $jsonData, $productAssociations);
    }

    /**
     * Remove special characters from Po Number
     * @param string $poNum
     * @return string|null
     */
    public function removeSpecialChars($poNum) {
        if ($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_SPECIALS_CHARS_REMOVE_TOGGLE) &&
         isset($poNum) &&
         !empty($poNum)
        ) {
            return preg_replace('/[^a-zA-Z0-9\s]/', '', $poNum);;
        }

        return null;

    }

    /**
     * Call Order Submission API
     *
     * @param int $quoteId
     * @param array $arrayRequest
     * @param array $jsonData
     * @param array $productAssociations
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function callOrderSubmissionApi($quoteId, $arrayRequest = [], $jsonData = [], $productAssociations = [])
    {
        $quotestring = ' Quote Id: ';
        $data_string = json_encode($arrayRequest, true);
        $gatewayToken = $this->_helper->getAuthGatewayToken();
        $tazToken = $this->_helper->getTazToken();
        $returnData = [];
        if (!empty($gatewayToken) && $tazToken) {
            $setupURL = $this->getOrderApiUrl();
            $authHeaderVal = $this->headerData->getAuthHeaderValue();
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Content-Length: " . strlen($data_string),
                "Cookie: Bearer=" . $tazToken,
                $authHeaderVal . $gatewayToken,
            ];
            $ch = curl_init($setupURL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            if (!$this->isJson($response)) {
                $rejectionReason = "Order submission failure";
                $this->sendFailureNotification($quoteId, $rejectionReason);
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    $quotestring . $quoteId . ' ' . $rejectionReason);
                return ['error' => 1, 'msg' => $rejectionReason, 'order_id' => '',
                    'product' => '', 'association' => ''];
            }
            $array_data = json_decode($response, true);
            if (isset($array_data['errors'])) {
                $this->logger->info(__METHOD__ . ":" . __LINE__ . ' Order Submission API Request:');
                $this->logger->error(__METHOD__ . ":" . __LINE__ . " " . $data_string);
                $this->logger->info(__METHOD__ . ":" . __LINE__ . ' Order Submission API response:');
                $this->logger->error(__METHOD__ . ":" . __LINE__ . " " . $response);
            }
            $returnData = $this->callOrderSubmissionApiComplexity(
                $response,
                $quoteId,
                $ch,
                $quotestring,
                $productAssociations,
                $jsonData
            );
            return $returnData;
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Order Submission API Error: Internal Token Error');
            $rejectionReason = 'Internal Token Error';
            $this->sendFailureNotification($quoteId, $rejectionReason);
            $returnData = ['error' => 1, 'msg' => $rejectionReason, 'order_id' => '',
                'product' => '', 'association' => ''];
        }

        return $returnData;

    }

    /**
     * Summary of callOrderSubmissionApiComplexity
     * @param mixed $response
     * @param mixed $quoteId
     * @param mixed $ch
     * @param mixed $quotestring
     * @param mixed $productAssociations
     * @param mixed $jsonData
     * @return array
     * @codeCoverageIgnore
     */
    public function callOrderSubmissionApiComplexity(
        $response,
        $quoteId,
        $ch,
        $quotestring,
        $productAssociations,
        $jsonData
    ) {
        $returnData = [];
        if ($response === false) {
            $rejectionReason = curl_error($ch);
            $this->sendFailureNotification($quoteId, $rejectionReason);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $rejectionReason);
            $returnData = ['error' => 1, 'msg' => $rejectionReason, 'order_id' => '',
                'product' => '', 'association' => ''];
        } else {
            $result = curl_getinfo($ch);
            curl_close($ch);
            if ($result['http_code'] == 200 || $result['http_code'] == 201) {
                $array_data = json_decode($response, true);
                $extOrderNumber = $this->getExternalOrderId($array_data);
                if (empty($extOrderNumber)) {
                    $rejectionReason = 'Order Submission ID Missing';
                    $this->sendFailureNotification($quoteId, $rejectionReason);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ .
                        $quotestring . $quoteId . ' ' . $rejectionReason);
                    $returnData = ['error' => 1, 'msg' => $rejectionReason, 'order_id' => '',
                        'product' => '', 'association' => ''];
                }
                if (empty($returnData)) {
                    $returnData = ['error' => 0, 'msg' => 'success',
                        'order_id' => $extOrderNumber, 'product' => $jsonData,
                        'association' => $productAssociations];
                }

            } else {
                $array_data = json_decode($response);

                $msg = '';
                foreach ($array_data->errors as $error) {
                    $msg .= $error->message . '. ';
                }
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Order Submission API Error: ' . $msg);
                $rejectionReason = $msg;
                $this->sendFailureNotification($quoteId, $rejectionReason);
                $returnData = ['error' => 1, 'msg' => $msg, 'order_id' => '', 'product' => '', 'association' => ''];
            }
        }
        return $returnData;
    }

    /**
     * Get External Order Number
     *
     * @param array $array_data
     *
     * @return string $res
     */
    public function getExternalOrderId($array_data = [])
    {
        if (isset($array_data['transactionId']) && isset($array_data['output']['orderSubmission']['orderNumber'])) {
            $res = $array_data['output']['orderSubmission']['orderNumber'];
        }
        return $res;
    }

    /**
     * Get existing quote item detail saved in DB corresponding to a quote.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     *  @codeCoverageIgnore
     * @return array $itemsArray
     */
    public function getDbQuoteDetails($quote)
    {
        $savedDbItems = $itemsArray = [];

        if($this->productBundleConfig->isTigerE468338ToggleEnabled() && !$quote->getIsEproQuote()) {
            $items = $quote->getAllItems();
        } else {
            $items = $quote->getItemsCollection(false);
        }

        $itemCount = 0;
        foreach ($items as $item) {
            if ($this->productBundleConfig->isTigerE468338ToggleEnabled()
                && $item->getProductType() == Type::TYPE_BUNDLE) {
                continue;
            }
            $itemOption = $item->getOptionByCode('info_buyRequest')->getValue();
            $optionData = json_decode((string) $itemOption, true);
            $ext_prod = $optionData['external_prod'][0] ?? [];
            $savedDbItems['productId'] = $item->getProductId();
            $savedDbItems['itemId'] = $item->getId();
            $savedDbItems['storeId'] = $item->getStoreId();
            $savedDbItems['sku'] = $item->getSku();
            $savedDbItems['name'] = $item->getName();
            $savedDbItems['qty'] = (int) ($item->getQty());
            $savedDbItems['unitPrice'] = $item->getPrice();
            $savedDbItems['subTotal'] = $item->getRowTotalInclTax();

            $product_json = $ext_prod;
            if (isset($ext_prod['catalogReference'])) {
                $product_json['catalogReference'] = $ext_prod['catalogReference'];
            }
            if (isset($product_json['preview_url'])) {
                unset($product_json['preview_url']);
            }
            if (isset($product_json['fxo_product'])) {
                unset($product_json['fxo_product']);
            }

            $product_json['instanceId'] = $item->getId();

            $pqty = (int) ($item['qty']);
            $product_json['qty'] = "$pqty";
            $savedDbItems['external_product'] = $product_json;
            $itemsArray[] = $savedDbItems;
            $itemCount++;
        }
        if (!empty($itemsArray)) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
                ' Quote items are available in database for the Quote ID: ' . $quote->getId());
        }
        return $itemsArray;
    }

    /**
     * Verify shipping detail.
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param array $poXml
     *
     * @return array
     */
    public function verifyShippingDetails($quote, $poXml = [])
    {
        $cxmAddress = [];
        $available = 0;
        $pickLocationId = 0;
        $shippingAddress = array();
        if (!empty($poXml)) {
            foreach ($poXml as $key => $val) {
                if ($key == 'Request') {
                    foreach ($val['OrderRequest'] as $rt => $rd) {
                        if ((isset($rd['ShipTo']['Address']))) {
                            $available = 1;
                            foreach ($rd['ShipTo'] as $ak => $ad) {
                                if (!empty($ad)) {
                                    $details = $this->getSavedShippingAddress($quote);
                                    $shippingAddress['shipping_method'] = $details['address']['shipping_method'] ?? '';
                                    $shippingAddress['region'] = $shippingAddress['region_code'] =
                                    $shippingAddress['country_id'] = "";
                                    if (is_array($ad['PostalAddress']['Street'])) {
                                        foreach ($ad['PostalAddress']['Street'] as $street) {
                                            if (!empty($street)) {
                                                $shippingAddress['street'][] = $street;
                                            }
                                        }
                                    } else {
                                        $shippingAddress['street'] = array(0 => $ad['PostalAddress']['Street']);
                                    }
                                    if (!is_array($ad['PostalAddress']['PostalCode']) &&
                                        !empty($ad['PostalAddress']['PostalCode'])) {
                                        $shippingAddress['postcode'] = $ad['PostalAddress']['PostalCode'];
                                    } else {
                                        $shippingAddress['postcode'] = $details['address']['postcode'];
                                    }
                                    if (!is_array($ad['PostalAddress']['City']) &&
                                        !empty($ad['PostalAddress']['City'])) {
                                        $shippingAddress['city'] = $ad['PostalAddress']['City'];
                                    } else {
                                        $shippingAddress['city'] = $details['address']['city'];
                                    }
                                    if (!is_array($ad['PostalAddress']['State']) &&
                                        !empty($ad['PostalAddress']['State'])) {
                                        $shippingAddress['region_code'] = $ad['PostalAddress']['State'];
                                        $regionCode = $ad['PostalAddress']['State'];
                                    } else {
                                        $shippingAddress['region_code'] = $details['address']['region_code'];
                                        $regionCode = $details['address']['region_code'];
                                    }

                                    $countryCode = 'US'; // Since all orders would be accepted for US Country Only.
                                    $regionId = $this->_region->loadByCode($regionCode, $countryCode)->getId();
                                    // For region code passed as full state name
                                    if (!isset($regionId)) {
                                        $state = ucwords(strtolower(trim($ad['PostalAddress']['State'])));
                                        $state = strtolower(trim($ad['PostalAddress']['State']));
                                        $regionId = $this->_region->loadByName($state, $countryCode)->getId();

                                        if (!isset($regionId)) {
                                            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Order Submission invalid state code');
                                            return ['error' => 1, 'msg' => "Invalid state code"];
                                        }
                                    }
                                    $shippingAddress['region_id'] = $regionId;
                                    $shippingAddress['country'] = $ad['PostalAddress']['Country'];
                                    $shippingAddress['countryCode'] = $countryCode;
                                    $shippingAddress['companyName'] = $ad['Name'];
                                    // D-75302: Check if DeliverTo tag exists and if its not blank
                                    if (isset($ad['PostalAddress']['DeliverTo']) &&
                                        !empty($ad['PostalAddress']['DeliverTo'])) {
                                        if (is_array($ad['PostalAddress']['DeliverTo'])) {
                                            $name = preg_replace('!\s+!', ' ', $ad['PostalAddress']['DeliverTo'][0]);
                                        } else {
                                            $name = preg_replace('!\s+!', ' ', $ad['PostalAddress']['DeliverTo']);
                                        }
                                    } else { //Get name from Address tag if DeliverTo is not available
                                        $name = $ad['Name'];
                                    }

                                        $getNameFromXML = $this->_poHelper->getNameFromXML($name);
                                        $shippingAddress['firstname'] = trim($getNameFromXML['firstname']);
                                        $shippingAddress['lastname'] = trim($getNameFromXML['lastname']);

                                    /* D-93054 B-1857860*/
                                    if ((strlen($shippingAddress['firstname']) < 2 ||
                                            strlen($shippingAddress['lastname']) < 2)) {
                                        $this->logger->info(__METHOD__ . ':' . __LINE__ .
                                            ' First/Last Name has less than 2 characters.');
                                        return ['error' => 1, 'msg' => "First/Last Name must" .
                                            " have atleast 2 characters."];
                                    }

                                    if (!is_array($ad['Email']) && !empty($ad['Email'])) {
                                        $shippingAddress['email'] = $ad['Email'];
                                    } else { // get from saved quote
                                        if (!empty($details['address']['email'])) {
                                            $shippingAddress['email'] = $details['address']['email'];
                                        } else { // get from contact detail
                                            $contDetails = $this->getCustomerContactInfo($quote);
                                            $shippingAddress['email'] = $contDetails['contact']['email'];
                                        }
                                    }
                                    if (!is_array($ad['Phone']['TelephoneNumber']['Number']) &&
                                        !empty($ad['Phone']['TelephoneNumber']['Number'])) {
                                        $phoneNumber = preg_replace("/[^0-9]/", "",
                                            $ad['Phone']['TelephoneNumber']['Number']);
                                        $areaCode = '';
                                        if (!is_array($ad['Phone']['TelephoneNumber']['AreaOrCityCode']) &&
                                            !empty($ad['Phone']['TelephoneNumber']['AreaOrCityCode'])) {
                                            $areaCode = preg_replace("/[^0-9]/", "",
                                                $ad['Phone']['TelephoneNumber']['AreaOrCityCode']);
                                        }
                                        if (strlen($phoneNumber) == 10) {
                                            $shippingAddress['telephone'] = $phoneNumber;
                                        } else {
                                            $shippingAddress['telephone'] = $areaCode . '' . $phoneNumber;
                                        }
                                        $shippingAddress['phoneNumberExt'] = "";
                                    } else {
                                        // Get Phone details and email from quote details
                                        if (!empty($details['address']['telephone'])) {
                                            $shippingAddress['telephone'] = $details['address']['telephone'];
                                            $shippingAddress['phoneNumberExt'] = $details['address']['phoneNumberExt'];
                                        } else {
                                            // Get Phone details from Saved contact details
                                            $contDetails = $this->getCustomerContactInfo($quote);
                                            $shippingAddress['telephone'] = $contDetails['contact']['contact_number'];
                                            $shippingAddress['phoneNumberExt'] = $contDetails['contact']['contact_ext'];
                                        }
                                    }
                                }
                            }
                            $cxmAddress = ['available' => $available,
                                'address' => $shippingAddress, 'pickupLocationId' => $pickLocationId];
                        }
                    }
                }
            }
        }

        // check recipient_address_from_po for this company
        $quoteCustomerId = $quote->getCustomerId();
        $quoteId = $quote->getId();
        $recipientAddressFromPo = 0;

        $quoteAddress = $this->getSavedShippingAddress($quote);

        return $this->verifyShippingDetailsReduceReturn(
            $quoteCustomerId,
            $quoteAddress,
            $available,
            $cxmAddress,
            $quoteId,
            $recipientAddressFromPo
        );
    }

    public function eProBillingNameFilter($name, $shippingAddress)
    {
        $name = preg_replace("/[^a-zA-Z0-9]+/", " ", trim($name));
        $name = trim($name);

        $nameArray = explode(" ", $name);
        $lname = end($nameArray);
        array_pop($nameArray);
        $fname = implode(" ", $nameArray);
        if ($fname == "") {
            $fname = $lname;
        }
        $shippingAddress['firstname'] = trim($fname);
        $shippingAddress['lastname'] = trim($lname);
        return $shippingAddress;
    }

    public function verifyShippingDetailsReduceReturn(
        $quoteCustomerId,
        $quoteAddress,
        $available,
        $cxmAddress,
        $quoteId,
        $recipientAddressFromPo
    ) {
        if ($quoteCustomerId) {
            $companyObj = $this->_companyMgmtRepository->getByCustomerId($quoteCustomerId);
            if ($companyObj && $companyObj->getId()) {
                $recipientAddressFromPo = $companyObj->getData('recipient_address_from_po');

                // If $recipientAddressFromPo is true, and
                //ship to address is not available, decline the order
                if ($quoteAddress['pickupLocationId'] == 0 && $available != 1 && $recipientAddressFromPo == 1) {
                    $rejectionReason = 'Order is declined due to missing shipping address.';
                    $this->sendFailureNotification($quoteId, $rejectionReason);
                    $this->logger->error(__METHOD__ . ":" . __LINE__ .
                        " Quote Id: " . $quoteId . " " . $rejectionReason);
                    return ['error' => 1, 'msg' => $rejectionReason];
                }
            }
        }

        // In case of available shipping address in cXML and shipping address from PO is set in admin
        if ($quoteAddress['pickupLocationId'] == 0 && $available && $recipientAddressFromPo == 1) {
            return $cxmAddress;
        } else {
            return $quoteAddress;
        }
    }

    /**
     * Check if negotiable Quote exist.
     *
     * @param array $poXml
     *
     * @return array
     */
    public function verifyQuoteDetails($poXml = [])
    {
        try {
            $quoteId = (int) $poXml['Request']['OrderRequest']['OrderRequestHeader']
                ['SupplierOrderInfo'][self::ATTRIBUTE]['orderID'];

            try {
                /** Condition for check quote already have an order with valid GTN */
                if ($this->verifyOrderExist($quoteId)) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' This Quote already approved Quote ID : ' . $quoteId);
                    return ['available' => 0, 'quote_id' => '', 'status' => '', 'message' => 'Order exist with current quote. Please try another quote.'];
                }
                $negotiableQuote = $this->_negotiableQuoteRepository->getById($quoteId);
                $negotiableQuoteId = $negotiableQuote->getQuoteID();
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Negotiable Quote details was verified
                successfully for the Quote ID: ' . $quoteId);
            } catch (Exception$e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Negotiable quote was not found for the quote id : '
                    . $quoteId . 'Exception: ' . $e->getMessage());
                $rejectionReason = 'Negotiable quote was not found.';
                $this->sendFailureNotification($quoteId, $rejectionReason);
                return ['available' => 0, 'quote_id' => '', 'status' => ''];
            }

            return ['available' => 1, 'quote_id' => $negotiableQuoteId, 'status' => $negotiableQuote->getStatus()];
        } catch (Exception$e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Error in retrieving quote id from order cXML : ' .
                json_encode($poXml, true) . ' Error: ' . $e->getMessage());
            return ['available' => 0, 'quote_id' => '', 'status' => ''];
        }
    }

    /**
     * Verify XML Line Items with DB Items
     *
     * @params Array $poXml|$dbItemDetails
     *
     * @return boolean 0|1
     */
    public function verifyQuoteLineItems($poXml = [], $dbItemDetails = [])
    {
        $cartItemId = [];
        $validItems = 0;

        $productIds = []; // to be removed

        $itemsQty = [];
        $productQty = []; // to be removed

        $dbItemCount = 0;
        $cxmlLineItemCount = 0;

        foreach ($dbItemDetails as $item) {
            $dbItemCount++;

            $itemId = $item['itemId'];
            $cartItemId[] = $itemId;
            $itemsQty[$itemId] = $item['qty'];

            // to be removed
            $productId = $item['productId'];
            $productIds[] = $productId;
            $productQty[$productId] = $item['qty'];
        }

        if (!empty($poXml)) {
            if (isset($poXml['Request']['OrderRequest']['ItemOut'][0])) {
                $cxmlLineItemCount = count($poXml['Request']['OrderRequest']['ItemOut']);
            } else if (isset($poXml['Request']['OrderRequest']['ItemOut'])) {
                $cxmlLineItemCount = 1;
            }
        }

        if ($dbItemCount != $cxmlLineItemCount) {
            return $validItems;
        }


        $matchedCounter = 0;
        $receivedCounter = 0;
        if (!empty($poXml)) {
            foreach ($poXml['Request']['OrderRequest'] as $key => $val) {
                $responseData = $this->verifyQuoteLineItemsComplexity(
                    $key,
                    $val,
                    $cartItemId,
                    $productIds,
                    $itemsQty,
                    $matchedCounter,
                    $receivedCounter
                );
                $receivedCounter = $responseData['receivedCounter'];
                $matchedCounter = $responseData['matchedCounter'];
            }
        }
        if ($receivedCounter == $matchedCounter) {
            $validItems = 1;
        }
        return $validItems;
    }

    public function verifyQuoteLineItemsComplexity(
        $key,
        $val,
        $cartItemId,
        $productIds, $itemsQty,
        $matchedCounter,
        $receivedCounter
    ) {
        $responseData['receivedCounter'] = $receivedCounter;
        $responseData['matchedCounter'] = $matchedCounter;
        if ($key == 'ItemOut') {
            if (isset($val[self::ATTRIBUTE]) && isset($val['ItemID'])) {
                $responseData = $this->verifyQuoteLineItemsComplexityIf(
                    $val,
                    $cartItemId,
                    $itemsQty,
                    $productIds,
                    $matchedCounter,
                    $receivedCounter
                );
            } else {
                $responseData = $this->verifyQuoteLineItemsComplexityElse(
                    $val,
                    $cartItemId,
                    $itemsQty,
                    $productIds,
                    $matchedCounter,
                    $receivedCounter
                );
            }
        }
        return $responseData;
    }

    public function verifyQuoteLineItemsComplexityIf(
        $val,
        $cartItemId,
        $itemsQty,
        $productIds,
        $matchedCounter,
        $receivedCounter
    ) {
        $qty = isset($val[self::ATTRIBUTE]['quantity']) ? $val[self::ATTRIBUTE]['quantity'] : '';
        $itemId = isset($val['ItemID']['SupplierPartAuxiliaryID']) ?
        $val['ItemID']['SupplierPartAuxiliaryID'] : '';
        $productId = isset($val['ItemID']['SupplierPartID']) ?
        $val['ItemID']['SupplierPartID'] : ''; // to be removed
        if ((in_array($itemId, $cartItemId) && (!empty($qty) &&
            ($qty == $itemsQty[$itemId]))) || (in_array($productId, $productIds) && !empty($qty))) {
            $matchedCounter++;
        }
        $receivedCounter++;
        $returnData['receivedCounter'] = $receivedCounter;
        $returnData['matchedCounter'] = $matchedCounter;
        return $returnData;
    }

    public function verifyQuoteLineItemsComplexityElse(
        $val,
        $cartItemId,
        $itemsQty,
        $productIds,
        $matchedCounter,
        $receivedCounter
    ) {
        for ($i = 0; $i < count($val); $i++) {
            $qty = isset($val[$i][self::ATTRIBUTE]['quantity']) ?
            $val[$i][self::ATTRIBUTE]['quantity'] : '';

            $itemId = isset($val[$i]['ItemID']['SupplierPartAuxiliaryID']) ?
            $val[$i]['ItemID']['SupplierPartAuxiliaryID'] : '';
            $productId = isset($val[$i]['ItemID']['SupplierPartID']) ?
            $val[$i]['ItemID']['SupplierPartID'] : ''; // to be removed
            if ((in_array($itemId, $cartItemId) &&
                (!empty($qty) && ($qty == $itemsQty[$itemId]))) ||
                (in_array($productId, $productIds) && !empty($qty))) {
                $matchedCounter++;
            }
            $receivedCounter++;
        }
        $returnData['receivedCounter'] = $receivedCounter;
        $returnData['matchedCounter'] = $matchedCounter;
        return $returnData;
    }

    /**
     * Get Order Submission API URL
     *
     * @param none
     *
     * @return string
     */
    public function getOrderApiUrl()
    {
        $orderApiUrl = $this->_configInterface->getValue("fedex/general/order_api_url");
        return $orderApiUrl;
    }

    /**
     * Get Assigned Company
     *
     * @param int $companyId
     *
     * @return ObjectArray
     */
    public function getAssignedCompany($companyId = '')
    {
        return $this->_companyRepository->get((int) $companyId);
    }

    /**
     * Get Company Site Name
     *
     * @param int $companyId
     *
     * @return null|string
     */
    public function getCompanySite($companyId = '')
    {
        $company = $this->getAssignedCompany($companyId);
        if ($company->getSiteName() != '') {
            return $company->getSiteName();
        } else {
            return null;
        }
    }

    /**
     * Get Customer Placed Quote Address Detail
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     *
     * @return array
     */
    public function getSavedShippingAddress($quote)
    {
        // Get Only shipping address from saved quote.
        $quoteId = $quote->getId();
        $addressData = $quote->getShippingAddress();
        $shippingAddress = [];
        $available = 0;
        if ($addressData['shipping_method'] == 'fedexshipping_PICKUP') {
            $pickLocationId = $addressData['shipping_description'];
        } else {
            $pickLocationId = 0;
        }
        // If shipping method doesn't exist log the entry
        if (empty($addressData['shipping_method'])) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Shipping Method in DB for the Quote ID: '
                . $quoteId . ' Does not exist');
            $rejectionReason = "Shipping Method Doesn't exist";
            $this->sendFailureNotification($quoteId, $rejectionReason);
        }
        if (!empty($addressData['street'])) {
            $available = 1;
            $shipperRegion = $this->_regionFactory->create()->load($addressData['region_id']);
            $region = $shipperRegion->getCode();
            $shippingAddress['street'] = array(0 => $addressData['street']);
            $shippingAddress['postcode'] = $addressData['postcode'];
            $shippingAddress['city'] = $addressData['city'];
            $shippingAddress['region_code'] = (isset($region)) ? $region : '';
            $shippingAddress['region_id'] = $addressData['region_id'];
            $shippingAddress['region'] = $shipperRegion->getDefaultName();
            $shippingAddress['country'] = $addressData['country_id'];
            $shippingAddress['countryCode'] = $addressData['country_id'];
            $shippingAddress['companyName'] = $addressData['company'];
            $shippingAddress['firstname'] = $addressData['firstname'];
            $shippingAddress['lastname'] = $addressData['lastname'];
            $shippingAddress['email'] = $addressData['email'];
            $shippingAddress['telephone'] = $addressData['telephone'];
            $shippingAddress['phoneNumberExt'] = "";
            $shippingAddress['shipping_method'] = $addressData['shipping_method'];
        }
        return ['available' => $available, 'address' => $shippingAddress, 'pickupLocationId' => $pickLocationId];
    }

    /**
     * Get customer contact detail.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param Bool $available
     *  @codeCoverageIgnore
     * return array $customerData
     */
    public function getCustomerContactInfo($quote, $available = 0)
    {
        $customerData = [];
        $customerId = $quote->getCustomer()->getId();
        $customer = $this->_customerRepositoryInterface->getById($customerId);

        if (!$available) {
            $available = 1;
            $customerData['fname'] = $customer->getFirstname();
            $customerData['lname'] = $customer->getLastname();
            $customerData['email'] = $customer->getEmail();
            $customerData['contact_number'] = !empty($customer->getCustomAttribute('contact_number')) ?
            $customer->getCustomAttribute('contact_number')->getValue() : '';
            $customerData['contact_ext'] = !empty($customer->getCustomAttribute('contact_ext')) ?
            $customer->getCustomAttribute('contact_ext')->getValue() : '';
            /* D-93054 B-1857860 */
            if ((strlen($customerData['fname']) < 2 || strlen($customerData['lname']) < 2)) {
                return ['available' => 3, 'contact' => $customerData,
                    'msg' => "First/Last Name for contact must have atleast 2 characters."];
            }
        }
        return ['available' => $available, 'contact' => $customerData];
    }

    /**
     * Get shipping option
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param array $poXml
     *
     * @return array
     */
    public function getDelieveryOption($quote, $poXml = [])
    {
        $cxmlMethod = '';

        $method = $quote->getShippingAddress()->getShippingMethod();
        $method = ltrim($method, 'fedexshipping_');

        if (empty($method)) {
            return ['available' => 0, 'serviceType' => ''];
        } else {
            return ['available' => 1, 'serviceType' => $method, 'reload' => 0];
        }
    }

    /**
     * Save External Order Reference ID
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $extOrderNo
     * @param Int    $order_id
     *
     * @return string
     */
    public function saveExternalOrdId($quote, $extOrderNo = '', $order_id = '', $paymentMethod = false, $companyId = '')
    {
        $orderNumber = $quote->getReservedOrderId();
        $quoteId = $quote->getId();
        try {
            $orderdata = $this->_order->load($order_id);
            $orderdata->setExtOrderId($extOrderNo);
            /* B-1261294 */
            $orderdata->setIncrementId($extOrderNo);
            if ($this->punchoutConfig->getMigrateEproNewPlatformOrderCreationToggle($companyId)) {
                if ($paymentMethod == 'fedex') {
                    $orderdata->getPayment()->setMethod('fedexaccount');
                } elseif ($paymentMethod == 'cc') {
                    $orderdata->getPayment()->setMethod('fedexccpay');

                }
            }
            $orderdata->save();
        } catch (Exception$e) {
            $rejectionReason = $e->getMessage();
            $this->sendFailureNotification($quoteId, $rejectionReason);
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $rejectionReason);
            return $this->_poHelper->sendError($e->getMessage());
        }
        return $this->_poHelper->sendSuccess($extOrderNo);
    }

    /**
     * Validate the required shipping Information.
     *
     * @param array $shippingAddress
     *
     * @return array
     */
    public function validateRequiredShippingInfo($shippingAddress = [])
    {
        $flag = 1;
        $msg = '';
        if (empty($shippingAddress['telephone'])) {
            $flag = 0;
            $msg = "Invalid/Missing telephone number";
        } else if (empty($shippingAddress['region_code'])) {
            $flag = 0;
            $msg = "Missing region code";
        } else if (empty($shippingAddress['postcode'])) {
            $flag = 0;
            $msg = "Missing postcode";
        } else if (empty($shippingAddress['countryCode'])) {
            $flag = 0;
            $msg = "Missing Country Code";
        }
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $msg);
        return ['available' => $flag, 'msg' => $msg];
    }

    /**
     * Validate the required contact Information
     *
     * @param array $contact
     *
     * @return array
     */
    public function validateRequiredContactInfo($contact = [])
    {
        $flag = 1;
        $msg = '';
        if (empty($contact['fname'])) {
            $flag = 0;
            $msg = "Missing firstname";
        } else if (empty($contact['lname'])) {
            $flag = 0;
            $msg = "Missing lastname";
        } else if (empty($contact['email'])) {
            $flag = 0;
            $msg = "Missing contact email";
        }
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $msg);
        return ['available' => $flag, 'msg' => $msg];
    }

    /**
     * Prepare Product Data from DB Items Array
     *
     * @param $dbItemDetails
     *
     * @return array
     */
    public function getProductData($dbItemDetails = [])
    {
        $jsonData = array();
        $productAssociations = [];
        $i = 0;
        if ($dbItemDetails) {
            foreach ($dbItemDetails as $item) {
                $jsonData[] = $item['external_product'];
                $instanceId = isset($item['external_product']['instanceId']) ? $item['external_product']['instanceId'] : '';
                if($instanceId ){
                    $productAssociations[] = ['id' => $instanceId, 'quantity' => (int) $item['qty']];
                }
                else{
                    $productAssociations[] = ['id' => $i, 'quantity' => (int) $item['qty']];
                }

                $i++;
            }
        }
        return ['jsonData' => $jsonData, 'productAssociations' => $productAssociations];
    }

    /**
     * Update snapshot in negotiable quote
     *
     * D-85695 - Validate shipping information during quote to order conversion
     * @param int $quoteId
     */
    public function validateSnapshot($quoteId)
    {
        if (!empty($quoteId)) {
            $orderObj = $this->_order->getCollection()->addFieldToFilter('quote_id', $quoteId)->getFirstItem();
            if (!$orderObj->getId()) {
                $this->_poHelper->updateSnapshotForQuote($quoteId);
            }
        }
    }

    public function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * filterPoNumber
     *
     * @param string $poNumber
     * @return string
     */
    public function filterPoNumber($poNumber)
    {
        $poNumber = preg_replace("/[^a-zA-Z0-9.\-]+/", "", trim($poNumber));
        return $poNumber;
    }

    /**
     * @param Quote $quote
     * @return string|null
     */
    public function getQuotePickupLocalTime($quote)
    {
        if($quote && $quote->getEstimatedPickupTime()) {
            $dateTime = date_create_from_format('l,F jS \A\t h:i A', $quote->getEstimatedPickupTime());
            if($dateTime) {
                return $dateTime->format('Y-m-d\TH:i:s');
            }
        }

        return  null;
    }

    /**
     * @param int $quoteId
     * @return int
     */
    public function verifyOrderExist($quoteId)
    {
        return $this->_order->getCollection()->addFieldToFilter('quote_id', $quoteId)->addFieldToFilter('ext_order_id', ['neq' => 'NULL'])->count();
    }

    /**
     * @param int $quoteId
     * @return Order
     */
    public function getExistingOrderFromQuoteId($quoteId)
    {
        return $this->_order->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('ext_order_id', ['null' => true])
            ->getFirstItem();
    }

    private function createDataObject(
        $companyId,
        $quote,
        $shippingAddress,
        $pickUpIdLocation,
        $poNumber,
        $orderNumber,
        $shipmentId
    ) {
        $streetAddress = (array)$shippingAddress['street'];
        if (isset($streetAddress[0])) {
            $streetAddress = explode(PHP_EOL, $streetAddress[0]);
        }

        $shipperRegion = null;
        if (isset($shippingAddress['region_id'])) {
            $shipperRegion = $this->_regionFactory->create()->load($shippingAddress['region_id']);
        }

        $shipMethod = $shippingAddress['shipping_method'];
        $array = explode('_', $shipMethod, 2);
        $shipMethod = "";
        if (!empty($array[1])) {
            $shipMethod = $array[1];
        }

        $estimatePickupTime = $quote->getData('estimate_pickup_time');
        $fedExAccountNumber = $this->companyHelper->getFedexAccountNumber($companyId);

        if (!(bool)$pickUpIdLocation && $this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix')) {
            $fedexShipAccountNumber = !empty($quote->getData('fedex_ship_account_number')) ? $quote->getData('fedex_ship_account_number') : $this->companyHelper->getFedexShippingAccountNumber($companyId);
            $addressClassification = "BUSINESS";
            $isResidenceShipping = $quote->getShippingAddress()->getData('is_residence_shipping');
            if ($isResidenceShipping) {
                $addressClassification = "HOME";
            }
        } else {
            $fedexShipAccountNumber = $this->companyHelper->getFedexShippingAccountNumber($companyId);
            $addressClassification = !(bool)$pickUpIdLocation ? SubmitOrderModelAPI::BUSINESS : '';
        }

        $isAlternate = $quote->getIsAlternate();
        $isAlternatePickup = $quote->getIsAlternatePickup();
        $recipientInfo = $this->getRecipientInformation(
            $isAlternate,
            $isAlternatePickup,
            $quote->getShippingAddress()
        );

        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');

        $company = (isset($shippingAddress['companyName']) ? $shippingAddress['companyName'] : null);

        $customerOrderInfo = [
            'isPickup' => (bool)$pickUpIdLocation,
            'addressClassification' => $addressClassification,
            'streetAddress' => $streetAddress,
            'city' => $shippingAddress['city'],
            'regionCode' => $shippingAddress['region_code'],
            'shipperRegion' => $shipperRegion,
            'zipcode' => $shippingAddress['postcode'],
            'shipMethod' => $shipMethod,
            'poReferenceId' => $poNumber,
            'fedExAccountNumber' => $fedExAccountNumber,
            'fedexShipAccountNumber' => $fedexShipAccountNumber,
            'estimatePickupTime' => $estimatePickupTime,
            'locationId' => $pickUpIdLocation,
            'requestedPickupLocalTime' => $this->getEstimatePickupTime($quote),
            'fName' => $shippingAddress['firstname'],
            'lName' => $shippingAddress['lastname'],
            'email' => $shippingAddress['email'],
            'telephone' => $shippingAddress['telephone'],
            'company' => $isCompanyNameToggleEnabled ? $company : null
        ];

        return $this->submitOrderBuilder->prepareDataObject(
            $quote,
            (bool)$pickUpIdLocation,
            $orderNumber,
            $shipmentId,
            $customerOrderInfo,
            $recipientInfo,
            true,
            $companyId
        );
    }

    /**
     * Return estimate pickup time for API
     *
     * @param Quote $cart
     * @return string|null
     */
    private function getEstimatePickupTime($quote): ?string
    {
        $estimatePickupTimeForApi = null;
        if($estimatePickupTime = $quote->getData('estimate_pickup_time')) {
            $expectedDate = new \DateTime($estimatePickupTime);
            $estimatePickupTimeForApi = $expectedDate->format("Y-m-d") . "T" . $expectedDate->format("H:i:s");
        }

        return $estimatePickupTimeForApi;
    }

    /**
     * Get Recipient Information
     *
     * @param bool|null $isAlternate
     * @param bool|null $isAlternatePickup
     * @param object $shippingAddress
     * @return array
     */
    private function getRecipientInformation(
        $isAlternate,
        $isAlternatePickup,
        $shippingAddress
    ) {
        $recipientFname = $recipientLname = $recipientEmail = $recipientTelephone = $recipientExt = null;
        if ($isAlternate || $isAlternatePickup) {
            $recipientFname = $shippingAddress['firstname'] ?? null;
            $recipientLname = $shippingAddress['lastname'] ?? null;
            $recipientEmail = $shippingAddress['email'] ?? null;
            $recipientTelephone = $shippingAddress['telephone'] ?? null;
            $recipientExt = $shippingAddress['ext_no'] ?? null;
        }

        return [
            "recipientFname" => $recipientFname,
            "recipientLname" => $recipientLname,
            "recipientEmail" => $recipientEmail,
            "recipientTelephone" => $recipientTelephone,
            "recipientExt" => $recipientExt
        ];
    }

    /**
     * Get Order Details
     *
     * @param object $dataObject
     * @param $quote
     * @param int $pickUpIdLocation
     * @return array|array[]
     */
    private function getOrderDetails($dataObject, $quote, $pickUpIdLocation) {

        $orderData = $this->submitOrderDataArray->getOrderDetails($dataObject, $quote);
        if (!(bool)$pickUpIdLocation) {
            $shipmentSpecialServices = $this->submitOrderModel->getRateRequestShipmentSpecialServices();
            if (!empty($shipmentSpecialServices)) {
                $orderData['rateQuoteRequest']['retailPrintOrder']['recipients'][0]['shipmentDelivery']['specialServices']
                    = $shipmentSpecialServices;
            }
        }

        return $orderData;
    }

    /**
     * Build PaymentData
     *
     * @param $companyId
     * @param $preferredPaymentMethod
     * @param $poNumber
     * @return \stdClass
     */
    private function buildPaymentData($companyId, $preferredPaymentMethod, $poNumber) {

        $paymentData = new \stdClass();
        $paymentData->paymentMethod = $preferredPaymentMethod == PaymentOptions::CREDIT_CARD?'cc':'fedex';
        if ($preferredPaymentMethod == PaymentOptions::CREDIT_CARD) {
            $companyCreditCard = $this->companyHelper->getCompanyCreditCardData($companyId);
            if (empty($companyCreditCard)) {
                throw new Exception('Company has no Credit Card saved.');
            }
            $paymentData->nameOnCard = $companyCreditCard['data']['nameOnCard'];
            $paymentData->number = $companyCreditCard['data']['ccNumber'];
            $paymentData->year = $companyCreditCard['data']['ccExpiryYear'];
            $paymentData->expire = $companyCreditCard['data']['ccExpiryMonth'];
            $paymentData->creditCardType = $companyCreditCard['data']['ccType'];
            $paymentData->isBillingAddress = true;
            $paymentData->billingAddress = new \stdClass();
            $paymentData->billingAddress->address = $companyCreditCard['data']['addressLine1'] ?? '';
            $paymentData->billingAddress->addressTwo = $companyCreditCard['data']['addressLine2'] ?? '';
            $paymentData->billingAddress->city = $companyCreditCard['data']['city'] ?? '';
            $paymentData->billingAddress->state = $companyCreditCard['data']['state'] ?? '';
            $paymentData->billingAddress->zip = $companyCreditCard['data']['zipCode'] ?? '';
            $paymentData->fedexAccountNumber = $this->companyHelper->getDiscountAccountNumber($companyId);
        } elseif ($preferredPaymentMethod == PaymentOptions::FEDEX_ACCOUNT_NUMBER) {
            $companyFedexAccount = $this->companyHelper->getFxoAccountNumber($companyId);
            if (is_null($companyFedexAccount)) {
                throw new Exception('Company has no Fedex Account saved.');
            }
            $paymentData->fedexAccountNumber = $companyFedexAccount;
        } else {
            throw new Exception('Company has no payment method configured.');
        }
        $paymentData->poReferenceId = $poNumber;

        return $paymentData;
    }

    /**
     * Build Data For Fujitsu
     *
     * @param $quote
     * @param $paymentData
     * @param $dataObject
     * @param $pickUpIdLocation
     * @param $preferredPaymentMethod
     * @param $orderData
     * @param $orderNumber
     * @return \Magento\Framework\DataObject
     */
    private function buildDataForFujitsu(
        $quote,
        $paymentData,
        $dataObject,
        $orderData,
        $pickUpIdLocation,
        $preferredPaymentMethod,
        $orderNumber
    ) {

        $dataObjectForFujistu = $this->dataObjectFactory->create();
        $dataObjectForFujistu->setQuoteData($quote);
        $dataObjectForFujistu->setPaymentData($paymentData);
        $dataObjectForFujistu->setEncCCData(null);
        $dataObjectForFujistu->setIsPickup((bool)$pickUpIdLocation);
        $dataObjectForFujistu->setShipmentId($dataObject->getShipmentId());
        $dataObjectForFujistu->setEstimatePickupTime($quote->getData('estimate_pickup_time'));
        $dataObjectForFujistu->setUseSiteCreditCard($preferredPaymentMethod == PaymentOptions::CREDIT_CARD);
        $dataObjectForFujistu->setOrderData($orderData);
        $dataObjectForFujistu->setQuoteId($quote->getId());
        $dataObjectForFujistu->setOrderNumber($orderNumber);
        $dataObjectForFujistu->setEproOrder(true);

        return $dataObjectForFujistu;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    private function fixQuantity($quote)
    {
        $items = $quote->getItemsCollection();
        $isFullMiraklQuote = $this->quoteHelper->isFullMiraklQuote($quote);
        $result = $this->submitOrderModel->getProductAndProductAssociations($items, $isFullMiraklQuote);
        if (is_array($result['product']) && !empty($result['product'])) {
            array_walk($result['product'], function(&$item) {
                if (isset($item['qty']) && is_numeric($item['qty'])) {
                    $item['qty'] = intval($item['qty']);
                }
            });
        }
        if (is_array($result['productAssociations']) && !empty($result['productAssociations'])) {
            array_walk($result['productAssociations'], function(&$item) {
                if (isset($item['quantity']) && is_numeric($item['quantity'])) {
                    $item['quantity'] = intval($item['quantity']);
                }
            });
        }

        return $result;
    }
}
