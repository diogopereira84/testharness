<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Total\Creditmemo;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order;

class FixDecimals extends AbstractTotal
{
    private const PRECISION = 4;
    private const MAX_DIFFERENCE = 0.01;

    private const FIELDS = [
        'Subtotal',
        'BaseSubtotal',
        'SubtotalInclTax',
        'BaseSubtotalInclTax',
        'GrandTotal',
        'BaseGrandTotal'
    ];

    public function __construct(
        private readonly HandleMktCheckout $handleMktCheckout,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * Collect totals for credit memo
     *
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo): self
    {
        $order = $creditmemo->getOrder();
        $missingRefund = $this->calculateRemainingRefundAmount($order);

        $this->adjustCreditmemoValues($creditmemo, $order, $missingRefund);

        return $this;
    }

    /**
     * Calculate the missing refund amount
     *
     * @param Order $order
     * @return float
     */
    private function calculateRemainingRefundAmount(Order $order): float
    {
        return $this->roundValue($order->getGrandTotal() - $order->getTotalRefunded());
    }

    /**
     * Adjust credit memo values based on missing refund
     *
     * @param Creditmemo $creditmemo
     * @param Order $order
     * @param float $missingRefund
     * @return void
     */
    private function adjustCreditmemoValues(Creditmemo $creditmemo, Order $order, float $missingRefund): void
    {
        foreach (self::FIELDS as $field) {
            $orderValue = $order->{"get$field"}() - $order->{"get{$field}Refunded"}();
            $this->adjustField($creditmemo, $field, $orderValue, $missingRefund);
        }
    }

    /**
     * Adjust a specific field in the credit memo
     *
     * @param Creditmemo $creditmemo
     * @param string $field
     * @param float $orderValue
     * @param float $missingRefund
     * @return void
     */
    private function adjustField(Creditmemo $creditmemo, string $field, float $orderValue, float $missingRefund): void
    {
        $getter = "get$field";
        $setter = "set$field";

        if ($missingRefund != $orderValue && $creditmemo->$getter()) {
            $difference = $this->roundValue($creditmemo->$getter() - $missingRefund);
            if ($this->isDifferenceWithinThreshold($difference)) {
                $newValue = $this->roundValue($creditmemo->$getter() - $difference);
                $creditmemo->$setter($newValue);
            }
        }
    }

    /**
     * Check if the difference is within the allowed threshold
     *
     * @param float $difference
     * @return bool
     */
    private function isDifferenceWithinThreshold(float $difference): bool
    {
        return $difference < self::MAX_DIFFERENCE && $difference >= 0;
    }

    /**
     * Round a value to the defined precision
     *
     * @param float $value
     * @return float
     */
    private function roundValue(float $value): float
    {
        return round($value, self::PRECISION);
    }
}
