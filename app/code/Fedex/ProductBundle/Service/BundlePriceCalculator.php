<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Psr\Log\LoggerInterface;

class BundlePriceCalculator
{
    /**
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CartRepositoryInterface $quoteRepository
    ) {}

    /**
     * Calculate total bundle price from rate quote and apply to quote item
     *
     * @param array $rateQuoteResponse
     * @param QuoteItem $quoteItem
     * @return void
     * @throws LocalizedException
     */
    public function calculateBundlePrice(array $rateQuoteResponse, QuoteItem $quoteItem): void
    {
        try {
            $productLines = $this->extractProductLines($rateQuoteResponse);
            $childInstanceIds = $this->getChildrenInstanceIds($quoteItem->getChildren());

            $total = $discount = $basePrice = 0.0;

            foreach ($productLines as $line) {
                if (!in_array($line['instanceId'] ?? null, $childInstanceIds, true)) {
                    continue;
                }
                $total     += $this->calculateFinalLinePrice($line);
                $discount  += $this->calculateDiscountLinePrice($line);
                $basePrice += $this->calculateLinePrice($line);
            }

            $bundlePrice        = round($total, 2);
            $bundleDiscount     = round($discount, 2);
            $bundleBaseUnitPrice = round($basePrice, 2);

            if ($bundlePrice > 0) {
                $this->applyCustomPriceToQuoteItem(
                    $quoteItem,
                    $bundlePrice,
                    $bundleDiscount,
                    $bundleBaseUnitPrice
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('Error calculating bundle price', [
                'exception' => $e,
                'rateQuoteResponse' => $rateQuoteResponse,
            ]);
        }
    }

    /**
     * @param array $rateQuoteResponse
     * @return array
     * @throws LocalizedException
     */
    private function extractProductLines(array $rateQuoteResponse): array
    {
        $details = $rateQuoteResponse['output']['rateQuote']['rateQuoteDetails']
            ?? $rateQuoteResponse['output']['rate']['rateDetails']
            ?? null;

        if (!is_array($details) || empty($details)) {
            throw new LocalizedException(__('Rate quote details not found or invalid.'));
        }

        return $details[0]['productLines'] ?? [];
    }

    /**
     * @param array $line
     * @return float
     */
    private function calculateFinalLinePrice(array $line): float
    {
        $price = isset($line['productRetailPrice'])
            ? preg_replace('/[\$,]/', '', (string)$line['productRetailPrice'])
            : '0';
        $tax = isset($line['productTaxAmount'])
            ? preg_replace('/[\$,]/', '', (string)$line['productTaxAmount'])
            : '0';
        $discount = isset($line['productDiscountAmount'])
            ? preg_replace(['/[($]/', '/[)]/', '/,/', '/\$/'], '', (string)$line['productDiscountAmount'])
            : '0';

        return (float)$price + (float)$tax - (float)$discount;
    }

    /**
     * @param array $line
     * @return float
     */
    private function calculateLinePrice(array $line): float
    {
        $price = isset($line['productRetailPrice'])
            ? preg_replace('/[\$,]/', '', (string)$line['productRetailPrice'])
            : '0';
        return (float)$price;
    }

    /**
     * @param array $line
     * @return float
     */
    private function calculateDiscountLinePrice(array $line): float
    {
        $discount = isset($line['productDiscountAmount'])
            ? preg_replace(['/[($]/', '/[)]/', '/,/', '/\$/'], '', (string)$line['productDiscountAmount'])
            : '0';
        return (float)$discount;
    }

    /**
     * @param QuoteItem $item
     * @param float $unitPrice
     * @param float $discountAmount
     * @param float $basePrice
     * @return void
     */
    private function applyCustomPriceToQuoteItem(
        QuoteItem $item,
        float $unitPrice,
        float $discountAmount,
        float $basePrice
    ): void {
        try {
            $qty = (float) $item->getQty();

            $rowTotal     = $basePrice * $qty;
            $totalDiscount = $discountAmount * $qty;

            $item->setCustomPrice($unitPrice);
            $item->setOriginalCustomPrice($unitPrice);
            $item->setBasePrice($unitPrice);
            $item->setPrice($unitPrice);
            $item->setPriceInclTax($rowTotal);
            $item->setBasePriceInclTax($rowTotal);
            $item->setRowTotal($rowTotal);
            $item->setBaseRowTotal($rowTotal);
            $item->setDiscountAmount($totalDiscount);
            $item->setBaseDiscountAmount($totalDiscount);
            $item->setDiscount($totalDiscount);

            if ($product = $item->getProduct()) {
                $product->setIsSuperMode(true);
            }

            if ($quote = $item->getQuote()) {
                $quote->collectTotals();
                $this->quoteRepository->save($quote);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to apply custom price to quote item', [
                'exception' => $e,
                'item_id'   => $item->getId(),
            ]);
        }
    }

    /**
     * @param array $bundleChildren
     * @return array
     */
    private function getChildrenInstanceIds(array $bundleChildren): array
    {
        return array_filter(array_map(
            static fn(QuoteItem $child) => $child->getId(),
            $bundleChildren
        ));
    }
}
