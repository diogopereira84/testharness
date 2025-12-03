<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rafael Silva <rafaelsilva.osv@fedex.com>
 */
declare (strict_types = 1);

namespace Fedex\MarketplaceCheckout\ViewModel;

use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\MarketplaceProduct\Helper\Data as MiraklHelper;
use Magento\Tax\Helper\Data as MagentoTaxHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;

class Cart implements ArgumentInterface
{

    private const FEDEX_SELLER_NAME = 'FedEx Office';

    /**
     * @var MarketPlaceHelper
     */
    private $marketPlaceHelper;

    /**
     * @param CheckoutSession $checkoutSession
     * @param MiraklHelper $miraklHelper
     * @param MagentoTaxHelper $magentoTaxHelper
     * @param NonCustomizableProduct $nonCustomizableProductModel
     */
    public function __construct(
        private CheckoutSession $checkoutSession,
        private MiraklHelper $miraklHelper,
        private MagentoTaxHelper $magentoTaxHelper,
        protected NonCustomizableProduct $nonCustomizableProductModel,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        readonly ToggleConfig $toggleConfig,
        MarketPlaceHelper $marketPlaceHelper
    )
    {
        $this->marketPlaceHelper = $marketPlaceHelper;
    }

    /**
     * Return all sellers in cart
     *
     * @return array
     */
	public function getAllSellersInCart()
	{
        if($this->addToCartPerformanceOptimizationToggle->isActive()) {
            $sellersInCart = [];
            $sellerNamesInCart = [];
            $itemsInCart = $this->checkoutSession->getQuote()->getAllVisibleItems();

            $isFedexSellerAdded = false;

            foreach ($itemsInCart as $item) {
                $offerData = $this->getBestOfferData($item->getProduct());
                $sellerName = $offerData['name'] ?? null;
                $tooltip = '';

                if (!empty($offerData['additional_info']['additional_field_values'])) {
                    foreach ($offerData['additional_info']['additional_field_values'] as $additionalValue) {
                        if (($additionalValue['code'] ?? null) === 'tooltip' && isset($additionalValue['value'])) {
                            $tooltip = $additionalValue['value'];
                            break;
                        }
                    }
                }

                if ($sellerName && !in_array($sellerName, $sellerNamesInCart)) {
                    $sellersInCart[] = [
                        "name" => $sellerName,
                        "tooltip" => $tooltip
                    ];
                    $sellerNamesInCart[] = $sellerName;
                }

                if (!$isFedexSellerAdded && !in_array(self::FEDEX_SELLER_NAME, $sellerNamesInCart)) {
                    array_unshift($sellersInCart, [
                        "name" => self::FEDEX_SELLER_NAME,
                        "tooltip" => ''
                    ]);
                    $sellerNamesInCart[] = self::FEDEX_SELLER_NAME;
                    $isFedexSellerAdded = true;
                }
            }
            return $sellersInCart;
        }

        $sellersInCart = array();
		$itemsInCart = $this->checkoutSession->getQuote()->getAllVisibleItems();

        foreach($itemsInCart as $item)
        {
            $offerData = $this->getBestOfferData( $item->getProduct() );
            $isNameSet = isset($offerData['name']);
            $isNameInCart = false;

            if( $isNameSet ) {
                $isNameInCart = in_array( $offerData['name'], $sellersInCart );
            }

            $isFedexSellerInCart = in_array( self::FEDEX_SELLER_NAME, $sellersInCart );

            $tooltip = '';
            $hasAdditionalFieldValues = isset( $offerData['additional_info']['additional_field_values'] );

            if($hasAdditionalFieldValues) {
                $additionalFieldValues = $offerData['additional_info']['additional_field_values'];

                foreach ($additionalFieldValues as $additionalValue) {
                    if (!(isset($additionalValue['code']) && $additionalValue['code'] == 'tooltip')) {
                        continue;
                    }

                    if (!isset($additionalValue['value'])) {
                        break;
                    }

                    $tooltip = $additionalValue['value'];
                    break;
                }
            }

            if( $isNameSet && !$isNameInCart )
            {
                array_push($sellersInCart, array(
                    "name" => $offerData['name'],
                    "tooltip" => $tooltip
                ));
            }
            else if( !$isFedexSellerInCart )
            {
                array_unshift( $sellersInCart, array(
                    "name" => self::FEDEX_SELLER_NAME,
                    "tooltip" => ''
                ));
            }
        }

        return $sellersInCart;
    }

