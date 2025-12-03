<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ProductUnavailabilityMessage\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ProductUnavailabilityMessage\Api\CheckProductAvailabilityInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplaceProduct\Helper\Data;

class CheckProductAvailabilityDataModel implements CheckProductAvailabilityInterface
{
    const XML_PATH_E441563_TOGGLE = 'tiger_team_e_441563';
    const XML_PATH_E441563_PRODUCT_PDP_ERROR_MESSAGE_TITLE =
        'fedex/product_error_message_setting/unavailable_items_generic_message/unavailable_items_generic_message_title';

    const XML_PATH_E441563_PRODUCT_PDP_ERROR_MESSAGE =
        'fedex/product_error_message_setting/unavailable_items_generic_message/unavailable_items_generic_error_message';
    const XML_PATH_E441563_PRODUCT_CARTLINE_ERROR_MESSAGE_TITLE =
        'fedex/product_error_message_setting/unavailable_items_cart_line_generic_message/unavailable_items_cart_line_generic_message_title';

    const XML_PATH_E441563_PRODUCT_CARTLINE_ERROR_MESSAGE =
        'fedex/product_error_message_setting/unavailable_items_cart_line_generic_message/unavailable_items_cart_line_generic_error_message';

    const XML_PATH_TIGER_TEAM_D_228743_OUT_OF_STOCK_FIX = 'tiger_team_D_228743_out_of_stock_fix';

    /**
     * @param ToggleConfig $toggleConfig
     * @param Cart $cart
     * @param StockItemRepository $stockItemRepository
     * @param MarketplaceConfig $config
     * @param Data $helper
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig,
        private readonly Cart $cart,
        private readonly StockItemRepository $stockItemRepository,
        private readonly MarketplaceConfig $config,
        private readonly Data $helper
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isE441563ToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::XML_PATH_E441563_TOGGLE);
    }
    /**
     * {@inheritdoc}
     */
    public function isTigerTeamD228743ToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::XML_PATH_TIGER_TEAM_D_228743_OUT_OF_STOCK_FIX);
    }
    /**
     * {@inheritdoc}
     */
    public function getProductPDPErrorMessageTitle()
    {
        return $this->toggleConfig->getToggleConfig(self::XML_PATH_E441563_PRODUCT_PDP_ERROR_MESSAGE_TITLE);
    }
    /**
     * {@inheritdoc}
     */
    public function getProductCartlineErrorMessageTitle()
    {
        return $this->toggleConfig->getToggleConfig(self::XML_PATH_E441563_PRODUCT_CARTLINE_ERROR_MESSAGE_TITLE);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductCartlineErrorMessage()
    {
        return $this->toggleConfig->getToggleConfig(self::XML_PATH_E441563_PRODUCT_CARTLINE_ERROR_MESSAGE);
    }
    /**
     * {@inheritdoc}
     */
    public function getProductPDPErrorMessage()
    {
        return $this->toggleConfig->getToggleConfig(self::XML_PATH_E441563_PRODUCT_PDP_ERROR_MESSAGE);
    }

    /**
     * @return mixed|string
     * @throws NoSuchEntityException
     */
    public function checkCartHaveUnavailbleProduct()
    {
        $items = $this->cart->getQuote()->getAllItems();

        foreach ($items as $item) {
            $product = $item->getProduct();

            if ($offers = $this->helper->getAllOffers($product)) {
                $allOffersUnavailable = true;

                foreach ($offers as $offer) {
                    if ($offer->getData('quantity') > 0) {
                        $allOffersUnavailable = false;
                        break;
                    }
                }

                if ($allOffersUnavailable || $product->getData("is_unavailable")) {
                    return true;
                }

            } elseif ($product->getData("is_unavailable")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getStockStatus($product)
    {
        $productStock = $this->stockItemRepository->get($product->getId());
        if($productStock->getIsInStock()){
            return true;
        }
        return false;
    }
}
