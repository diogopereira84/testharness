<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Customer\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\Validator\Exception;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Controller\Adminhtml\Index\Save;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Model\EnhanceRolePermission;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Interception\Test\Unit\Custom\Module\Model\ItemContainer\Enhanced;
use Magento\TestFramework\Utility\ChildrenClassesSearch\E;

class SavePermissions extends Save implements HttpPostActionInterface
{
    private const EMAIL_ALLOW = 'Yes::email_allow::manage_users';
    private const EMAIL_DENY = 'No::email_deny::manage_users';
    private const USER_PERMISSIONS_TOGGLE = 'sgc_b_2256325';

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param FormFactory $formFactory
     * @param SubscriberFactory $subscriberFactory
     * @param View $viewHelper
     * @param Random $random
     * @param CustomerRepositoryInterface $customerRepository
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Mapper $addressMapper
     * @param AccountManagementInterface $customerAccountManagement
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param ObjectFactory $objectFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param JsonFactory $resultJsonFactory
     * @param SubscriptionManagerInterface $subscriptionManager
     * @param CompanyManagementInterface $companyRepository
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param EnhanceUserRoles $roleUser
     * @param EnhanceRolePermission $rolePermissions
     * @param ToggleConfig $toggleConfig
     * @param AddressRegistry|null $addressRegistry
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        View $viewHelper,
        Random $random,
        CustomerRepositoryInterface $customerRepository,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        Mapper $addressMapper,
        AccountManagementInterface $customerAccountManagement,
        AddressRepositoryInterface $addressRepository,
        CustomerInterfaceFactory $customerDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        LayoutFactory $resultLayoutFactory,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        JsonFactory $resultJsonFactory,
        SubscriptionManagerInterface $subscriptionManager,
        private CompanyManagementInterface $companyRepository,
        private ResourceConnection $resourceConnection,
        private LoggerInterface $logger,
        private EnhanceUserRoles $roleUser,
        private EnhanceRolePermission $rolePermissions,
        private ToggleConfig $toggleConfig,
        AddressRegistry $addressRegistry = null,
        ?StoreManagerInterface $storeManager = null
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $customerFactory,
            $addressFactory,
            $formFactory,
            $subscriberFactory,
            $viewHelper,
            $random,
            $customerRepository,
            $extensibleDataObjectConverter,
            $addressMapper,
            $customerAccountManagement,
            $addressRepository,
            $customerDataFactory,
            $addressDataFactory,
            $customerMapper,
            $dataObjectProcessor,
            $dataObjectHelper,
            $objectFactory,
            $layoutFactory,
            $resultLayoutFactory,
            $resultPageFactory,
            $resultForwardFactory,
            $resultJsonFactory,
            $subscriptionManager
        );
    }

    /**
     * Save customer action
     *
     * @return Redirect
     */
    public function execute()
    {
        if ($this->toggleConfig->getToggleConfigValue(self::USER_PERMISSIONS_TOGGLE)) {
            $customerData = $this->getRequest()->getPostValue();
            $userPermissions = $customerData['user_permissions'] ?? [];
            $customerId = $customerData['customer_id'] ?? null;
            if ($userPermissions && $customerId) {
                $emailPermission = $customerData['email_permission'] ?? null;
                if ($emailPermission) {
                    $emailPermissionArr = explode(',', $emailPermission);
                    $userPermissions[(int) $emailPermissionArr[0]] = "1";
                    $userPermissions[(int) $emailPermissionArr[1]] = "0";
                } else {
                    $userPermissions = $userPermissions + $this->getManageUsersEmailPermissionIds();
                }

                $this->setPermissions($userPermissions, $customerId);
            }
        }
        
        return parent::execute();
    }

    /**
     * Set user level permissions
     *
     * @param array $permissionIds
     * @param int $customerId
     *
     * @return void
     */
    public function setPermissions($permissionIds, $customerId): void
    {
        try {
            $companyData = $this->companyRepository->getByCustomerId($customerId);
            if ($companyData) {
                $companyId = $companyData->getId();
                $connection  = $this->resourceConnection->getConnection();
                $tableName = "enhanced_user_roles";
                $insertData = [];
                $deleteData = [];
                foreach ($permissionIds as $permissionId => $value) {
                    if ($value === "0") {
                        $deleteData[] = $permissionId;
                    } elseif ($value === "1") {
                        $insertData[] =
                        ['company_id' => $companyId, 'customer_id' => $customerId, 'permission_id' => $permissionId];
                    }
                }
                
                $conditions = [
                    'customer_id = ?' => $customerId,
                    'company_id = ?' => $companyId,
                    'permission_id IN (?)' => $deleteData
                ];
                $connection->delete($tableName, $conditions);
                if ($insertData) {
                    $connection->insertOnDuplicate($tableName, $insertData, []);
                }
            }
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' An error occured while saving permissions for the user: ' .
                $e->getMessage()
            );
        }
    }

    /**
     * Get ids for email permissions in enhanced_role_permissions table
     *
     * @return array
     */
    public function getManageUsersEmailPermissionIds()
    {
        $emailPermissionIds = [];
        $collection = $this->rolePermissions->getCollection();
        foreach ($collection as $permission) {
            if ($permission->getLabel() === self::EMAIL_ALLOW || $permission->getLabel() === self::EMAIL_DENY) {
                $emailPermissionIds[$permission->getId()] = "0";
            }

        }

        return $emailPermissionIds;
    }
}
