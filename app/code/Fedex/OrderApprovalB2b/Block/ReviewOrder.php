<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Fedex\OrderApprovalB2b\Model\OrderHistory\GetAllOrders;
use Magento\Theme\Block\Html\Pager;

/**
 * ReviewOrder Block class
 */
class ReviewOrder extends Template
{
    /**
     * Initializing Constructor
     *
     * @param Context $context
     * @param GetAllOrders $getAllOrders
     */
    public function __construct(
        Context $context,
        protected GetAllOrders $getAllOrders
    ) {
        parent::__construct($context);
    }

    /**
     * Prepare layout for template.
     *
     * @return OrderHistory
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $getAllOrderHirory = $this->getAllOrderHirory();
        if ($getAllOrderHirory) {
            $pager = $this->getLayout()->createBlock(Pager::class, 'reviews.order.pager');
            $pager->setCollection($getAllOrderHirory);
            $this->setChild('pager', $pager);
        }

        return $this;
    }

    /**
     * Get all orders
     *
     * @return object
     */
    public function getAllOrderHirory()
    {
        return $this->getAllOrders->getAllOrderHirory();
    }
}
