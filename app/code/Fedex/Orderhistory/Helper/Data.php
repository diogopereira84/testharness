<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\Cart\ViewModel\CheckoutConfig;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Fedex\Shipment\Helper\Data as ShipmentDataHelper;
use Magento\Customer\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Cart\Controller\Dunc\Index;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    public const TOGGLE_ORDER_STATUS_PENDING_AFTER_FAILURE = 'techtitans_D171230_order_pending_status_after_failure';
    public const TIGER_D219344_ORDER_INFO_ALTERNATE_ADDRESS_FIX = 'tiger_d_219344_order_info_alternate_address_fix';

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var Country
     */
    protected $country;

    /**
     * @var Collection
     */
    private $collectionFactory;
    protected $orderRepository;
    protected $logger;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Fedex\Delivery\Helper\Data $deliveryHelper
     * @param ToggleConfig $toggleConfig
     * @param CheckoutConfig $checkoutConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory
     * @param \Fedex\SSO\ViewModel\SsoConfiguration $ssoConfiguration
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Directory\Model\Country $country
     * @param \Magento\Catalog\Helper\Image $productHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Fedex\SDE\Helper\SdeHelper $sdeHelper
     * @param CollectionFactory $collectionFactory
     * @param ShipmentDataHelper $shipmentDataHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface $orderCollectionFactory
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param SelfReg $selfRegHelper;
     * @param Index $duncCall
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        protected \Fedex\Delivery\Helper\Data $deliveryHelper,
        protected ToggleConfig $toggleConfig,
        protected CheckoutConfig $checkoutConfig,
        private \Magento\Framework\App\Request\Http $request,
        protected \Magento\Customer\Model\Session $customerSession,
        private \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory,
        protected \Fedex\SSO\ViewModel\SsoConfiguration $ssoConfiguration,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Directory\Model\Country $country,
        private \Magento\Catalog\Helper\Image $productHelper,
        protected \Magento\Framework\UrlInterface $urlBuilder,
        protected \Fedex\SDE\Helper\SdeHelper $sdeHelper,
        CollectionFactory $collectionFactory,
        private ShipmentDataHelper $shipmentDataHelper,
        protected \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface $orderCollectionFactory,
        protected ProductRepositoryInterface $productRepositoryInterface,
        private SelfReg $selfRegHelper,
        protected Index $duncCall,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->country = $country;
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function isModuleEnabled()
    {
        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();

        if ($isEproCustomer || $isSelfRegCustomer) {
            return true;
        }

        return false;
    }

    /**
     * Toggle for retail orders listing
     *
     * @return boolean
     */
    public function isRetailOrderHistoryEnabled()
    {
        if ($this->ssoConfiguration->isFclCustomer()) {
            return true;
        }

        return false;
    }

    /**
     * B-1058846 - Print Quote Receipt
     *
     * @codeCoverageIgnore
     */
    public function isModuleEnabledForPrint()
    {
        if ($this->customerSession->getCustomer()->getId() && $this->deliveryHelper->getAssignedCompany()) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * B-900085 Sanchit Bhatia RT-ECVS-ePro - Accessing Order History and Remove left navigation bar
     * Change Layout to 1 Column
     */
    public function isSetOneColumn()
    {
        $isCommercialCustomer = $this->deliveryHelper->isCommercialCustomer();
        $fullaction = $this->request->getFullActionName();
        $actions = [
            'customer_account_index',
            'customer_address_form',
            'customer_address_index',
            'sales_order_history',
            'sales_order_view',
            'customer_account_edit',
            'negotiable_quote_quote_index',
            'negotiable_quote_quote_view',
            'sales_order_print'
        ];

        if (in_array($fullaction, $actions) && $isCommercialCustomer) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * Accessing Order History and Remove left navigation bar
     * Change Layout to 1 Column
     */
    public function isSetOneColumnRetail()
    {
        $isFclCustomer = $this->ssoConfiguration->isFclCustomer();
        $fullaction = $this->request->getFullActionName();
        $actions = [
            'customer_account_index',
            'sales_order_history',
            'sales_order_view',
            'customer_account_edit',
            'negotiable_quote_quote_index',
            'negotiable_quote_quote_view'
        ];

        if (in_array($fullaction, $actions) && $isFclCustomer) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1067059 | code to display purchase order number
     */
    public function getPoNumberFromQuoteIds($quoteIds)
    {
        $poNumbers = [];
        $quotePaymentCollection = $this->quotePaymentFactory->create()
            ->getCollection()
            ->addFieldToFilter('quote_id', ['in' => $quoteIds]);

        if ($quotePaymentCollection->getSize()) {
            foreach ($quotePaymentCollection as $quotePaymentColl) {
                $poNumbers[$quotePaymentColl->getQuoteId()] = $quotePaymentColl->getPoNumber();
            }
        }

        return $poNumbers;
    }

    /**
     * @inheritDoc
     *
     * B-1053021 - Sanchit Bhatia - RT-ECVS - ePro - Search Capability for Quotes
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * @inheritDoc
     *
     * B-1112160 - View Quote Details.
     */
    public function isEnhancementEnabeled()
    {
        if ($this->deliveryHelper->isCommercialCustomer()) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1112160 - View Quote Details.
     * Add Class only for Epro for OH Enhancement
     */
    public function isEnhancementClass()
    {
        $isCommercialCustomer = $this->deliveryHelper->isCommercialCustomer();
        if ($isCommercialCustomer) {
            $fullaction = $this->request->getFullActionName();
            $actions = [
                'customer_account_index',
                'customer_address_form',
                'customer_address_index',
                'sales_order_history',
                'sales_order_view',
                'customer_account_edit',
                'negotiable_quote_quote_index',
                'negotiable_quote_quote_view',
                'sales_order_print'
            ];
            if (in_array($fullaction, $actions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * Retail - B-1140444 RT-ECVS-Ability to expands order detail on click of View Order
     * Add Class only for Retail for OH Enhancement
     */
    public function isRetailEnhancementClass()
    {
        $isFclCustomer = $this->ssoConfiguration->isFclCustomer();

        if ($isFclCustomer) {
            $fullaction = $this->request->getFullActionName();
            $actions = [
                'customer_account_index',
                'sales_order_history',
                'sales_order_view',
                'customer_account_edit',
                'negotiable_quote_quote_index',
                'negotiable_quote_quote_view'
            ];
            if (in_array($fullaction, $actions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1149275 - View Order Receipt - Delivery
     * Get Alternate Address in Order View
     */
    public function getAlternateAddress($quoteId)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);
        $billingAddressData = [];

        if ($quote->getIsAlternate() || $quote->getIsAlternatePickup()) {
            $billingAddress = $quote->getBillingAddress();
            $billingAddressData['name'] = $billingAddress->getFirstname().' '.$billingAddress->getLastname();
            $billingAddressData['email'] = $billingAddress->getEmail();
            $billingAddressData['telephone'] = $billingAddress->getTelephone();
            $billingAddressData['is_alternate_pickup'] = $quote->getIsAlternatePickup() != null
                ? $quote->getIsAlternatePickup() : 0;
            $billingAddressData['is_alternate'] = $quote->getIsAlternate()!=null?$quote->getIsAlternate():0;
        }

        return $billingAddressData;
    }

    /**
     * @inheritDoc
     *
     * B-1149275 - View Order Receipt - Delivery
     * Get Alternate Address in Order View
     */
    public function getContactAddress($quoteId)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);
        $billingAddressData = [];
        $billingAddressData['name'] = $quote->getCustomerFirstname().' '.$quote->getCustomerLastname();
        $billingAddressData['email'] = $quote->getCustomerEmail();
        $billingAddressData['telephone'] = $quote->getCustomerTelephone();

        return $billingAddressData;
    }

    public function getOrderShippingAddress($order)
    {
        $orderShippingAddress = $order->getShippingAddress();

        return [
            'name' => $orderShippingAddress->getFirstname().' '.$orderShippingAddress->getLastname(),
            'email' => $orderShippingAddress->getEmail(),
            'telephone' => $orderShippingAddress->getTelephone()
        ];
    }

    /**
     * @inheritDoc
     *
     * B-1149275 - View Order Receipt - Delivery
     * Get Alternate Address in Order View
     */
    public function getContactAddressForRetail($order)
    {
        $billingAddressData = [];
        $billingAddressData['name'] = $order->getCustomerFirstname().' '.$order->getCustomerLastname();
        $billingAddressData['email'] = $order->getCustomerEmail();
        $billingAddressData['telephone'] = $order->getBillingAddress()->getTelephone();

        return $billingAddressData;
    }

    /**
     * @inheritDoc
     *
     * B-1149275 - View Order Receipt - Delivery
     * Get PO Number in Order View
     */
    public function getPoNumber($quoteId)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);

        return $quote->getPayment()->getPoNumber();
    }

    /**
     * @inheritDoc
     *
     * B-1149275 - View Order Receipt - Delivery
     * Format Address in order view
     */
    public function formatAddress($addressArray)
    {
        $address = null;
        $countryCode = null;

        if (isset($addressArray['address']['countryCode'])) {
            $countryCode = $addressArray['address']['countryCode'];
        }

        if (isset($addressArray['name']) && $addressArray['name']) {
            $address .= $addressArray['name'] . '<br>';
        }

        if (isset($addressArray['address']['street'])) {
            $address = $address . $addressArray['address']['street'] . '<br>';
        }

        if (isset($addressArray['address']['city'])) {
            $address = $address . $addressArray['address']['city'] . ', ';
        }

        $address = $this->addRegionInfoComplexity($address, $addressArray);

        if (isset($addressArray['address']['postalCode'])) {
            $address = $address . $addressArray['address']['postalCode'] . '<br>';
        }

        if ($countryCode) {
            $countryName = $this->country->loadByCode($countryCode)->getName();
            $address = $address . $countryName . '<br><br>';
        }

        if (isset($addressArray['email']) && $addressArray['email']) {
            $address = $address . $addressArray['email']. '<br>';
        }

        if (isset($addressArray['phone']) && $addressArray['phone']) {
            $telephone = substr_replace($addressArray['phone'], '(', 0, 0);
            $telephone = substr_replace($telephone, ')', 4, 0);
            $telephone = substr_replace($telephone, ' ', 5, 0);
            $telephone = substr_replace($telephone, '-', 9, 0);
            $address = $address . $telephone;
        }

        return $address;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore | resize method internally dependent on protected method
     */
    public function addRegionInfoComplexity($address, $addressArray)
    {
        if (isset($addressArray['address']['region']) || isset($addressArray['address']['stateOrProvinceCode'])) {
            if (isset($addressArray['address']['stateOrProvinceCode'])) {
                $address .= $addressArray['address']['stateOrProvinceCode'] . ' ';
            } else {
                $stateName = $addressArray['address']['region'];
                $regionCode = $this->getRegionCode($stateName);
                $address .= $regionCode . ' ';
            }
        }

        return $address;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore | resize method internally dependent on protected method
     */
    public function getItemThumbnailUrl($productObj)
    {
        if (!empty($productObj)) {
            return $this->productHelper->init($productObj, 'product_page_image_small')
              ->setImageFile($productObj->getSmallImage())
              ->keepFrame(false)
              ->resize(140, 160)
              ->getUrl();
        } else {
            return $this->productHelper->getDefaultPlaceholderUrl('thumbnail');
        }
    }

    /**
     * @inheritDoc
     *
     * B-1160912 - Print the screen of view order and view quote via Ctrl+P
     */
    public function enableCtrlPFunctionality()
    {
        $isEnhancementEnabeled = $this->isEnhancementEnabeled();
        if ($isEnhancementEnabeled) {
            $fullaction = $this->request->getFullActionName();
            $actions = ['sales_order_view', 'negotiable_quote_quote_view'];
            if (in_array($fullaction, $actions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1160912 - Print the screen of view order and view quote via Ctrl+P
     */
    public function getPrintUrl()
    {
        $quoteId = $this->request->getParam('quote_id');
        $orderId = $this->request->getParam('order_id');
        if ($quoteId) {
            return $this->urlBuilder->getUrl('negotiable_quote/quote/print', ['quote_id' => $quoteId]);
        } elseif ($orderId) {
            return $this->urlBuilder->getUrl('sales/order/print', ['order_id' => $orderId]);
        }

        return null;
    }

    /**
     * @inheritDoc
     *
     * B-1148619- Print Quote Receipt
     */
    public function getDuncOfficeUrl()
    {
        return $this->checkoutConfig->getDocumentOfficeApiUrl() ?? '';
    }

    /**
     * B-1148619- Print Quote Receipt
     *
     * @codeCoverageIgnore
     */
    public function isEnhancementEnabledForPrint()
    {
        return $this->isModuleEnabledForPrint();
    }

    /**
     * @inheritDoc
     *
     * B-1140448 - View Order Receipt - Delivery
     * Check FCL and retail Or its SDE
     *
     * @return bool
     */
    public function isPrintReceiptRetail()
    {
        $isFclCustomer = $this->ssoConfiguration->isFclCustomer();
        if ($isFclCustomer || $this->getIsSdeStore()) {
            return true;
        }

        return false;
    }

    public function hasAlternateContactInfo($order)
    {
        $quote = $this->quoteFactory->create()->load($order->getQuoteId());
        return $quote->getIsAlternate() || $quote->getIsAlternatePickup();
    }


    private function isAlternateContactInfoConditionMet($order, $shippingAddress, $billingAddress)
    {
        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D219344_ORDER_INFO_ALTERNATE_ADDRESS_FIX)) {
            return $this->hasAlternateContactInfo($order);
        }

        return $shippingAddress->getFirstname() != $billingAddress->getFirstname()
            && $shippingAddress->getLastname() != $billingAddress->getLastname()
            && $shippingAddress->getEmail() != $billingAddress->getEmail();
    }

    /**
     * @inheritDoc
     *
     * B-1149275 - View Order Receipt - Delivery
     * Get Alternate Address in Order View
     */
    public function getAlternateShippingAddress($order)
    {
        $addressData = [];
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        if ($this->isAlternateContactInfoConditionMet($order, $shippingAddress, $billingAddress)) {
            if ($order->getShippingMethod() != 'fedexshipping_PICKUP') {
                $addressData['name'] = $billingAddress->getFirstname() . ' ' .$billingAddress->getLastname();
                $addressData['email'] = $billingAddress->getEmail();
                $addressData['telephone'] = $billingAddress->getTelephone();
                $addressData['is_alternate'] = 1;
            } else {
                $addressData['name'] = $shippingAddress->getFirstname() . ' ' .$shippingAddress->getLastname();
                $addressData['email'] = $shippingAddress->getEmail();
                $addressData['telephone'] = $shippingAddress->getTelephone();
                $addressData['is_alternate_pickup'] = 1;
            }
        }

        return $addressData;
    }

    /**
     * @inheritDoc
     *
     * B-1145903 - Show Order History with only shipped, ready for pickup or delivered
     */
    public function isSDEHomepageEnable()
    {
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        if ($isSdeStore) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1213999 - "View Order" for completed should redirect to Order History with only shipped, ready for
     * pickup, or delivered
     */
    public function isEProHomepageEnable()
    {
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();
        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();

        if ($isSelfRegCustomer || ($isEproCustomer && !$isSdeStore)) {
            return true;
        }

        return false;
    }

    /**
     * Get region code
     *
     * @param string $region
     * @return string
     */
    public function getRegionCode(string $region): string
    {
        $regionCode = $this->collectionFactory->create()->addRegionNameFilter($region)->getFirstItem()->toArray();

        return $regionCode['code'];
    }

    /**
     * Toggle for retail orders History Reorder
     *
     * @return boolean
     */
    public function isRetailOrderHistoryReorderEnabled()
    {
        $isFclCustomer = $this->ssoConfiguration->isFclCustomer();
        if ($isFclCustomer) {
            return true;
        }

        return false;
    }

    /**
     * Toggle for retail orders history item discount
     *
     * @return boolean
     */
    public function isRetailItemDiscountToggle()
    {
        if ($this->ssoConfiguration->isFclCustomer() || $this->getIsSdeStore()) {
            return true;
        }

        return false;
    }

    /**
     * Get quote by id
     *
     * @param int $quoteId
     * @return object
     */
    public function getQuoteById($quoteId)
    {
        return $this->quoteFactory->create()->load($quoteId);
    }

    /**
     * Get pickup estimated date
     *
     * @param int $shipmentId
     * @return string
     */
    public function getShipmentOrderCompletionDate($shipmentId)
    {
        $shipment = $this->shipmentDataHelper->getShipmentById($shipmentId);
        if (!$shipment) {
            return false;
        }

        $shipmentCompleteDate = $shipment->getOrderCompletionDate();

        return $shipmentCompleteDate ?
             date("l, F j", strtotime($shipmentCompleteDate)) .', '
        .strtolower(date("g:ia", strtotime($shipmentCompleteDate)))
            : false;
    }

    /**
     * Check current store is SDE or not
     *
     * @return boolean
     */
    public function getIsSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Check Reorderable or not on view page
     *
     * @param int $orderId
     * @return boolean
     */
    public function isReOrderable($orderId)
    {
        $isReOrderable = false;
        $orderCollection = $this->orderCollectionFactory->create();

        $getCurrentDate = new \DateTime();
        $toDate = $getCurrentDate->format('Y-m-d H:i:s');
        $thirteenMonthToDate = $getCurrentDate->modify('-13 months');
        $fromDate = $thirteenMonthToDate->format('Y-m-d H:i:s');

        $orderCollection->addFieldToFilter('created_at', ['from' => $fromDate, 'to' => $toDate])
        ->addFieldToFilter('entity_id', ['eq' => $orderId]);

        $orderData = $orderCollection->getFirstItem();
        if ($orderData->getDiff() <= 0 && $orderData->getReorderable()) {
            $isReOrderable = true;
        }

        return $isReOrderable;
    }

    /**
     * Toggle for Epro User orders History Reorder
     *
     * @return bool
     */
    public function isCommercialReorderEnabled()
    {
        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();

        if (($isEproCustomer || $isSelfRegCustomer) && !$isSdeStore) {
            return true;
        }

        return false;
    }

    /**
     * Get Product Attribute Set Name
     *
     * @param int $attributeSetId
     * @return string
     */
    public function productAttributeSetName($attributeSetId)
    {
        return $this->deliveryHelper->getProductAttributeName($attributeSetId);
    }

    /**
     * Get Product Custom Attribute Value
     *
     * @param int $productId
     * @param string $customizeValue
     * @return bool
     */
    public function getProductCustomAttributeValue($productId, $customizeValue)
    {
        return $this->deliveryHelper->getProductCustomAttributeValue($productId, $customizeValue);
    }

    /**
     * Get Product Object
     *
     * @param int $productId
     */
    public function loadProductById($productId)
    {
        try {
            $productObject = $this->productRepositoryInterface->getById($productId);
            if ($productObject->getStatus() == 1) {
                return $productObject;
            } else {
                return false;
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Get getQuoteProductImage
     *
     * @return string
     */
    public function getQuoteProductImage($previewId)
    {
        $responseData = false;
        $imgSessionValue = $this->customerSession->getDuncResponse();
        if (is_array($imgSessionValue) && array_key_exists($previewId, $imgSessionValue)) {
            $responseData = "data:image/png;base64,"  . $imgSessionValue[$previewId];
        } elseif (!empty($previewId)) {
            $duncResponse = $this->duncCall->callDuncApi($previewId);
            if (isset($duncResponse['output']['imageByteStream'])) {
                $responseData = "data:image/png;base64, " . $duncResponse['output']['imageByteStream'];
            }
        }

        return $responseData;
    }

     /**
     * @inheritDoc
     *
     * D-156759 - ePro/SDE/Selfreg_Able to Ordered and Processing Status Twice and Thrice in Order Status
     *
     */
    public function isCommercialCustomer()
    {
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();
        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        if ($isSelfRegCustomer || $isEproCustomer || $isSdeStore) {
            return true;
        }

        return false;
    }

    /**
     * Check order has legacy document or not
     * 
     * @param int|string $orderId
     * @return bool|array
     */
    public function checkOrderHasLegacyDocument($orderId): bool|array
    {
        try {
            $order = $this->orderRepository->get($orderId);
            return $this->hasLegacyDocument($order);
        } catch (\Exception $e) {
            $this->logger->error('Exception occurred while removing legacy document from Order 
            history reOrder section for order id : ' . $orderId . $e->getMessage());
            return false; 
        }
    }

        /**
     * Check which order items have a legacy document
     *
     * @param mixed $order
     * @return array
     */
    private function hasLegacyDocument($order): array
    {
        $legacyItems = [];

        foreach ($order->getItems() as $item) {
            $productOptions = $item->getProductOptions();
            $legacyItems[$item->getId()] = false; // Default to false

            if (isset($productOptions['info_buyRequest']['external_prod']) &&
            $this->checkContentAssociations($productOptions['info_buyRequest']['external_prod'])) {
              $legacyItems[$item->getId()] = true;
            }
        }
        return $legacyItems;
    }

    /**
     * Check the external product content associations
     * 
     * @param mixed $externalProducts
     * @return bool
     */
    private function checkContentAssociations($externalProducts): bool
    {
        foreach ($externalProducts as $product) {
            if (isset($product['contentAssociations'])) {
                foreach ($product['contentAssociations'] as $content) {
                    if (isset($content['contentReference']) && is_numeric($content['contentReference'])) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     *
     * D-233959
     * Get Contact Address in Order History isD233959Enabled
     */
    public function getContactAddressForOrder($order)
    {
        $billingAddress = $order->getBillingAddress();
       
        // Break up name formatting into separate steps for clarity
        $fullName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        $trimmedName = trim($fullName);
        $lowercaseName = strtolower($trimmedName);
        $formattedName = ucwords($lowercaseName);

        return [
            'name' => $formattedName,
            'email' => $order->getCustomerEmail(),
            'telephone' => $billingAddress ? $billingAddress->getTelephone() : ''
        ];
    }
}
