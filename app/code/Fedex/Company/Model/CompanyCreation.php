<?php
/**
 * @category Fedex
 * @package  Fedex_Company
 * @copyright   Copyright (c) 2024 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Company\Model;

use Exception;
use Fedex\CatalogMvp\Helper\SharedCatalogProduct;
use Fedex\Company\Api\CreateCompanyEntitiesMessageInterface;
use Fedex\Ondemand\Api\Data\ConfigInterface;
use Fedex\Company\Api\Data\ConfigInterface as CompanyConfigInterface;
use Fedex\SaaSCommon\Api\ConfigInterface as FedexSaaSCommonConfig;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as PermissionCollectionFactory;
use Magento\CatalogPermissions\Model\PermissionFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Model\Repository;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\Store\Api\Data\GroupInterface as StoreGroupInterface;
use Magento\Store\Model\GroupFactory as StoreGroupFactory;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Fedex\Catalog\Api\AttributeHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CompanyCreation.
 * Handles creation of Shared Catalog, Customer Group and Root Category when Company is created.
 */
class CompanyCreation
{
    public const ROOT_CATEGORY_SUFFIX = ' Browse Catalog';
    public const SHARED_CATALOG_SUFFIX = ' Shared Catalog';
    public const CUSTOMER_GROUP_SUFFIX = ' Customer Group';
    public const MAIN_WEBSITE_ID = '1';
    public const ALL_WEBSITE_ID = '-1';
    public const ONDEMAND_STORE_CODE = 'ondemand';
    public const ALL_CUSTOMER_GROUPS_ID = '-1';
    public const CATEGORY_PERMISSION_ALLOW = '-1';
    public const CATEGORY_PERMISSION_DENY = '-2';

    /**
     * @var string[]
     */
    protected ?array $productsSku = null;

    /**
     * @var GroupInterface|null
     */
    protected ?GroupInterface $customerGroup = null;

    /**
     * @var SharedCatalogInterface|null
     */
    protected ?SharedCatalogInterface $sharedCatalog = null;

    /**
     * @var CategoryInterface|null
     */
    protected ?CategoryInterface $rootCategory = null;

    /**
     * @param AdminSession $adminSession
     * @param SharedCatalogFactory $sharedCatalogFactory
     * @param Repository $sharedCatalogRepository
     * @param ProductManagementInterface $productManagement
     * @param CategoryManagementInterface $categoryManagement
     * @param GroupInterfaceFactory $groupFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param Collection $catalogPermission
     * @param PermissionFactory $permissionFactory
     * @param PermissionCollectionFactory $permissionCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CategoryRepository $categoryRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryInterfaceFactory $categoryInterfaceFactory
     * @param StoreGroupFactory $storeGroupFactory
     * @param ConfigInterface $ondemandConfig
     * @param SharedCatalogProduct $sharedCatalogProductHelper
     * @param CompanyConfigInterface $companyConfigInterface
     * @param AttributeHandlerInterface $attributeHandlerInterface
     * @param CustomerGroupAttributeHandlerInterface $customerGroupAttributeHandler
     * @param CreateCompanyEntitiesMessageInterface $message
     * @param PublisherInterface $publisher
     * @param Json $serializer
     * @param LoggerInterface $logger
     * @param Registry $registry
     */
    public function __construct(
        //NOSONAR
        private AdminSession                $adminSession,
        private SharedCatalogFactory        $sharedCatalogFactory,
        private Repository                  $sharedCatalogRepository,
        private ProductManagementInterface  $productManagement,
        private CategoryManagementInterface $categoryManagement,
        private GroupInterfaceFactory       $groupFactory,
        private GroupRepositoryInterface    $groupRepository,
        private TaxClassRepositoryInterface $taxClassRepository,
        private Collection                  $catalogPermission,
        private PermissionFactory           $permissionFactory,
        private PermissionCollectionFactory $permissionCollectionFactory,
        private SearchCriteriaBuilder       $searchCriteriaBuilder,
        private CategoryRepository          $categoryRepository,
        private CategoryCollectionFactory   $categoryCollectionFactory,
        private CategoryInterfaceFactory    $categoryInterfaceFactory,
        private StoreGroupFactory                       $storeGroupFactory,
        private ConfigInterface                         $ondemandConfig,
        private SharedCatalogProduct                    $sharedCatalogProductHelper,
        private CompanyConfigInterface                  $companyConfigInterface,
        private AttributeHandlerInterface               $attributeHandlerInterface,
        protected CreateCompanyEntitiesMessageInterface $message,
        protected PublisherInterface                    $publisher,
        protected Json                                  $serializer,
        protected LoggerInterface                       $logger,
        protected \Magento\Framework\Registry           $registry,
        private FedexSaaSCommonConfig                   $fedexSaaSCommonConfig,
        private CustomerGroupAttributeHandlerInterface  $customerGroupAttributeHandler,
    ) {
    }

