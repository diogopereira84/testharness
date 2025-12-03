<?php

/** @codeCoverageIgnore */

namespace Fedex\CcPay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

abstract class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'fedexccpay';
}