<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Controller\Adminhtml\Index;

use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\GroupExtensionInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Fedex\SelfReg\ViewModel\CompanyUser;
use Magento\Framework\Stdlib\DateTime as DateTimeFormatter;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Model\ResourceModel\ParentUserGroup\CollectionFactory as ParentUserGroupCollectionFactory;
use DateTime;

/**
 * Save class for customer group form
 */
class Save implements ActionInterface
{
    const TABLE_NAME = 'parent_customer_group';

    protected const SGC_FLP_TOGGLE = 'sgc_user_group_and_folder_level_permissions';

    /**
     * Save Class Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupDataFactory
     * @param ForwardFactory $resultForwardFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param DataObjectProcessor $dataObjectProcessor
     * @param GroupExtensionInterfaceFactory  $groupExtensionInterfaceFactory
     * @param ResourceConnection $resourceConnection
     * @param FolderPermission $folderPermission
     * @param ManagerInterface $messageManager
     * @param AdminSession $adminSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CompanyUser $companyUser
     * @param ToggleConfig $toggleConfig
     * @param ParentUserGroupCollectionFactory $parentUserGroupCollection
     */
    public function __construct(
        protected Context $context,
        protected Registry $coreRegistry,
        protected GroupRepositoryInterface $groupRepository,
        protected GroupInterfaceFactory $groupDataFactory,
        protected ForwardFactory $resultForwardFactory,
        protected RedirectFactory $resultRedirectFactory,
        protected DataObjectProcessor $dataObjectProcessor,
        protected GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory,
        protected ResourceConnection $resourceConnection,
        protected FolderPermission $folderPermission,
        protected ManagerInterface $messageManager,
        protected AdminSession $adminSession,
        private CompanyRepositoryInterface $companyRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private CompanyUser $companyUser,
        protected ToggleConfig $toggleConfig,
        protected ParentUserGroupCollectionFactory $parentUserGroupCollection
    ) {
    }

    /**
     * Store Customer Group Data to session
     *
     * @param array $customerGroupData
     * @return void
     */
    public function storeCustomerGroupDataToSession($customerGroupData)
    {
        if (array_key_exists('code', $customerGroupData)) {
            $customerGroupData['customer_group_code'] = $customerGroupData['code'];
            unset($customerGroupData['code']);
        }
        $this->adminSession->setCustomerGroupData($customerGroupData);
    }