    /**
     * Initialize creation of all entities
     *
     * @param $urlExtensionName
     * @return $this
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function initializeCompanyExtraEntitiesCreation($urlExtensionName)
    {
        try {
            $taxClassId = $this->getRetailCustomerTaxClassId();
            $this->createCustomerGroup($urlExtensionName, $taxClassId);
            $customerGroup = $this->getCreatedCustomerGroup();

            $this->createRootCategory($urlExtensionName, $customerGroup);

            $this->createSharedCatalog($urlExtensionName, $customerGroup->getId(), $taxClassId);

            $this->checkErrors();
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            $this->deleteEntitiesCreatedDuringCompanyFlow();
            throw $e;
        }

        return $this;
    }

    /**
     * Initialize creation of Customer Group and Shared Catalog, using existing Root Category
     *
     * @param $urlExtensionName
     * @param $categoryId
     * @return $this
     * @throws CouldNotSaveException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function initializeOnlyCustomerGroupCreation($urlExtensionName, $categoryId)
    {
        try {
            $rootCategorySelected = $this->categoryRepository->get($categoryId);
            $this->rootCategory = $rootCategorySelected;

            $taxClassId = $this->getRetailCustomerTaxClassId();
            $this->createCustomerGroup($urlExtensionName, $taxClassId);
            $customerGroup = $this->getCreatedCustomerGroup();

            $this->createSharedCatalog($urlExtensionName, $customerGroup->getId(), $taxClassId);

            $this->updateCategoryPermission($customerGroup);

            $this->checkErrors();
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            $this->rootCategory = null;
            $this->deleteEntitiesCreatedDuringCompanyFlow();
            throw $e;
        }

        return $this;
    }

    /**
     * Initialize creation of Root Category, using existing Customer Group and Shared Catalog
     *
     * @param $urlExtensionName
     * @param $customerGroupSelected
     * @return $this
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function initializeOnlyRootCategoryCreation($urlExtensionName, $customerGroupSelected)
    {
        try {
            $customerGroup = $this->groupRepository->getById($customerGroupSelected);

            $this->createRootCategory($urlExtensionName, $customerGroup);

            $this->checkErrors(true);
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            $this->deleteEntitiesCreatedDuringCompanyFlow();
            throw $e;
        }

        return $this;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function deleteEntitiesCreatedDuringCompanyFlow()
    {
        $this->registry->register('isSecureArea', true);
        if($sharedCatalog = $this->getCreatedSharedCatalog()) {
            if(is_array($this->productsSku) && !empty($this->productsSku)) {
                $this->sharedCatalogProductHelper->applyUnassignedLogic(
                    $sharedCatalog->getId(),
                    $this->productsSku
                );
            }
            $this->sharedCatalogRepository->delete($sharedCatalog);
            $this->sharedCatalog = null;
            $this->customerGroup = null;
        }

        if($rootCategory = $this->getCreatedRootCategory()) {
            $this->categoryRepository->delete($rootCategory);
            $this->rootCategory = null;
        }

        if($customerGroup = $this->getCreatedCustomerGroup()) {
            $this->groupRepository->delete($customerGroup);
            $this->customerGroup = null;
        }
        $this->registry->unregister('isSecureArea');
    }

    /**
     * Publish message to create company entities
     * @param array $messageContent
     * @return void
     */
    public function publishCompanyEntities(array $messageContent): void
    {
        $messageSerialized = $this->serializer->serialize($messageContent);
        $this->message->setMessage($messageSerialized);
        $this->publisher->publish('createCompanyEntities', $this->message);
    }

