<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Block\Order;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Sales\Model\Order\Address\Renderer;
use Fedex\Orderhistory\Helper\Data as orderHistoryHelper;

/**
 * Order information for print
 *
 * @api
 * @since 100.0.2
 */
class PrintShipment extends \Magento\Sales\Block\Order\PrintShipment
{
    /**
     * @var string
     */
    protected $retailPrintTemplate = 'Fedex_Orderhistory::order/retail-print.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $addressRenderer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Fedex\Orderhistory\Helper\Data $orderHistoryDataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $paymentHelper,
        Renderer $addressRenderer,
        protected orderHistoryHelper $orderHistoryDataHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $paymentHelper,
            $addressRenderer,
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
        $template = $this->_template;
        if ($this->orderHistoryDataHelper->isPrintReceiptRetail()) {
            $template = '';
        }
        return $template;
    }
}
