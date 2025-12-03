<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Model\OrderHistory;

use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SortOrder;
use Fedex\OrderApprovalB2b\Model\ResourceModel\OrderGrid\CollectionFactory as OrderCollectionFactory;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission\CollectionFactory as UserGroupPermissionCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Customer;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\Data as SelfRegHelper;

/**
 * Class to provide Pending approval order list
 */
class GetAllOrders
{
    public const COMMERCIAL_USER_GROUP_ORDER_APPROVER = 'commercial_user_group_order_approvers';
    public const TK_4669320_REVIEW_ORDER_BLANK_PAGE = 'tk_4669320_review_order_blank_page';

    /**
     * Initializing dependencies
     *
     * @param RequestInterface $request
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param RevieworderHelper $revieworderHelper
     * @param DeliveryHelper $deliveryDataHelper
     * @param UserGroupPermissionCollection $userGroupsPermissionCollection
     * @param ResourceConnection $resourceConnection
     * @param Customer $customer
     * @param ToggleConfig $toggleConfig
     * @param SelfRegHelper $selfRegHelper
     */
    public function __construct(
        protected RequestInterface $request,
        protected OrderCollectionFactory $orderCollectionFactory,
        protected RevieworderHelper $revieworderHelper,
        protected DeliveryHelper $deliveryDataHelper,
        private UserGroupPermissionCollection $userGroupsPermissionCollection,
        private ResourceConnection $resourceConnection,
        private Customer $customer,
        private ToggleConfig $toggleConfig,
        private SelfRegHelper $selfRegHelper
    ) {
    }

    /**
     * Retrieve company id
     *
     * @return int|null
     */
    protected function getCompanyId()
    {
        return $this->revieworderHelper->getCompanyId();
    }

    /**
     * Get all orders
     *
     * @return object
     */
    public function getAllOrderHirory()
    {
        $orderApprovalFeatureToggle = $this->getUserGroupOrderApproverToggel();
        $statusArr = ['pending_approval'];
        $searchValue = $this->request->getParam('search') ? $this->request->getParam('search') : '';
        $sortValue = $this->request->getParam('sortby') ? $this->request->getParam('sortby') : 'created_at';
        $orderValue = $this->request->getParam('orderby') ? $this->request->getParam('orderby') : 'DESC';
        $sortDir = SortOrder::SORT_DESC;
        $sortByValue = 'created_at';
        if ($orderValue && $orderValue != 'DESC') {
            $sortDir = SortOrder::SORT_ASC;
        }
        if ($sortValue && $sortValue != 'created_at') {
            $sortByValue = $sortValue;
        }
        //get values of current page
        $page = ($this->request->getParam('p'))? $this->request->getParam('p') : 1;

        //get values of current limit
        $pageSize = ($this->request->getParam('limit'))? $this->request->getParam('limit') : 10;

        $customerId = $this->revieworderHelper->getCustomerId();
        $companyId = $this->getCompanyId();
        $orderCollection = $this->orderCollectionFactory->create();
        $enhancedUserRolesTable = $orderCollection->getTable('enhanced_user_roles');
        $enhancedRolePermissionsTable = $orderCollection->getTable('enhanced_role_permissions');
        $companyOrderEntity = $orderCollection->getTable('company_order_entity');
        $orderCollection->addFieldToSelect(['entity_id', 'increment_id', 'created_at', 'grand_total']);
        if (!$this->deliveryDataHelper->isSelfRegCustomerAdminUser()) {
            $orderCollection->join(
                $enhancedUserRolesTable,
                $enhancedUserRolesTable.'.customer_id='.$customerId.' AND 
                '.$enhancedUserRolesTable.'.company_id = '.$companyId.'',
                []
            );
            $orderCollection->join(
                $enhancedRolePermissionsTable,
                $enhancedRolePermissionsTable.'.id='.$enhancedUserRolesTable.'.permission_id AND '
                .$enhancedRolePermissionsTable.'.label = "Review Orders::review_orders"',
                []
            );
        }
        $orderCollection->join(
            $companyOrderEntity,
            'main_table.entity_id=' .$companyOrderEntity.'.order_id AND '
            .$companyOrderEntity.'.company_id = '.$companyId.'',
            []
        );
        if ($orderApprovalFeatureToggle) {
            $userGroupPermission = $orderCollection->getTable('user_groups_permission');
            $isOrderApprovar = $this->checkIfCustomerIsOrderApprovar($customerId);
            $permissionId = $this->selfRegHelper->getReviewOrderPermissionID();
            $isOrderReviwer =  $this->selfRegHelper->checkIfValueExist($customerId, $permissionId, $companyId);

            if ($isOrderApprovar || $isOrderReviwer) {
                $allCustomerIds = $this->getAllCusomterIds($companyId, $customerId);
                $includedIds = $this->getCurrenctOrderApprovarUser(
                    $companyId,
                    $customerId,
                    $userGroupPermission,
                    $allCustomerIds
                );

                $orderCollection->addFieldToFilter('main_table.customer_id', ['in'=> $includedIds]);
            } else {
                if ($this->deliveryDataHelper->isSelfRegCustomerAdminUser()) {
                    $allCustomerIds = $this->getAllCusomterIds($companyId, $customerId);
                    $includedIds = $this->getCurrenctOrderApprovarUser(
                        $companyId,
                        $customerId,
                        $userGroupPermission,
                        $allCustomerIds
                    );
                    $orderCollection->addFieldToFilter('main_table.customer_id', ['in'=> $includedIds]);
                }
            }
        }
        $orderCollection->addFieldToFilter('main_table.status', ['in' => $statusArr]);
        if ($searchValue) {
            $orderCollection->addFieldToFilter('increment_id', $searchValue);
        }
        $orderCollection->setOrder($sortByValue, $sortDir);
        $orderCollection->setPageSize($pageSize);
        $orderCollection->setCurPage($page);
        return $orderCollection;
    }