    /**
     * Execute class
     *
     * @return mixed
     */
    public function execute()
    {
        $data = $this->context->getRequest()->getPostValue();
        $id = $data['customergroup_general']['id'];
        $parentMessage = 'You saved the customer group but parent group not saved.';
        $message = 'You saved the customer group.';
        if ($id !== -1) {
            $groupId = $id;
        }
        $taxClass = $data['customergroup_general']['tax_class_id'];
        /** @var GroupInterface $customerGroup */
        $customerGroup = null;
        if ($taxClass) {
            $websitesToExclude = empty($data['customergroup_general']['customer_group_excluded_website_ids'])
                ? [] : $data['customergroup_general']['customer_group_excluded_website_ids'];
            $resultRedirect = $this->resultRedirectFactory->create();
            try {
                $customerGroupCode = (string)$data['customergroup_general']['code'];

                if ($id != -1) {
                    $customerGroup = $this->groupRepository->getById((int)$id);
                    $customerGroupCode = $customerGroupCode ?: $customerGroup->getCode();
                } else {
                    $customerGroup = $this->groupDataFactory->create();
                }
                $customerGroup->setCode(!empty($customerGroupCode) ? $customerGroupCode : null);
                $customerGroup->setTaxClassId($taxClass);

                if ($websitesToExclude !== null) {
                    $customerGroupExtensionAttributes = $this->groupExtensionInterfaceFactory->create();
                    $customerGroupExtensionAttributes->setExcludeWebsiteIds($websitesToExclude);
                    $customerGroup->setExtensionAttributes($customerGroupExtensionAttributes);
                }

                $parentData = isset($data['customergroup_general']['data']['parent']) ?
                    $data['customergroup_general']['data']['parent'] : null;

                $isFolderLevelPermissionToggleEnabled = $this->companyUser->toggleUserGroupAndFolderLevelPermissions();

                if ($isFolderLevelPermissionToggleEnabled && $parentData !== 'null') {
                    $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                        'customer_group_id',
                        $parentData
                    )->create();
                    foreach ($this->companyRepository->getList($searchCriteria)->getItems() as $company) {
                        $customerGroupSearchName = '<' . $company->getCompanyUrlExtention() . '> ' . $customerGroupCode;
                        $customerGroupSearch = $this->searchCriteriaBuilder->addFilter(
                            'customer_group_code',
                            $customerGroupSearchName
                        )->create();
                        if ($this->groupRepository->getList($customerGroupSearch)->getItems()) {
                            $this->messageManager->addErrorMessage(
                                __('Customer group already exists.')
                            );
                            $resultRedirect->setPath('customer/group/edit');
                            return $resultRedirect;
                        }
                    }
                }

                try {
                    $newGroup = $this->groupRepository->save($customerGroup);
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());

                    if ($isFolderLevelPermissionToggleEnabled && $id == -1) {
                        $resultRedirect->setPath('customer/group/edit');
                    } else {
                        $resultRedirect->setPath('customer/group/edit', ['id' => $id]);
                    }

                    return $resultRedirect;
                }
                
                if ($newGroup) {
                    $groupId = $newGroup->getId();
                }
                $parentId = $this->getParentGroupId($groupId);

                $categoryIds = isset($data['customergroup_catalog_permission']['customergroup_catalog_permission_wrapper']['data']['categories'])?
                              $data['customergroup_catalog_permission']['customergroup_catalog_permission_wrapper']['data']['categories']:[] ;
                $categoryIds = array_values(array_filter($categoryIds, fn ($value) => !is_null($value) && $value != 'null' && $value != ''));

                if($parentData) {
                    $this->folderPermission->mapCategoriesCustomerGroup($categoryIds, $parentData, $newGroup->getId());
                    $unAssignedCategories = $this->folderPermission->getUnAssignedCategories($newGroup->getId(), $categoryIds);
                    if (!empty($unAssignedCategories)) {
                        $this->folderPermission->unAssignCustomerGroupId($unAssignedCategories, $newGroup->getId());
                    }
                    $this->folderPermission->assignCustomerGroupId($categoryIds, $newGroup->getId());
                }

                if ($parentData) {
                    if (!$parentId) {
                        if ($groupId == $parentData) {
                            $this->messageManager->addSuccessMessage(__($parentMessage));
                        } else {
                            if ($this->toggleConfig->getToggleConfigValue(static::SGC_FLP_TOGGLE)) {
                                $this->updateCustomerGroupCategories($groupId, $categoryIds);
                            }
                            $this->insertParentGroup($groupId, $parentData, $categoryIds);
                            $this->messageManager->addSuccessMessage(__($message));
                        }
                    } else {
                        if ($id == $parentData) {
                            $this->messageManager->addSuccessMessage(__($parentMessage));
                        } else {
                            if ($this->toggleConfig->getToggleConfigValue(static::SGC_FLP_TOGGLE)) {
                                $this->updateCustomerGroupCategories($groupId, $categoryIds);
                            }
                            $this->updateParentGroup($id, $parentData, $categoryIds);
                            $this->messageManager->addSuccessMessage(__($message));
                        }
                    }
                } else {
                    $this->messageManager->addSuccessMessage(__($message));
                }
                $resultRedirect->setPath('customer/group');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if ($customerGroup != null) {
                    $this->storeCustomerGroupDataToSession(
                        $this->dataObjectProcessor->buildOutputDataArray(
                            $customerGroup,
                            GroupInterface::class
                        )
                    );
                }
                $resultRedirect->setPath('customer/group/edit', ['id' => $id]);
            }
            return $resultRedirect;
        } else {
            return $this->resultForwardFactory->create()->forward('new');
        }
    }
    /**
     * UpdateParentGroup
     *
     * @param string $customerGroupId
     * @param int $parentGroupId
     * @return void
     */
    public function updateParentGroup($customerGroupId, $parentGroupId, $categoryIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::TABLE_NAME);
        $data = [
            'parent_group_id' => $parentGroupId,
            'category_ids' => implode(',', $categoryIds)
        ];
        $where = ['customer_group_id = ?' => $customerGroupId];
        try {
            $connection->update($tableName, $data, $where);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
    /**
     * InsertParentGroup
     *
     * @param string $customerGroupId
     * @param int $parentGroupId
     * @return void
     */
    public function insertParentGroup($customerGroupId, $parentGroupId, $categoryIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::TABLE_NAME);

        $data = [
            'customer_group_id' => $customerGroupId,
            'parent_group_id' => $parentGroupId,
            'category_ids' => implode(',', $categoryIds),
            'created_at' => (new DateTime())->format(DateTimeFormatter::DATETIME_PHP_FORMAT)
        ];
        try {
            $connection->insert($tableName, $data);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
    /**
     * Get the parent group ID for a given customer group ID
     *
     * @param string $customerGroupId
     * @return int|null
     */
    public function getParentGroupId($customerGroupId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::TABLE_NAME);

        $select = $connection->select()->from(
            $tableName,
            ['parent_group_id']
        )->where('customer_group_id = ?', $customerGroupId);

        try {
            $parentId = $connection->fetchOne($select);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $parentId !== false ? (int)$parentId : null;
    }

    /**
     * Checks if customer group category permission needs to be updated
     *
     * @param string $customerGroupId
     * @param array $categoryIds
     *
     * @return void
     */
    public function updateCustomerGroupCategories($customerGroupId, $categoryIds): void
    {
        $parentUserGroupCollection = $this->parentUserGroupCollection->create();
        $parentUserGroupCollection->addFieldToSelect('category_ids')
            ->addFieldToFilter('customer_group_id', $customerGroupId);

        try {
            $categoriesColumn = $parentUserGroupCollection->getFirstItem();
            
            if ($categoriesColumn && $categoriesColumn->getCategoryIds()) {
                $categoriesColumnValue = $categoriesColumn->getData('category_ids');
            } else {
                $categoriesColumnValue = '';
            }

            $categories = $categoriesColumnValue ? explode(',', $categoriesColumnValue) : [];
            $updatedCategories = array_diff($categories, $categoryIds);

            if ($updatedCategories) {
                $this->folderPermission->updatePermissions($updatedCategories);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
