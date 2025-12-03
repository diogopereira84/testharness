<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Plugin\Block\Order;

use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Magento\Sales\Block\Order\View as Subject;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

/**
 * Order history view block plugin
 *
 */
class ViewPlugin
{
    /**
     * @var string
     */
    protected $template = 'Magento_Sales::order/view.phtml';

    /**
     * @param OrderHistoryHelper $orderHistoryDataHelper
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected OrderHistoryHelper $orderHistoryDataHelper,
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * After GetTemplate
     *
     * @param Subject $subject
     * @param string $result
     * @return string
     */
    public function afterGetTemplate(Subject $subject, $result)
    {
        $coreOverrideToggle = $this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override');
        if ($coreOverrideToggle && $this->orderHistoryDataHelper->isPrintReceiptRetail()) {
            $result = '';
        }
        return $result;
    }
}
