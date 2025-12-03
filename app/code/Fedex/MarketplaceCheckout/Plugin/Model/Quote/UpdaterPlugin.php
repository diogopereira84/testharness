<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Plugin\Model\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Updater;
use Magento\Quote\Api\Data\CartItemInterface;
use Fedex\MarketplaceCheckout\Model\QuoteOptions;
use Fedex\MarketplaceCheckout\Helper\Data;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;

class UpdaterPlugin
{
    /**
     * @param OfferCollector $offerCollector
     * @param Config $config
     * @param PriceCurrencyInterface $priceCurrency
     * @param QuoteOptions $quoteOptions
     * @param Data $data
     */
    public function __construct(
        private OfferCollector $offerCollector,
        private Config $config,
        private PriceCurrencyInterface $priceCurrency,
        private QuoteOptions $quoteOptions,
        private readonly Data $data
    ) {
    }

    /**
     * @param Updater $subject
     * @param null $result
     * @param CartInterface $quote
     * @return void
     */
    public function afterSynchronize(Updater $subject, $result, CartInterface $quote): void
    {
        foreach ($this->offerCollector->getItemsWithOffer($quote) as $item) {
            $shippingRateOffer = $subject->getItemShippingRateOffer($item);

            $offerPrice = $shippingRateOffer->getPrice();
            $itemPrice = $this->config->getOffersIncludeTax($quote->getStoreId())
                ? $item->getPriceInclTax()
                : $item->getPrice();

            if ($itemPrice != $offerPrice) {
                $item->removeMessageByText(__(
                    'Price has changed from %1 to %2',
                    $this->priceCurrency->format($itemPrice, false),
                    $this->priceCurrency->format($offerPrice, false)
                ));
            }
        }
    }

    /**
     * @param Updater $subject
     * @param OrderShippingFee $result
     * @param CartItemInterface $item
     * @return OrderShippingFee
     */
    public function afterGetItemOrderShippingFee(
        Updater $subject,
        OrderShippingFee $result,
        CartItemInterface $item
    ): OrderShippingFee {
        $additionalData = json_decode($item->getAdditionalData(), true) ?? [];
        if (isset($additionalData['mirakl_shipping_data'])) {
            return $result;
        }

        $offers = $result->getOffers() ?? [];
        foreach($offers as $shippingRateOffer) {
            if ($item->getMiraklOfferId() == $shippingRateOffer->getId()) {
                $shippingRateOffer->setLineShippingPrice(0);
            }
        }

        return $result;
    }
}
