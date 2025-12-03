<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\ViewModel;

use Exception;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Catalog\Helper\Image;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Customer\Model\Session;
use Fedex\Cart\Controller\Dunc\Index;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Cart\ViewModel\CheckoutConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Mirakl\Api\Helper\Order as MiraklHelper;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Mirakl\Connector\Helper\Order as MiraklOrderHelper;
use Fedex\MarketplaceRates\Helper\Data as RateHelper;
use Fedex\Shipment\Model\ShipmentFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper as OrderApprovalAdminConfigHelper;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Fedex\Cart\Helper\Data as CartHelper;
use Magento\Quote\Api\CartRepositoryInterface as CartRepositoryInterface;
use Fedex\InstoreConfigurations\Api\ConfigInterface;

/**
 *
 */
class OrderHistoryEnhacement implements ArgumentInterface
{
    const SHIPPING_TYPE_PICKUP = 'fedexshipping_PICKUP';

    public const RECIPIENT_EMAILS_ALLOW_LIMIT = 'fedex/recipient_email_address_limit/set_recipient_email_address_limit';

    const CUSTOM_ATTRIBUTE_EMAIL_ID_CODE = 'email_id';

    private const TOGGLE_MARKETPLACE_COMMERCIAL = 'tiger_tk_410245';

