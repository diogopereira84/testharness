<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Fedex\MarketplaceProduct\Api\NonCustomizableProductInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\MarketplaceProduct\Helper\Data as MiraklHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;
use Magento\CatalogInventory\Model\Configuration;

class NonCustomizableProduct implements NonCustomizableProductInterface
{
    protected $attributeSetLoaded = [];
    const XPATH_ENABLE_MKT_CBB_PRODUCTS = 'tiger_e_422180';
    const XPATH_D213961 = 'tiger_d213961';
    const LOGGER_MESSAGE = ' Item quantity is greater than max quantity allowed.';
    const MAX_QTY_ALLOWED_MESSAGE = 'Max Qty Allowed: ';

    const FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET = 'FXONonCustomizableProducts';

    /**
     * @param ToggleConfig $toggleConfig
     * @param CheckoutSession $checkoutSession
     * @param MiraklHelper $miraklHelper
     * @param StockRegistryInterface $stockRegistry
     * @param LoggerInterface $logger
     * @param Image $imageHelper
     * @param Configuration $configuration
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig,
        private readonly CheckoutSession $checkoutSession,
        private readonly MiraklHelper $miraklHelper,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly LoggerInterface $logger,
        private readonly Image $imageHelper,
        private readonly Configuration $configuration,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private AttributeSetRepositoryInterface $attributeSetRepository
    ) {
    }

    /**
     * Checks if marketplace CBB toggle is enabled
     * @return bool
     */
    public function isMktCbbEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XPATH_ENABLE_MKT_CBB_PRODUCTS);
    }

    /**
     * Checks if marketplace Essendant toggle is enabled
     * @return bool
     */
    public function isEssendantEnabled(): bool
    {
        return (bool)$this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
    }

    /**
     * Checks if D213961 toggle is enabled
     * @return bool
     */
    public function isD213961Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XPATH_D213961);
    }

    /**
     * Checks if D217169 toggle is enabled
     * @return bool
     */
    public function isD217169Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue('tiger_d217169');
    }

    /**
     * Checks if there are any third party with punchout disabled products in the cart
     *
     * @return bool
     */
    public function isThirdPartyOnlyCartWithAnyPunchoutDisabled()
    {
        if(!$this->isMktCbbEnabled()) {
            return false;
        }

        $cartItems  = $this->checkoutSession->getQuote()->getAllVisibleItems();

        foreach ($cartItems as $item) {
            $additionalData = json_decode($item->getAdditionalData() ?? '', true);
            $punchoutStatusForProduct = $additionalData['punchout_enabled'] ?? true;
            if ( !$this->hasMiraklOffers($item->getProduct()) || !$punchoutStatusForProduct ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return mixQty, maxQt and punchoutEnabled to be used in the template
     * @param Offer $offer
     * @return array
     */
    public function getMinMaxPunchoutInfo($offer, $product)
    {
        if(!$this->isMktCbbEnabled() || !$offer) {
            return [null, null, false];
        }
        $minSaleQty = $offer->getMinOrderQuantity();
        $maxSaleQty = $offer->getMaxOrderQuantity();

        if ($this->isEssendantEnabled()) {
            if ($minSaleQty === null || $minSaleQty==0) {
                $minSaleQty = $this->configuration->getMinSaleQty();
            }
            if ($maxSaleQty === null || $maxSaleQty==0) {
                $maxSaleQty = $this->configuration->getMaxSaleQty();
            }
        }

        $punchoutDisabled = $product->getCustomizableProduct() ? 0 : 1;

        return [$minSaleQty, $maxSaleQty, $punchoutDisabled];
    }

    /**
     * Returns true if product has available mirakl offers
     *
     * @param   Product $product
     * @return  bool
     */
    private function hasMiraklOffers($product)
    {
        return $this->miraklHelper->hasAvailableOffersForProduct($product);
    }

    /**
     * Return Punchout status on the item ## false = disabled | true = enabled
     * Punchout determines if the item is a third party product and can be updated
     *
     * @param Item $item
     * @return mixed|true
     */
    public function isProductPunchoutDisabledForThirdPartyItem(Item $item)
    {
        if(!$this->isMktCbbEnabled() || !$item->getAdditionalData()) {
            return false;
        }
        $additionalData = json_decode($item->getAdditionalData(), true);
        return !($additionalData['punchout_enabled'] ?? true);
    }

    /**
     * Validate Product Max Qty
     *
     * @param Product|ProductInterface|int $product
     * @param int $maxQtyForProduct
     * @return string
     */
    public function validateProductMaxQty($product, $itemQty)
    {
        if ($this->isD213961Enabled()) {
            $offer = $this->miraklHelper->getBestOffer($product);
            $maxQtyForProduct = $offer ? $offer->getMaxOrderQuantity() : null;

            if ($this->isEssendantEnabled() && ($maxQtyForProduct === null || $maxQtyForProduct == 0)) {
                $maxQtyForProduct = $this->configuration->getMaxSaleQty();
            }
        } else {
            $stockManager = $this->stockRegistry->getStockItem($product);
            $maxQtyForProduct = $stockManager->getMaxSaleQty();
        }

        $error = '';
        if ($itemQty > $maxQtyForProduct) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . self::LOGGER_MESSAGE
            );
            $error = self::MAX_QTY_ALLOWED_MESSAGE.$maxQtyForProduct;
        }

        return $error;
    }

    public function getProductImage($product, $imageId = 'product_small_image')
    {
        return $this->imageHelper->init($product, $imageId)->getUrl();
    }

    /**
     * @param Product|ProductInterface $product
     * @return bool
     */
    public function checkIfNonCustomizableProduct($product): bool
    {
        if (!$product) {
            return false;
        }

        $attributeSetId = $product->getAttributeSetId();
        if (!$attributeSetId) {
            return false;
        }

        $attributeSetName = $this->loadAttributeSet($attributeSetId);
        return $attributeSetName == self::FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET;
    }

    private function loadAttributeSet($attributeSetId): ?string
    {
        if (empty($attributeSetId)) {
            $this->logger->warning('Attribute set ID is empty.');
            return null;
        }

        if (isset($this->attributeSetLoaded[$attributeSetId])) {
            return $this->attributeSetLoaded[$attributeSetId];
        }

        try {
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
            $this->attributeSetLoaded[$attributeSetId] = $attributeSet->getAttributeSetName();
            return $this->attributeSetLoaded[$attributeSetId];
        } catch (\Exception $e) {
            $this->logger->error('Error loading attribute set (ID: ' . $attributeSetId . '): ' . $e->getMessage());
            return null;
        }
    }
}
