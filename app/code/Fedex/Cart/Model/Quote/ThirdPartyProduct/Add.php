<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2022 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\ThirdPartyProduct;

use Exception;
use Fedex\ExpiredItems\Model\Quote\UpdaterModel;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item;
use Fedex\MarketplaceCheckout\Helper\Data AS MarketplaceCheckoutHelper;
use Psr\Log\LoggerInterface;

class Add
{
    /**
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param SearchCriteriaBuilder $searchBuilder
     * @param Update $update
     * @param NonCustomizableProduct $nonCustomizableProductModel
     * @param MarketplaceCheckoutHelper $checkoutHelper
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param UpdaterModel $updaterModel
     * @param LoggerInterface $logger
     * @param Configurable $configurable
     */
    public function __construct(
        private Cart $cart,
        private ProductRepositoryInterface $productRepository,
        private RequestInterface $request,
        private SerializerInterface $serializer,
        private SearchCriteriaBuilder $searchBuilder,
        private Update $update,
        private NonCustomizableProduct $nonCustomizableProductModel,
        private CheckoutHelper $checkoutHelper,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private UpdaterModel $updaterModel,
        private LoggerInterface $logger,
        private Configurable $configurable
    ) {
    }

    /**
     * Adds third party product to cart
     *
     * @param array $requestData
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function addItemToCart(array $requestData): void
    {
        $this->searchBuilder->addFilter('sku', $requestData['sku']);
        $searchCriteria = $this->searchBuilder->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();
        $product = last($products);
        $childProduct = null;

        if($product->getTypeId() == Configurable::TYPE_CODE && $this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
            $superAttributes = $requestData['super_attribute']??[];
            if(!empty($superAttributes)){
                $params['super_attribute'] = $superAttributes;
            }
            $childProduct = $this->configurable->getProductByAttributes($requestData['super_attribute'], $product);

            if ($childProduct && $childProduct->getId()) {
                $product = $childProduct;
            }
        }

        $sku = (!empty($childProduct) && $childProduct->getId()) ? $childProduct->getSku() : $product->getSku();

        $params = [
            'product' => $sku,
            'qty' => $this->request->getParam('qty') != '' ? $this->request->getParam('qty') : 1,
            'offer_id' => $this->request->getParam('offer_id')
        ];

        $this->request->setPostValue('isMarketplaceProduct', true);
        $cart = $this->cart;
        $isEssendantToggleEnabled = $this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
        $isProductisConfig = $product->getTypeId() == Configurable::TYPE_CODE;
        $isNonProductConfigurableWithEssendantEnable = (!($isEssendantToggleEnabled && $isProductisConfig));
        $isProductConfigurableWithEssendantEnable = ($isEssendantToggleEnabled && $isProductisConfig );

        if($this->isPunchoutDisabledProductInCart($product) && $isNonProductConfigurableWithEssendantEnable) {
            $this->updateProductQtyInCart($product, (float)$params['qty']);
        }elseif($this->isPunchoutDisabledProductInCart($product) && $isProductConfigurableWithEssendantEnable){
            $this->updateVariantProductQtyInCart($product, $params);
        } else {
            if($isEssendantToggleEnabled){
                $this->cart->addProduct($product, $params);
            }else{
                $cart = $this->cart->addProduct($product, $params);
            }
        }
        if($isEssendantToggleEnabled){
            $this->cart->save();
            $items =  $this->cart->getItems();
        }else{
            $cart->save();
            $items =  $cart->getItems();
        }
        $quoteItem = null;
        foreach ($items as $item) {
            if ($item->getProductId() == $product->getId()) {
                $quoteItem = $item;
            }
        }

        if (!$quoteItem) {
            throw new Exception('Error adding product to cart.');
        }
        if($isEssendantToggleEnabled){
            $quoteItem = $this->update->updateThirdPartyItemSellerPunchout($quoteItem, $product,$requestData);
        }else{
            $quoteItem = $this->update->updateThirdPartyItemSellerPunchout($quoteItem, $product);
        }

        try {
            $this->cart->getQuote()->addItem($quoteItem);
            $this->cart->getQuote()->save();
        } catch (LocalizedException|Exception $e) {
            $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' Quote ID: '.$this->cart->getQuote()->getId().' Error: '.$e->getMessage());
            throw $e;
        }

        if ($this->marketplaceCheckoutHelper->isToggleD214903Enabled() && !$quoteItem->getMiraklOfferId()) {
            $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' Quote ID: '.$this->cart->getQuote()->getId().' Missing Mirakl Offer ID');
            $this->updaterModel->synchronize($this->cart->getQuote());
        }
    }

    /**
     * @param $product
     * @param float $qty
     * @return void
     * @throws LocalizedException
     */
    protected function updateProductQtyInCart($product, float $qty): void
    {
        foreach ($this->cart->getQuote()->getItems() as $quoteItem) {
            if ($quoteItem->getProductId() == $product->getId()) {
                $qty += $quoteItem->getQty();
                if ($this->nonCustomizableProductModel->isD213961Enabled()) {
                    $validateProductMaxQty = $this->nonCustomizableProductModel->validateProductMaxQty(
                        $product,
                        $qty
                    );
                } else {
                    $validateProductMaxQty = $this->nonCustomizableProductModel->validateProductMaxQty(
                        $product->getId(),
                        $qty
                    );
                }
                if ($validateProductMaxQty != '') {
                    throw new LocalizedException(__($validateProductMaxQty));
                }
                $this->updateItemQuantity($quoteItem, $qty);
            }
        }
    }

