<?php
namespace Fedex\SDE\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Fedex\SDE\Model\Customer\OrderHistory;

/**
 * Controller for obtaining stores suggestions by query.
 *
 * B-1255699 Ajax implementation for updating order count in home page
 */
class OrderCount extends Action implements HttpPostActionInterface
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
