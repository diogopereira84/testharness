<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Plugin\Model\Address;

use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Rate
{
    /**
     * Rate Plugin constructor.
     * @param ToggleConfig $toggleConfig
     * @param Data         $helper
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private Data $helper
    ) {
    }

    /**
     * Intercept shipping rate method result
     *
     * @param \Magento\Quote\Model\Quote\Address\Rate $subject
     * @param \Magento\Quote\Model\Quote\Address\AbstractResult $result
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method $rate
     * @return \Magento\Quote\Model\Quote\Address\Rate
     */
    public function afterImportShippingRate($subject, $result, $rate)
    {
        if (
            $this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') &&
            $this->helper->isCommercialCustomer() &&
            $rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Method
        ) {
            $result->setProductionLocation(
                $rate->getProductionLocation()
            );
        }

        return $result;
    }
}