    /**
     * Updates quote item quantity.
     *
     * @param Item $item
     * @param float $qty
     * @return void
     * @throws LocalizedException
     */
    private function updateItemQuantity(Item $item, float $qty)
    {
        if ($qty > 0) {
            $item->clearMessage();
            $item->setHasError(false);
            $item->setQty($qty);

            if ($item->getHasError()) {
                throw new LocalizedException(__($item->getMessage()));
            }
        }
    }

    /**
     * @param ProductInterface $product
     * @return bool
     */
    protected function isPunchoutDisabledProductInCart($product)
    {
        $cbbEnabled = $this->nonCustomizableProductModel->isMktCbbEnabled();
        $isPunchoutDisabled = $this->request->getParam('punchout_disabled') ?? false;
        $isProductInCart = in_array($product->getId(), $this->cart->getQuoteProductIds());
        return $cbbEnabled && $isPunchoutDisabled && $isProductInCart;
    }
    /**
     * @param $product
     * @param $superAttributes
     * @return void
     * @throws LocalizedException
     */
    protected function updateVariantProductQtyInCart($product, $params)
    {
        $sameVariantItem = null;
        $superAttribute = isset($params['super_attribute'])?$params['super_attribute']:[];

        foreach ($this->cart->getQuote()->getItems() as $quoteItem) {
            $configSuperAttributes = $this->getSuperAttributes($quoteItem->getProduct());
            if ($quoteItem->getProductId() == $product->getId() && $superAttribute == $configSuperAttributes) {
                $sameVariantItem = $quoteItem;
            }
        }
        if ($sameVariantItem) {
            $qty = isset($params['qty']) ? $params['qty'] : 1;
            $qty += $sameVariantItem->getQty();
            if ($this->nonCustomizableProductModel->isD213961Enabled()) {
                $validateProductMaxQty = $this->nonCustomizableProductModel->validateProductMaxQty(
                    $product,
                    $qty
                );
            } else {
                $validateProductMaxQty = $this->nonCustomizableProductModel->validateProductMaxQty(
                    $product->getId(),
                    $qty
                );
            }
            if ($validateProductMaxQty !== '') {
                throw new LocalizedException(__($validateProductMaxQty));
            }

            $this->updateItemQuantity($sameVariantItem, $qty);
        } else {
            $this->cart->addProduct($product, $params);
        }

    }

    /**
     * Get configurable product attributes.
     *
     * @param $parentProduct
     * @return array
     */
    public function getSuperAttributes($parentProduct): array
    {
        $buyRequest = $parentProduct->getCustomOption('info_buyRequest')->getValue();
        if($buyRequest){
            $superAttribute = $this->serializer->unserialize($buyRequest);
        }
        return (isset($superAttribute['super_attribute'])?$superAttribute['super_attribute']:[]);
    }
}