    /**
     * Returns true if product has available mirakl offers
     *
     * @param   Product Magento\Catalog\Model\Product $product
     * @return  bool
     */
    public function hasMiraklOffers($product)
    {
        return $this->miraklHelper->hasAvailableOffersForProduct($product);
    }

    /**
     * Checks if there are only third party products in the cart
     *
     * @return bool
     */
    public function isThirdPartyOnlyCart()
    {
        $cartItems  = $this->checkoutSession->getQuote()->getAllVisibleItems();

        foreach ($cartItems as $item) {
            if ( !$this->hasMiraklOffers($item->getProduct()) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if there are any third party with punchout disabled products in the cart
     *
     * @return bool
     */
    public function isThirdPartyOnlyCartWithAnyPunchoutDisabled()
    {
        return $this->nonCustomizableProductModel->isThirdPartyOnlyCartWithAnyPunchoutDisabled();
    }

    /**
     * Returns the amount of merged cells
     *
     * @return int
     */
    public function getMergedCells()
    {
        if ($this->magentoTaxHelper->displayCartBothPrices()) {
            return 2;
        }
        return 1;
    }

    /**
     * Returns an array with the data from the best mirakl offer of a product
     *
     * @param   Product Magento\Catalog\Model\Product $product
     * @return  array
     */
    public function getBestOfferData($product)
    {
        if ($this->hasMiraklOffers($product)) {
            if (!$product->getData('main_offer_data')) {
                $bestOffer = $this->miraklHelper->getBestOffer($product);
                $product->setData('main_offer_data', $this->miraklHelper->getOfferShop($bestOffer)->getData());
            }
        }
        return $product->getData('main_offer_data');
    }

    /**
     * Returns the FedEx seller name
     *
     * @return string
     */
    public function getFedexSellerName()
    {
        return self::FEDEX_SELLER_NAME;
    }

    /**
     * Checks if marketplace CBB toggle is enabled
     * @return bool
     */
    public function isMktCbbEnabled()
    {
        return $this->nonCustomizableProductModel->isMktCbbEnabled();
    }

    /**
     * Checks if MazeGeeks - E-451614 Improving Update Item Qty Cart enabled
     * @return bool
     */
    public function isImprovingUpdateItemQtyCart()
    {
        return (boolean)$this->toggleConfig->getToggleConfigValue('mazegeeks_improving_update_item_qty_cart');
    }
    /**
     * Check if the given cart item has a legacy document.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool|null
     */
    public function checkLegacyDocumentInCart($item)
    {
        return $this->marketPlaceHelper->checkItemIsLegacyDocument($item);
    }

    /**
     * Check if the given cart bundle item has a legacy document.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool|null
     */
    public function checkBundleLegacyDocumentInCart($item)
    {
        if ($item->getProductType() !== Type::TYPE_BUNDLE) {
            return $this->marketPlaceHelper->checkItemIsLegacyDocument($item);
        }

        foreach ($item->getChildren() as $childItem) {
            return $this->marketPlaceHelper->checkItemIsLegacyDocument($childItem);
        }
    }

    /**
     * Check if the legacy document API call should be triggered on cart toggle.
     *
     * @return bool|null
     */
    public function checkLegacyDocApiOnCartToggle()
    {
        return $this->marketPlaceHelper->checkLegacyDocApiOnCartToggle();
    }
}