    //Millionaires - E-398131 : Adoption of New Document Platform
    public const NEW_DOCUMENT_API_IMAGE_PREVIEW_TOGGLE = 'new_documents_api_image_preview_toggle';
    public const TECH_TITANS_B_2041921 = 'tech_titans_B2041921_detected_on_fxo_ecommerce_platform';
    public const TIGER_D190906 = 'tiger_d190906';
    public const TIGER_D214230 = 'tiger_d214230';
    public const LEGACY_DOCUMENT_REORDER_SECTION = 'techtitans_B2353508_legacy_document_items_not_reorderable';
    public const TECH_TITANS_D_233959 = 'tech_titans_d_233959';
    public const TIGER_D219344_ORDER_INFO_ALTERNATE_ADDRESS_FIX = 'tiger_d_219344_order_info_alternate_address_fix';
    public const TIGER_SUBTOTAL_TK_4668950 = 'tiger_subtotal_TK4668950';

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Image $imageHelper
     * @param OrderHistoryHelper $orderHistoryhelper
     * @param SdeHelper $sdeHelper
     * @param SelfReg $selfRegHelper
     * @param Session $customerSession
     * @param Index $duncCall
     * @param ToggleConfig $toggleConfig
     * @param MiraklHelper $miraklHelper
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param CheckoutConfig $checkoutConfig
     * @param CollectionFactory $itemCollectionFactory
     * @param Json $jsonSerializer
     * @param OrderDetailsDataMapper $orderDetailsDataMapper
     * @param MiraklOrderHelper $miraklOrderHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param RateHelper $helperRate
     * @param ShipmentFactory $shipmentStatusFactory
     * @param RedirectInterface $redirectInterface
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper
     * @param ShopManagement $shopManagement
     * @param Data $helper
     * @param \Magento\Framework\UrlInterface $_urlBuilder
     * @param HandleMktCheckout $handleMktCheckout
     * @param MarketplaceConfig $config
     * @param PriceHelper $priceHelper
     * @param CartHelper $cartHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param ConfigInterface $instoreConfig
     */
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected Image $imageHelper,
        protected OrderHistoryHelper $orderHistoryhelper,
        protected SdeHelper $sdeHelper,
        protected SelfReg $selfRegHelper,
        protected Session $customerSession,
        protected Index $duncCall,
        protected ToggleConfig $toggleConfig,
        protected MiraklHelper $miraklHelper,
        protected ScopeConfigInterface $scopeConfigInterface,
        protected CheckoutConfig $checkoutConfig,
        private CollectionFactory $itemCollectionFactory,
        protected Json $jsonSerializer,
        private OrderDetailsDataMapper $orderDetailsDataMapper,
        private readonly MiraklOrderHelper $miraklOrderHelper,
        private readonly DataObjectFactory $dataObjectFactory,
        private RateHelper $helperRate,
        private ShipmentFactory $shipmentStatusFactory,
        protected RedirectInterface $redirectInterface,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        protected OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper,
        private ShopManagement $shopManagement,
        private Data $helper,
        private  \Magento\Framework\UrlInterface $_urlBuilder,
        readonly private HandleMktCheckout  $handleMktCheckout,
        private MarketplaceConfig $config,
        private PriceHelper $priceHelper,
        private CartHelper $cartHelper,
        private CartRepositoryInterface $quoteRepository,
        private readonly ConfigInterface $instoreConfig
    ) {
    }

    /**
     * Is terms and conditions enabled
     *
     * @return bool
     */
    public function isTermsAndConditionsEnabled()
    {
        return (bool) $this->checkoutConfig->isTermsAndConditionsEnabled();
    }

    /**
     * Gets status of enable customer shipping account for third party products
     * @return bool
     */
    public function isCustomerShippingAccount3PEnabled(): bool
    {
        return $this->helper->isCustomerShippingAccount3PEnabled();
    }

    /**
     * Is reorder enabled
     *
     * @return bool
     */
    public function isReorderEnabled()
    {
        return (bool) $this->checkoutConfig->isReorderEnabled();
    }

    /**
     * @inheritDoc
     */
    public function getItemsByOrderId($orderId)
    {
        $orderObj = $this->orderRepository->get($orderId);
        if($this->helper->isEssendantToggleEnabled()){
            return $orderObj->getAllVisibleItems();
        }
        return $orderObj->getAllItems();
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore | resize method internally dependent on protected method
     */
    public function getItemThumbnailUrl($productObj)
    {
        if (!empty($productObj)) {
            return $this->imageHelper->init($productObj, 'product_page_image_small')
                ->setImageFile($productObj->getSmallImage())
                ->keepFrame(false)
                ->resize(140, 160)
                ->getUrl();
        } else {
            return $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
        }
    }
    public function isTokenizedUrlToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B_2041921);
    }

    /**
     * Retrieve url with token
     * @param $order
     * @return string
     */
    public function getViewUrl($order): string
    {
        $route = 'sales/order/view';
        if (!$order->getCustomerId()) {
            return $this->_urlBuilder->getUrl($route, ['order_id' => $order->getId()]);
        }

        $token = $this->generateSecureToken($order);
        $url = $this->_urlBuilder->getUrl($route, ['_secure' => true]);
        return $url . '?key=' . $token;
    }

    private function generateSecureToken($order)
    {
        $data = json_encode(['order_id' => $order->getId()]);
        return base64_encode($this->encryptData($data, (int)$this->getCustomerSession()->getId()));
    }

    private function encryptData($data, $key)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encryptedData);
    }

    /**
     * Get if current store is SDE store
     *
     * @return bool
     */
    public function isSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Get current logged in customer id from session
     *
     * @return int|string|null
     */
    public function getCurrentCustomerIdFromSession()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * Check If Last Page Url is Shared Order Listing Page
     *
     * @return bool
     */
    public function isLastPageUrlSharedOrderListing()
    {
        $lastPageUrl = $this->redirectInterface->getRefererUrl();
        if (strpos($lastPageUrl, 'shared/order/history') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isModuleEnabled()
    {
        return $this->orderHistoryhelper->isModuleEnabled();
    }

    /**
     * @inheritDoc
     */
    public function isEnhancementEnabeled()
    {
        return $this->orderHistoryhelper->isEnhancementEnabeled();
    }

    /**
     * @inheritDoc
     * B-1501794
     */
    public function isSelfRegCompany()
    {
        return $this->selfRegHelper->isSelfRegCompany();
    }

    /**
     * @inheritDoc
     */
    public function isModuleEnabledForPrint()
    {
        return $this->orderHistoryhelper->isModuleEnabledForPrint();
    }

    /**
     * @inheritDoc
     */
    public function isPrintReceiptRetail()
    {
        return $this->orderHistoryhelper->isPrintReceiptRetail();
    }

    /**
     * Get image path for masking SDE product image
     *
     * @return string|boolean
     */
    public function getSdeMaskSecureImagePath()
    {
        return $this->sdeHelper->getSdeMaskSecureImagePath();
    }

    /**
     * Get Shipment Order Complete date
     *
     * @param int $shipmentId
     * @return mixed
     */
    public function shipmentOrderCompletionDate($shipmentId)
    {
        return $this->orderHistoryhelper->getShipmentOrderCompletionDate($shipmentId);
    }

    /**
     * Get Contact Address
     *
     * @param object $order
     */
    public function getContactAddress($order)
    {
        if ($this->isD219344OrderInfoAlternateAddressFixEnabled()) {
            return $this->getContactAddressWithAlternateConditionCheck($order);
        }

        return $this->orderHistoryhelper->getContactAddress($order->getQuoteId());
    }

    public function getContactAddressWithAlternateConditionCheck($order)
    {
        $toggle1 = $this->isD233959Enabled();
        $toggle2 = $this->isD219344OrderInfoAlternateAddressFixEnabled();

        if (!$toggle1 || !$toggle2) {
            return $this->orderHistoryhelper->getContactAddress($order->getQuoteId());
        }

        $quoteId = $order->getQuoteId();

        try {
            $quote = $this->quoteRepository->get($quoteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->orderHistoryhelper->getContactAddressForOrder($order);
        }

        if ($quote->getData('is_alternate')) {
            return $this->orderHistoryhelper->getOrderShippingAddress($order);
        }

        return $this->orderHistoryhelper->getContactAddressForOrder($order);
    }

    /**
     * Get Contact Address
     *
     * @param int $order
     */
    public function getContactAddressForRetail($order)
    {
        if ($this->isD219344OrderInfoAlternateAddressFixEnabled()) {
            return $this->getContactAddressWithAlternateConditionCheck($order);
        }

        return $this->orderHistoryhelper->getContactAddressForRetail($order);
    }

    /**
     * Get PO Number
     *
     * @param int $quoteId
     */
    public function getPoNumber($quoteId)
    {
        return $this->orderHistoryhelper->getPoNumber($quoteId);
    }

    /**
     * Get Alternate Address
     *
     * @param object $order
     */
    public function getAlternateAddress($order)
    {
        if ($this->isD219344OrderInfoAlternateAddressFixEnabled()) {
            return $this->getAlternateShippingAddress($order);
        }

        return $this->orderHistoryhelper->getAlternateAddress($order->getQuoteId());
    }

    /**
     * Get Alternate Shipping Address
     *
     * @param object $order
     */
    public function getAlternateShippingAddress($order)
    {
        return $this->orderHistoryhelper->getAlternateShippingAddress($order);
    }

    /**
     * Is Reorderable
     *
     * @param int $orderId
     */
    public function isReOrderable($orderId)
    {
        return $this->orderHistoryhelper->isReOrderable($orderId);
    }

    /**
     * @inheritDoc
     */
    public function isRetailOrderHistoryEnabled()
    {
        return $this->orderHistoryhelper->isRetailOrderHistoryEnabled();
    }

    /**
     * @inheritDoc
     */
    public function isRetailOrderHistoryReorderEnabled()
    {
        return $this->orderHistoryhelper->isRetailOrderHistoryReorderEnabled();
    }

    /**
     * @inheritDoc
     */
    public function isCommercialOrderHistoryReorderEnabled()
    {
        return $this->orderHistoryhelper->isCommercialReorderEnabled();
    }

    /**
     * Get Product Attribute Set Name
     *
     * @param int $attributeSetId
     * @return string
     */
    public function getProductAttributeSetName($attributeSetId)
    {
        return $this->orderHistoryhelper->productAttributeSetName($attributeSetId);
    }

    /**
     * Get Product Custom Attribute Value
     *
     * @param int $productId
     * @param string $customizeValue
     * @return boolean
     */
    public function getProductCustomAttributeValue($productId, $customizeValue)
    {
        return $this->orderHistoryhelper->getProductCustomAttributeValue($productId, $customizeValue);
    }

    /**
     * Get Product Custom Attribute Value
     *
     * @param int $productId
     */
    public function loadProductObj($productId)
    {
        return $this->orderHistoryhelper->loadProductById($productId);
    }

    /**
     * Get Current Customer Session
     *
     * @return \Magento\Customer\Model\Session
     */
    public function getCustomerSession()
    {
        return $this->orderHistoryhelper->getCustomerSession();
    }

    /**
     * Order product option to get prepare serialize Product Data
     *
     * @param array $infoBuyRequestData
     * @return array
     */
    public function serializeProductData($infoBuyRequestData)
    {
        $externalProductData = $infoBuyRequestData['external_prod'][0] ?? [];

        if (isset($infoBuyRequestData['fxoMenuId'])) {
            $serializedProductData = [];
            $serializedProductData['userProductName'] = $externalProductData['userProductName'] ?? '';
            $serializedProductData['id'] = $externalProductData['id'] ?? null;
            $serializedProductData['productionContentAssociations'] =
            $externalProductData['productionContentAssociations'] ?? [];
            $serializedProductData['version'] = $externalProductData['version'] ?? null;
            $serializedProductData['name'] = $externalProductData['name'] ?? '';
            $serializedProductData['qty'] = $externalProductData['qty'] ?? null;
            $serializedProductData['priceable'] = $externalProductData['priceable'] ?? false;
            $serializedProductData['instanceId'] = $externalProductData['instanceId'] ?? null;
            $serializedProductData['proofRequired'] = $externalProductData['proofRequired'] ?? false;
            $serializedProductData['isOutSourced'] = $externalProductData['isOutSourced'] ?? false;
            $serializedProductData['features'] = $externalProductData['features'] ?? [];
            $serializedProductData['pageExceptions'] = $externalProductData['pageExceptions'] ?? [];
            $serializedProductData['contentAssociations'] = $externalProductData['contentAssociations'] ?? [];
            $serializedProductData['properties'] = $externalProductData['properties'] ?? [];
            $productConfig = $infoBuyRequestData['productConfig'] ?? [];
            $productPresetId = $productConfig['productPresetId'] ?? null;
        } else {
            $fxoProductData = $externalProductData["fxo_product"] ?? '';
            $fxoProductData = json_decode($fxoProductData, true);
            $serializedProductData = [];
            $serializedProductData['userProductName'] = $externalProductData['userProductName'] ?? '';
            $serializedProductData['id'] = $externalProductData['id'] ?? '';
            $serializedProductData['productionContentAssociations'] =
            $externalProductData['productionContentAssociations'] ?? '';
            $serializedProductData['version'] = $externalProductData['version'] ?? null;
            $serializedProductData['name'] = $externalProductData['name'] ?? '';
            $serializedProductData['qty'] = $externalProductData['qty'] ?? null;
            $serializedProductData['priceable'] = $externalProductData['priceable'] ?? false;
            $serializedProductData['instanceId'] = $externalProductData['instanceId'] ?? '';
            $serializedProductData['proofRequired'] = $externalProductData['proofRequired'] ?? false;
            $serializedProductData['isOutSourced'] = $externalProductData['isOutSourced'] ?? false;
            $serializedProductData['features'] = $externalProductData['features'] ?? [];
            $serializedProductData['pageExceptions'] = $externalProductData['pageExceptions'] ?? [];
            $serializedProductData['contentAssociations'] = $externalProductData['contentAssociations'] ?? [];
            $serializedProductData['properties'] = $externalProductData['properties'] ?? [];
            $productPresetId = $fxoProductData ?
                isset($fxoProductData['fxoProductInstance']['productConfig']['productPresetId']) : null;
        }

        return [
            'serializedProductData' => $serializedProductData,
            'productPresetId' => $productPresetId
        ];
    }

    /**
     * Get Sorted Discounts
     *
     * @return array
     */
    public function getSortedDiscounts($data)
    {
        usort($data, fn ($a, $b) => $b['price'] <=> $a['price']);

        return $data;
    }

    /**
     * Get Sorted Totals
     *
     * @return array
     */
    public function getSortedTotals($totals)
    {
        $sortedTotals = $totals;
        if (isset($totals['discount'])) {
            $order = ['subtotal', 'shipping', 'tax', 'discount','grand_total'];
            $sortedTotals = array_merge(array_flip($order), $totals);
        }

        return $sortedTotals;
    }

    public function isMarketplaceCommercialToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::TOGGLE_MARKETPLACE_COMMERCIAL);
    }

    /**
     * Get getProductImage
     *
     * @return string
     */
    public function getProductImage($previewId, $newDocumentImage = 0)
    {
        $responseData = false;

        /*B-2353185 Document API On Order History Page*/
        if ($this->cartHelper->isRemoveBase64ImageToggleEnabled()) {
            $responseData = $this->getDocumentImageApiUrl($previewId);
        } else {
            $imgSessionValue = $this->customerSession->getDuncResponse();
            if (is_array($imgSessionValue) && array_key_exists($previewId, $imgSessionValue)) {
                $responseData = "data:image/png;base64,"  . $imgSessionValue[$previewId];
            } elseif (!empty($previewId)) {
                if ($newDocumentImage) {
                    $imageData = $this->catalogDocumentRefranceApi->curlCallForPreviewApi($previewId);
                    $base64ImageSrc = base64_encode($imageData);
                    $imageResponse = [];
                    $imageResponse['output']['imageByteStream']= $base64ImageSrc;
                } else {
                    $imageResponse = $this->duncCall->callDuncApi($previewId);
                }
                if (isset($imageResponse['output']['imageByteStream'])) {
                    $responseData = "data:image/png;base64, " . $imageResponse['output']['imageByteStream'];
                }
            }
        }

        return $responseData;
    }

    /**
     * Get Documnent Image API URL
     *
     * @return string|false The document image URL if available, otherwise false
     */
    public function getDocumentImageApiUrl($previewId)
    {
        $documentImageApiUrl = $this->checkoutConfig->getDocumentImagePreviewUrl();
        if ($documentImageApiUrl) {
            $documentImageApiUrl .= "v2/documents/" . $previewId . "/previewpages/1?zoomFactor=2&ClientName=POD2.0";
            return $documentImageApiUrl;
        }

        return false;
    }

    /**
     * Get Selected Shipping Medthod Name
     *
     * @param string $shippingDescription
     * @return string
     */
    public function getSelectedShippingMethodName($shippingDescription)
    {
        $shippingName = '';
        if (!empty($shippingDescription) && substr_count($shippingDescription, '-') == 1) {
            $shippingDescriptionArray = explode("-", $shippingDescription);
            $shippingName = trim($shippingDescriptionArray[0]);
        } else {
            $shippingDescriptionArray = explode("-", $shippingDescription);
            $shippingName = trim($shippingDescriptionArray[1]);
        }

        return $shippingName;
    }

    /**
     * Get Selected Shipping Medthod Date
     *
     * @param string $shippingDescription
     * @return string
     */
    public function getSelectedShippingMethodDate($shippingDescription)
    {
        $shippingDate = '';

        if (!empty($shippingDescription)) {
            $shippingDescriptionArray = explode('-', $shippingDescription);
            if (substr_count($shippingDescription, '-') == 1 && substr_count($shippingDescription, ',') == 2) {
                $shippingDate = trim($shippingDescriptionArray[1]);
            } elseif (substr_count($shippingDescription, '-') == 2 && substr_count($shippingDescription, ',') == 2) {
                $shippingDate = trim($shippingDescriptionArray[2]);
            } elseif (substr_count($shippingDescription, '-') == 1 && substr_count($shippingDescription, ',') == 1) {
                $shipDate = trim($shippingDescriptionArray[1]);
                $shippingDate = $this->getShippingDate($shipDate);
            } elseif (substr_count($shippingDescription, '-') == 2 && substr_count($shippingDescription, ',') == 1) {
                $shipDate = trim($shippingDescriptionArray[2]);
                $shippingDate = $this->getShippingDate($shipDate);
            } else {
                $shippingDate = trim($shippingDescriptionArray[1]);
            }
        }

        return $shippingDate;
    }

    /**
     * Get Shipping Date
     *
     * @param string $shipDate
     * @return string
     */
    public function getShippingDate($shipDate)
    {
        if (str_contains($shipDate, 'End')) {
            $shipDateArray = explode(' ', $shipDate);
            $shippingDate = $shipDateArray[0].' '.$shipDateArray[1].' '.$shipDateArray[2].' '
            .$shipDateArray[3].' '.$shipDateArray[4].' '.$shipDateArray[5];
        } else {
            $shipDateArray = explode(' ', $shipDate);
            $shippingDate = $shipDateArray[0].' '.$shipDateArray[1].' '.$shipDateArray[2].', '
            .$shipDateArray[3].strtolower($shipDateArray[4]);
        }

        return $shippingDate;
    }

    /**
     * @param Order $order
     * @return int
     */
    public function getMiraklItemsCount($order)
    {
        $miraklItemCount = 0;
        foreach ($order->getItems() as $item) {
            if ($item->getData('mirakl_offer_id')) {
                $miraklItemCount++;
            }
        }

        return $miraklItemCount;
    }

    /**
     * @param $commercialId
     * @param string $productSku
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMiraklOrderStatus($commercialId, string $productSku = null, $offerId = null)
    {
        $status = "";

        $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($productSku);
        $orders = $this->miraklHelper->getOrders(
            [
                'commercial_ids' => $commercialId,
                'shop_ids' => $shopCustomAttributes['shop_id'],
                'offer_ids' => $offerId,
            ]
        );

        if (!empty($orders)) {
            foreach ($orders as $order) {
                /** @var MiraklOrder $order */
                $status = $order->getStatus()->getState();
                break;
            }
        }

        return $status;
    }

    /**
     * Configuration Track Order URL
     *
     * @return String
     */
    public function getTrackOrderUrl()
    {
        return $this->scopeConfigInterface->getValue("fedex/general/track_order_url", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return int
     */
    public function getRecipientEmailAddressLimit(): int
    {
        return (int) $this->scopeConfigInterface->getValue(self::RECIPIENT_EMAILS_ALLOW_LIMIT);
    }

    /**
     * @param $shippingMethod
     * @return string
     */
    public function formatShippingMethodName($shippingMethod): string
    {
        if (str_contains($shippingMethod, '_')) {
            $words = explode("_", strtolower($shippingMethod));
        } else {
            $words = explode(" ", strtolower($shippingMethod));
        }
        array_shift($words);
        $formattedWords = array_map(function ($word) {
            return ucfirst($word);
        }, $words);

        array_unshift($formattedWords, 'FedEx');

        return implode(" ", $formattedWords);
    }

    /**
     * @param $order
     * @return string
     */
    public function getOrderShippingDescription($order)
    {
        $shippingDescription = "";
        $isPickUp = ($order->getShippingMethod() == self::SHIPPING_TYPE_PICKUP) ? 1 : 0;
        if ($isPickUp) {
            $shipmentId = $order->getShipmentsCollection()->getFirstItem()->getId();
            $shippingDescription = $this->shipmentOrderCompletionDate($shipmentId);
            if (!$shippingDescription) {
                $shippingDescription = $order->getEstimatedPickupTime();
            }
        } else {
            if (!empty($order->getShippingDescription())) {
                $shippingDescription = explode('-', $order->getShippingDescription());
                $shippingDescription = end($shippingDescription) ?? null;
            }
        }
        return $shippingDescription;
    }

    /*
     * Get estimated delivery for 3P products
     */
    /**
     * @param $order
     * @return mixed|string
     */
    public function getEstimatedDeliveryThirdParty($order, $item = null)
    {
        $jsonColumn = 'additional_data';
        $searchElement = 'deliveryDate';
        $maxDeliveryDate = '';
        $maxDeliveryDateToDisplay = '';

        if ($item) {
            $jsonValue = $this->jsonSerializer->unserialize($item->getData($jsonColumn));
            if (isset($jsonValue['mirakl_shipping_data'][$searchElement])) {
                $maxDeliveryDateToDisplay = $jsonValue['mirakl_shipping_data'][$searchElement];
            }
        } else {
            $orderItemCollection = $this->getOrderItems($order, $jsonColumn, $searchElement);
            if (!empty($orderItemCollection)) {
                foreach ($orderItemCollection as $item) {
                    $jsonValue = $this->jsonSerializer->unserialize($item->getData($jsonColumn));
                    if (isset($jsonValue['mirakl_shipping_data'][$searchElement])) {
                        $deliveryDate = strtotime($jsonValue['mirakl_shipping_data'][$searchElement]);
                        if ($deliveryDate > $maxDeliveryDate) {
                            $maxDeliveryDate = $deliveryDate;
                            $maxDeliveryDateToDisplay = $jsonValue['mirakl_shipping_data'][$searchElement];
                        }
                    }
                }
            }
        }
        return $maxDeliveryDateToDisplay;
    }

    /**
     * @param $item
     * @return mixed|string
     */
    public function getStrtotimeFromItem($item)
    {
        $jsonColumn = 'additional_data';
        $searchElement = 'deliveryDate';

        $jsonValue = $this->jsonSerializer->unserialize($item->getData($jsonColumn));
        if (isset($jsonValue['mirakl_shipping_data'][$searchElement])) {
            return strtotime($jsonValue['mirakl_shipping_data'][$searchElement]);
        }
        return false;
    }

    /**
     * @param $items
     * @return mixed|string
     */
    public function getEstimatedDeliveryThirdPartyBYOrderItems($items)
    {
        $jsonColumn = 'additional_data';
        $searchElement = 'deliveryDate';
        $maxDeliveryDate = '';
        $maxDeliveryDateToDisplay = '';

        $orderItemCollection = $this->getOrderItemCollectionBYItemID($items, $jsonColumn, $searchElement);
        if (!empty($orderItemCollection)) {
            foreach ($orderItemCollection as $item) {
                $jsonValue = $this->jsonSerializer->unserialize($item->getData($jsonColumn));
                if (isset($jsonValue['mirakl_shipping_data'][$searchElement])) {
                    $deliveryDate = strtotime($jsonValue['mirakl_shipping_data'][$searchElement]);
                    if ($deliveryDate > $maxDeliveryDate) {
                        $maxDeliveryDate = $deliveryDate;
                        $maxDeliveryDateToDisplay = $jsonValue['mirakl_shipping_data'][$searchElement];
                    }
                }
            }
        }
        return $maxDeliveryDateToDisplay;
    }

    /**
     * @param $order
     * @param $jsonColumn
     * @param $searchElement
     * @return Collection
     */
    public function getOrderItems($order, $jsonColumn, $searchElement)
    {
        $orderItemCollection = $this->itemCollectionFactory->create();
        $orderItemCollection->addFieldToFilter('order_id', $order->getId());
        $orderItemCollection->addFieldToFilter('mirakl_offer_id', ['notnull' => true]);
        $orderItemCollection->addFieldToFilter(
            $jsonColumn,
            ['like' => '%' . $searchElement . '%']
        );


        return $orderItemCollection;
    }

    /**
     * @param $items
     * @param $jsonColumn
     * @param $searchElement
     * @return Collection
     */
    public function getOrderItemCollectionBYItemID($items, $jsonColumn, $searchElement)
    {
        $itemId=[];
        $orderItemCollection='';
        foreach($items as $item){
            $itemId[]=$item->getId();
        }
        if(!empty($itemId)){
            $orderItemCollection = $this->itemCollectionFactory->create();
            $orderItemCollection->addFieldToFilter('item_id', ['in'=>$itemId]);
            $orderItemCollection->addFieldToFilter('mirakl_offer_id', ['notnull' => true]);
            $orderItemCollection->addFieldToFilter(
                $jsonColumn,
                ['like' => '%' . $searchElement . '%']
            );
        }
        return $orderItemCollection;
    }

    /*
     * Get shipping total from order
     * @param $order
     * @return int
     */
    /**
     * @param $order
     * @return mixed
     */
    public function getOrderShippingTotal($order)
    {
        $shippingTotal = $order->getShippingAmount() + $this->helperRate->getMktShippingTotalAmount($order);
        return $order->formatPrice($shippingTotal);
    }

    /**
     * @param $order
     * @return mixed
     */
    public function getOrderShippingSubTotal($order)
    {
        return $order->getShippingAmount() + $this->helperRate->getMktShippingTotalAmount($order);
    }

    /**
     * @param $order
     * @return mixed
     */
    public function getMiraklShippingAmount($order)
    {
        $shippingTotal = $this->helperRate->getMktShippingTotalAmount($order);
        return $order->formatPrice($shippingTotal);
    }

    /**
     * @param $order
     * @return mixed
     */
    public function getMiraklShippingTotal($order)
    {
        return $this->helperRate->getMktShippingTotalAmount($order);
    }

    /**
     * Get order item status
     * @param $orderItem
     * @param $order
     * @return string
     */
    public function getOrderItemStatus($orderItem, $order): string
    {
        $orderItemStatus = $orderItem->getStatus();
        $isOrderDelated = $this->isOrderDelayed($order);
        return $this->getStatusLabelMapping($orderItemStatus, $isOrderDelated);
    }

    /**
     * @param $order
     * @return bool
     */
    public function isOrderDelayed($order)
    {
        return $this->orderDetailsDataMapper->isOrderDelayed($order, $order->getIncrementid());
    }

    /**
     * @param $orderItemStatus
     * @param $delayedFlag
     * @return mixed
     */
    public function getStatusLabelMapping($orderItemStatus, $delayedFlag=false)
    {
        return match ($orderItemStatus) {
            $this->orderDetailsDataMapper::CHECK_NEW =>
            $this->orderDetailsDataMapper::STATUS_ORDERED,

            $this->orderDetailsDataMapper::CHECK_CANCELLED =>
            $this->orderDetailsDataMapper::STATUS_CANCELED,

            $this->orderDetailsDataMapper::CHECK_IN_PROGRESS,
            $this->orderDetailsDataMapper::CHECK_CONFIRMED =>
            $delayedFlag
                ? $this->orderDetailsDataMapper::STATUS_DELAY
                : $this->orderDetailsDataMapper::STATUS_PROCESSING,

            $this->orderDetailsDataMapper::CHECK_DELIVERED,
            $this->orderDetailsDataMapper::CHECK_SHIPPED =>
            $this->orderDetailsDataMapper::STATUS_SHIPPED,

            $this->orderDetailsDataMapper::CHECK_READY_FOR_PICKUP =>
            $this->orderDetailsDataMapper::STATUS_READY_FOR_PICKUP,
            $this->orderDetailsDataMapper::CHECK_COMPLETE =>
            $this->orderDetailsDataMapper::STATUS_COMPLETE,

            $this->orderDetailsDataMapper::CHECK_DECLINED =>
            $this->orderDetailsDataMapper::STATUS_DECLINED,

            $this->orderDetailsDataMapper::CHECK_PENDING_APPROVAL =>
            $this->orderDetailsDataMapper::STATUS_PENDING_APPROVAL,

            default =>
            $this->orderDetailsDataMapper::STATUS_PROCESSING,
        };
    }

    /**
     * Update the status label mapping for orders
     * @param $statusLabel
     * @return string
     */
    public function orderStatusLabel($statusLabel)
    {
        return match(strtolower($statusLabel)) {
            'new' => "ordered",
            'confirmed' => "processing",
            'assigned' => "ordered",
            'in progress' => "processing",
            default => $statusLabel
        };
    }

    /**
     * Status Mapping for 3P items
     * @param $statusLabel
     * @return string
     */
    public function orderLineItem3P($statusLabel)
    {
        $statusLabel = strtolower($statusLabel);

        return match($statusLabel) {
            $this->orderDetailsDataMapper::CHECK_STATUS_STAGING,
            $this->orderDetailsDataMapper::CHECK_WAITING_DEBIT,
            $this->orderDetailsDataMapper::CHECK_WAITING_DEBIT_PAYMENT,
            $this->orderDetailsDataMapper::CHECK_SHIPPING => $this->orderDetailsDataMapper::STATUS_PROCESSING,
            $this->orderDetailsDataMapper::CHECK_SHIPPED  => $this->orderDetailsDataMapper::STATUS_SHIPPED,
            $this->orderDetailsDataMapper::CHECK_TO_COLLECT,
            $this->orderDetailsDataMapper::CHECK_RECEIVED,
            $this->orderDetailsDataMapper::CHECK_CLOSED,
            $this->orderDetailsDataMapper::CHECK_REFUSED,
            $this->orderDetailsDataMapper::CHECK_INCIDENT_OPEN,
            $this->orderDetailsDataMapper::CHECK_WAITING_REFUND,
            $this->orderDetailsDataMapper::CHECK_WAITING_REFUND_PAYMENT,
            $this->orderDetailsDataMapper::CHECK_REFUNDED => $this->orderDetailsDataMapper::STATUS_SHIPPED,
            $this->orderDetailsDataMapper::CHECK_CANCELED => $this->orderDetailsDataMapper::STATUS_CANCELED,
            default => $this->orderDetailsDataMapper::STATUS_ORDERED
        };
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function isMixedOrder(Order $order): bool
    {

        return $this->miraklOrderHelper->isMiraklOrder($order) && !$this->miraklOrderHelper->isFullMiraklOrder($order);
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function isPickupOrder(Order $order): bool
    {
        return ($order->getShippingMethod() === self::SHIPPING_TYPE_PICKUP) &&
            !$this->isMixedOrder($order);
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getMixedOrderShippingAddress(Order $order): string
    {
        if ($order->getShippingMethod() !== self::SHIPPING_TYPE_PICKUP) {
            $miraklShippingAddress = $order->getShippingAddress();
        } else {
            $orderItemCollection = $this->getOrderedMiralkItemsCollection($order);
            $miraklShippingAddress = $this->getMiraklShippingAddress($orderItemCollection);
        }
        return $this->getFormattedMiraklShippingAddress($miraklShippingAddress);
    }

    /**
     * @param Order $order
     * @return Collection
     */
    private function getOrderedMiralkItemsCollection(Order $order): Collection
    {
        $orderItemCollection = $this->itemCollectionFactory->create();
        $orderItemCollection->addFieldToFilter('order_id', $order->getId());
        $orderItemCollection->addFieldToFilter('mirakl_offer_id', ['notnull' => true]);
        $orderItemCollection->getItems();
        return $orderItemCollection;
    }

    /**
     * @param Collection $orderItemCollection
     * @return DataObject
     */
    private function getMiraklShippingAddress(Collection $orderItemCollection): DataObject
    {
        $miraklShippingAddressDO = $this->dataObjectFactory->create();
        foreach ($orderItemCollection as $orderItem) {
            $additionalDataAsObject = json_decode($orderItem->getAdditionalData());
            if (!property_exists($additionalDataAsObject, 'mirakl_shipping_data')) {
                continue;
            }
            try {
                $miraklShippingAddress = $additionalDataAsObject->mirakl_shipping_data->address;
                foreach ($miraklShippingAddress as $key => $value) {
                    $miraklShippingAddressDO->setData($key, $value);
                }
                break;
            } catch (Exception) {
                continue;
            }

        }
        return $miraklShippingAddressDO;
    }

    /**
     * @param DataObject $miraklShippingAddress
     * @return string
     */
    private function getFormattedMiraklShippingAddress(DataObject $miraklShippingAddress): string
    {
        $returnAddress = $this->getMiraklAddressName($miraklShippingAddress);
        $returnAddress .= $this->getMiraklAddressCompany($miraklShippingAddress);
        $returnAddress .= $this->getMiraklAddressStreet($miraklShippingAddress);
        $returnAddress .= $this->getMiraklRegionData($miraklShippingAddress);
        $returnAddress .= $this->getMiraklCountry($miraklShippingAddress);
        return $returnAddress;
    }

    /**
     * @param DataObject $miraklShippingAddress
     * @return string
     */
    private function getMiraklAddressName(DataObject $miraklShippingAddress): string
    {
        if (!$miraklShippingAddress->getFirstname() && !$miraklShippingAddress->getLastname()) {
            return '';
        }
        return $miraklShippingAddress->getFirstname() . ' ' . $miraklShippingAddress->getLastname() . '<br>';
    }

    /**
     * @param DataObject $miraklShippingAddress
     * @return string
     */
    private function getMiraklAddressStreet(DataObject $miraklShippingAddress): string
    {
        $firstLine = $miraklShippingAddress->getStreet()[0] ?? null;
        $secondLine = $miraklShippingAddress->getStreet()[1] ?? null;
        $breakLine = ($secondLine) ? '<br>' : '';
        return $firstLine . $breakLine . $secondLine . '<br>';
    }

    /**
     * @param DataObject $miraklShippingAddress
     * @return string
     */
    private function getMiraklRegionData(DataObject $miraklShippingAddress): string
    {
        $city = $miraklShippingAddress->getCity();
        $city = $city ? $city . ', ' : null;
        $region = $miraklShippingAddress->getRegion();
        $region = $region ? $region . ', ' : null;
        $postCode = $miraklShippingAddress->getPostcode();
        $breakLine = ($city || $region || $postCode) ? '<br>' : '';

        return $city . $region . $postCode . $breakLine;
    }

    /**
     * @param DataObject $miraklShippingAddress
     * @return string
     */
    private function getMiraklCountry(DataObject $miraklShippingAddress): string
    {
        $country = $miraklShippingAddress->getData('countryId') ?? $miraklShippingAddress->getData('country_id');
        return $country ? $country . '<br>' : '';
    }

    /**
     * Get mirakl shipping by item.
     *
     * @param $item
     * @return false|string
     */
    public function getMiraklShippingByItem($item)
    {
        $order = $item->getOrder();
        $miraklShippingAddress = $this->helperRate->getMktShippingAddress($order);
        if (!isset($miraklShippingAddress)) {
            return false;
        }

        $addressParts = [
            'company' => $miraklShippingAddress['company'] ?? '',
            'street' => $miraklShippingAddress['street'][0] ?? '',
            'complement' => !empty($miraklShippingAddress['street'][1]) ? $miraklShippingAddress['street'][1] : '',
            'city' => $miraklShippingAddress['city'] ?? '',
            'regionCode' => $miraklShippingAddress['regionCode'] ?? '',
            'countryPostcode' => ($miraklShippingAddress['countryId'] ?? '') . ' ' . ($miraklShippingAddress['postcode'] ?? '')
        ];

        $formattedAddress = array_filter([
            $addressParts['company'],
            $addressParts['street'],
            $addressParts['complement'],
            $addressParts['city'],
            $addressParts['regionCode'],
            $addressParts['countryPostcode']
        ]);

        return implode(', ', $formattedAddress);
    }

    /**
     * @param $order
     * @param $item
     * @return mixed|null
     */
    public function getMiraklShipping($order, $item = null)
    {
        return $this->helperRate->getMktShipping($order, $item);
    }

    /**
     * @param $item
     * @return string
     */
    public function getFreightSpecialServices($item): string|array
    {
        if ($this->helperRate->isFreightShippingEnabled()) {
            $additionalData = $item->getAdditionalData();

            $jsonData = json_decode($additionalData, true);
            $surcharge = isset($jsonData['mirakl_shipping_data']['surcharge_amount']) ?
                (float)$jsonData['mirakl_shipping_data']['surcharge_amount'] : 0;
            if ($surcharge > 0) {
                return [
                    'title' => $this->helperRate->getFreightShippingSurchargeText(),
                    'amount' => $this->priceHelper->currency($surcharge, true, false)
                ];
            }
        }
        return false;
    }

    /**
     * @param DataObject $miraklShippingAddress
     * @return string
     */
    private function getMiraklAddressCompany(DataObject $miraklShippingAddress): string
    {
        if (!$miraklShippingAddress->getCompany()) {
            return '';
        }
        return $miraklShippingAddress->getCompany() . '<br>';
    }

    /**
     * @param Order $order
     * @return mixed[]
     */
    public function getMixedOrderAlternateAddress(Order $order): array
    {
        $contactInfo = [];
        if ($order->getShippingMethod() == self::SHIPPING_TYPE_PICKUP) {
            foreach ($order->getItems() as $orderItem) {
                if ($orderItem->getData('mirakl_offer_id') && $orderItem->getAdditionalData()) {
                    $additionalData = json_decode($orderItem->getAdditionalData(), true);
                    if (isset($additionalData['mirakl_shipping_data']['address']['is_alternate'])
                        && $additionalData['mirakl_shipping_data']['address']['is_alternate']) {
                        $contactInfo = [
                            'name' => $additionalData['mirakl_shipping_data']['address']['altFirstName']
                            .' ' . $additionalData['mirakl_shipping_data']['address']['altLastName'],
                            'email' => $additionalData['mirakl_shipping_data']['address']['altEmail'],
                            'phone' => $additionalData['mirakl_shipping_data']['address']['altPhoneNumber']
                        ];
                        break;
                    }
                }
            }
        }

        return $contactInfo;
    }


    /**
     * @param Order $order
     * @return false|void
     */
    public function getShipmentStatus1P(Order $order)
    {
        /** @var Shipment $onePShipping */
        $onePShipping = $this->getShippingOneP($order);
        if ($onePShipping === false) {
            return false;
        }
        if ($onePShipping->getId()) {
            /** @var \Fedex\Shipment\Model\Shipment */
            $shipmentStatus = $this->shipmentStatusFactory->create();
            try {
                $shipmentStatus->load($onePShipping->getShipmentStatus(), 'value');
                return $shipmentStatus->getKey();
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param Order $order
     * @return false|mixed
     */
    public function getShippingOneP(Order $order)
    {
        $shipments = $order->getShipmentsCollection();
        $toggleForD214230 = $this->isD214230Enabled();
        foreach ($shipments as $shipment) {
            if ($shipment->getFxoShipmentId() && (!$toggleForD214230 || !$shipment->getMiraklShippingReference())) {
                return $shipment;
            }
        }
        return false;
    }

    /**
     * @param Array $tracknumbers
     * @param Array Magento\Sales\Model\Order\Shipment $shipments
     * @return String $sellerType
     * @return String HTML
     */
    public function getTrackingNumbersBySellerType($trackingNumbers, $shipments, $sellerType = 'first_party')
    {
        $trackOrderUrl = $this->getTrackOrderUrl();
        foreach ($trackingNumbers as $tracking) {
            foreach ($shipments as $shipment) {
                $shipmentId = $shipment->getData('entity_id');
                $trackingNumber = $tracking->getData('track_number');
                if ($shipmentId == $tracking->getData('parent_id')) {
                    $trackingNumberUrl = $trackOrderUrl.$trackingNumber;
                    $shipmentMiraklReference = $shipment->getData('mirakl_shipping_reference');
                    $isFirstPartyShipment = ($sellerType == 'first_party' && empty($shipmentMiraklReference));
                    $isThirdPartyShipment = ($sellerType == 'third_party' && !empty($shipmentMiraklReference));

                    if ($isFirstPartyShipment || $isThirdPartyShipment) {
                        echo "<a href='" .$trackingNumberUrl. "'target='_blank'>" .$trackingNumber. "</a>";
                    }
                }
            }
        }
    }

    /**
     * @param Order\Item $orderItem
     * @param Shipment[] $shipments
     * @return String
     */
    public function getTrackingNumbersByOrderItem($orderItem, $shipments)
    {
        $trackOrderUrl = $this->getTrackOrderUrl();
        foreach ($shipments as $shipment) {
            $shipmentOrderItems = $shipment->getItems();
            foreach ($shipmentOrderItems as $shipmentItem) {
                if ($shipmentItem->getOrderItemId() == $orderItem->getId()) {
                    $trackingNumbers = $shipment->getTracks();
                    foreach ($trackingNumbers as $tracking) {
                        $trackingNumber = $tracking->getData('track_number');
                        $trackingNumberUrl = $trackOrderUrl . $trackingNumber;
                        return "<a href='" . $trackingNumberUrl . "'target='_blank'>" . $trackingNumber . "</a>";
                    }
                }
            }
        }

        return '';
    }

    public function isNewDocumentImageToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::NEW_DOCUMENT_API_IMAGE_PREVIEW_TOGGLE);
    }

    /**
     * @param $item
     * @return string
     */
    public function getShopName($item)
    {
        $product = $item->getProduct();
        if ($this->helper->isEssendantToggleEnabled() && $product->getTypeId()=='configurable') {
            $childProduct = $product->getTypeInstance()->getUsedProducts($product)[0] ?? null;
            if ($childProduct) {
                $product = $childProduct;
            }
        }
       $shop = $this->shopManagement->getShopByProduct($product);
       return $shop->getSellerAltName();
    }

    /**
     * Filter all quotes visible items to get first party items
     * @param $quote
     * @return array
     */
    public function getFirstPartyItems($order)
    {
        if($this->helper->isEssendantToggleEnabled()){
            return array_filter($order->getAllVisibleItems(),function ($item){
                return !$item->getData('mirakl_offer_id');
            });
        }

        return array_filter($order->getItems(),function ($item){
            return !$item->getData('mirakl_offer_id');
        });

    }

    /**
     * Get Fedex items count
     * @param $order
     * @return int
     */
    public function getFedexItemsCount($order) {
        $firstPartyItems = $this->getFirstPartyItems($order);
        return $firstPartyItems ? count($firstPartyItems) : 0;
    }

    /**
     * Create a new third party items array organized by seller
     * @param $quote
     * @return array
     */
    public function getThirdPartySellers($order)
    {
        $result = [];
        $thirdPartyItems = $this->getThirdPartyItems($order);

        foreach ($thirdPartyItems as $item) {
            $sellerName = $this->getShopName($item);

            if (array_key_exists($sellerName, $result)) {
                array_push($result[$sellerName], $item);
            } else {
                $result[$sellerName] = [$item];
            }
        }

        return $result;
    }

    /**
     * Filter all quotes visible items to get third party items
     * @param $quote
     * @return array
     */
    public function getThirdPartyItems($order)
    {
        if($this->helper->isEssendantToggleEnabled()){
            return array_filter($order->getAllVisibleItems(),function ($item){
                return $item->getData('mirakl_offer_id');
            });
        }
        return array_filter($order->getItems(),function ($item){
            return $item->getData('mirakl_offer_id');
        });
    }

    /**
     * Check Order Approval B2B is enabled or not
     *
     * @return boolean
     */
    public function isOrderApprovalB2bEnabled()
    {
        return $this->orderApprovalAdminConfigHelper->isOrderApprovalB2bEnabled();
    }

    /**
     * Check is review action is set or not
     *
     * @return boolean
     */
    public function checkIsReviewActionSet()
    {
        return $this->orderApprovalAdminConfigHelper->checkIsReviewActionSet();
    }

    /**
     * Get Order Object from Order Id
     * @param orderId | int
     * @return Object
     */
    public function getOrderById($orderId)
    {
        return $this->orderRepository->get($orderId);
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getCustomerShippingAddress(Order $order): array
    {
        $shippingAddress = $order->getShippingAddress();

        return [
            'fullName' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
            'company' => $shippingAddress->getCompany(),
            'street1' => $shippingAddress->getStreetLine(1),
            'street2' => $shippingAddress->getStreetLine(2),
            'cityStateCountryPostCode' => $shippingAddress->getCity() . ', ' . $shippingAddress->getRegionCode() . ', ' . $shippingAddress->getCountryId() . ' ' . $shippingAddress->getPostcode(),
            'country' => $shippingAddress->getCountryId(),
        ];
    }

    /**
     * Check Legacy Document for reorder
     *
     * @param int|string $orderId
     * @return bool|array
     */
    public function checkLegacyDocumentForReorder($orderId): bool|array
    {
        return $this->orderHistoryhelper->checkOrderHasLegacyDocument($orderId);
    }

    /**
     * Toggle button to handle the legacy document for reorder scetion
     *
     * @return bool|null
     */
    public function checkLegacyDocReorderSectionToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(self::LEGACY_DOCUMENT_REORDER_SECTION);
    }

    /**
     * @return bool
     */
    public function isEssendantToggleEnabled(): bool
    {
        return $this->helper->isEssendantToggleEnabled();
    }

    /**
     * @return bool
     */
    public function isCBBToggleEnabled(): bool
    {
        return $this->helper->isCBBToggleEnabled();
    }
    /**
     * Retrieve Tiger - D-214230 - Wrong order status showing in Order History Page for 1P
     * @return bool
     */
    public function isD214230Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D214230);
    }

    /**
     * Retrieve Tiger - D-219344 - Order Info Alternate Address Fix
     * @return bool
     */
    public function isD219344OrderInfoAlternateAddressFixEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D219344_ORDER_INFO_ALTERNATE_ADDRESS_FIX);
    }

    /**
     * @param $order
     * @return false|string
     * @throws Exception
     */
    public function getShippingDueDate($order): false|string
    {
        if ($this->instoreConfig->isUpdateDueDateEnabled() &&
            $shippingDueDate = $order->getShipmentsCollection()->getFirstItem()->getShippingDueDate()) {
            $shippingUpdatedDueDate = new \DateTime($shippingDueDate);
            return $shippingUpdatedDueDate->format('l, F j, g:ia');
        }
        return false;
    }

    /**
     * Retrieve D-233959 - Order History Blank Fix
     * @return bool
     */
    public function isD233959Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_D_233959);
    }

    /**
     * TK-4668950: Subtotal including shipping in order details
     * @return bool
     */
    public function isSubtotalInclusiveTaxToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_SUBTOTAL_TK_4668950);
    }
}
