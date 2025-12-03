<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\ThirdPartyProduct;

use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Magento\Catalog\Model\Indexer\Product\Eav;
use Magento\Eav\Model\Config;
use Psr\Log\LoggerInterface;

class Update
{
    private const FILE_NAME_1 = 'FileName1';
    private const ARTWORK = 'Artwork';
    private const UNIT_PRICE = 'UnitPrice';
    private const PRODUCTION_TIME = 'ProductionTime';
    private const ASPECT = 'Aspect';
    private const COMPOSITE = 'composite';

    private const VARIANTID = 'VariantID';
    private const SIZE = 'Size';
    private const QUANTITY = 'Quantity';

    private const IMAGE = 'ProductImage';

    /**
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param ElementFactory $xmlFactory
     * @param File $file
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ShopManagement $shopManagement
     * @param CheckoutSession $checkoutSession
     * @param MarketplaceConfigProvider $marketplaceConfigProvider
     * @param Data $helper
     * @param ExternalProd $externalProd
     * @param NonCustomizableProduct $nonCustomizableProductModel
     * @param MarketplaceRatesHelper $marketplaceRatesHelper
     * @param Config $eavConfig
     * @param CollectionFactory $category
     * @param Data $marketplaceCheckoutHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        private RequestInterface $request,
        private SerializerInterface $serializer,
        private ElementFactory $xmlFactory,
        private File $file,
        private Filesystem $filesystem,
        private StoreManagerInterface $storeManager,
        private ShopManagement $shopManagement,
        private CheckoutSession $checkoutSession,
        private MarketplaceConfigProvider $marketplaceConfigProvider,
        private Data $helper,
        private ExternalProd $externalProd,
        private NonCustomizableProduct $nonCustomizableProductModel,
        private MarketplaceRatesHelper $marketplaceRatesHelper,
        private Config $eavConfig,
        private CollectionFactory $category,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Updates third party quote item
     *
     * @param Item $quoteItem
     * @param ProductInterface|null $product
     * @param null $requestData
     * @param null $cartData
     * @return Item
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateThirdPartyItem(
        Item $quoteItem,
        ProductInterface $product = null,
        $requestData = null,
        $cartData = null
    ): Item {

        if ($this->helper->isCartIntegrationPrintfulEnabled()) {
            return $this->updateThirdPartyItemSellerPunchout($quoteItem, $product, null, $cartData);
        }

        $xml = $this->xmlFactory->create(
            ['data' => $this->request->getParam('cxml-urlencoded')]
        );

        $supplierPartID = $xml->Message->PunchOutOrderMessage
            ->ItemIn->ItemID->SupplierPartID;
        $supplierPartAuxiliaryID = $xml->Message->PunchOutOrderMessage
            ->ItemIn->ItemID->SupplierPartAuxiliaryID;
        $total = $xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader
            ->Total->Money;
        $imageUrl = $xml->Message->PunchOutOrderMessage
            ->ItemIn->ItemDetail->URL;
        $quantity = $xml->Message->PunchOutOrderMessage->ItemIn->getAttribute('quantity');
        $name = $xml->Message->PunchOutOrderMessage->ItemIn->ItemDetail
            ->Description->ShortName;
        $extrinsic = $xml->Message->PunchOutOrderMessage->ItemIn->ItemDetail->Extrinsic;
        $imageName = '';
        $unitPrice = '';
        $productionTime = 1;
        $features = [];

        if (!$quoteItem->getQuote()) {
            $quoteItem->setQuote($this->checkoutSession->getQuote());
        }

        foreach ($extrinsic as $item) {
            switch ($item->getAttribute('name')) {
                case self::PRODUCTION_TIME:
                    $data = (explode(' ', (string) $item[0]));
                    $productionTime = $data[0];
                    break;
                case self::UNIT_PRICE:
                    $unitPrice = (double)$item[0];
                    break;
                case self::ARTWORK:
                    if ($item[0]->Extrinsic[0]->getAttribute('name') == self::FILE_NAME_1) {
                        $imageName = $supplierPartAuxiliaryID.time().$item[0]->Extrinsic[0];
                        $name = (string) $item[0]->Extrinsic[0];
                    }
                    break;
                case self::ASPECT:
                    if (isset($item[0]->Extrinsic[1])) {
                        $features[] = [
                            'name' => (string) $item[0]->Extrinsic[0],
                            'choice' => [
                                'name' => (string) $item[0]->Extrinsic[1]
                            ]
                        ];
                    }
                    break;
            }
        }

        $itemXml = $xml->Message->PunchOutOrderMessage->ItemIn;
        foreach ($itemXml as $item) {
            if ($item->getAttribute('itemType') == self::COMPOSITE) {
                $extrinsicComposite = $item->ItemDetail->Extrinsic;
                foreach ($extrinsicComposite as $itemComposite){
                    if ($itemComposite->getAttribute('name') == self::ASPECT) {
                        if (isset($itemComposite[0]->Extrinsic[1])) {
                            $features[] = [
                                'name' => (string) $itemComposite[0]->Extrinsic[0],
                                'choice' => [
                                    'name' => (string) $itemComposite[0]->Extrinsic[1]
                                ]
                            ];
                        }
                    }
                }
            }
        }

        // When no image is added, ShortName returns an object and not string
        if (is_object($name)) {
            $nameArray = get_object_vars($name);
            if (count($nameArray) > 0) {
                $name = $nameArray[0];
            }
        }

        $additionalData = [];
        if ($quoteItem->getAdditionalData()) {
            $additionalData = (array) json_decode($quoteItem->getAdditionalData());
        }

        $cartExpire = null;
        $cartExpireSoon = null;
        $shop = $this->shopManagement->getShopByProduct($quoteItem->getProduct());
        if ($shop->getId()) {
            $cartExpire = $shop->getCartExpire();
            $cartExpireSoon = $shop->getCartExpireSoon();
                foreach ($shop->getAdditionalInfo()['additional_field_values'] as $additionalInfo) {
                    if ($additionalInfo['code'] === 'allow-edit-reorder') {
                        $additionalData['can_edit_reorder'] = $additionalInfo['value'] === 'true';
                    }
                }
        }

        if (!isset($additionalData['cart_quantity_tooltip'])) {
            $additionalData['cart_quantity_tooltip'] = $this->marketplaceConfigProvider->getCartQuantityTooltip();
            $additionalData['expire'] = $cartExpire;
            $additionalData['expire_soon'] = $cartExpireSoon;
        }

        if ($product) {
            $additionalData['weight_unit'] = $product->getResource()
                ->getAttribute('weight_unit')->getFrontend()->getValue($product);
        }

        $additionalData['supplierPartID'] = (string) $supplierPartID;
        $additionalData['supplierPartAuxiliaryID'] = (string) $supplierPartAuxiliaryID;
        $additionalData['seller_sku'] = $additionalData['seller_sku'] ?? $this->request->getParam('seller_sku');
        $additionalData['offer_id'] = $additionalData['offer_id'] ?? $this->request->getParam('offer_id');
        $additionalData['isMarketplaceProduct'] = 'true';
        $additionalData['total'] = (double) $total;
        $additionalData['unit_price'] = $unitPrice;
        $additionalData['image'] = (string) $imageUrl;
        $additionalData['quantity'] = (int) $quantity;
        $additionalData['marketplace_name'] = $name;
        $additionalData['business_days'] = $productionTime;
        $additionalData['features'] = $features;

        $quoteItem->setAdditionalData(json_encode($additionalData));

        $quoteItem->setCustomPrice((double) $unitPrice);
        $quoteItem->setOriginalCustomPrice((double) $unitPrice);
        $quoteItem->setQty($quantity);
        $quoteItem->setBaseRowTotal((double) $total);
        $quoteItem->setRowTotal((double) $total);
        $quoteItem->setIsSuperMode(true);

        $externalData = [
            'preview_url' => (string) $imageUrl,
            'name' => $quoteItem->getName(),
        ];

        $save = [
            'external_prod' => [
                0 => $externalData,
            ],
            'quantityChoices' => ['1'],
            'total' => (double) $total,
            'unit_price' => $unitPrice,
            'image' => (string) $imageUrl,
            'quantity' => (int) $quantity,
            'marketplace_name' => $name,
            'supplier_part_auxiliary_id' => (string) $supplierPartAuxiliaryID,
            'supplier_part_id' => (string) $supplierPartID,
            'features' => $features
        ];
        $quoteItem->addOption([
            'product_id' => $quoteItem->getProductId(),
            'code' => 'marketplace_data',
            'value' => $this->serializer->serialize($save),

        ]);

        $quoteItem->saveItemOptions();
        return $quoteItem;
    }

    /**
     * Updates third party quote item
     *
     * @param Item $quoteItem
     * @param ProductInterface|null $product
     * @param null $requestData
     * @param null $cartData
     * @return Item
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateThirdPartyItemSellerPunchout(
        Item $quoteItem,
        ProductInterface $product = null,
        $requestData = null,
        $cartData = null
    ): Item {

        $packagingData = '';
        if ($this->nonCustomizableProductModel->isMktCbbEnabled() && !$this->request->getParam('cxml-urlencoded')) {
            $itemQty = isset($cartData[$quoteItem->getItemId()]['qty']) ? $cartData[$quoteItem->getItemId()]['qty'] : $quoteItem->getQty();
            $name = $quoteItem->getName();
            $total = $quoteItem->getPrice() * $itemQty;
            $unitPrice = $quoteItem->getPrice();
            $quantity = $itemQty;
            $supplierPartID = null;
            $supplierPartAuxiliaryID = null;
            $imageUrl = $this->nonCustomizableProductModel->getProductImage($quoteItem->getProduct()) ?? null;
            $productionTime = 1;
            $features = [];
            $variantId = null;
            $variantDetails = [];
        } else {
            $xml = $this->xmlFactory->create(
                ['data' => $this->request->getParam('cxml-urlencoded')]
            );
            $productionTime = 1;
            $features = [];

            //Printful is sendind the xml structure with ItemId and navitor as ItemID.
            $supplierPartID = $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemId->SupplierPartID
                ? $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemId->SupplierPartID
                : $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemID->SupplierPartID;


            $supplierPartAuxiliaryID = $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemId->SupplierPartAuxiliaryID
                ? $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemId->SupplierPartAuxiliaryID
                : $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemID->SupplierPartAuxiliaryID;

            $total = $xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader->Total->Money
                ? $xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader->Total->Money
                : $xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader->TotalPrice->Money;

            $quantity = $xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader->TotalQuantity
                ? $xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader->TotalQuantity
                : $xml->Message->PunchOutOrderMessage->ItemIn->getAttribute('quantity');

            $itemXml = $xml->Message->PunchOutOrderMessage->ItemIn;

            $unitPrice = '';

            $commonAddedFeatures = [];
            $featuresSize = [];
            $commonFeatures = [];
            $variantId = [];
            $variantDetails = [];

            foreach ($itemXml as $item) {
                if ($this->helper->isToggleD201080Enabled()) {
                    if(isset($item->ItemDetail->URL)){
                        $imageUrl = $item->ItemDetail->URL;
                    }
                }else{
                    $imageUrl = $item->ItemDetail->URL;
                }
                $name = $item->ItemDetail->Description->ShortName;
                $extrinsic = $item->ItemDetail->Extrinsic;

                if ($item->getAttribute('itemType') == self::COMPOSITE) {
                    $extrinsicComposite = $item->ItemDetail->Extrinsic;
                    foreach ($extrinsicComposite as $itemComposite) {
                        if ($itemComposite->getAttribute('name') == self::ASPECT) {
                            if (isset($itemComposite[0]->Extrinsic[1])) {
                                $features[] = [
                                    'name' => (string)$itemComposite[0]->Extrinsic[0],
                                    'choice' => [
                                        'name' => (string)$itemComposite[0]->Extrinsic[1]
                                    ]
                                ];
                            }
                        }
                    }
                }

                foreach ($extrinsic as $itemEx) {
                    /** PunchOut edit cart variant details */
                        $lineNumber = (int)$item->getAttribute('lineNumber');
                        $this->setVariantDetails($variantDetails, $itemEx, $lineNumber);

