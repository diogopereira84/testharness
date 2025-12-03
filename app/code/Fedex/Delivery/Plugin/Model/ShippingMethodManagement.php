<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Plugin\Model;

/**
 * ShippingMethodManagement Model plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class ShippingMethodManagement
{
    /**
     * Filter Shipping Carrier
     *
     * @param object     $shippingMethodManagement
     * @param object       $output
     * @return array
     */
    public function afterEstimateByExtendedAddress($shippingMethodManagement, $output)
    {
        return $this->filterOutput($output);
    }

    private function filterOutput($output)
    {
        $shipping = [];
        if (!isset($output['errors'])) {
            foreach ($output as $shippingMethod) {
                if (is_array($shippingMethod) && $shippingMethod['marketplace'] ||
                    $shippingMethod->getCarrierCode() == 'fedexshipping') {
                    $shipping[] = $shippingMethod;
                }
            }
        }

        if (isset($output['errors'])) {
            return $output;
        }
        return $shipping;
    }
}
