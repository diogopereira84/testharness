<?php
/**
 * @category Fedex
 * @package  Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Model;

use Fedex\Company\Model\Config\Source\PaymentOptions;
use Fedex\Customer\Api\Data\ConfigInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * ConfigProvider Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        protected ConfigInterface $config
    )
    {
    }

    /**
     * Shipping configuration for checkout page
     *
     * @return array
     */
    public function getConfig()
    {
        $marketingOptInEnabled = $this->config->isMarketingOptInEnabled();

        return [
            'marketing_opt_in_toggle' => $marketingOptInEnabled
        ];
    }
}
