<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;
use Fedex\SelfReg\Model\UserGroupsFactory;
use Fedex\SelfReg\Helper\Data;
use Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface;
use Fedex\SelfReg\Api\UserGroupsPermissionRepositoryInterface;
use Fedex\SelfReg\Model\UserGroupsPermissionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;




/**
 * Index controller class
 */
class NewGroup implements ActionInterface
{
    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        protected PageFactory $resultPageFactory,
        private RequestInterface $request,
        private UserGroupsRepositoryInterface $userGroupsRepository,
        private UserGroupsFactory $usergroupFactory,
        private Data $data,
        private UserGroupsPermissionRepositoryInterface $userGroupsPermissionRepository,
        private UserGroupsPermissionFactory $userGroupsPermissionFactory,
        private ResultFactory $resultRedirectFactory,
        private ManagerInterface $messageManager
    )
    {
    }

    /**
     * Execute method for showing psg customer details
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $postData = $this->request->getParams() ?? [];
        if (isset($postData['group_name']) && $postData['group_name']) {
            $this->save($postData);
            $resultRedirect = $this->resultRedirectFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('orderapprover/index/index');
            $this->messageManager->addSuccessMessage(__('You saved the order approver group.'));
            return $resultRedirect;
        } else {
            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('New Approver Group'));

            return $resultPage;
        }
    }

    public function save(array $postData)
    {
        try {
            $groupType = UserGroupsPermissionInterface::ORDER_APPROVAL;
            $companyId = $postData['site'];
            if (isset($postData['id']) && !empty($postData['id'])) {
                $usergroup = $this->userGroupsRepository->get((int)$postData['id']);
            } else {
                $usergroup = $this->usergroupFactory->create();
            }

            if ($usergroup) {
                $groupName = $postData['group_name'] ?? ($usergroup->getGroupName() ?? '');

                $usergroup->setGroupName($groupName);
                $usergroup->setGroupType($groupType);
                $this->userGroupsRepository->save($usergroup);

                $groupId = $usergroup->getId();
                if ($groupId) {
                    if (isset($postData['id']) && !empty($postData['id'])) {
                        $permissions = $this->userGroupsPermissionRepository
                                            ->getByGroupId((int) $postData['id']);

                        if ($groupType == UserGroupsPermissionInterface::ORDER_APPROVAL) {
                            $userGroupsPermissionCollection = $this->userGroupsPermissionFactory
                                                                ->create()
                                                                ->getCollection()
                                                                ->addFieldToFilter("group_id", $groupId)
                                                                ->getFirstItem();

                            $orderApproversArrayExisting = explode(',', ($userGroupsPermissionCollection->getOrderApproval() ?? ''));
                            $orderApproversArrayExisting = array_unique($orderApproversArrayExisting);
                        }

                        if ($permissions) {
                            foreach ($permissions as $permission) {
                                $this->userGroupsPermissionRepository->delete($permission);
                            }
                        }
                    }

                    // Remove duplicate user ids
                    $userIdsArray = null;
                    if (isset($postData['users']) && !empty($postData['users'])) {
                        $users = implode(',', $postData['users']);
                        $userIdsArray = explode(',', ($users ?? ''));
                        $userIdsArray = array_unique($userIdsArray);

                        // Remove user record if associated with different group
                        if ($userIdsArray) {
                            $this->userGroupsPermissionRepository
                                ->deleteByUserGroupInfo(
                                    $companyId,
                                    (int)$groupId,
                                    $groupType,
                                    $userIdsArray
                                );
                        }
                    }

                    // Remove duplicate order approver ids
                    $orderApprover = implode(',', $postData['order_approver']);
                    $orderApprovers = $orderApprover ?? '';
                    $orderApproversArray = explode(',', ($orderApprovers ?? ''));
                    $orderApproversArray = array_unique($orderApproversArray);
                    $orderApprovers = implode(',', $orderApproversArray);

                    if ($userIdsArray) {
                        foreach ($userIdsArray as $userId) {
                            $usergroupPermission = $this->userGroupsPermissionFactory->create();

                            $usergroupPermission->setGroupId($groupId);
                            $usergroupPermission->setUserId((int)$userId);
                            $usergroupPermission->setCompanyId($companyId);
                            $usergroupPermission->setOrderApproval($orderApprovers);

                            $this->userGroupsPermissionRepository->save($usergroupPermission);
                        }
                    }

                    if ($groupType == UserGroupsPermissionInterface::ORDER_APPROVAL) {
                        if (isset($postData['id']) && !empty($postData['id'])) {
                            // Get difference order appprover ids
                            $orderApproverdiff = array_diff($orderApproversArrayExisting,$orderApproversArray);
                            $orderApproverdiff = array_values($orderApproverdiff);

                            if(is_array($orderApproverdiff) && sizeof($orderApproverdiff) > 0){
                                foreach($orderApproverdiff as $eachOrderApproverdififf){
                                    $orderApproverCount = $this->data
                                                            ->checkIfCustomerIsOrderApprovar($eachOrderApproverdififf);

                                    // Check order approver not assigned to other user guoups
                                    // If not assign to any group then delete from order review role table
                                    if (!$this->data->checkIfCustomerIsOrderApprovar($eachOrderApproverdififf)) {
                                        // Order approver id Delete from order review permission
                                        $this->data
                                            ->deletePermission($eachOrderApproverdififf, $companyId);
                                    }
                                }
                            }
                        }

                        // Save value in enhace user role permission
                        $this->saveValueInEnhanceUserRole($orderApproversArray, $companyId);
                    }

                } 
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function saveValueInEnhanceUserRole($orderApproversArray, $companyId) {
        foreach ($orderApproversArray as $orderApprover) {
            $this->data->setPermissions($orderApprover, $companyId);
        }
    }
}

