<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Api\OfferRepositoryInterface;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use \Magento\Catalog\Model\Product;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class ShopManagement implements ShopManagementInterface
{
    /**
     * @param OfferRepositoryInterface $offerRepository
     * @param ShopRepositoryInterface $shopRepository
     * @param AdminConfigHelper $adminConfigHelper
     */
    public function __construct(
        private OfferRepositoryInterface $offerRepository,
        private ShopRepositoryInterface $shopRepository,
        private AdminConfigHelper $adminConfigHelper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getShopByProduct(Product $product): ShopInterface
    {
        $offer = $this->offerRepository->getById($product->getSku());
        return $this->shopRepository->getById((int)$offer->getShopId());
    }

    /**
     * Create a new third party items array organized by seller
     * @param $quote
     * @return array
     */
    public function getThirdPartySellers($quote)
    {
        $result = [];
        $thirdPartyItems = $this->getThirdPartyItems($quote);
        $quoteId = $quote->getId();
        foreach ($thirdPartyItems as $item) {
            $productValue = $this->getProductValue($item, $quoteId, $quote);

            if ($this->adminConfigHelper->isToggleD206707Enabled()) {
                if (($productValue && isset($productValue['external_prod']) && count($productValue['external_prod']) === 0)
                    || (!$productValue || !isset($productValue['external_prod']))) {
                    continue;
                }

                $externalProd = array_pop($productValue['external_prod']);
                $sellerName = $externalProd['product']['vendorReference']['altName'] ?? '';
            } else {
                if (count($productValue['external_prod']) === 0) {
                    continue;
                }

                $externalProd = array_pop($productValue['external_prod']);
                $sellerName = $externalProd['product']['vendorReference']['altName'];
            }

            if (array_key_exists($sellerName, $result)) {
                array_push($result[$sellerName], [
                    'item' => $item,
                    'product' => $productValue
                ]);
            } else {
                $result[$sellerName] = [[
                    'item' => $item,
                    'product' => $productValue
                ]];
            }
        }

        return $result;
    }

    /**
     * Filter all quotes visible items to get third party items
     * @param $quote
     * @return array
     */
    public function getThirdPartyItems($quote)
    {
        return array_filter($quote->getAllVisibleItems(),function ($item){
            return $item->getData('mirakl_offer_id');
        });
    }

    /**
     * Get product value
     *
     * @param object $item
     * @param int $quoteId
     *
     * @return array
     */
    public function getProductValue($item, $quoteId, $quote = null)
    {
        return $this->adminConfigHelper->getProductValue($item, $quoteId, $quote);
    }
}
