<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Customer;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Framework\App\ResourceConnection;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Model\EnhanceRolePermission;
use Fedex\CustomerGroup\Controller\Adminhtml\Options\Save as CustomerGroupSaveHelper;

/**
 * Update bulk customer for company structure.
 */
class BulkSave extends \Magento\Framework\App\Action\Action
{
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
     * @param ResourceConnection $resourceConnection
     * @param EnhanceUserRoles $roleUser
     * @param EnhanceRolePermission $enhanceRolePermission
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
        protected EnhanceRolePermission $enhanceRolePermission,
        private CustomerGroupSaveHelper $customerGroupSaveHelper
    ) {
        parent::__construct($context);
        $this->messageManager = $messageManager;
    }

    /**
     * Update bulk customer permission
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            $resultJsonData = $this->resultJsonFactory->create();

            $response = $resultJsonData->setData(['status' => 'error', 'message' => 'You cannot update yourself.']);
            if (array_key_exists("customerIds",$data)) {
                $customerIds = explode(",", $data["customerIds"]);
                $bulkGroupId = $data['group'] ?? null;
                foreach ($customerIds as $customerId) {
                    $customer = $this->customerRepository->getById(trim($customerId));
                    $companyUrl = $this->storeManager->getStore()->getBaseUrl();
                    $companyName = $this->getCompanyNameByCustomerId($customerId);
                    $customerNameForEmail = $customer->getFirstname() . " " . $customer->getLastname();
                    $groupId = $customer->getGroupId();

                    if ($customer->getCustomAttribute('secondary_email')!== null) {
                        $customerEmail = $customer->getCustomAttribute('secondary_email')->getValue();
                    } else {
                        $customerEmail = $customer->getEmail();
                    }
                    $companyAttributes = [];
                    $extensionAtribute = [];
                    $extensionAtribute = $customer->getExtensionAttributes();
                    $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
                    $sendApprovalEmail = false;
                    $previousStatus = $companyAttributes->getStatus();
                    if (array_key_exists("status",$data) && $data['status'] == '0') {
                        $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_INACTIVE);
                        $extensionAtribute->setCompanyAttributes($companyAttributes);
                        $customer->setExtensionAttributes($extensionAtribute);
                    }
                    if (array_key_exists("status",$data) && $data['status'] == '1') {
                        $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_ACTIVE);
                        $extensionAtribute->setCompanyAttributes($companyAttributes);
                        $customer->setExtensionAttributes($extensionAtribute);
                        $sendApprovalEmail = ($previousStatus == "0") ? true : false;
                    }
                    if (array_key_exists("status",$data)) {
                        $customer->setCustomAttribute('customer_status',$data['status']);
                    }
                    if(isset($data['rolePermissions'])) {
                        $permissionIds = $data['rolePermissions'] ?? [];
                        if(isset($data['emailApproval'])) {
                            $permissionIds[] = $data['emailApproval'];
                        }
                        $this->setPermissions($permissionIds, $customerId);
                    }
                    $this->customerRepository->save($customer);

                    if ($bulkGroupId) {
                        $this->customerGroupSaveHelper->updateCustomerAttribute($customerId, $bulkGroupId);
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
                }
                $message = count($customerIds) . " users have been successfully edited.";
                $response = $resultJsonData->setData(['status' => 'success', 'message' => $message]);
            }
        } catch (\Exception $e) {
            return $resultJsonData->setData(['status' => 'error', 'message' => $e->getMessage()]);
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
            $emailYesId = $this->getEmailYesPermissions();
            $emailNoId = $this->getEmailNoPermissions();
            if ($companyData) {
                $companyId = $companyData->getId();
                $connection  = $this->resourceConnection->getConnection();
                $tableName = "enhanced_user_roles";
                $insertData = [];
                foreach ($permissionIds as $permissionId) {
                    $insertData[] = ['company_id' => $companyId, 'customer_id' => $customerId, 'permission_id' => $permissionId];
                    if($emailYesId == $permissionId) {
                        $this->deletePermission($customerId,$emailNoId,$companyId);
                    }
                    if($emailNoId == $permissionId) {
                        $this->deletePermission($customerId,$emailYesId,$companyId);
                    }
                }
                $connection->insertOnDuplicate($tableName, $insertData, []);

            }
        } catch (\Exception $e) {
        }

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
     * Get Email Approval Yes permission
     */
    public function getEmailYesPermissions()
    {
        $emailYesId = 0;
        $collection = [];
        $collection =  $this->enhanceRolePermission->getCollection()
                        ->addFieldToFilter('label',array('like' => '%email_allow::manage_users%'))->getFirstItem();
        $emailYesId = $collection->getId();
        return $emailYesId;
    }

    /**
     * Get Email Approval No permission
     */
    public function getEmailNoPermissions()
    {
        $emailNoId = 0;
        $collection = [];
        $collection =  $this->enhanceRolePermission->getCollection()
                        ->addFieldToFilter('label',array('like' => '%email_deny::manage_users%'))->getFirstItem();
        $emailNoId = $collection->getId();
        return $emailNoId;
    }

    /**
     * Delete permission
     */
    public function deletePermission($customerId,$permissionId,$companyId)
    {
        $collection = [];
        $collection =  $this->roleUser->getCollection()
                        ->addFieldToFilter('customer_id',$customerId)
                        ->addFieldToFilter('company_id',$companyId)
                        ->addFieldToFilter('permission_id',$permissionId)->getFirstItem();
        if($collection->getData()) {
            $collection->delete();
        }
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
}
