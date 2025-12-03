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
use Fedex\Orderhistory\Helper\Data as orderHistoryDataHelper;

/**
 * Order information for print
 *
 * @api
 * @since 100.0.2
 */
class RetailPrintShipment extends \Magento\Sales\Block\Order\Info
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
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $addressRenderer;

    /**
     * @param Context $context,
     * @param Registry $registry,
     * @param Data $paymentHelper,
     * @param Renderer $addressRenderer,
     * @param Data $orderHistoryDataHelper,
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $paymentHelper,
        Renderer $addressRenderer,
        protected orderHistoryDataHelper $orderHistoryDataHelper,
        array $data = []
    ) {
        $this->paymentHelper = $paymentHelper;
        parent::__construct(
            $context,
            $registry,
            $this->paymentHelper,
            $addressRenderer,
            $data
        );
    }

    /**
     * @inheritdoc
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        if ($this->orderHistoryDataHelper->isModuleEnabled() &&
         $this->orderHistoryDataHelper->isEnhancementEnabeled()
         ) {
            $this->pageConfig->getTitle()->set(__('Order Number #%1', $this->getOrder()->getRealOrderId()));
        } elseif ($this->orderHistoryDataHelper->isPrintReceiptRetail()) {
            $this->pageConfig->getTitle()->set(__('Order number #%1', $this->getOrder()->getRealOrderId()));
        } else {
            $this->pageConfig->getTitle()->set(__('Order  # %1', $this->getOrder()->getRealOrderId()));
        }
        $infoBlock = $this->paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        $template = '';
        if ($this->orderHistoryDataHelper->isPrintReceiptRetail()) {
            $template = $this->retailPrintTemplate;
        }
        return $template;
    }
}
