<?php

namespace Fedex\Delivery\Plugin\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class LayoutProcessorPlugin
{
    const TOGGLE_KEY_E411396 = 'tigerteam_e411396Toggle';

    public function __construct(
        protected ToggleConfig $toggleViewModel
    )
    {
    }

    public function afterProcess(LayoutProcessor $subject, $result): array
    {
        if ($this->toggleViewModel->getToggleConfigValue(self::TOGGLE_KEY_E411396)) {
            $result['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['component'] = 'Fedex_Delivery/js/view/shipping-refactored';
        }
        return $result;
    }
}
