<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote\Item;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Mirakl\FrontendDemo\Helper\Config;
use Mirakl\FrontendDemo\Model\Quote\Loader as QuoteLoader;


class Quote extends \Mirakl\FrontendDemo\Helper\Quote
{
    /**
     * @param Context $context
     * @param Config $config
     * @param QuoteLoader $quoteLoader
     * @param QuoteSynchronizer $quoteSynchronizer
     * @param QuoteUpdater $quoteUpdater
     * @param QuoteHelper $quoteHelper
     * @param OfferCollector $offerCollector
     */
    public function __construct(
        Context                   $context,
        Config                    $config,
        QuoteLoader               $quoteLoader,
        QuoteSynchronizer         $quoteSynchronizer,
        QuoteUpdater              $quoteUpdater,
        QuoteHelper               $quoteHelper,
        OfferCollector            $offerCollector
    ) {
        parent::__construct(
            $context,
            $config,
            $quoteLoader,
            $quoteSynchronizer,
            $quoteUpdater,
            $quoteHelper,
            $offerCollector
        );
    }

    /**
     * @param Item $item
     * @return mixed[]
     */
    public function getMarketplaceRateQuoteRequest(Item $item): array
    {
        $additionalData = json_decode($item->getAdditionalData());
        $product = $item->getProduct();
        $mapSku = $product->getData('map_sku');

        if (empty($mapSku)) {
           $mapSku = $additionalData->map_sku??null;
        }

        return [
            "id" => $product->getData('product_id'),
            "qty" => $additionalData->quantity,
            "name" => $item->getName(),
            "version" => "1",
            "instanceId" => $item->getItemId(),
            "vendorReference" => [
                "vendorId" => $item->getData('mirakl_shop_id'),
                "vendorProductName" => $item->getName(),
                "vendorProductDesc" => $item->getName(),
            ],
            "externalSkus" => [
                [
                    "skuDescription" => $additionalData->marketplace_name ?? $additionalData->navitor_name,
                    "skuRef" => $mapSku,
                    "code" => $mapSku,
                    "unitPrice" => $additionalData->unit_price,
                    "price" => $additionalData->total,
                    "qty" => $additionalData->quantity
                ]
            ]
        ];
    }
}
