<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Plugin\Sales\Block\Order;

use Fedex\Delivery\Helper\Data as permissionHelper;

/**
 * Class HistoryPlugin
 */
class HistoryPlugin
{
    /**
     * HistoryPlugin constructor.
     *
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\NegotiableQuote\Block\Order\OwnerFilter $ownerFilterBlock
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param  PermissionHelper $permissionHelper
     */
    public function __construct(
        private \Magento\Authorization\Model\UserContextInterface $userContext,
        private \Magento\Framework\App\RequestInterface $request,
        private \Magento\NegotiableQuote\Block\Order\OwnerFilter $ownerFilterBlock,
        private \Magento\Company\Api\AuthorizationInterface $authorization,
        private permissionHelper $permissionHelper
    )
    {
    }

    /**
     * After history getOrders plugin
     *
     * @param \Magento\Sales\Block\Order\History $subject
     * @param bool|\Magento\Sales\Model\ResourceModel\Order\Collection $result
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetOrders(
        \Magento\Sales\Block\Order\History $subject,
        $result
    ) {

        $createdBy = $this->request->getParam('created_by');
        if (($result !== false) &&
            ($createdBy === $this->ownerFilterBlock->getShowMyParam() ||
                !$this->checkPermission())
        ) {
            $customerId = $this->getCustomerId();
            $result->addFieldToFilter('customer_id', (int)$customerId);
          }
        return $result;
    }

    /**
     * Get customer id from user context
     *
     * @return int|null
     */
    private function getCustomerId()
    {
        return $this->userContext->getUserId() ? : null;
    }

    /**
     * check permission for user
     *
     * @return bool
     */
    private function checkPermission()
    {
        if ($this->authorization->isAllowed('Magento_Sales::view_orders_sub')) {
           return true; 
        } elseif ($this->permissionHelper->getToggleConfigurationValue('change_customer_roles_and_permissions')) {
            return $this->permissionHelper->checkPermission('shared_orders');
        }
        return false;
    }
}