    /**
     * Create Customer Group
     *
     * @param $urlExtensionName
     * @param $taxClassId
     * @return void
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function createCustomerGroup($urlExtensionName, $taxClassId)
    {
        /** @var GroupInterface $customerGroup */
        $customerGroup = $this->groupFactory->create();
        $customerGroup->setCode($urlExtensionName . self::CUSTOMER_GROUP_SUFFIX);
        $customerGroup->setTaxClassId($taxClassId);
        $customerGroup = $this->groupRepository->save($customerGroup);

        if ($this->fedexSaaSCommonConfig->isTigerD200529Enabled()) {
            $this->customerGroupAttributeHandler->addAttributeOption([$customerGroup->getId()]);
        }

        $this->customerGroup = $customerGroup;
    }

    /**
     * Create Shared Catalog
     *
     * @param string $urlExtensionName
     * @param int $customerGroupId
     * @param int $taxClassId
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    protected function createSharedCatalog($urlExtensionName, $customerGroupId, $taxClassId)
    {
        try {
            /**
             * @var $sharedCatalog SharedCatalogInterface
             */
            $sharedCatalog = $this->sharedCatalogFactory->create();
            $sharedCatalogName = $urlExtensionName . self::SHARED_CATALOG_SUFFIX;
            $sharedCatalog->setName($sharedCatalogName)
                ->setDescription($sharedCatalogName)
                ->setCreatedBy($this->getCurrentUserId())
                ->setType(SharedCatalogInterface::TYPE_CUSTOM)
                ->setCustomerGroupId($customerGroupId)
                ->setTaxClassId($taxClassId);

            $this->sharedCatalogRepository->save($sharedCatalog);
            $this->sharedCatalog = $sharedCatalog;

            // Create shared_catalogs attribute option for new site
            if ($this->companyConfigInterface->getSharedCatalogsMapIssueFixToggle()) {
                $this->attributeHandlerInterface->addAttributeOption([$sharedCatalog->getId()]);
            }

            $productsFromDefaultSharedCatalog = $this->getProductsFromConfiguredSharedCatalog();
            $this->productManagement->reassignProducts($sharedCatalog, $productsFromDefaultSharedCatalog);

            if(is_array($this->productsSku) && !empty($this->productsSku)) {
                $this->sharedCatalogProductHelper->applyAssignedLogic(
                    $sharedCatalog->getId(),
                    $this->productsSku
                );
            }

            $categoriesFromDefaultSharedCatalog = $this->getCategoryFromConfiguredSharedCatalog();
            $createdRootCategory = $this->getCreatedRootCategory();
            $categoriesFromDefaultSharedCatalog[$createdRootCategory->getId()] = $createdRootCategory;
            $this->categoryManagement->assignCategories($sharedCatalog->getId(), $categoriesFromDefaultSharedCatalog);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Could not save new Shared Catalog for Company:'.$urlExtensionName);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            throw new CouldNotSaveException(
                __('Could not save new Shared Catalog.')
            );
        }
    }

    /**
     * @param string $urlExtensionName
     * @param GroupInterface $b2bCustomerGroup
     * @return void
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    protected function createRootCategory(string $urlExtensionName, GroupInterface $b2bCustomerGroup): void
    {
        try {
            $b2bRootCategory = $this->getOndemandRootCategory();
            $newRootCategory = $this->categoryInterfaceFactory->create();
            $categoryName = $urlExtensionName . self::ROOT_CATEGORY_SUFFIX;
            $newRootCategory->setName($categoryName);
            $newRootCategory->setIsActive(true);
            $newRootCategory->setIncludeInMenu(true);
            $newRootCategory->setData('show_promo_banner',true);
            $newRootCategory->setData('pod2_0_editable',true);
            $newRootCategory->setCustomAttributes(['is_anchor' => 0 ]);
            $newRootCategory->setParentId($b2bRootCategory->getId());
            $newRootCategory->setStoreId(self::ONDEMAND_STORE_CODE);
            $newRootCategory->setData('custom_use_parent_settings', true);
            $newRootCategory->setData('md_column_count', '4');
            $this->categoryRepository->save($newRootCategory);

            /**
             * Saving the category for the second time because of the Magento's limitation to build the
             * permissions tree on repository save, only category->save() can trigger the permissions setup
             */
            if($this->companyConfigInterface->getCategoryEditD173846Toggle()){
                $newRootCategory->setStoreId(0);
            }
            $newRootCategory->save();

            // Save permissions using the PermissionFactory approach
            $this->saveCategoryPermissions($newRootCategory->getId(), $b2bCustomerGroup);

            $this->rootCategory = $newRootCategory;
        } catch (CouldNotSaveException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (NoSuchEntityException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Could not find store Root Category for Company:' . $urlExtensionName);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            throw new NoSuchEntityException(__('Could not find store Root Category.'));
        }
    }

    /**
     * @param GroupInterface $b2bCustomerGroup
     * @param bool $forExistinCategory
     * @return array[]
     */
    private function buildRootCategoryPermissions(GroupInterface $b2bCustomerGroup, $existAllGroupsPermission = false): array
    {
        if ($existAllGroupsPermission) {
            return [
                [
                    'website_id' => (string) self::ALL_WEBSITE_ID,
                    'customer_group_id' => (string) $b2bCustomerGroup->getId(),
                    'grant_catalog_category_view' => (string) self::CATEGORY_PERMISSION_ALLOW,
                    'grant_catalog_product_price' => (string) self::CATEGORY_PERMISSION_ALLOW,
                    'grant_checkout_items' => (string) self::CATEGORY_PERMISSION_ALLOW
                ]
            ];

        }

        return [
            [
                'website_id' => (string) self::ALL_WEBSITE_ID,
                'customer_group_id' => (string) $b2bCustomerGroup->getId(),
                'grant_catalog_category_view' => (string) self::CATEGORY_PERMISSION_ALLOW,
                'grant_catalog_product_price' => (string) self::CATEGORY_PERMISSION_ALLOW,
                'grant_checkout_items' => (string) self::CATEGORY_PERMISSION_ALLOW
            ],
            [
                'website_id' => (string) self::ALL_WEBSITE_ID,
                'customer_group_id' => (string) self::ALL_CUSTOMER_GROUPS_ID,
                'grant_catalog_category_view' => (string) self::CATEGORY_PERMISSION_DENY,
                'grant_catalog_product_price' => (string) self::CATEGORY_PERMISSION_DENY,
                'grant_checkout_items' => (string) self::CATEGORY_PERMISSION_DENY
            ]
        ];
    }

    /**
     * Save category permissions using PermissionFactory
     *
     * @param $categoryId
     * @param GroupInterface $b2bCustomerGroup
     * @param bool $existAllGroupsPermission
     * @return void
     */
    private function saveCategoryPermissions($categoryId, GroupInterface $b2bCustomerGroup, $existAllGroupsPermission = false): void
    {
        try {
            // First check if permissions already exist for this category
            $existingPermissions = $this->permissionCollectionFactory->create()
                ->addFieldToFilter('category_id', $categoryId);
            
            // Delete existing permissions to avoid conflicts
            foreach ($existingPermissions as $existingPermission) {
                $existingPermission->delete();
            }

            // Get the permission data structure from buildRootCategoryPermissions
            $permissionsData = $this->buildRootCategoryPermissions($b2bCustomerGroup, $existAllGroupsPermission);
            
            // Save each permission record
            foreach ($permissionsData as $permissionData) {
                $permission = $this->permissionFactory->create();
                
                // Add the category_id to the permission data
                $permissionData['category_id'] = $categoryId;
                
                // Convert website_id and customer_group_id to proper values
                if ($permissionData['website_id'] === (string) self::ALL_WEBSITE_ID) {
                    $permissionData['website_id'] = null; // Magento uses null for all websites
                }
                if ($permissionData['customer_group_id'] === (string) self::ALL_CUSTOMER_GROUPS_ID) {
                    $permissionData['customer_group_id'] = null; // Magento uses null for all customer groups
                }
                
                // Set the data and save the permissions
                $permission->setData($permissionData)->save();
            }

        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error saving category permissions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function checkErrors($onlyRootCategory = false)
    {
        $whichEntity = false;
        if ($onlyRootCategory) {
            if (!$this->getCreatedRootCategory()) {
                $whichEntity = 'Root Category';
            }
        } else {
            if (!$this->getCreatedCustomerGroup()) {
                $whichEntity = 'Customer Group';
            } elseif (!$this->getCreatedRootCategory()) {
                $whichEntity = 'Root Category';
            } elseif (!$this->getCreatedSharedCatalog()) {
                $whichEntity = 'Shared Catalog';
            }
        }

        if ($whichEntity) {
            throw new Exception($whichEntity . ' could not be created.');
        }
    }

    /**
     * @return false|GroupInterface
     */
    public function getCreatedCustomerGroup()
    {
        if ($this->customerGroup instanceof GroupInterface) {
            return $this->customerGroup;
        }

        return false;
    }

    /**
     * @return false|SharedCatalogInterface
     */
    public function getCreatedSharedCatalog()
    {
        if ($this->sharedCatalog instanceof SharedCatalogInterface) {
            return $this->sharedCatalog;
        }

        return false;
    }

    /**
     * @return false|CategoryInterface
     */
    public function getCreatedRootCategory()
    {
        if ($this->rootCategory instanceof CategoryInterface) {
            return $this->rootCategory;
        }

        return false;
    }

    /**
     * @return null|int
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getRetailCustomerTaxClassId()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->create();
        $customerTaxClasses = $this->taxClassRepository->getList($searchCriteria)->getItems();
        $customerTaxClass = array_shift($customerTaxClasses);

        return ($customerTaxClass && $customerTaxClass->getClassId()) ? $customerTaxClass->getClassId() : null;
    }

    /**
     * @return CategoryInterface|mixed|null
     * @throws NoSuchEntityException
     */
    private function getOndemandRootCategory()
    {
        /** @var StoreGroupInterface $group */
        $group = $this->storeGroupFactory->create();
        $group = $group->load(self::ONDEMAND_STORE_CODE, 'code');
        return $this->categoryRepository->get($group->getRootCategoryId());
    }

    /**
     * @return string[]
     */
    private function getProductsFromConfiguredSharedCatalog()
    {
        $defaultSharedCatalog = $this->ondemandConfig->getDefaultSharedCatalog();
        $this->productsSku = $this->productManagement->getProducts($defaultSharedCatalog);
        return $this->productsSku;
    }

    /**
     * @return DataObject[]|Category[]
     */
    protected function getCategoryFromConfiguredSharedCatalog()
    {
        $defaultSharedCatalog = $this->ondemandConfig->getDefaultSharedCatalog();
        $sharedCatalogCategoryIds = $this->categoryManagement->getCategories($defaultSharedCatalog);

        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addFieldToFilter('entity_id', $sharedCatalogCategoryIds);

        return $categoryCollection->getItems();
    }

    /**
     * @param GroupInterface $b2bCustomerGroup
     * @return void
     * @throws Exception
     */
    private function updateCategoryPermission($b2bCustomerGroup)
    {
        $existingRootCategory = $this->getCreatedRootCategory();
        $existingCategoryPermission = $this->getExistingCategoriesPermission($existingRootCategory->getId());
        if($existingCategoryPermission->getSize()) {
            $permissions = $existingCategoryPermission->getData();
            foreach ($permissions as &$permission) {
                $permission['id'] = $permission['permission_id'];
                if (!$permission['customer_group_id']) {
                    $existAllGroupsPermission = true;
                }
            }
        }

        $existingRootCategory->save();
        
        // save permissions using the PermissionFactory approach
        $this->saveCategoryPermissions($existingRootCategory->getId(), $b2bCustomerGroup, $existAllGroupsPermission ?? false);
    }

    /**
     * @param $categoryId
     * @return Collection
     */
    private function getExistingCategoriesPermission($categoryId) {
        return $this->catalogPermission->addFieldToFilter('category_id', $categoryId);
    }

    /**
     * Return current admin user ID
     *
     * @return mixed|null
     */
    private function getCurrentUserId()
    {
        return $this->adminSession->getUser()?->getId();
    }
}
