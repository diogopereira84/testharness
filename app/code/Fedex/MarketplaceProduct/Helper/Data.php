<?php
declare (strict_types = 1);

namespace Fedex\MarketplaceProduct\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;
use Mirakl\Connector\Helper\Offer as ConnectorOfferHelper;
use Mirakl\Connector\Model\Offer as OfferModel;
use Mirakl\Connector\Model\Product\Inventory\IsOperatorProductAvailable;
use Mirakl\Core\Model\Shop;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Block\Product\Context as ProductContext;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\ShopFactory;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Magento\Catalog\Helper\Data as CatalogHelper;

class Data extends AbstractHelper
{
    /**
     * Thrid party product message that will be displayed without an offer related.
     */
    private const XML_MESSAGE_PRODUCT_WITHOUT_OFFER = 'fedex/three_p_product/offer_product_without_relation_message';

    const XML_PATH_OFFER_NEW_STATE = 'mirakl_frontend/offer/new_state';

    const XML_PATH_STORE_INFO_NAME = 'general/store_information/name';

    const CUSTOM_SELLER_ALT_NAME_CODE = 'seller-alt-name';

    const CUSTOM_SELLER_TOOLTIP_CODE = 'tooltip';

    /**
     * @var Registry
     */
    protected $coreRegistry;


    /**
     * @param Context $context
     * @param ScopeConfigInterface $config
     * @param ConnectorOfferHelper $connectorOfferHelper
     * @param IsOperatorProductAvailable $isOperatorProductAvailable
     * @param ProductContext $coreRegistry
     * @param ShopFactory $shopFactory
     * @param ShopResourceFactory $shopResourceFactory
     * @param MarketplaceHelper $marketPlaceHelper
     * @param CatalogHelper $catalogHelper
     */
    public function __construct(
        Context $context,
        protected ScopeConfigInterface $config,
        protected ConnectorOfferHelper $connectorOfferHelper,
        protected IsOperatorProductAvailable $isOperatorProductAvailable,
        ProductContext $coreRegistry,
        protected ShopFactory $shopFactory,
        protected ShopResourceFactory $shopResourceFactory,
        private MarketplaceHelper $marketPlaceHelper,
        private CatalogHelper $catalogHelper
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry->getRegistry();
    }

    /**
     * Return message of a product that has no associated offers
     *
     * @return mixed
     */
    public function getOfferErrorRelationMessage()
    {
        return $this->config->getValue(self::XML_MESSAGE_PRODUCT_WITHOUT_OFFER);
    }

    /**
     * Get the first item to display
     *
     * @param   Product|ProductInterface $product
     * @return  OfferModel|null
     */
    public function getBestOffer(Product|ProductInterface $product)
    {
        if (!$this->isOperatorProductAvailable($product)) {
            return null;
        }

        if (!$product->getData('main_offer')) {
            $offers = $this->getAllOffers($product);
            $product->setData('main_offer', array_shift($offers));
        }

        return $product->getData('main_offer');
    }

    /**
     * Get all offers
     *
     * @param   Product     $product
     * @param   int|array   $excludeOfferIds
     * @return  OfferModel[]
     */
    public function getAllOffers(Product $product, $excludeOfferIds = null)
    {
        /** @var OfferModel[] $offers */
        $offers = $this->connectorOfferHelper
            ->getAvailableOffersForProduct($product, $excludeOfferIds)
            ->getItems();

        $this->sortOffers($offers);


        return $offers;
    }

    /**
     * @param   Product     $product
     * @return  bool
     */
    public function isOperatorProductAvailable(Product $product)
    {
        if (!$product->hasData('operator_product_available')) {
            $isAvailable = $product->isSalable() && $this->isOperatorProductAvailable->execute($product);
            $product->setData('operator_product_available', $isAvailable);
        }
        return $product->getData('operator_product_available');
    }

    /**
     * Sort given offers array by new state and price
     *
     * @param   OfferModel[]    $offers
     * @return  void
     */
    public function sortOffers(array &$offers)
    {
        uasort($offers, function ($offer1, $offer2) {
            /** @var OfferModel $offer1 */
            /** @var OfferModel $offer2 */
            if ($offer1->getStateCode() == $offer2->getStateCode()) {
                $totalPriceOffer1 = $offer1->getPrice() + $offer1->getMinShippingPrice();
                $totalPriceOffer2 = $offer2->getPrice() + $offer2->getMinShippingPrice();
                return $totalPriceOffer1 < $totalPriceOffer2 ? -1 : 1;
            }

            if ($this->isProductNew($offer1)) {
                return -1;
            }

            return $offer1->getStateCode() > $offer2->getStateCode() ? -1 : 1;
        });
    }

    /**
     * Indicates whether the specified offer is at New state or not
     *
     * @param   OfferModel  $offer
     * @return  bool
     */
    public function isProductNew(OfferModel $offer)
    {
        return $offer->getStateCode() === $this->getNewOfferStateId();
    }

    /**
     * Return if product has available offer
     *
     * @param   Product $product
     * @return  bool
     */
    public function hasAvailableOffersForProduct($product)
    {
        return $this->connectorOfferHelper->hasAvailableOffersForProduct($product);
    }

    /**
     * Returns shop of specified offer if available
     *
     * @param   OfferModel  $offer
     * @return  Shop
     */
    public function getOfferShop(OfferModel $offer)
    {
        return $this->connectorOfferHelper->getOfferShop($offer);
    }

    /**
     * Returns condition name of specified offer
     *
     * @param   OfferModel  $offer
     * @return  string
     */
    public function getOfferCondition(OfferModel $offer)
    {
        return $this->connectorOfferHelper->getOfferCondition($offer);
    }

    /**
     * @return mixed
     */
    public function getNewOfferStateId()
    {
        return $this->config->getValue(self::XML_PATH_OFFER_NEW_STATE);
    }

    /**
     * Get shop by offer
     *
     * @param $offers
     * @return Shop
     */
    public function getShopByOffer($offers)
    {
        $offers = array_slice($offers, 0);
        $shopId = $offers[0]->getData('shop_id');
        return $this->getShop($shopId);
    }

    /**
     * Get Custom attributes
     *
     * @param $offers
     * @return string[]
     */
    public function getCustomAttributes($offers)
    {
        $shop     = $this->getShopByOffer($offers);
        $altValue = ['shop_id' => $shop->getId()];
        $altValue['offer_id'] = $offers[0]->getId();
        foreach($shop->getAdditionalInfo()['additional_field_values'] as $additionalInfo){
            $altValue[$additionalInfo['code']] = $additionalInfo['value'];
        }
        return $altValue;
    }

    /**
     * Get current product
     *
     * @return  null|Product
     */
    public function getProduct()
    {
        return $this->coreRegistry->registry('product');
    }

    /**
     * Returns shop of specified offer if available
     *
     * @return  Shop
     */
    public function getShop($shopId)
    {
        $shop = $this->shopFactory->create();
        $this->shopResourceFactory->create()->load($shop, $shopId);

        return $shop;
    }

    /**
     * @return bool
     */
    public function canMovePageTitleToNewLocation(): bool
    {
        if ($this->marketPlaceHelper->isEssendantToggleEnabled()) {
            $product = $this->catalogHelper->getProduct();
            if ($product->getTypeId() == Configurable::TYPE_CODE
                && $this->hasAvailableOffersForProduct($product)) {
                return true;
            }
        }
        return false;
    }

}
