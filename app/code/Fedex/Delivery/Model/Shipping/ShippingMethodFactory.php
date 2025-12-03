<?php

declare(strict_types=1);

namespace Fedex\Delivery\Model\Shipping;

use Magento\Quote\Api\Data\ShippingMethodExtensionFactory;
use Magento\Quote\Api\Data\ShippingMethodInterface;

class ShippingMethodFactory
{
    /**
     * @param ShippingMethodExtensionFactory $extensionFactory
     */
    public function __construct(
        private readonly ShippingMethodExtensionFactory $extensionFactory
    ){
    }
    public function createFromArray(array $shippingMethods): array
    {
        $result = [];
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod instanceof ShippingMethodInterface) {
                $result[] = new ShippingMethod(
                    $shippingMethod->getCarrierCode(),
                    $shippingMethod->getMethodCode(),
                    $shippingMethod->getAmount(),
                    strtotime($this->replaceEndOfDayString($shippingMethod->getMethodTitle()))
                );
            } elseif (is_array($shippingMethod)) {
                $result[] = new ShippingMethod(
                    (string) ($shippingMethod['seller_id'] ?? $shippingMethod['carrier_code'] ?? 'marketplace'),
                    (string) ($shippingMethod['method_code'] ?? $shippingMethod['carrier_code'] ?? 'marketplace'),
                    (float) ($shippingMethod['amount'] ?? 0),
                    strtotime($this->replaceEndOfDayString($shippingMethod['deliveryDate'] ?? ''))
                );
            }
        }
        return $result;
    }

    /**
     * @param $shippingMethods
     * @param ShippingMethod[] $updatedMethods
     * @return mixed
     */
    public function convertToArray($shippingMethods, $updatedMethods)
    {
        foreach ($updatedMethods as $key => $updatedMethod) {
            $shippingMethod = &$shippingMethods[$key];
            if ($shippingMethod instanceof ShippingMethodInterface) {

                $extensionAttributes = $shippingMethod->getExtensionAttributes() ?? $this->extensionFactory->create();
                $extensionAttributes->setCheapest($updatedMethod->isCheapest());
                $extensionAttributes->setFastest($updatedMethod->isFastest());
                $shippingMethod->setExtensionAttributes($extensionAttributes);
            } elseif (is_array($shippingMethods[$key])) {

                $shippingMethod = &$shippingMethods[$key];
                $shippingMethod['extension_attributes'] = [
                    'fastest' => $updatedMethod->isFastest(),
                    'cheapest' => $updatedMethod->isCheapest()
                ];
            }
        }
        return $shippingMethods;
    }

    /**
     * @param string $dateTime
     * @return array|string
     */
    private function replaceEndOfDayString(string $dateTime): array|string
    {
        if(str_contains($dateTime, 'End of Day')) {
            $dateTime = str_replace('End of Day', '11:59pm', $dateTime);
        }

        return $dateTime;
    }
}
