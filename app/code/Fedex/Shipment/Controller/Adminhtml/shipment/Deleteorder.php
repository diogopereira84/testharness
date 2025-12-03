<?php
/**
 * https://staging3.office.fedex.com/fedexf4e5d6/shipment/shipment/deleteorder/order_id/21707/
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\Controller\Adminhtml\shipment;

use \Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use \Magento\Quote\Model\QuoteFactory;

class Deleteorder extends \Magento\Backend\App\Action
{
    protected $_publicActions = ['deleteorder'];

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Sales\Model\Order $orderModel
     * @param LoggerInterface $logger
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        protected \Magento\Framework\Registry $registry,
        protected \Magento\Sales\Model\Order $orderModel,
        private ToggleConfig $toggleConfig,
        protected LoggerInterface $logger,
        protected QuoteFactory $quoteFactory
    ) {
        parent::__construct($context);
    }

    /**
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('order_id');

        $quote = $this->getRequest()->getParam('quote');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $order = $this->orderModel->load($id);
            if ($quote) {
                $quoteId = $order->getQuoteId();
                $quote = $this->quoteFactory->create()->load($quoteId);
                $quote->delete();
            }
            $this->registry->register('isSecureArea','true');
            $order->delete();
            $this->registry->unregister('isSecureArea');
            $this->messageManager->addSuccess(__('Order Deleted'));
            return $resultRedirect->setPath('*/*/');
        }
        return $resultRedirect->setPath('*/*/');
    }
}
