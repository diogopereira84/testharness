<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Customer;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Framework\App\ResourceConnection;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\CustomerGroup\Controller\Adminhtml\Options\Save as CustomerGroupSaveHelper;


/**
 * Update customer for company structure.
 */
class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Magento\Company\Model\Action\SaveCustomer $customerAction
     * @param \Magento\Company\Model\Company\Structure $structureManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Fedex\SelfReg\Helper\Email $emailHelper
     * @param \Magento\Company\Model\Company $companyData
     * @param JsonFactory $resultJsonFactory
     * @param CommercialHelper $commercialHelper
     * @param \Fedex\SelfReg\Helper\SelfReg $selfregHelper
     * @param ResourceConnection $resourceConnection;
     * @param EnhanceUserRoles $roleUser
     * @param CustomerGroupSaveHelper $customerGroupSaveHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private \Magento\Company\Model\CompanyContext $companyContext,
        private \Magento\Company\Model\Action\SaveCustomer $customerAction,
        private \Magento\Company\Model\Company\Structure $structureManager,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private \Fedex\SelfReg\Helper\Email $emailHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        private \Magento\Store\Model\StoreManagerInterface $storeManager,
        private \Magento\Company\Model\Company $companyData,
        protected JsonFactory $resultJsonFactory,
        private \Fedex\SelfReg\Helper\SelfReg $selfregHelper,
        private \Magento\Customer\Model\Customer $customerData,
        private CommercialHelper $commercialHelper,
        private CompanyManagementInterface $companyRepository,
        protected ResourceConnection $resourceConnection,
        protected EnhanceUserRoles $roleUser,
        private DeliveryHelper $deliveryHelper,
        private CustomerGroupSaveHelper $customerGroupSaveHelper
    ) {
        parent::__construct($context);
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $resultJsonData = $this->resultJsonFactory->create();
        $customerId = $data['customer_id'] ?? null;
        $response = $this->customerFaceErrorMsg($customerId, $resultJsonData);
        $has_manage_user_permission=false;
        if($this->deliveryHelper->getToggleConfigurationValue('change_customer_roles_and_permissions')){
            $has_manage_user_permission=$this->deliveryHelper->checkPermission('manage_users');
        }
        $isSelfRegCustomerAdmin = $this->selfregHelper->isSelfRegCustomerAdmin();
        if (($this->selfregHelper->isSelfRegCustomer()
            && !empty($data)
            && is_null($response)
            && !is_null($customerId))
            || $isSelfRegCustomerAdmin || $has_manage_user_permission) {
            $customer = $this->customerRepository->getById($customerId);
            $companyUrl = $this->storeManager->getStore()->getBaseUrl();
            $companyName = $this->getCompanyNameByCustomerId($customerId);
            $customerNameForEmail = $data['firstname'] . " " . $data['lastname'];
            $groupId = $customer->getGroupId();

            if ($customer->getCustomAttribute('secondary_email')!== null) {
                $customerEmail = $customer->getCustomAttribute('secondary_email')->getValue();
            } else {
                $customerEmail = $customer->getEmail();
            }
            try {
		        $sendApprovalEmail = false;
                /** @var CompanyCustomerInterface $companyAttributes */
                $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
                $previousStatus = $companyAttributes->getStatus();
                if (array_key_exists("status",$data) && $data['status'] == '0') {
                    $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_INACTIVE);
                }
                if (array_key_exists("status",$data) && $data['status'] == '1') {
                    $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_ACTIVE);
                    $sendApprovalEmail = ($previousStatus == "0") ? true : false;
                }
                if (array_key_exists("status",$data)) {
                    $customer->setCustomAttribute('customer_status',$data['status']);
                }
                $isRolesAndPermissionEnabled = $this->commercialHelper->isRolePermissionToggleEnable();
                if($isRolesAndPermissionEnabled){
                 if(isset($data['rolePermissions'])){
                    $permissionIds = $data['rolePermissions'] ?? [];
                    if(isset($data['emailApproval'])) {
                        $permissionIds[] = $data['emailApproval'];
                    }
                    $this->setPermissions($permissionIds, $customerId);
                }else{
                    $companyData = $this->companyRepository->getByCustomerId($customerId);
                    if ($companyData){
                        $companyId = $companyData->getId();
                        $this->deleteAllPermission($customerId,$companyId);
                    }
                }}

                $this->customerRepository->save($customer);

                $customerGroupId = $data['group'] ?? null;
                if ($customerGroupId) {
                    $this->customerGroupSaveHelper->updateCustomerAttribute($customerId, $customerGroupId);
                } else {
                    $this->customerGroupSaveHelper->updateCustomerAttribute($customerId, $groupId);
                }

                $this->sendApprovalMail(
                    $sendApprovalEmail,
                    $companyUrl,
                    $companyName,
                    $customerNameForEmail,
                    $customerEmail
                );
                $message = 'User information for "' . $customerNameForEmail . '" has been updated.';
                $response = $resultJsonData->setData(['status' => 'ok', 'message' => $message]);


            //@codeCoverageIgnoreStart
		    } catch (\Magento\Framework\Exception\LocalizedException $e) {

                return $resultJsonData->setData(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (\Exception $e) {

                return $resultJsonData->setData(['status' => 'error', 'message' => $e->getMessage()]);
            }
		//@codeCoverageIgnoreStart
        }
        return $response;
    }

    /**
     * Set user level permissions
     * @param array $permissionId
     * @param int $customerId
     */
    public function setPermissions($permissionIds, $customerId)
    {
        try {
            $companyData = $this->companyRepository->getByCustomerId($customerId);
            if ($companyData) {
                $companyId = $companyData->getId();
                $connection  = $this->resourceConnection->getConnection();
                $tableName = "enhanced_user_roles";
                $insertData = [];
                foreach ($permissionIds as $permissionId) {
                    $insertData[] = ['company_id' => $companyId, 'customer_id' => $customerId, 'permission_id' => $permissionId];
                }
                $connection->insertOnDuplicate($tableName, $insertData, []);
                $collection = $this->roleUser->getCollection()->addFieldToFilter('company_id', ['eq' => $companyId])
                    ->addFieldToFilter('customer_id', ['eq' => $customerId])
                    ->addFieldToFilter('permission_id', ['nin' => $permissionIds]);
                foreach ($collection as $permissionData) {
                    $this->roleUser->load($permissionData->getId())->delete();
                }
            }
        } catch (\Exception $e) {
        }

    }
    /**
     * Get attribute type for upcoming validation.
     *
     * @param customerId
     * @param resultJsonData
     * @return errorMsg
     */
    public function customerFaceErrorMsg($customerId, $resultJsonData)
    {
        if (!empty($customerId) && $customerId == $this->companyContext->getCustomerId()) {
            return $resultJsonData->setData(['status' => 'error', 'message' => 'You cannot update yourself.']);
        }
        return null;
    }
    /**
     * getCompanyNameByCustomerId
     *
     * @param customerId
     * @return companyName
     */
    public function getCompanyNameByCustomerId($customerId)
    {
        $companyName = "";
        $companyData = $this->companyRepository->getByCustomerId($customerId);
        if ($companyData) {
            $companyName = $companyData->getCompanyName();
        }
        return $companyName;
    }
    /**
     * sendApprovalMail
     *
     * @param sendApprovalEmail
     * @param companyUrl
     * @param companyName
     * @param customerNameForEmail
     *  @param customerEmail
     * @return null
     */
    public function sendApprovalMail(
        $sendApprovalEmail,
        $companyUrl,
        $companyName,
        $customerNameForEmail,
        $customerEmail
    )
    {
        if ($sendApprovalEmail) {
            $this->emailHelper->sendApprovalEmail(
                $companyUrl,
                $companyName,
                $customerNameForEmail,
                $customerEmail
            );
        }
    }

      /**
     * @param $customerid
     * @param $companyId
     * Delete All permissions
     */
    public function deleteAllPermission($customerId,$companyId)
    {
        $collection = [];
        $collection =  $this->roleUser->getCollection()
                        ->addFieldToFilter('customer_id',$customerId)
                        ->addFieldToFilter('company_id',$companyId);
        foreach ($collection as $permissionData) {
        $this->roleUser->load($permissionData->getId())->delete();
    }}
}