                    switch ($itemEx->getAttribute('name')) {
                        case self::PRODUCTION_TIME:
                            $data = (explode(' ', (string)$itemEx[0]));
                            $productionTime = $data[0];
                            break;
                        case self::UNIT_PRICE:
                            $unitPrice = $xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader->TotalUnitCost->Money
                                ? (double)$xml->Message->PunchOutOrderMessage->PunchOutOrderMessageHeader->TotalUnitCost->Money
                                : (double)$itemEx[0];
                            break;
                        case self::ARTWORK:
                            if ($itemEx[0]->Extrinsic[0]->getAttribute('name') == self::FILE_NAME_1) {
                                $imageName = $supplierPartAuxiliaryID . time() . $itemEx[0]->Extrinsic[0];
                                $name = (string)$itemEx[0]->Extrinsic[0];
                            }
                            break;
                        case self::ASPECT:
                            if (isset($itemEx[0]->Extrinsic[1])) {
                                $nameEx = (string)$itemEx[0]->Extrinsic[0];
                                $value = (string)$itemEx[0]->Extrinsic[1];

                                if ($nameEx === 'Size') {
                                    $featuresSize[] = [
                                        'name' => $nameEx,
                                        'choice' => [
                                            'name' => $value
                                        ]
                                    ];
                                } else {
                                    // Check if the common feature has already been added
                                    if (!in_array($nameEx, $commonAddedFeatures)) {
                                        $commonFeatures[] = [
                                            'name' => $nameEx,
                                            'choice' => [
                                                'name' => $value
                                            ]
                                        ];
                                        // Mark the feature as added
                                        $commonAddedFeatures[] = $nameEx;
                                    }
                                }
                            }
                            break;
                        case self::VARIANTID:
                            $variantId[] = (double)$itemEx[0];
                            break;
                        case self::IMAGE:
                            $imageUrl = $itemEx[0];
                            break;
                    }
                }
            }

            $features = array_merge($commonFeatures, $featuresSize);
        }

        if (!$quoteItem->getQuote()) {
            $quoteItem->setQuote($this->checkoutSession->getQuote());
        }

        // When no image is added, ShortName returns an object and not string
        if (is_object($name)) {
            $nameArray = get_object_vars($name);
            if (count($nameArray) > 0) {
                $name = $nameArray[0];
            }
        }

        $additionalData = [];
        if ($quoteItem->getAdditionalData()) {
            $additionalData = (array) json_decode($quoteItem->getAdditionalData());
        }

        $cartExpire = null;
        $cartExpireSoon = null;
        $shop = $this->shopManagement->getShopByProduct($quoteItem->getProduct());
        if($shop->getId()){
            $cartExpire = $shop->getCartExpire();
            $cartExpireSoon = $shop->getCartExpireSoon();
                foreach ($shop->getAdditionalInfo()['additional_field_values'] as $additionalInfo) {
                    if ($additionalInfo['code'] === 'allow-edit-reorder') {
                        $additionalData['can_edit_reorder'] = $additionalInfo['value'] === 'true';
                    }
                }
            if ($this->nonCustomizableProductModel->isMktCbbEnabled() && $this->request->getParam('cxml-urlencoded')) {
                if ($this->marketplaceRatesHelper->isFreightShippingEnabled()) {
                    $shopShippingInfo = $shop->getShippingRateOption();
                    if ($shopShippingInfo['freight_enabled']) {
                        $packagingData = $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemID->PackagingData
                            ? $xml->Message->PunchOutOrderMessage->ItemIn[0]->ItemID->PackagingData
                            : '';
                    }
                }
            }
        }

        if (!isset($additionalData['cart_quantity_tooltip'])) {
            $additionalData['cart_quantity_tooltip'] = $this->marketplaceConfigProvider->getCartQuantityTooltip();
            $additionalData['expire'] = $cartExpire;
            $additionalData['expire_soon'] = $cartExpireSoon;
        }

        if ($product) {
            $additionalData['weight_unit'] = $product->getResource()
                ->getAttribute('weight_unit')->getFrontend()->getValue($product);
        }

        if ($this->helper->isEssendantToggleEnabled()) {
            if (empty($features)) {
                $features = $additionalData['features'] ?? [];

                if (empty($features)) {
                    $superAttributes = $requestData['super_attribute'] ?? [];

                    if (empty($superAttributes)) {
                        $buyRequest = $quoteItem->getOptionByCode('info_buyRequest');
                        if ($buyRequest && $buyRequest->getValue()) {
                            $buyRequestData = json_decode($buyRequest->getValue(), true);
                            $superAttributes = $buyRequestData['super_attribute'] ?? [];
                        }
                    }

                    if (!empty($superAttributes)) {
                        $features = $this->getFormattedSuperAttributes($superAttributes);
                    }
                }
            }

            if ($this->helper->isScheduledMaintenancePageEssendantProductsToggle()) {
                if ($product) {
                    $features = $this->addBrandToFeatures($features ?? [], $product);
                    $additionalData['features'] = $features;
                }
            }else {
                $features = $this->addBrandToFeatures($features ?? [], $product);
                $additionalData['features'] = $features;
            }
        }

        $additionalData['supplierPartID'] = (string) $supplierPartID;
        $additionalData['supplierPartAuxiliaryID'] = (string) $supplierPartAuxiliaryID;
        $additionalData['seller_sku'] = $additionalData['seller_sku'] ?? $this->request->getParam('seller_sku');
        $additionalData['offer_id'] = $additionalData['offer_id'] ?? $this->request->getParam('offer_id');
        $additionalData['isMarketplaceProduct'] = 'true';
        $additionalData['total'] = (double) $total;
        $additionalData['unit_price'] = $unitPrice;
        $additionalData['image'] = (string) $imageUrl;
        $additionalData['quantity'] = (int) ($quantity);
        $additionalData['marketplace_name'] = $name;
        $additionalData['business_days'] = $productionTime;
        $additionalData['variantId'] = $variantId;
        $additionalData['variantDetails'] = $variantDetails;

        if ($this->helper->isScheduledMaintenancePageEssendantProductsToggle()) {
            if ($this->helper->isEssendantToggleEnabled() && $product){
                $categoryIds = $product->getCategoryIds();
                $additionalData['map_sku'] = $this->setMapSkuToProduct($categoryIds,$product);
            }
        } else {
            if ($this->helper->isEssendantToggleEnabled()){
                $categoryIds = $product->getCategoryIds();
                $additionalData['map_sku'] = $this->setMapSkuToProduct($categoryIds,$product);
            }
        }

        $punchoutDisabled = $this->request->getParam('punchout_disabled');
        if (!isset($additionalData['punchout_enabled'])) {
            $additionalData['punchout_enabled'] = $punchoutDisabled != 1;
        }
        $additionalData['packaging_data'] = json_decode((string) $packagingData);

        $quoteItem->setAdditionalData(json_encode($additionalData));

        $quoteItem->setCustomPrice((double) $unitPrice);
        $quoteItem->setOriginalCustomPrice((double) $unitPrice);
        $quoteItem->setQty($quantity);
        $quoteItem->setBaseRowTotal((double) $total);
        $quoteItem->setRowTotal((double) $total);
        $quoteItem->setIsSuperMode(true);

        $externalData = [
            'preview_url' => (string) $imageUrl,
            'name' => $quoteItem->getName(),
        ];

        $save = [
            'external_prod' => [
                0 => $externalData,
            ],
            'quantityChoices' => ['1'],
            'total' => (double) $total,
            'unit_price' => $unitPrice,
            'image' => (string) $imageUrl,
            'quantity' => (int) $quantity,
            'marketplace_name' => $name,
            'supplier_part_auxiliary_id' => (string) $supplierPartAuxiliaryID,
            'supplier_part_id' => (string) $supplierPartID,
            'features' => $features
        ];
        $quoteItem->addOption([
            'product_id' => $quoteItem->getProductId(),
            'code' => 'marketplace_data',
            'value' => $this->serializer->serialize($save),

        ]);
        $this->updateInfoBuyRequestQuantity($quoteItem, (int)$quantity);

        $quoteItem->saveItemOptions();
        return $quoteItem;
    }

    /**
     * Saves Marketplace product image
     *
     * @param string $imageUrl
     * @param string $imageName
     * @return string
     * @throws LocalizedException
     */
    public function saveImage(string $imageUrl, string $imageName)
    {
        $path = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath(). 'temp/catalog/';
        $this->file->checkAndCreateFolder($path);
        $path .= $imageName;
        $this->file->read($imageUrl, $path);
        $mediaUrl = $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA, true);

        return $mediaUrl . "temp/catalog/".$imageName;
    }

    /**
     * Retrieve the product variations for edit cart feature
     *
     * @param array $variantDetails
     * @param Element|null $itemEx
     * @param int $lineNumber
     * @return void
     */
    public function setVariantDetails(array &$variantDetails, ?Element $itemEx, int $lineNumber): void
    {
        $attrName = $itemEx->getAttribute('name');
        if ($attrName === self::QUANTITY
            || $attrName === self::UNIT_PRICE
            || $attrName === self::SIZE
            || $attrName === self::VARIANTID
        ) {
            $variantDetails[$lineNumber][$attrName] = (string)$itemEx[0];
        }
    }

    /**
     * Update info_buyRequest qty for 3P products
     *
     * @param Item $quoteItem
     * @param int $quantity
     * @return void
     * @throws LocalizedException
     */
    private function updateInfoBuyRequestQuantity(Item &$quoteItem, int $quantity)
    {
        if ($quantity > 0) {

            $infoBuyRequest = $quoteItem->getOptionByCode('info_buyRequest');
            $infoByReqValue = false;
            if($infoBuyRequest && $infoBuyRequest->getValue()){
                $infoByReqValue = $this->serializer->unserialize($infoBuyRequest->getValue());
            }
            if ($infoByReqValue && isset($infoByReqValue['qty']) && $infoByReqValue['qty'] != $quantity) {

                $infoByReqValue['qty'] = $quantity;
                $serializeInfoByReq = $this->serializer->serialize($infoByReqValue);
                $quoteItem->addOption([
                    'product_id' => $quoteItem->getProductId(),
                    'code' => 'info_buyRequest',
                    'value' => $serializeInfoByReq,
                ]);
            }
        }
    }
    /**
     * Get values from attribute.
     *
     * @param array $superAttributes
     * @return array
     */
    public function getFormattedSuperAttributes(array $superAttributes): array
    {
        $result = [];

        foreach ($superAttributes as $attributeId => $optionId) {

            $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeId);
            if ($attribute->getId()) {
                $attributeName = $attribute->getFrontendLabel();
                $optionText = $attribute->getSource()->getOptionText($optionId);
                $result[] = [
                    'name' => $attributeName,
                    'choice' => [
                        'name' => $optionText,
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * Adds the brand attribute to the features array.
     *
     * @param $features
     * @param ProductInterface $product
     * @return array
     */
    public function addBrandToFeatures($features, ProductInterface $product): array
    {
        $brandExists = array_filter($features, function ($feature) {
            if (is_array($feature)) {
                return isset($feature['name']) && $feature['name'] === 'Brand';
            } elseif (is_object($feature)) {
                return isset($feature->name) && $feature->name === 'Brand';
            }
            return false;
        });

        if (empty($brandExists)) {
            $brand = $product->getAttributeText('brand') ?: $product->getBrand();
            if (!empty($brand)) {
                $features[] = [
                    'name' => 'Brand',
                    'choice' => [
                        'name' => $brand,
                    ],
                ];
            }
        }

        return $features;
    }

    /**
     * @param $categoryIds
     * @param $product
     * @return mixed
     * @throws LocalizedException
     */
    public function setMapSkuToProduct($categoryIds, $product): mixed
    {
        $mapSku = $product->getData('map_sku');

        if (!empty($mapSku)) {
            return $mapSku;
        }

        if (empty($categoryIds)) {
            $categoryIds = $product->getCategoryIds();
        }

        if (empty($categoryIds)) {
            return null;
        }

        $categories = $this->category->create()
            ->addAttributeToSelect(['entity_id', 'map_sku'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter([
                ['attribute' => 'map_sku', 'neq' => ''],
                ['attribute' => 'map_sku', 'notnull' => true]
            ])
            ->setPageSize(1);
        if($categories->getSize()){
            foreach ($categories as $category) {
                return $category->getData('map_sku');
            }
        }else{
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' - No categories found with map_sku. Size: ' . $categories->getSize());
        }
        return null;
    }

}