    /**
     * Check if customer is order approver or not
     *
     * @param int|null $customerId
     */
    public function checkIfCustomerIsOrderApprovar($customerId)
    {
        $collection = $this->userGroupsPermissionCollection->create();
        $collection->addFieldToFilter('order_approval', ['finset' => $customerId]);
        $collectionData = $collection->getData();
        return count($collectionData);
    }

    /**
     * Check if customer is order approver or not
     *
     * @param int|null $customerId
     */
    public function checkIfCompanyIsUser($customerId)
    {
        $collection = $this->userGroupsPermissionCollection->create();
        $collection->addFieldToFilter('user_id', ['finset' => $customerId]);
        $collectionData = $collection->getData();
        return count($collectionData);
    }

    /**
     * Get all customer of current company
     *
     * @param int $companyId
     * @param int|null $currentUSerID
     */
    public function getAllCusomterIds($companyId, $currentUSerID)
    {
        $companyAdvancedTable = $this->getCompanyAdvanceCustomerEntityConnection();
        $customerCollection = $this->getCustomerConnection();
        $customerCollection->addAttributeToSelect("*");
        if (!empty($currentUSerID)) {
            $customerCollection->addFieldToFilter('entity_id', ['nin' => $currentUSerID]);
        }

        $customerCollection->getSelect()->join(
            $companyAdvancedTable .
            ' as ad_customer',
            'e.entity_id = ad_customer.customer_id AND ad_customer.company_id = '
            . $companyId,
            ['*']
        );
        
        if ($this->getTK4669320Toggle()) {
            $allCustomerIds = [];
        }
        foreach ($customerCollection->getData() as $customer) {
            $allCustomerIds[] = $customer['customer_id'];
        }
        return $allCustomerIds;
    }

    /**
     * Get current user orders approver
     *
     * @param int|null $companyId
     * @param int|null $currentUSerID
     * @param string $userGroupPermission
     * @param array $allCustomerIds
     */
    public function getCurrenctOrderApprovarUser($companyId, $currentUSerID, $userGroupPermission, $allCustomerIds)
    {
        $companyAdvancedTable = $this->getCompanyAdvanceCustomerEntityConnection();
        $customerCollection = $this->getCustomerConnection();
        $customerCollection->getSelect()->join(
            $companyAdvancedTable .
            ' as ad_customer',
            'e.entity_id = ad_customer.customer_id AND ad_customer.company_id = '
            . $companyId,
            ['*']
        );

        $customerCollection->getSelect()->join(
            $userGroupPermission,
            'e.entity_id=' .$userGroupPermission.'.user_id ',
            ['*']
        )->join(
            'user_groups',
            'user_groups.id=' .$userGroupPermission.'.group_id',
            ['*']
        )
        ->where('user_groups.group_type = ?', 'order_approval');
        $otherGroupIds = [];

        $isUser = $this->checkIfCompanyIsUser($currentUSerID);
        $isApprover = $this->checkIfCustomerIsOrderApprovar($currentUSerID);

        foreach ($customerCollection->getData() as $customer) {
            $pattern = '/(?<=^|,)' . preg_quote($currentUSerID, '/') . '(?=,|$)/';
            if (!preg_match($pattern, $customer['order_approval'])) {
                $otherGroupIds[] = $customer['customer_id'];
            }
        }
        $result = array_diff($allCustomerIds, $otherGroupIds);
        
        if (!$isUser || $isApprover) {
            $result[] = $currentUSerID;
        }
        return $result;
    }

    /**
     * Get company Advance connection
     */
    public function getCompanyAdvanceCustomerEntityConnection()
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->getTableName('company_advanced_customer_entity');
    }

    /**
     * Get customer connection
     */
    public function getCustomerConnection()
    {
        return $this->customer->getCollection();
    }

    /**
     * Get commercial user group order approver Toggle
     */
    public function getUserGroupOrderApproverToggel()
    {
        return $this->toggleConfig->getToggleConfigValue(static::COMMERCIAL_USER_GROUP_ORDER_APPROVER);
    }

    public function getTK4669320Toggle()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(static::TK_4669320_REVIEW_ORDER_BLANK_PAGE);
    }
}
