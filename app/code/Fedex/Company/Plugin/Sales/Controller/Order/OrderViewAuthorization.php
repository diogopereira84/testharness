<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Plugin\Sales\Controller\Order;

use Magento\Sales\Model\Order as SalesOrder;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Customer\Model\Session;
use Magento\Company\Controller\Order\OrderViewAuthorization as ParentOrderViewAuthorization;
use Fedex\OrderApprovalB2b\ViewModel\ReviewOrderViewModel;

/**
 * Plugin class that handles the order view permissions
 */
class OrderViewAuthorization
{
    /**
     * @param DeliveryDataHelper $deliveryDataHelper
     * @param Session $customerSession
     * @param ReviewOrderViewModel $reviewOrderViewModel
     */
    public function __construct(
        protected DeliveryDataHelper $deliveryDataHelper,
        protected Session $customerSession,
        protected ReviewOrderViewModel $reviewOrderViewModel
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function afterCanView(ParentOrderViewAuthorization $subject, $result, SalesOrder $order)
    {
        $currentCustomerGroupId = $this->customerSession->getCustomer()->getGroupId();
        if (($this->deliveryDataHelper->isCompanyAdminUser()
         && $currentCustomerGroupId == $order->getCustomerGroupId()) 
         || ($this->reviewOrderViewModel->isOrderApprovalB2bEnabled() && $this->reviewOrderViewModel->checkIfUserHasReviewOrderPermission() || ( $this->deliveryDataHelper->getToggleConfigurationValue('change_customer_roles_and_permissions') && $this->deliveryDataHelper->checkPermission('shared_orders')))) {
            return true;
        }

        return $result;
    }
}
