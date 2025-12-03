<?php
declare(strict_types=1);

namespace Fedex\Pay\Model\Checkout;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class RemoveDiscountLayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @var string[]
     */
    private const DISCOUNT_COMPONENT_PATHS = [
        'components/checkout/children/steps/children/billing-step/children/payment/children/afterMethods/children/discount',
        'components/checkout/children/sidebar/children/summary/children/totals/children/discount'
    ];

    public function __construct(
        private ToggleConfig $toggleConfig,
    )
    {
    }

    /**
     * Process js Layout of checkout page
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        if (!$this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
            return $jsLayout;
        }

        foreach (self::DISCOUNT_COMPONENT_PATHS as $path) {
            $jsLayout = $this->removeComponentByPath($jsLayout, $path);
        }

        return $jsLayout;
    }

    /**
     * Remove component by path notation
     *
     * @param array $jsLayout
     * @param string $path
     * @return array
     */
    private function removeComponentByPath(array $jsLayout, string $path): array
    {
        $keys = explode('/', $path);
        $array = &$jsLayout;

        foreach (array_slice($keys, 0, -1) as $key) {
            if (!isset($array[$key])) {
                return $jsLayout;
            }
            $array = &$array[$key];
        }

        $lastKey = end($keys);
        if (isset($array[$lastKey])) {
            unset($array[$lastKey]);
        }

        return $jsLayout;
    }
}
