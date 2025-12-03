<?php

namespace Fedex\FedexAccountPay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

abstract class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'fedexaccount';
}