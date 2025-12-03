<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

/* B-1060632 - RT-ECVS- View Order Details
Custom Class to add Breadcrumbs Template  */
namespace Fedex\Orderhistory\Block\Order;

use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper as OrderApprovalAdminConfigHelper;

class Orderviewbreadcrumbs extends \Magento\Framework\View\Element\Template
{
    public $coreRegistry;
    /**
     * @var string
     */
    protected $template = 'Fedex_Orderhistory::orderviewbreadcrumbs.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        public OrderRepositoryInterface $orderRepository,
        public StoreManagerInterface $storeManager,
        public Data $helper,
        protected OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve BreadCrumbs on Order View Page
     */
    public function getOrderviewbreadcrumbs()
    {
        if ($this->helper->isModuleEnabled() || $this->helper->isPrintReceiptRetail()) {
            $orderData = $this->coreRegistry->registry('current_order');
            $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');

            $orderLabel = 'Home';

            $breadcrumbs->addCrumb(
                'myoreder',
                [
                    'label' => $orderLabel,
                    'title' => $orderLabel,
                    'link' => $this->storeManager->getStore()->getBaseUrl() . 'sales/order/history',
                ]
            );

            $breadcrumbs->addCrumb(
                'orderid',
                [
                    'label' => 'Order #' . $orderData->getIncrementId(),
                    'title' => 'Order Id',
                ]
            );

            if ($this->helper->isPrintReceiptRetail()) {
                $breadcrumbs->addCrumb(
                    'orderid',
                    [
                        'label' => 'Order number #' . $orderData->getIncrementId(),
                        'title' => 'Order Id',
                    ]
                );
            }

            return $this->getLayout()->getBlock('breadcrumbs')->toHtml();
        }
    }

    /**
     * Retrieve Base Url
     *
     * B-1053021 - Sanchit Bhatia - RT-ECVS - ePro - Search Capability for Quotes
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Check Order Approval B2B is enabled or not
     *
     * @return boolean
     */
    public function isOrderApprovalB2bEnabled()
    {
        return $this->orderApprovalAdminConfigHelper->isOrderApprovalB2bEnabled();
    }

    /**
     * Get Order Info
     *
     * @return object
     */
    public function getOrderInfo()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Check is review action is set or not
     *
     * @return boolean
     */
    public function checkIsReviewActionSet()
    {
        return $this->orderApprovalAdminConfigHelper->checkIsReviewActionSet();
    }

    /**
     * Get Order Status Decline Date
     *
     * @return string
     */
    public function getDeclineDate()
    {
        $declineDate = '';
        $order = $this->coreRegistry->registry('current_order');
        $declineCollection = $order->getStatusHistoryCollection();
        if ($declineCollection->getSize() > 0) {
            foreach ($declineCollection as $item) {
                if ($item->getStatus() === 'declined') {
                    $declineDate = $item->getCreatedAt();
                }
            }
        }

        return $declineDate;
    }
}
