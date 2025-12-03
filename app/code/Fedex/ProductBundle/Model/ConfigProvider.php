<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Model;

use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Construct
     *
     * @param ConfigInterface $productBundleConfig
     */
    public function __construct(
        protected ConfigInterface $productBundleConfig
    ) {
    }

    /**
     * Get checkout product bundle toggle.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'tiger_e468338' => $this->productBundleConfig->isTigerE468338ToggleEnabled(),
        ];
    }
}
