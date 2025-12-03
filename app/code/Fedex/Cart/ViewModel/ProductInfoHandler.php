<?php
/**
 * @category Fedex
 * @package Fedex_Cart
 * @copyright (c) 2022.
 */
declare(strict_types=1);

namespace Fedex\Cart\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class ProductInfoHandler implements ArgumentInterface
{
    protected const PRODUCT_CONFIG = 'productConfig';
    protected const DESIGN_PRODUCT = 'designProduct';

    /**
     * @param $item
     * @return array
     */
    public function getItemExternalProd($item)
    {
        if (!$item) {
            return false;
        }
        $infoBuyRequest = $this->getInfoBuyRequest($item);
        if (!isset($infoBuyRequest['external_prod'][0])) {
            return [];
        }

        return (array)$infoBuyRequest['external_prod'][0];
    }

    /**
     * @param AbstractItem $item
     * @return string
     */
    public function getFxoMenuId(AbstractItem $item)
    {
        $infoBuyRequest = $this->getInfoBuyRequest($item);

        return $infoBuyRequest['fxoMenuId'];
    }

    /**
     * @param AbstractItem $item
     * @return array
     */
    public function getProductConfig(AbstractItem $item)
    {
        $infoBuyRequest = $this->getInfoBuyRequest($item);

        return (array)$infoBuyRequest[self::PRODUCT_CONFIG];
    }

    /**
     * @param AbstractItem $item
     * @return array
     */
    public function getProductRateTotal(AbstractItem $item)
    {
        $infoBuyRequest = $this->getInfoBuyRequest($item);

        return isset($infoBuyRequest['productRateTotal']) ? (array) $infoBuyRequest['productRateTotal'] : [];
    }

    /**
     * @param AbstractItem $item
     * @return array
     */
    public function getQuantityChoices(AbstractItem $item)
    {
        $infoBuyRequest = $this->getInfoBuyRequest($item);

        return isset($infoBuyRequest['quantityChoices']) ? (array) $infoBuyRequest['quantityChoices'] : [];
    }

    /**
     * @param AbstractItem $item
     * @return array
     */
    public function getFileManagementState(AbstractItem $item)
    {
        $infoBuyRequest = $this->getInfoBuyRequest($item);
        return isset($infoBuyRequest['fileManagementState']) ? (array) $infoBuyRequest['fileManagementState'] : [];
    }

    /**
     * @param AbstractItem $item
     * @return string
     */
    public function getDesign(AbstractItem $item)
    {
        $externalProd = $this->getItemExternalProd($item);
        $designId = false;
        if (isset($externalProd['fxo_product'])) {
            $designProduct = $externalProd['fxo_product']->fxoProductInstance->productConfig->designProduct ?? false;
            if ($designProduct) {
                $designId = $designProduct->designId ?? false;
            }
        } else {
            $infoBuyRequest = $this->getInfoBuyRequest($item);
            $productConfig = false;
            if (isset($infoBuyRequest[self::PRODUCT_CONFIG])) {
                $productConfig = (array)$infoBuyRequest[self::PRODUCT_CONFIG];
            }
            if ($productConfig
            && isset($productConfig[self::DESIGN_PRODUCT])
            && !empty($productConfig[self::DESIGN_PRODUCT])) {
                $designId = $productConfig[self::DESIGN_PRODUCT]->designId;
            }
        }

        return $designId;
    }

    public function getInfoBuyRequest($item)
    {
        if ($item instanceof AbstractItem) {
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');

            return $infoBuyRequest && $infoBuyRequest->getValue() ? (array)json_decode($infoBuyRequest->getValue()) : [];
        } elseif ($item instanceof OrderItem) {
            $infoBuyRequest = $item->getProductOptions();

            return $infoBuyRequest['info_buyRequest'] ?? [];
        }

        return false;
    }
}
