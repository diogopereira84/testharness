<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Block\Order;

use Fedex\Orderhistory\Helper\Data as orderHistoryHelper;
use Magento\Framework\App\Http\Context as httpContext;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as templateContext;
use Magento\Payment\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

/**
 * Sales order view block
 *
 * @api
 * @since 100.0.2
 */
class View extends \Magento\Sales\Block\Order\View
{
    /**
     * @var string
     */
    protected $template = 'Magento_Sales::order/view.phtml';

    /**
     * Core registry class
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var httpContext
     */
    protected $httpContext;

    /**
     * @var Data
     */
    protected $paymentHelper;

    /**
     * @param templateContext $context
     * @param Registry $registry
     * @param httpContext $httpContext
     * @param Data $paymentHelper
     * @param orderHistoryHelper $orderHistoryDataHelper
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        templateContext $context,
        Registry $registry,
        httpContext $httpContext,
        Data $paymentHelper,
        protected orderHistoryHelper $orderHistoryDataHelper,
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $httpContext,
            $paymentHelper,
            $data
        );
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        $template = $this->template;
        $coreOverrideToggle = $this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override');
        if (!$coreOverrideToggle && $this->orderHistoryDataHelper->isPrintReceiptRetail()) {
            $template = '';
        }
        return $template;
    }
}
