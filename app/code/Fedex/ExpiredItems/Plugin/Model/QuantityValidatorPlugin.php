<?php
/**
 * @category    Fedex
 * @package     Fedex_ExpiredItems
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ExpiredItems\Plugin\Model;

use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Data;
use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Math\Division as MathDivision;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Connector\Model\OfferFactory;

class QuantityValidatorPlugin
{
    /**
     * @param OfferFactory $offerFactory
     * @param MathDivision $mathDivision
     * @param CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel
     */
    public function __construct(
        protected OfferFactory $offerFactory,
        protected MathDivision $mathDivision,
        private readonly CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel
    ) {
    }

    public function aroundValidate(QuantityValidator $subject, \Closure $proceed, Observer $observer): void
    {
        /** @var QuoteItem $quoteItem */
        $quoteItem = $observer->getEvent()->getItem();

        if (!$quoteItem->getProductId()) {
            $proceed($observer);

            return;
        }

        $qty = $quoteItem->getQty();

        $getMiraklOffer = function (Product $product) {
            return $product->getCustomOption('mirakl_offer');
        };

        /** @var \Magento\Quote\Model\Quote\Item\Option $offerCustomOption */
        $offerCustomOption = $getMiraklOffer($quoteItem->getProduct());

        if (!$offerCustomOption) {
            /** @var QuoteItem $parentItem */
            if ($parentItem = $quoteItem->getParentItem()) {
                $qty = $parentItem->getQty();
                $offerCustomOption = $getMiraklOffer($parentItem->getProduct());
            }
        }

        if (!$offerCustomOption) {
            // Quote item is not associated to any Mirakl offer, process standard validation
            $proceed($observer);
        } else {
            // Mirakl offer has been found, verify that requested qty is available
            $offer = $this->offerFactory->fromJson($offerCustomOption->getValue());

            $product = $quoteItem->getProduct();
            $toggle  = $this->checkProductAvailabilityDataModel->isE441563ToggleEnabled();
            $isProductUnavailable = $product->getData('is_unavailable');

            /** Check the toggle and conditions applied to check if is unavailable message must be shown. */
            if ($offer->getQuantity() < $qty && (!$toggle && ($toggle && !$isProductUnavailable))) {
                $this->addQuantityError($quoteItem, Data::ERROR_QTY, __(
                    'Requested quantity of %1 is not available for product "%2". Quantity available: %3.',
                    (int) $quoteItem->getQty(),
                    $quoteItem->getName(),
                    (int) $offer->getQuantity()
                ));
            }

            if ($offer->getMinOrderQuantity() && $qty < $offer->getMinOrderQuantity()) {
                $this->addQuantityError($quoteItem, Data::ERROR_QTY, __(
                    'The fewest you may purchase is %1.',
                    $offer->getMinOrderQuantity() * 1
                ));
            }

            if ($offer->getMaxOrderQuantity() && $qty > $offer->getMaxOrderQuantity()) {
                $this->addQuantityError($quoteItem, Data::ERROR_QTY, __(
                    'The most you may purchase is %1.',
                    $offer->getMaxOrderQuantity() * 1
                ));
            }

            $qtyIncrements = $offer->getPackageQuantity() * 1;
            if ($qtyIncrements && $this->mathDivision->getExactDivision($qty, $qtyIncrements) != 0) {
                $this->addQuantityError($quoteItem, Data::ERROR_QTY_INCREMENTS, __(
                    'You can buy %1 only in quantities of %2 at a time.',
                    $quoteItem->getName(),
                    $qtyIncrements
                ));
            }
        }
    }

    /**
     * @param   QuoteItem   $quoteItem
     * @param   int         $code
     * @param   string      $message
     */
    protected function addQuantityError(QuoteItem $quoteItem, $code, $message)
    {
        $quoteItem->addErrorInfo('cataloginventory', $code, $message);

        $quoteItem->getQuote()->addErrorInfo(
            'qty',
            'cataloginventory',
            $code,
            __('Please correct the quantity for some products.')
        );
    }
}
