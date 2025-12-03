<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\TaxCollector\Model\Quote\Total;

/**
 * Tax Model Class
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Tax extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Collect Grand Custom Tax Amount totals for quote address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $customTaxAmount = $quote->getCustomTaxAmount();

        if (isset($customTaxAmount)) {
            $total->setTotalAmount('custom_tax_amount', $customTaxAmount);
            $total->setBaseTotalAmount('custom_tax_amount', $customTaxAmount);
            $total->setCustomTaxAmount($customTaxAmount);
            $quote->setBaseTotalAmount($customTaxAmount);
            $quote->setCustomTaxAmount($customTaxAmount);

            return $this;
        }
    }

    /**
     * Fetch Quote Data
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $customTaxAmount = $quote->getCustomTaxAmount();

        $result = [];
        if ($customTaxAmount) {
            $result = [
                'code' => 'custom_tax_amount',
                'title' => 'Custom Tax Amount',
                'value' => $customTaxAmount
            ];
        } else {
            return [];
        }
        return $result;
    }

    /**
     * Clear Quote Value Data
     *
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return void
     */
    protected function clearValues(\Magento\Quote\Model\Quote\Address\Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
    }
}
