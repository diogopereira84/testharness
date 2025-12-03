<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Fedex\OrderApprovalB2b\Helper\OrderEmailHelper;

/**
 * SendOrderEmail Controller class
 */
class SendOrderEmail implements ActionInterface
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param OrderEmailHelper $orderEmailHelper
     */
    public function __construct(
        protected Context $context,
        protected OrderEmailHelper $orderEmailHelper
    )
    {
    }

    /**
     * To submit account request form data
     *
     * @return json|boolean
     */
    public function execute()
    {
        $status = $this->context->getRequest()->getParam('status');
        $orderId = $this->context->getRequest()->getParam('order_id');
        $declineMessage = $this->context->getRequest()->getParam('decline_message');
        $orderData = [
            'order_id' => $orderId,
            'status' => $status,
            'decline_message' => $declineMessage,
        ];

        return $this->orderEmailHelper->sendOrderGenericEmail($orderData);
    }
}
