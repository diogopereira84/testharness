<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\Quote\PaymentFactory;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Sales\Model\Order;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Cart;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Fedex\ReorderInstance\Helper\ReorderInstanceHelper;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Framework\App\RequestInterface;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\Shipment\Helper\StatusOption as ShipmentHelper;
use Fedex\Cart\Model\Quote\Integration\Repository;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\Cart\ViewModel\CartSummary;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class Data extends AbstractHelper
{
    public const LINE_NUMBER = ' Line:';
    public const ORDER_ID_TEXT = ' Order ID: ';
    public const PAYMENT_METHOD_TEXT = 'paymentMethod';
    public const FXO_SHIPMENT_ID = 'fxo_shipment_id';
    public const FEDEX_SHIP_ACCOUNT_NUMBER = 'fedex_ship_account_number';
    public const MAZEGEEKS_D237618 = 'mazegeeks_D237618';

    /**
     * @var selfRegHelper
     */
    protected $selfRegHelper;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param ConvertOrder $convertOrder
     * @param QuoteManagement $quoteManagement
     * @param PaymentFactory $paymentFactory
     * @param LoggerInterface $logger
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param ProducingAddressFactory $producingAddress
     * @param Order $orderCollection
     * @param ToggleConfig $toggleConfig
     * @param DeliveryHelper $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     * @param SsoConfiguration $ssoConfiguration
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CollectionFactory $orderCollectionFactory
     * @param CheckoutSession $checkoutSession
     * @param SdeHelper $sdeHelper
     * @param ReorderInstanceHelper $reorderInstanceHelper
     * @param OptimizeItemInstanceHelper $optimizeItemInstanceHelper
     * @param QuoteFactory $quoteFactory
     * @param Cart $cart
     * @param CartDataHelper $cartDataHelper
     * @param RequestInterface $request
     * @param RequestQueryValidator $requestQueryValidator
     * @param ResourceConnection $resourceConnection
     * @param AttributeRepositoryInterface $attributeRepositoryInterface
     * @param SelfReg $selfRegHelper
     * @param AuthHelper $authHelper
     * @param TimezoneInterface $timezone
     * @param ShipmentHelper $shipmentHelper
     * @param Repository $cartIntegrationRepository
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param CartRepositoryInterface $quoteRepository
     * @param CartSummary $cartSummary
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        Context $context,
        private OrderRepositoryInterface $orderRepository,
        private ConvertOrder $convertOrder,
        private QuoteManagement $quoteManagement,
        private PaymentFactory $paymentFactory,
        protected LoggerInterface $logger,
        private InvoiceService $invoiceService,
        private TransactionFactory $transactionFactory,
        protected ProducingAddressFactory $producingAddress,
        protected Order $orderCollection,
        protected ToggleConfig $toggleConfig,
        private DeliveryHelper $helper,
        protected CustomerRepositoryInterface $customerRepository,
        protected Session $customerSession,
        protected SsoConfiguration $ssoConfiguration,
        protected CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        private CollectionFactory $orderCollectionFactory,
        protected CheckoutSession $checkoutSession,
        protected SdeHelper $sdeHelper,
        protected ReorderInstanceHelper $reorderInstanceHelper,
        protected OptimizeItemInstanceHelper $optimizeItemInstanceHelper,
        private QuoteFactory $quoteFactory,
        private Cart $cart,
        private CartDataHelper $cartDataHelper,
        private RequestInterface $request,
        private RequestQueryValidator $requestQueryValidator,
        private ResourceConnection $resourceConnection,
        private AttributeRepositoryInterface $attributeRepositoryInterface,
        SelfReg $selfRegHelper,
        protected AuthHelper $authHelper,
        private TimezoneInterface $timezone,
        private ShipmentHelper $shipmentHelper,
        private Repository $cartIntegrationRepository,
        protected OrderApprovalViewModel $orderApprovalViewModel,
        protected CartRepositoryInterface $quoteRepository,
        protected CartSummary $cartSummary,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    ) {
        $this->selfRegHelper = $selfRegHelper;
        parent::__construct($context);
    }

    /**
     * Use to find duplicate order if exist then redirect to order success
     *
     * @param int $quoteId
     * @return boolean|true|false
     */
    public function isDuplicateOrder($quoteId)
    {
        try {
            $orderInProgressSession = $this->checkoutSession->getOrderInProgress();
            $existOrders = $this->orderCollectionFactory->create()->addFieldToSelect(['entity_id'])
            ->addFieldToFilter('quote_id', $quoteId);

            $this->logger->info(
                __METHOD__.':'.__LINE__. "Order exist count with same quote id place order" . $existOrders->getSize()
            );

            if ($existOrders->getSize() > 0 || $orderInProgressSession) {
                $this->logger->info(__METHOD__.':'.__LINE__. "Order already exist with same quote id " . $quoteId);

                return true;
            } else {
                $this->checkoutSession->setOrderInProgress(true);

                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__. "Error in duplicate order number: " . $e->getMessage());
        }
    }

    /**
     * Place order from pickup and shipping
     * (Uses plugin Magento\Sales\Model\Service\OrderService to replace email in case of alternate Address)
     *
     * @param object $quote
     * @param int $cartId
     * @param int $shipmentId
     * @param int $retailTransectionId
     * @param string $productLineDetailsAttributes
     * @param array $paymentData
     * @return int
     */
    public function placeOrder(
        $quote,
        $cartId,
        $shipmentId,
        $retailTransectionId,
        $productLineDetailsAttributes,
        $paymentData = []
    ) {
        $this->logger->info(__METHOD__.':'.__LINE__.': Start of placeOrder process Quote ID:' . $quote->getId());

        $data = $this->getPaymentParametersData($quote, $paymentData);
        $quote = $this->setQuotePaymentInfo($quote, $data);
        $this->logger->info(__METHOD__.':'.__LINE__.': Finished check payment info Quote ID:'. $quote->getId());

        if ((!$this->helper->isCommercialCustomer()) && ($this->helper->getCustomer())) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod($shippingAddress->getShippingMethod());
        }
        $quote = $this->updateCustomerInformation($quote);
        $this->logger->info(__METHOD__.':'.__LINE__. ' Before Verifying Quote Integrity:' . $quote->getId());
        $this->verifyQuoteIntegrity($quote);
        $this->logger->info(__METHOD__.':'.__LINE__. ' After Verifying Quote Integrity:' . $quote->getId());
        $this->logger->info(__METHOD__.':'.__LINE__. ' Before Save quote Quote ID: '. $quote->getId());

        $this->updateQuoteItemName($quote);

        $quote->save();
        $this->logger->info(__METHOD__.':'.__LINE__.': Quote ID:' . $quote->getId());

        // Create Order From Quote
        $order = $this->createOrderFromQuote($quote);

        // Check if order has already shipping or can be shipped
        if (!$order->canShip()) {
            $this->logger->error(
                __METHOD__.':'.__LINE__.': Quote ID:' . $quote->getId() . ' You cant create the Shipment.'
            );

            throw new \Magento\Framework\Exception\LocalizedException(__('You cant create the Shipment.'));
        }

        // Initializing Object for the order shipment
        $shipment = $this->convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            // Check if the order item has Quantity to ship or is virtual
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }
            $qtyShipped = $orderItem->getQtyToShip();

            // Create Shipment Item with Quantity
            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add Shipment Item to Shipment
            $shipment->addItem($shipmentItem);
        }

        // Register Shipment
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(false);
        $shipment->setData(self::FXO_SHIPMENT_ID, $shipmentId);
        $shipment->setData('shipping_account_number', $data['shippingAccountNumber']);
        $shipment->setShipmentStatus("1");

        try {
            // Save created Shipment and Order
            $shipment->save();
            $shipment->getOrder()->save();
            $paymentData = $order->getPayment();
            $paymentData->setFedexAccountNumber($data['fedexAccountNumber']);
            $paymentData->setPoNumber($data['fedexPoNumber']);
            $paymentData->setCcOwner($data['ccOwner']);
            $paymentData->setRetailTransactionId($retailTransectionId);
            $paymentData->setProductLineDetails($productLineDetailsAttributes);
            $paymentData->setCcLast4($data['ccNumber']);
            if ($data['useSitePayment']) {
                $paymentData->setSiteConfiguredPaymentUsed(1);
            }
            $paymentData->save();
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__.':'.__LINE__.':An error occurred while save payment : '
                . $quote->getId() . ' :' . $e->getMessage()
            );
        }
        $this->generateInvoice($order->getId());

        return $order->getId();
    }

    /**
     * Get Some data
     *
     * @param object $quote
     * @param array $paymentData
     *
     * @return array
     */
    public function getPaymentParametersData($quote, $paymentData)
    {
        $shippingAccountNumber = "";
        $useSitePayment = false;
        $fedexAccountNumber = "";
        $lteIdentifier = "";
        $ccNumber = null;
        $ccOwner = null;
        $fedexPoNumber = null;
        $paymentMethod = '';

        if ($quote->getData(self::FEDEX_SHIP_ACCOUNT_NUMBER)) {
            $shippingAccountNumber = $quote->getData(self::FEDEX_SHIP_ACCOUNT_NUMBER);
        }

        //B-1326759: Set flag to identify order is using site configured payment
        if ($quote->getSiteConfiguredPaymentUsed()) {
            $useSitePayment = true;
        }

        if (isset($paymentData->fedexAccountNumber)) {
            $fedexAccountNumber = $paymentData->fedexAccountNumber;
        }
        if (isset($paymentData->lteIdentifier)) {
            $lteIdentifier = $paymentData->lteIdentifier;
        }
        /* Get Last four digits of credit card */
        if (isset($paymentData->number)) {
           $ccNumber = substr($paymentData->number, -4);
        }

        if (isset($paymentData->nameOnCard) && $paymentData->nameOnCard != 'undefined') {
            $ccOwner = $paymentData->nameOnCard;
        }

        /* Get fedex po number */
        if (isset($paymentData->poReferenceId)) {
            $fedexPoNumber = $paymentData->poReferenceId ?? null;
        }

        if ($paymentData->paymentMethod == 'fedex') {
            $paymentMethod = 'fedexaccount';
        } elseif ($paymentData->paymentMethod == 'cc') {
            $paymentMethod = 'fedexccpay';
        } elseif ($paymentData->paymentMethod == 'instore') {
            $paymentMethod = 'instorepayment';
        }

        return [
            'shippingAccountNumber' => $shippingAccountNumber,
            'useSitePayment'        => $useSitePayment,
            'fedexAccountNumber'    => $fedexAccountNumber,
            'lteIdentifier'         => $lteIdentifier,
            'ccNumber'              => $ccNumber,
            'ccOwner'               => $ccOwner,
            'fedexPoNumber'         => $fedexPoNumber,
            'paymentMethod'         => $paymentMethod
        ];
    }

    /**
     * Update Customer Information
     *
     * @param object $quote
     * @return object
     */
    public function updateCustomerInformation($quote)
    {
        if (!$this->authHelper->isLoggedIn()) {
            $quote->setCustomerId(null);
            $quote->setCustomerIsGuest(true);
        }

        if ($quote->getCustomerEmail() === null) {
            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        }

        if ($quote->getCustomerFirstname() === null && $quote->getCustomerLastname() === null) {
            $quote->setCustomerFirstname($quote->getBillingAddress()->getFirstname());
            $quote->setCustomerLastname($quote->getBillingAddress()->getLastname());
            if ($quote->getBillingAddress()->getMiddlename() === null) {
                $quote->setCustomerMiddlename($quote->getBillingAddress()->getMiddlename());
            }
        }

        return $quote;
    }

    /**
     * Create order from quote
     *
     * @param object $quote
     * @return object
     */
    public function createOrderFromQuote($quote)
    {
        $quoteId = $quote->getId();
        if (!$this->isGraphQlRequest()) {
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
            $quote->save();
        }

        $quoteSubTotal = null;
        $quoteBaseSubTotal = null;
        $quoteSubTotal = $quote->getSubtotal();
        $quoteBaseSubTotal = $quote->getBaseSubtotal();
        $couponCode = $quote->getData('coupon_code');
        try {
            $order = $this->quoteManagement->submit($quote);
            $this->logger->info(
                __METHOD__ . ':' . __LINE__. ' Order was created with Id: '. $order->getId().'
                    for the Quote Id:' .$quoteId
            );
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__. ': Exception occurred while converting quote into order for the quote id: '
                .$quoteId.' : '. $e->getMessage()
            );
        }

        if (!$quote->getData("fxo_shipment_id")) {
            $shipmentId = (string)(random_int(1000, 9999));
            $quote->setData('fxo_shipment_id', $shipmentId);
        }

        /*Fix Order subtotal history*/
        if ($quoteSubTotal !== $quote->getSubtotal()) {
            $quote->setSubtotal($quoteSubTotal);
            $quote->setBaseSubtotal($quoteBaseSubTotal);
        }

        if ($this->toggleConfig->getToggleConfigValue('techtitans_208009_promo_code_fix') && $couponCode) {
            $quote->setData('coupon_code', $couponCode);
        }

        $quote->save();

        if (!empty($this->checkoutSession->getRateQuoteResponse())) {
            $isQuoteUpdate = $this->updateQuoteInfoIfRateQuotePriceMisMatch(
                $quote,
                $this->checkoutSession->getRateQuoteResponse()
            );
            if ($isQuoteUpdate) {
                $order->setGrandTotal($quote->getGrandTotal());
                $order->setBaseGrandTotal($quote->getBaseGrandTotal());
            }
        }
        if ($order->getSubtotal() != $quote->getSubtotal()) {
            $order->setSubtotal($quote->getSubtotal());
            $order->setBaseSubtotal($quote->getSubtotal());
        }
        if ($order->getGrandTotal() != $quote->getGrandTotal()) {
            $order->setGrandTotal($quote->getGrandTotal());
            $order->setBaseGrandTotal($quote->getBaseGrandTotal());
        }
        /** D-216028 Shipping cost twice issue fix */
        if (
            !$this->toggleConfig->getToggleConfigValue('tech_titans_d_216028') &&
            !empty($quote->getFedexShipAccountNumber()) &&
            !empty($quote->getShippingCost())
        ) {
            $grandTotal = $quote->getGrandTotal() + $quote->getShippingCost();
            $order->setGrandTotal($grandTotal);
            $order->setBaseGrandTotal($grandTotal);
        }

        if ($this->orderApprovalViewModel->isOrderApprovalB2bEnabled()) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__.
                ' Before setting Custom Billing Fields and order status as pending_approval for B2b Order approval with quote Id : '. $quoteId
            );
            $order->setBillingFields($quote->getBillingFields());
            $order->setStatus("pending_approval");
            $this->logger->info(
                __METHOD__ . ':' . __LINE__.
                ' After setting Custom Billing Fields and order status as pending_approval for B2b Order approval with quote Id : '. $quoteId
            );
        } else {
            $order->setStatus("pending");
        }

        try {
            $order->save();
        } catch (\Exception $e) {
            $this->checkoutSession->unsOrderInProgress();
            $this->logger->error(
                __METHOD__ . ':' . __LINE__.': Order was not created for the quote id: '
                . $quoteId. ' : '. $e->getMessage()
            );
        }

        return $order;
    }

    /**
     * Return validate if is GraphQl request
     *
     * @return boolean
     */
    private function isGraphQlRequest(): bool
    {
        return $this->requestQueryValidator->isGraphQlRequest($this->request);
    }

    /**
     * Set GTN Number as Order Id
     *
     * @param Quote $quote
     * @param int $orderNumber
     *
     * @return bool false|true
     */
    public function isSetOrderId($quote, $orderNumber)
    {
        $this->logger->info(
            __METHOD__.':'.__LINE__.': Setting GTN number :'. $orderNumber.' as Order Id for the Quote Id.'
                . $quote->getId()
        );

        $orderIdSaveStatus = false;
        try {
            $quote->setData('reserved_order_id', $orderNumber);
            $quote->save();
            $orderIdSaveStatus = true;
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__.':'.__LINE__.': An error occured while setting GTN number '. $orderNumber.'
                     as Order Id for Quote Id.' . $quote->getId().' is: '.$e->getMessage()
            );
            $orderIdSaveStatus = false;
        }

        return $orderIdSaveStatus;
    }

    /**
     * Generate Invoice
     *
     * @param integer $orderId
     */
    public function generateInvoice($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setSubtotal($order->getSubTotal());
                $invoice->setBaseSubtotal($order->getBaseSubTotal());
                $invoice->setGrandTotal($order->getGrandTotal());
                $invoice->setBaseGrandTotal($order->getBaseGrandTotal());
                $invoice->setDiscountAmount($order->getDiscountAmount());
                $invoice->setBaseDiscountAmount($order->getBaseDiscountAmount());
                $invoice->register();
                $invoice->save();
                $invoice->getOrder()->setGrandTotal($order->getGrandTotal());
                $invoice->getOrder()->setBaseGrandTotal($order->getBaseGrandTotal());
                $invoice->getOrder()->setSubTotal($order->getSubTotal());
                $invoice->getOrder()->setBaseSubTotal($order->getBaseSubTotal());
                $invoice->getOrder()->setDiscountAmount($order->getDiscountAmount());
                $invoice->getOrder()->setBaseDiscountAmount($order->getBaseDiscountAmount());

                $transactionSave = $this->transactionFactory->create()->addObject($invoice)
                ->addObject($invoice->getOrder());
                $transactionSave->save();

                $orderInfo = $this->orderCollection->load($orderId);
                $orderInfo->setTotalPaid($order->getGrandTotal());
                $orderInfo->setBaseTotalPaid($order->getBaseGrandTotal());
                $orderInfo->save();
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                ': Invoice was created successfully for order: ' . $orderId);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->critical(
                __METHOD__.':'.__LINE__.':An error occurred while retrieving the order to generate invoice: '
                . $orderId . ' ' . $e->getMessage()
            );
        }
    }

    /**
     * Save Order Producing Address
     *
     * @param array $addressInfo
     * @param integer $orderId
     * @param date|null $estimatedTime
     * @param string|null $estimatedDuration
     *
     * @return void
     */
    public function saveOrderProducingAddress($addressInfo, $orderId, $estimatedTime, $estimatedDuration)
    {
        try {
            $address = $phoneNumber = $emailAddress = $shipmentId = '';
            $orderInfo = $this->orderCollection->load($orderId);
            $shipmentCollection = $orderInfo->getShipmentsCollection();
            foreach ($shipmentCollection as $shipment) {
                if ($estimatedTime) {
                    $shipment->setData('order_completion_date', $estimatedTime);
                } else {
                    $shipment->setData('estimated_delivery_duration', $estimatedDuration);
                }
                $shipmentId = $shipment->getId();
            }

            if ($estimatedTime || $estimatedDuration) {
                $shipmentCollection->save();
            }

            if ($addressInfo['address']) {
                $address = $addressInfo['address'];
                $phoneNumber = $addressInfo['phone_number'];
                $emailAddress = $addressInfo['email_address'];
            }

            if (($this->toggleConfig->getToggleConfigValue('mazegeeks_B_2599706')) &&
                ($this->isEproQuote($orderInfo->getQuoteId()) && ($shipmentId == ''))
            ) {
                $shipmentId = NULL;
            }

            $data['store_id'] = $orderInfo->getStoreId();
            $data['order_id'] = $orderId;
            $data['shipment_id'] = $shipmentId;
            $data['address'] = $address;
            $data['phone_number'] = $phoneNumber;
            $data['email_address'] = $emailAddress;
            $model = $this->producingAddress->create();
            $model->addData($data);
            $model->save();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.': Order ID:' . $orderId . ' Message => ' . $e->getMessage());
        }
    }

    /**
     * Prepare Order Producing ddress
     *
     * @param array $checkoutResponse
     * @param integer $orderId
     *
     * @return void
     */
    public function prepareOrderProducingAddress($checkoutResponse, $orderId)
    {
        try {
            $addressInfo = [];
            $estimatedTime = null;
            $estimatedDuration = null;
            $responseArray = json_decode((string)$checkoutResponse, true);
            $retailPrintOrderDetails = $responseArray['output']['checkout']['lineItems'][0]['retailPrintOrderDetails'];
            $dataForAddress = $retailPrintOrderDetails[0]['responsibleCenterDetail'];
            $addressArray = $dataForAddress[0]['address'];

            if (isset($retailPrintOrderDetails[0]['deliveryLines'][0]['estimatedDeliveryLocalTime'])
            && $retailPrintOrderDetails[0]['deliveryLines'][0]['estimatedDeliveryLocalTime'] != '') {
                $estimatedTime = $retailPrintOrderDetails[0]['deliveryLines'][0]['estimatedDeliveryLocalTime'];
            } else {
                if (isset($retailPrintOrderDetails[0]['deliveryLines'][0]['estimatedDeliveryDuration']['value'])) {
                    $value = $retailPrintOrderDetails[0]['deliveryLines'][0]['estimatedDeliveryDuration']['value'];
                    $unit = $retailPrintOrderDetails[0]['deliveryLines'][0]['estimatedDeliveryDuration']['unit'];
                    $estimatedDuration = $value . ' ' . $unit;
                }
            }

            if ($addressArray) {
                $phoneNumber = '';
                $emailAddress = '';
                if (isset($dataForAddress[0]['phoneNumberDetails'][0]['phoneNumber']['number'])) {
                    $phoneNumber = $dataForAddress[0]['phoneNumberDetails'][0]['phoneNumber']['number'];
                }

                if (isset($dataForAddress[0]['emailDetail']['emailAddress'])) {
                    $emailAddress = $dataForAddress[0]['emailDetail']['emailAddress'];
                }

                $addressInfo['phone_number'] = $phoneNumber;
                $addressInfo['email_address'] = $emailAddress;
                $street = implode(" ", $addressArray['streetLines']);
                $city = $addressArray['city'];
                $stateOrProvinceCode = $addressArray['stateOrProvinceCode'];
                $postalCode = $addressArray['postalCode'];
                $countryCode = $addressArray['countryCode'];
                $address = $street.' '.$city.' '.$stateOrProvinceCode.' '.$postalCode.' '.$countryCode;
                $addressInfo['address'] = $address;
            }

            $this->saveOrderProducingAddress($addressInfo, $orderId, $estimatedTime, $estimatedDuration);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.': Order ID:' . $orderId . ' Message => ' . $e->getMessage());
        }
    }

    /**
     * Get fcl customer Uuid
     *
     * @return string
     */
    public function getUuid()
    {
        try {
            $fclUuid = '';
            if ($this->customerSession->getCustomerId()
                && !$this->customerSession->getCustomerCompany()
            ) {
                $customerId = $this->customerSession->getCustomerId();
                    $attribute = $this->attributeRepositoryInterface->get('customer', 'customer_uuid_value');
                    $connection = $this->resourceConnection->getConnection();
                    $query = $connection->select()
                        ->from('customer_entity_varchar')
                        ->where('attribute_id=?', $attribute->getAttributeId())
                        ->where('entity_id=?', $customerId);
                    $rowData = $connection->fetchRow($query);
                    $fclUuid = $rowData['value'] ?? '';
            }

            return $fclUuid;
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__.':'.__LINE__.':Error in getting customer UUID. CustomerId is: '
                . $this->customerSession->getCustomerId() . ' :' . $e->getMessage()
            );
        }
    }

    /**
     * Create a public cookie
     *
     * @param string $cookieName
     * @param integer $value
     * @param integer $duration
     * @param boolean $secure
     * @param boolean $httpOnly
     * @param string $path
     * @return void
     */
    public function setCookie($cookieName, $value, $duration = 600, $secure = true, $httpOnly = false, $path = '/')
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setSecure($secure)
            ->setPath($path)
            ->setHttpOnly($httpOnly);

        $this->cookieManager->setPublicCookie($cookieName, $value, $metadata);
    }

    /**
     * Get cookie value
     *
     * @param string $cookieName
     * @return mixed
     */
    public function getCookie($cookieName)
    {
        return $this->cookieManager->getCookie($cookieName);
    }

    /**
     * Get rate quote id from rate quote response
     *
     * @param array $rateQuoteResponse
     * @return string|null
     */
    public function getRateQuoteId($rateQuoteResponse)
    {
        if (isset($rateQuoteResponse['output']['rateQuote'])
            && isset($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'])
        ) {
            foreach ($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'] as $data) {
                if (isset($data['rateQuoteId'])) {
                    return $data['rateQuoteId'];
                }
            }
        }

        return null;
    }

    /**
     * Get order total from rate quote response
     *
     * Based on new rate quote response order total will be in a separate index
     * If this is not present take the ACTUAL total amount from the rateQuoteDetails
     *
     * @param array $rateQuoteResponse
     * @return float
     */
    public function getOrderTotalFromRateQuoteResponse($rateQuoteResponse)
    {
        if (isset($rateQuoteResponse['output']['rateQuote'])) {
            if (isset($rateQuoteResponse['output']['rateQuote']['orderTotal'])) {

                return $rateQuoteResponse['output']['rateQuote']['orderTotal'];
            } elseif (isset($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'])) {
                foreach ($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'] as $data) {
                    if (isset($data['estimatedVsActual']) && $data['estimatedVsActual'] == 'ACTUAL') {

                        return $data['totalAmount'];
                    }
                }
            }
        }
    }

    /**
     * Get delivery line price from rate quote response
     *
     * @param array $rateQuoteResponse
     * @return float
     */
    public function getDeliveryLinePrice($rateQuoteResponse)
    {
        if (isset($rateQuoteResponse['output']['rateQuote'])
            && isset($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'])
        ) {
            $rateQuoteDetails = $rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'];
            //check for estimated shipping total, then take delivery line price from it
            $deliveryLinePrice = $this->retrieveDeliveryLinePrice($rateQuoteDetails, 'ESTIMATED');
            //if rate quote contain only ACTUAL
            if (!$deliveryLinePrice) {
                $deliveryLinePrice = $this->retrieveDeliveryLinePrice($rateQuoteDetails);
            }

            return $deliveryLinePrice;
        }
    }

    /**
     * Retrieve Delivery Line price based on response params
     *
     * For actual type, there will be more than 1 deliveryLinePrice
     * We need to get the delivery line price for delivery line type SHIPPING
     *
     * @param array $rateQuoteDetails
     * @param string $estimatedVsActual
     * @return float
     */
    public function retrieveDeliveryLinePrice($rateQuoteDetails, $estimatedVsActual = 'ACTUAL')
    {
        $deliveryLinePrice = 0.00;
        foreach ($rateQuoteDetails as $data) {
            if (isset($data['estimatedVsActual'])
            && isset($data['deliveryLines'])
            && $data['estimatedVsActual'] == $estimatedVsActual) {
                foreach ($data['deliveryLines'] as $deliveryData) {
                    if (isset($deliveryData['deliveryLineType'])
                        && $deliveryData['deliveryLineType'] == 'SHIPPING'
                        && isset($deliveryData['deliveryLinePrice'])
                    ) {
                        $deliveryLinePrice = $deliveryData['deliveryLinePrice'];
                    }
                }
            }
        }

        return $deliveryLinePrice;
    }

    /**
     * Verify integrity of quote
     *
     * @param Quote $quote
     * @return void
     */
    public function verifyQuoteIntegrity(Quote $quote)
    {
        $billing = $quote->getBillingAddress();
        $shipping = $quote->getShippingAddress();
        $quoteId = $quote->getId();

        if ($billing->getShippingMethod() && !$shipping->getShippingMethod()) {
            $shipping->setShippingMethod($billing->getShippingMethod());
            $shipping->setShippingDescription($billing->getShippingDescription());
            if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
                $shipping->setProductionLocation($billing->getProductionLocation());
            }
            $this->logger->info(
                __METHOD__.':'.__LINE__. ' ShippingMethod was missing from Shipping For Quote ID:'. $quoteId
                . ' ShippingMethod: ' . $billing->getShippingMethod()
            );
        } elseif (!$billing->getShippingMethod() && $shipping->getShippingMethod()) {
            $billing->setShippingMethod($shipping->getShippingMethod());
            $billing->setShippingDescription($shipping->getShippingDescription());
            if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
                $billing->setProductionLocation($shipping->getProductionLocation());
            }
            $this->logger->info(
                __METHOD__.':'.__LINE__. ' ShippingMethod was missing from Billing For Quote ID: '.$quoteId
                .' ShippingMethod: ' . $shipping->getShippingMethod()
            );
        } elseif (!$billing->getShippingMethod() && !$shipping->getShippingMethod()) {
            $this->setShippingMissingDataFromCheckoutSession($quote);
        } else {
            $this->logger->info(
                __METHOD__.':'.__LINE__. ' ShippingMethod was present in Billing and Shipping For Quote ID: '. $quoteId
            );
        }

        if ($billing->getCustomerId() && !$shipping->getCustomerId()) {
            $shipping->setCustomerId($billing->getCustomerId());
            $this->logger->info(
                __METHOD__.':'.__LINE__. ' CustomerID was missing from Shipping For Quote ID: '.$quoteId
                . ' CustomerID: ' . $billing->getCustomerId()
            );
        } elseif (!$billing->getCustomerId() && $shipping->getCustomerId()) {
            $billing->setCustomerId($shipping->getCustomerId());
            $this->logger->info(
                __METHOD__.':'.__LINE__. ' CustomerID was missing from Billing For Quote ID: '.$quoteId
                . ' CustomerID: ' . $shipping->getCustomerId()
            );
        } elseif (!$billing->getCustomerId() && !$shipping->getCustomerId()) {
            $this->logger->info(
                __METHOD__.':'.__LINE__. ' CustomerID was missing in Billing and Shipping For Quote ID: '.$quoteId
            );
        } else {
            $this->logger->info(
                __METHOD__.':'.__LINE__. ' CustomerID was present in Billing and Shipping For Quote ID: '. $quoteId
            );
        }
    }

    /**
     * Set shipping method and description from checkout session
     *
     * @param object $quote
     * @return void
     */
    public function setShippingMissingDataFromCheckoutSession($quote)
    {
        $billing = $quote->getBillingAddress();
        $shipping = $quote->getShippingAddress();
        $quoteId = $quote->getId();
        $extShipInfo = $quote->getExtShippingInfo();
        if (!empty($extShipInfo)) {
            $extShipInfoData = json_decode($extShipInfo, true);
            if (empty($this->checkoutSession->getCustomShippingMethodCode())) {
                $this->checkoutSession->setCustomShippingMethodCode($extShipInfoData['shippingMethodCode']);
            }
            if (empty($this->checkoutSession->getCustomShippingCarrierCode())) {
                $this->checkoutSession->setCustomShippingCarrierCode($extShipInfoData['shippingCarrierCode']);
            }
            if (empty($this->checkoutSession->getCustomShippingTitle())) {
                $this->checkoutSession->setCustomShippingTitle($extShipInfoData['shipMethodTitle']);
            }
        }
        $shippingMethodInCheckoutSession = $this->checkoutSession->getCustomShippingCarrierCode() . '_'
        . $this->checkoutSession->getCustomShippingMethodCode();
        $shippingDescriptionInCheckoutSession = $this->checkoutSession->getCustomShippingTitle();

        $billing->setShippingMethod($shippingMethodInCheckoutSession);
        $billing->setShippingDescription($shippingDescriptionInCheckoutSession);
        $shipping->setShippingMethod($shippingMethodInCheckoutSession);
        $shipping->setShippingDescription($shippingDescriptionInCheckoutSession);
        $this->logger->info(
            __METHOD__.':'.__LINE__. ' Set Shipping Missing Data From Checkout Session For Quote ID: '. $quoteId
            .'Shipping Method: ' . $shippingMethodInCheckoutSession.' And Shipping Description: '
            . $shippingDescriptionInCheckoutSession
        );
    }

    /**
     * Create Shipment
     *
     * @param object $quote
     * @param int $orderId
     * @throws \Exception
     */
    public function createShipment($quote, $orderId)
    {
        $fxoShipmentId = null;
        $shippingAccountNumber = null;
        $shippingDueDate = null;

        if ($quote->getData(self::FEDEX_SHIP_ACCOUNT_NUMBER)) {
            $shippingAccountNumber = $quote->getData(self::FEDEX_SHIP_ACCOUNT_NUMBER);
        }

        if ($quote->getData(self::FXO_SHIPMENT_ID)) {
            $fxoShipmentId = $quote->getData(self::FXO_SHIPMENT_ID);
        }

        $order = $this->orderRepository->get($orderId);
        if (
            $this->shipmentHelper->hasShipmentCreated($order)
        ) {
            $this->logger->info(__METHOD__.':'.__LINE__. 'Shipment was already created for the order: '. $orderId);
            return true;
        }
        // Initializing Object for the order shipment
        $shipment = $this->convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            // Check if the order item has Quantity to ship or is virtual
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual() || $orderItem->getMiraklOfferId()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();

            // Create Shipment Item with Quantity
            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add Shipment Item to Shipment
            $shipment->addItem($shipmentItem);
        }

        // Register Shipment
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(false);
        $shipment->setData(self::FXO_SHIPMENT_ID, $fxoShipmentId);
        $shipment->setData('shipping_account_number', $shippingAccountNumber);
        $shipment->setShipmentStatus("1");

        /**
         * TK-3053255 - Save shipment due date
         */
        // Check if Fuse/In-Store Order
        try {
            $incrementId = $order->getIncrementId() ?? '';
            if (str_starts_with($incrementId, \Fedex\Punchout\Helper\Data::INSTORE_GTN_PREFIX)) {
                $cartIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
                if ($cartIntegration->getIntegrationId() && $cartIntegration->getPickupLocationDate()) {
                    $shippingDueDate = $cartIntegration->getPickupLocationDate();
                }
            } else { // Non Fuse/In-Store Orders
                if (!empty($order->getEstimatedPickupTime())) {
                    $shippingDueDate = $this->timezone->date(new \DateTime($order->getEstimatedPickupTime()))->format('Y-m-d') . ' 00:00:00';
                } else {
                    $shipDescription = str_replace('End of Day', '11:59pm', $order->getShippingDescription());
                    $shipDate = explode(" - ", $shipDescription);
                    if (isset($shipDate[1])) {
                        $shippingDueDate = $this->timezone->date(new \DateTime($shipDate[1]))->format('Y-m-d') . ' 00:00:00';
                    }
                }
            }
            if ($shippingDueDate) {
                $shipment->setData('shipping_due_date', $shippingDueDate);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ': An error occurred while setting shipping_due_date for Order Id: ' . $orderId . ' :' . $e->getMessage()
            );
        }

        try {
            // Save created Shipment and Order
            $shipment->save();
            $shipment->getOrder()->save();
            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__.':'.__LINE__.
                ': An error occurred while creating shipment for Order Id: ' . $orderId . ' :' . $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Get Product Json Data
     *
     * @param string $additionalOptions
     * @return array
     */
    public function getProductJsonData($additionalOptions)
    {
        $productJson = (array) json_decode((string)$additionalOptions)->external_prod[0];

        if (isset($productJson['catalogReference'])) {
            $productJson['catalogReference'] = (array) $productJson['catalogReference'];
        }

        if (isset($productJson['preview_url'])) {
            unset($productJson['preview_url']);
        }

        if (isset($productJson['fxo_product'])) {
            unset($productJson['fxo_product']);
        }

        return $productJson;
    }

    /**
     * Get Credit Cart Details
     *
     * @param string $response
     * @param int|string|null $ccToken
     * @param string|null $nameOnCard
     * @return array
     */
    public function getCreditCartDetails($response, $ccToken, $nameOnCard)
    {
        if (isset($response->output->creditCard->creditCardToken)) {
            $ccToken = $response->output->creditCard->creditCardToken;
        }
        if (isset($response->output->creditCard->cardHolderName)) {
            $nameOnCard = $response->output->creditCard->cardHolderName;
        }

        return ['ccToken' => $ccToken, 'nameOnCard' => $nameOnCard];
    }

    /**
     * Get Retail Transection Id and Product Line Details Attributes
     *
     * @param string $transactionReponse
     * @return array
     */
    public function getTransactionAndProductLineDetails($transactionReponse)
    {
        $retailTransectionId = null;
        $productLineDetailsAttributes = null;
        $transactionReponseData = json_decode((string)$transactionReponse[0]);

        if (isset($transactionReponseData->output)) {
            $retailTracId = $transactionReponseData->output->checkout->transactionHeader;
            if (isset($retailTracId->retailTransactionId)) {
                $retailTransectionId = $retailTracId->retailTransactionId;
            }
            $productLineDetailsResponse =
            $transactionReponseData->output->checkout->lineItems[0]->retailPrintOrderDetails[0];
            if (isset($productLineDetailsResponse->productLines)) {
                $productLineDetailsAttributes = json_encode($productLineDetailsResponse->productLines);
            }
        }

        return [
            'retailTransectionId' => $retailTransectionId,
            'productLineDetailsAttributes' => $productLineDetailsAttributes
        ];
    }

    /**
     * Producing address
     *
     * @param array $checkoutReponse
     * @param object $quote
     * @param int $orderId
     */
    public function producingAddress($checkoutResponse, $quote, $orderId)
    {
        $logHeader = 'File: ' . static::class . ' Method: ' . __METHOD__;

        $this->logger->info($logHeader . self::LINE_NUMBER . __LINE__ .
        ' Before prepare producing address Quote ID:' . $quote->getId());
        $this->prepareOrderProducingAddress($checkoutResponse[0], $orderId);

        $this->logger->info($logHeader . self::LINE_NUMBER . __LINE__ .
            ' After prepare producing address Quote ID:' . $quote->getId());
    }

    /**
     * Reorder instance save
     * @param int $orderId
     */
    public function reorderInstanceSave($orderId): void
    {
        // If Order id is generated then call reorderable Instance API to preserve the instance.
        if ($this->authHelper->isLoggedIn()) {
            $this->reorderInstanceHelper->pushOrderIdInQueue($orderId);
        }
    }

    /**
     * Push order id and counter data in queue for shipment retry
     * @param int $orderId
     */
    public function pushOrderIdInQueueForShipmentCreation($messageRequest): void
    {
        $this->reorderInstanceHelper->pushOrderIdInQueueForShipmentCreation($messageRequest);
    }

    /**
     * Clean product item instance
     * @param int $quoteId
     */
    public function cleanProductItemInstance($quoteId): void
    {
        // Push quote id in queue to clean item instance from quote
        $this->optimizeItemInstanceHelper->pushQuoteIdQueue($quoteId);
    }

    /**
     * Clear quote checkout sessions and storage
     *
     * @param int $quoteId
     * @param int $orderId
     */
    public function clearQuoteCheckoutSessionAndStorage($quoteId, $orderId): void
    {
        $logHeader = 'File: ' . static::class . ' Method: ' . __METHOD__;

        $this->logger->info(
            $logHeader . self::LINE_NUMBER . __LINE__ . ' Order Creation Successful For Quote ID:'
            . $quoteId . self::ORDER_ID_TEXT . $orderId
        );

        $this->checkoutSession->clearQuote();

        if ($this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_D237618)) { 
            $this->checkoutSession->setLoadInactive(false);
            $this->checkoutSession->replaceQuote($this->checkoutSession->getQuote()->save());
        }
        $this->checkoutSession->unsAll();
        $this->checkoutSession->clearStorage();
        $this->cart->truncate();

        if ($this->isGraphQlRequest()) {
            return;
        }

        /* Generate new quote id after place order successfully and clear cart and checkout session */

        $quoteId = null;
        $newQuoteGenerate = $this->quoteFactory->create();
        $newQuoteGenerate->save();
        $this->logger->info("New generated Quote Id : " . $newQuoteGenerate->getId());
        $this->logger->info(
            $logHeader . self::LINE_NUMBER . __LINE__ . ' New generated Quote ID:' .
            $newQuoteGenerate->getId() . ' Old quote ID' . $quoteId . self::ORDER_ID_TEXT . $orderId
        );

        /* End here to generate new quote id and clear checkout session */
        $this->logger->info($logHeader . self::LINE_NUMBER . __LINE__ .
            ' Checkout process done New generated Quote ID:' .
            $newQuoteGenerate->getId() . ' Old quote ID' .
            $quoteId . self::ORDER_ID_TEXT . $orderId);
    }

    /**
     * Check if quote order non exiting with same GTN number
     *
     * @param object $quote
     * @return bool
     */
    public function isQuoteOrderAvailable($quote)
    {
        $gtnNumber = $quote->getGtn();
        $quoteId = $quote->getId();
        $orderObj = $this->orderCollection->loadByIncrementId($gtnNumber);

        // Check if order exiting with same GTN number
        if (isset($orderObj) && $orderObj->getStatus()) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .' : Quote order already exiting for the same GTN:'. $gtnNumber
            );

            return false;
        }

        try {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .' : Quote deactivate for this Quote ID:' . $quoteId);
            $quote->setIsActive(0);
            $quote->save();
            if(!$this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
                 $this->saveQuoteByRepository($quote);
            }


            /* Clear cart and checkout session */
            $this->checkoutSession->clearQuote();
            $this->checkoutSession->unsAll();
            $this->checkoutSession->clearStorage();
            $this->cart->truncate();
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .' :
                Exception occurred while deactivate the quote for Quote Id : '. $quoteId.':'. $e->getMessage()
            );
        }

        return true;
    }

    /**
     * Get active quote
     *
     * @param int $quoteId
     * @return object
     */
    public function getActiveQuote($quoteId)
    {
        return $this->quoteRepository->getActive($quoteId);
    }

    /**
     * Get active quote
     *
     * @param object $quote
     */
    public function saveQuoteByRepository($quote)
    {
        try {
            $this->quoteRepository->save($quote);
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .' :
                Exception occurred while updating quote status by repository for Quote Id : '. $quote->getId()
                .':'. $e->getMessage()
            );
        }
    }

    /**
     * Set quote payment information in quote object
     *
     * @param object $quote
     * @param array $data
     * @return object
     */
    public function setQuotePaymentInfo($quote, $data)
    {
        $couponCode = $quote->getData('coupon_code');

        if ($this->ssoConfiguration->isFclCustomer() || $this->isSdeStore()) {
            $quote->getPayment()->setMethod($data[self::PAYMENT_METHOD_TEXT]);
        } else {
            $payment = $this->paymentFactory->create();
            $payment->setMethod($data[self::PAYMENT_METHOD_TEXT]);
            $quote->setPayment($payment);
            $quote->getPayment()->importData(['method' => $data[self::PAYMENT_METHOD_TEXT]]);
        }
        if ($this->authHelper->isLoggedIn() && $this->customerSession->getCustomer()->getGroupId()) {
            $groupId = $this->customerSession->getCustomer()->getGroupId();
            $quote->setCustomerGroupId($groupId);
        }

        if ($this->toggleConfig->getToggleConfigValue('techtitans_208009_promo_code_fix') && $couponCode) {
            $quote->setData('coupon_code', $couponCode);
        }

        return $quote;
    }

    /**
     * Get On Behalf Of From Customer Session
     * @param array $headers
     * @return array
     */
    public function getCustomerOnBehalfOf($headers)
    {
        if ($this->customerSession->getOnBehalfOf()) {
            $headers['X-On-Behalf-Of'] = $this->customerSession->getOnBehalfOf();
        }

        return $headers;
    }

    /**
     * Update quote item name
     *
     * @param object $quote
     * @return void
     */
    public function updateQuoteItemName($quote)
    {
        $items = $quote->getAllItems();
        foreach ($items as $item) {
            $additionalOption = $item->getOptionByCode('info_buyRequest');
            $additionalOptions =  json_decode($additionalOption->getValue());
            if (isset($additionalOptions->external_prod[0]->userProductName)) {
                $item->setName($additionalOptions->external_prod[0]->userProductName);
                $item->save();
            }
        }
    }

    /**
     * Get Retail Transaction id in case of rate quote alert
     *
     * @param string $tId
     */
    public function saveRetailTransactionId($tId)
    {
        $this->customerSession->setRetailTransactionId($tId);
    }

    /**
     * Get Retail Transaction id in case of rate quote alert
     *
     * @return string
     */
    public function getRetailTransactionIdFromSession()
    {
        return $this->customerSession->getRetailTransactionId();
    }

    /**
     * Unset Retail Transaction Id
     */
    public function unsetRetailTransactionId()
    {
        $this->customerSession->unsRetailTransactionId();
    }

    /**
     * Check if store is SDE
     */
    public function isSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Update Quote Information if Grand total amount mismatch with rate quote total amount
     *
     * @param Obj $quote
     * @param array $productRates
     */
    public function updateQuoteInfoIfRateQuotePriceMisMatch($quote, $rateQuoteResponse)
    {
        $totalNetAmount = 0;
        $responseRateDetails = $rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'];
        foreach ($responseRateDetails as $rateDetail) {
            if (
                $this->toggleConfig->getToggleConfigValue('tech_titans_d_216028') &&
                array_key_exists('totalAmount', $rateDetail)
            ) {
                $totalNetAmount += $this->cartDataHelper->formatPrice($rateDetail['totalAmount']);
            } else {
                $totalNetAmount = $this->cartDataHelper->formatPrice($rateDetail['totalAmount']);
            }
        }
        if ($quote->getGrandTotal() != $totalNetAmount) {
            $quote->setGrandTotal($totalNetAmount);
            $quote->setBaseGrandTotal($totalNetAmount);
            $quote->save();

            if (!empty($this->checkoutSession->getRateQuoteResponse())) {
                $this->checkoutSession->unsRateQuoteResponse();
            }

            return true;
        }
    }

    /**
     * Get toggle value for Millionaires - B-2154431: Update Continue Shopping CTA
     *
     * @return boolean
     */
    public function getUpdateContinueShoppingCtaToggle() {
        return $this->cartSummary->getUpdateContinueShoppingCtaToggle();
    }

    /**
     * Get CTA retail/commercial site url for continue shopping button
     *
     * @return string
     */
    public function getAllPrintProductUrl() {
        return $this->cartSummary->getAllPrintProductUrl();
    }

    /**
     * Check if the quote is an EPRO quote
     *
     * @return bool
     */
    public function isEproQuote($quoteId): bool
    {
        $quote = $this->quoteRepository->get($quoteId);
        return (bool)$quote->getIsEproQuote();
    }
}
