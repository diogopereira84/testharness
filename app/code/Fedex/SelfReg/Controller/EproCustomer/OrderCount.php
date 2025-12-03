<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\EproCustomer;

use Fedex\SelfReg\Model\EproCustomer\OrderHistory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;

/**
 * Controller for obtaining stores suggestions by query.
 *
 * D-105630 Ajax implementation for updating order count in home page
 */
class OrderCount extends Action
{
    /**
     * OrderCount Construct.
     *
     * @param Context      $context
     * @param OrderHistory $orderHistory
     * @return void
     */
    public function __construct(
        Context $context,
        protected OrderHistory $orderHistory
    ) {
        parent::__construct($context);
    }

    /**
     * Get Order count for SDE Homepage
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $orderCount = $this->orderHistory->getOrderCountForHomepage();
        $result->setData($orderCount);

        return $result;
    }
}
