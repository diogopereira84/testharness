<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Block\Order;

use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;

/**
 * Invoice view  comments form
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class RetailInfo extends \Magento\Sales\Block\Order\Info
{
    /**
     * @var string
     */
    protected $retailTemplate = 'Fedex_Orderhistory::order/retail-view.phtml';

    /**
     * Core
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;
    
    /**
     * @param TemplateContext $context
     * @param Registry $coreRegistry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param OrderHistoryHelper $orderHistoryHelper
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $coreRegistry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        protected OrderHistoryHelper $orderHistoryHelper,
        array $data = []
    ) {
        parent::__construct($context, $coreRegistry, $paymentHelper, $addressRenderer, $data);
    }

    /**
     * @inheritdoc
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        if ($this->orderHistoryHelper->isModuleEnabled()
        && $this->orderHistoryHelper->isEnhancementEnabeled()
        && !$this->orderHistoryHelper->getIsSdeStore() //B-1255707 show order view similar to retail
        ) {
            $this->pageConfig->getTitle()->set(__('Order Number #%1', $this->getOrder()->getRealOrderId()));
        } elseif ($this->orderHistoryHelper->isPrintReceiptRetail()) {
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
        if ($this->orderHistoryHelper->isPrintReceiptRetail()) {
            $template = $this->retailTemplate;
        }
        return $template;
    }
}
