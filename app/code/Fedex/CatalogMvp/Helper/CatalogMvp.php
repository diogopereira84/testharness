<?php

namespace Fedex\CatalogMvp\Helper;

use Fedex\CatalogMvp\Model\ProductActivity;
use Fedex\Commercial\Helper\CommercialHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory as PermissionCollectionFactory;
use Fedex\SelfReg\Model\CustomerGroupPermissionManager;

class CatalogMvp extends AbstractHelper
{
    const POD_2_0_EDITABLE = 'pod2_0_editable';

    const PRINT_ON_DEMAND = 'PrintOnDemand';

    const ENABLE_SHARED_CATALOG_MENU_CHANGES = 'enable_shared_catalog_menu_changes';

    const ENABLE_SHARED_CATALOG_HOMEPAGE_MENU_CHANGES = 'enable_shared_catalog_home_page_changes';

    const D_174502_CATALOG_DEFAULT_SORTING  = 'd_174502_catalog_defaul_sorting';
    const SUB_FOLDER_NOT_SHOWING_TOGGLE = 'techtitans_D179665_fix';

    const FOLDER_PERMISSION_FIX_TOGGLE = 'explorers_d_191654_folder_permission_fix';

    protected const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';

    public const  TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    const MILLIONAIRES_EPRO_MIGRATED_CUSTOM_DOC = 'b2184326_epro_migrated_custom_doc';

    const TECHTITANS_B2193925_PRODUCT_UPDATED_AT = 'techtitans_B_2193925_product_updated_at';

    public const  MAZEGEEKS_MERGED_SHARED_CATALOG_FILES = 'merge_shared_catalog_files';

    const TECHTITANS_D_199955_SUBFOLDERS = 'tech_titans_d_199955';

    const TIGER_D213910 = 'tiger_d213910';
    public const TIGER_D_216406_DUPLICATE_SHARED_CATALOG_ID = 'tiger_D_216406_duplicate_shared_catalog_id';

    public function __construct(
        Context                                               $context,
        protected ToggleConfig                                $toggleConfig,
        protected DeliveryHelper                              $deliveryHelper,
        protected SdeHelper                                   $sdeHelper,
        protected Registry                                    $registry,
        private CategoryRepository                            $categoryRepository,
        private LoggerInterface                               $logger,
        private ProductRepository                             $productRepository,
        protected Http                                        $request,
        protected CategoryManagementInterface                 $categoryManagement,
        protected StoreManagerInterface                       $storeManger,
        protected AttributeSetRepositoryInterface             $attributeSetRepository,
        protected SessionFactory                              $customerSession,
        protected CompanyFactory                              $companyFactory,
        protected SelfReg                                     $selfRegHelper,
        protected CategoryCollectionFactory                   $CategoryCollectionFactory,
        protected CategoryLinkManagementInterface             $categoryLinkManagementInterface,
        protected Category                                    $category,
        protected CategoryHelper                              $categoryHelper,
        protected ProductFactory                              $productFactory,
        protected ResourceConnection                          $resourceConnection,
        protected ModuleDataSetupInterface                    $moduleDataSetup,
        private AttributeSetCollectionFactory                 $attributeSetCollection,
        private StoreRepositoryInterface                      $storeRepository,
        private Action                                        $productAction,
        private CommercialHelper                              $commercialHelper,
        private ScopeOverriddenValue                          $scopeOverriddenValue,
        protected CompanyManagementInterface                  $companyRepository,
        private ProductActivity                               $productActivity,
        private Resolver                                      $layerResolver,
        private Session                                       $session,
        private EntityType                                    $entityType,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        private PermissionCollectionFactory                   $permissionCollectionFactory,
        private CustomerGroupPermissionManager                $customerGroupPermissionManager,
        private readonly OndemandConfigInterface              $ondemandConfig
    )
    {
        parent::__construct($context);
    }

    /**
     * Get attribute set id by name
     *
     * @param  varchar $attributeSetName
     * @return int
     */
    public function getAttrSetIdByName($attributeSetName)
    {
        $attributeSet = $this->attributeSetCollection->create()
            ->addFieldToFilter('attribute_set_name', $attributeSetName)
            ->getFirstItem();

        $attributeSetId = null;

        if (is_object($attributeSet)) {
            $attributeSetId = $attributeSet->getAttributeSetId();
        }

        return $attributeSetId;
    }

    /**
     * Get product attribute set collection
     *
     * @param  varchar $attributeSetName
     * @return int
     */
    public function getProductAttrSetCollection()
    {
        $entityTypeId = $this->entityType->loadByCode(\Magento\Catalog\Model\Product::ENTITY)->getId();

        $attributeSetCollection = $this->attributeSetCollection->create()
            ->setEntityTypeFilter($entityTypeId);
        $attributeSetArray = [];

        foreach ($attributeSetCollection as $attributeSet) {
            $attributeSetArray[$attributeSet->getId()] = $attributeSet->getAttributeSetName();
        }

        return $attributeSetArray;
    }

    /**
     * Get attribute set id by product id
     *
     * @return array
     */
    public function getAttrSetIdByProductId($productId)
    {
        $product = $this->productRepository->getById($productId);
        $attributeSetId = $product->getAttributeSetId();
        $attributeSet = $this->attributeSetRepository->get($attributeSetId);
        $attributeSetName = isset($attributeSet) ? $attributeSet->getAttributeSetName() : '';

        return $attributeSetName;
    }

    /**
     * B-1556306
     *
     * Checks if catalogMvp CTC Admin Toggle Enable
     */
    public function isMvpCtcAdminEnable()
    {
        return true;
    }

    /**
     * B-1569412
     *
     * Checks if catalogMvp is enable
     */
    public function isMvpSharedCatalogEnable()
    {
        $isCommercialCustomer = $this->deliveryHelper->isCommercialCustomer();
        if ($isCommercialCustomer && $this->isMvpCatalogEnabledForCompany()) {
            return true;
        }
        return false;
    }

    /**
     * Tech Titans bugfix spinner
     * @return bool
     */
    public function isLoaderRemovedEnable(){
        return (bool)$this->toggleConfig->getToggleConfigValue('techtitans_bugfix_spinner');
    }

    /**
     * Check if edit folder access toggle is enabled
     *
     * @return bool
     */
    public function isEditFolderAccessEnabled(){
        return (bool) $this->toggleConfig->getToggleConfigValue('add_edit_folder_access');
    }

    /**
     * Check if edit folder access toggle is enabled
     *
     * @return bool
     */
    public function isD212350FixEnabled(){
        return (bool) $this->toggleConfig->getToggleConfigValue('sgc_d_212530');
    }

    /**
     * Check if Renaming folders results in broken URL links
     *
     * @return bool
     */
    public function isD231833FixEnabled(){
        return (bool) $this->toggleConfig->getToggleConfigValue('sgc_d_231833');
    }

    /**
     * Generate a url_key from a name
     *
     * @param string $name
     * @return string
     */
    public function generateUrlKey(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', '-', $name), '-'));
    }

    /**
     * D-223040 - Restricted Folders view sync in Shared Catalog toggle
     *
     * @return bool
     */
    public function isRestictedFoldersSyncEnabled()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue('sgc_d223040');
    }

    /**
     * Checks if commercial customer
     *
     * @return boolean
     */
    public function isCommercialCustomer()
    {
        return $this->deliveryHelper->isCommercialCustomer();
    }

    /**
     * @return array|Collection
     * @throws NoSuchEntityException
     */
    public function getSubCategories($customeradmin = '0', $sharedCatalogCategoryId = null): array|Collection
    {
        $category = $this->getCurrentCategory();
        if ($this->isD212350FixEnabled()) {
            $parentid = $sharedCatalogCategoryId ? $sharedCatalogCategoryId : $category->getId();
        } else {
            $parentid = $category->getId();
        }

        if ($customeradmin) {
            $categoryCollection = $this->CategoryCollectionFactory->create();
            if ($categoryCollection) {
                $categoryCollection->addAttributeToSelect('*')
                    ->addFieldToFilter('parent_id', $parentid);
            }

            $subcategories = $categoryCollection;
        } else {
            /* B-1573026 */
            $categoryObj = $this->categoryRepository->get($parentid);
            $subcategories = $categoryObj->getChildrenCategories();
            $subcategories->addAttributeToFilter("is_publish", "1");
        }
        /* B-1573026 */
        $productListOrder = $this->request->getParam('product_list_order');
        if($this->sortingToggle()) {
            if($subcategories){
                $subcategories->addAttributeToSort("name", "asc");
            }
        }
        if ($productListOrder == 'most_recent') {
            $subcategories->addAttributeToSort("updated_at", "desc");
        } elseif ($productListOrder == 'name_asc') {
            $subcategories->addAttributeToSort("name", "asc");
        } elseif ($productListOrder == 'name_desc') {
            $subcategories->addAttributeToSort("name", "desc");
        } else {
            if($subcategories){
                $subcategories->addAttributeToSort("updated_at", "desc");
            }
        }
        return $subcategories ?? [];
    }

    /**
     * Function to get sub category by parent ID
     *
     * @param  $categoryId
     * @return array
     */
    public function getSubCategoryByParentID($categoryId, $B2bPrintCategory)
    {
        $categoryData = [];

        $getSubCategory = $this->getCategoryData($categoryId);
        foreach ($getSubCategory->getChildrenData() as $category) {
            $categoryData[$category->getId()] = [
                'id' => $category->getId(),
            ];

            if (count($category->getChildrenData()) && $category->getId() != $B2bPrintCategory) {
                $getSubCategoryLevelDown = $this->getCategoryData($category->getId());
                $childrendata = $category->getCategories($category->getId(), 10, false, true, true);
                foreach ($childrendata as $child) {
                    $categoryData[$child->getId()] = [
                        'id' => $child->getId(),
                    ];
                }
            }
        }
        $categoryId = [];
        foreach ($categoryData as $cdata) {
            $categoryId[] = $cdata['id'];
        }
        return $categoryId;
    }

    /**
     * Function to get category data
     *
     * @param  $categoryId
     * @return CategoryTreeInterface|null
     */
    public function getCategoryData($categoryId)
    {
        try {
            $getSubCategory = $this->categoryManagement->getTree($categoryId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Category not found", [$e]);
            $getSubCategory = null;
        }

        return $getSubCategory;
    }

    /**
     * Function to getScopeConfigValue
     *
     * @param  $path
     * @return mix|null
     */
    public function getScopeConfigValue($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Function to get root category from store code
     *
     * @param  $storeCode
     * @return int
     */
    public function getRootCategoryFromStore($storeCode)
    {
        $allStores = $this->storeManger->getStores(true, false);
        foreach ($allStores as $store) {
            if ($store->getCode() === $storeCode) {
                return $store->getRootCategoryId();
            }
        }
    }

    /**
     * Function to get root category data from store code
     *
     * @param  $storeCode
     * @return array
     */
    public function getRootCategoryDetailFromStore($storeCode)
    {
        $store = $this->storeRepository->get($storeCode);
        $rootCategoryId = $store->getRootCategoryId();
        $category = $this->categoryRepository->get($rootCategoryId);
        return ['id' => $rootCategoryId, 'name' => $category->getName()];
    }

    /**
     * Get all store except ondemand
     *
     * @param  int $ondemandStoreId
     * @return array
     */
    public function getAllStoreExceptOndemand($ondemandStoreId)
    {
        $storeIds = [];
        $allStores = $this->storeRepository->getList();
        foreach ($allStores as $store) {
            $storeIds[] = $store->getId();
        }

        $storeIds = array_diff($storeIds, [0, $ondemandStoreId]);

        return $storeIds;
    }

    /**
     * Product visibility attribute value
     *
     * @param object $product
     * @param int    $attributeSetId
     *
     * @return boolean
     */
    public function setProductVisibilityValue($product, $attributeSetId)
    {
        $isAttributeSetPrintOnDemand = $this->isAttributeSetPrintOnDemand($attributeSetId);

        if ($isAttributeSetPrintOnDemand) {
            $ondemandStoreId = $this->getOndemandStoreId();
            $allStoreIdExceptOndemandStore = $this->getAllStoreExceptOndemand($ondemandStoreId);
            foreach ($allStoreIdExceptOndemandStore as $storeId) {
                $isOverriden = $this->scopeOverriddenValue->containsValue(
                    ProductInterface::class,
                    $product,
                    'visibility',
                    $storeId
                );
                if ($isOverriden) {
                    $this->productAction->updateAttributes([$product->getId()], ['visibility' => 1], $storeId);
                }
            }

            // D-184917 make visibility dynamic on ondemand store
            if (!empty($this->request->getPostValue()['product']['current_product_id'])) {
                $postData =$this->request->getPostValue();
                if (!empty($postData['product']['visibility']) && !empty($postData['product']['current_store_id'])
                 && $postData['product']['current_store_id'] == $ondemandStoreId) {
                    $this->productAction->updateAttributes(
                        [$product->getId()],
                        ['visibility' => $postData['product']['visibility']],
                        $ondemandStoreId
                    );
                }
            } else {
                $this->productAction->updateAttributes([$product->getId()], ['visibility' => 4], $ondemandStoreId);
            }
        }

        return true;
    }

    /**
     * Function to get getAttributeSetName from attribute id
     *
     * @param  $attributeSetId
     * @return string|null
     */
    public function getAttributeSetName($attributeSetId)
    {
        try {
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
            return;
        }
        $attributeSetName = trim($attributeSet['attribute_set_name']);
        return $attributeSetName;
    }

    public function isCataloUpdateMessagesEnabled()
    {
        return true;
    }
    /**
     * Identify product attribute set id PrintOnDemand
     *
     * @param  $attributeSetId
     * @return boolean
     */
    public function isAttributeSetPrintOnDemand($attributeSetId)
    {
        $attributeSetName = $this->getAttributeSetName($attributeSetId);
        if (strtolower($attributeSetName) == strtolower("PrintOnDemand")) {
            return true;
        }

        return false;
    }

    /**
     * Get Ondemand Stoe Id
     *
     * @return int
     */
    public function getOndemandStoreId()
    {
        $storeCode = $this->scopeConfig->getValue(
            "ondemand_setting/category_setting/b2b_default_store"
        );
        $store = $this->storeRepository->get($storeCode);
        return $store->getId();
    }

    /**
     * @return boolean
     * @throws Exception
     */
    public function getIsLegacyItemBySku($sku)
    {

        $returnValue = true;
        try {
            if ($sku) {
                $product = $this->productRepository->get($sku);
                $attributeSetId = $product->getAttributeSetId();
                $attributeSet = $this->attributeSetRepository->get($attributeSetId);
                $attributeSetName = isset($attributeSet) ? $attributeSet->getAttributeSetName() : '';
                $customAttributeValue = $product->getCustomAttribute(self::POD_2_0_EDITABLE);

                if (($attributeSetName !== self::PRINT_ON_DEMAND)
                    || ($attributeSetName == self::PRINT_ON_DEMAND
                    && $customAttributeValue
                    && $customAttributeValue->getValue())
                ) {
                    $returnValue = false;
                }
            }
            return $returnValue;
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .
                ' Error get custom product attribute: ' . $error->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Checks if the isMvpCatalogEnabledFoCompany
     */
    public function isMvpCatalogEnabledForCompany()
    {
        static $return = null;
        if ($this->performanceImprovementPhaseTwoConfig->isActive()
            && $return !== null
        ) {
            return $return;
        }

        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSession->create();
        }

        if ($customerSession->isLoggedIn()) {
            $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
            $loginMethod = '';
            if ($companyData) {
                $loginMethod = $companyData->getStorefrontLoginMethodOption();
                $isMvpCatalogEnabled = $companyData->getIsCatalogMvpEnabled();
            }
            if (($this->selfRegHelper->isSelfRegCustomer() || (isset($loginMethod) && $loginMethod == 'commercial_store_epro')) && $isMvpCatalogEnabled) {
                $return = true;
                return $return;
            }

        }
        $return = false;
        return $return;
    }

    /**
     * Checks if the customer is SelfReg Admin
     *
     * @return boolean
     */
    public function isSelfRegCustomerAdmin()
    {
        return $this->selfRegHelper->isSelfRegCustomerAdmin();
    }
    /**
     * Checks if the customer is SelfReg Admin
     *
     * @return boolean
     */
    public function isSelfRegCustomer()
    {
        return $this->selfRegHelper->isSelfRegCustomer();
    }
    /**
     * Checks if the customer has the shared catalog permission
     *
     * @return boolean
     */
    public function isSharedCatalogPermissionEnabled()
    {
        if ($this->selfRegHelper->isSelfRegCustomerAdmin()) {
            return true;
        }
        $isRolesAndPermissionEnabled = $this->commercialHelper->isRolePermissionToggleEnable();
        if ($isRolesAndPermissionEnabled) {
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customerSession = $this->getOrCreateCustomerSession();
            } else {
                $customerSession = $this->customerSession->create();
            }
            $customer = $customerSession->getCustomer();
            if (is_object($customer)) {
                $isSharedCatalogEnabled = false;
                $permisssionData = $customerSession->getUserPermissionData();
                if (is_array($permisssionData) && !empty($permisssionData)) {
                    $keys = array_keys($permisssionData);
                    $isSharedCatalogEnabled = preg_grep("/\b::manage_catalog\b/", $keys);
                }
                if ($isSharedCatalogEnabled) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks if the category is print product or not
     * Logic introduced in D-239305 will encapsulate Office Supplies and Shipping, Packing and Mailing Supplies Categories
     * on this same logic + code improvement
     */
    public function checkPrintCategory()
    {
        if ($this->ondemandConfig->isTigerD239305ToggleEnabled()) {
            $b2bcategories = $this->ondemandConfig->getGlobalB2BCategories();
            $category = $this->getCurrentCategory();
            if (!$category) {
                return false;
            }
            $currentCategoryID = $category->getId();
            if (in_array($currentCategoryID, $b2bcategories)) {
                return true;
            }
            $parentCategories = explode("/", $category->getPath());
            if (array_intersect($b2bcategories, $parentCategories)) {
                return true;
            }
        } else {
            $b2bcategory = $this->scopeConfig->getValue('ondemand_setting/category_setting/epro_print');
            $category = $this->getCurrentCategory();
            if ($category) {
                $currentCategoryID = $category->getId();
                if ($b2bcategory == $currentCategoryID) {
                    return true;
                }
                $parentCategories = explode("/", $category->getPath());
                if (in_array($b2bcategory, $parentCategories)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find the products which need to excluded from collection
     *
     * @param  \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @return array
     */
    public function getFilteredCategoryItem($productCollection)
    {
        $date = $this->getCurrentPSTDateAndTime();
        if ($this->deliveryHelper->isCommercialCustomer()
            && $this->isMvpSharedCatalogEnable() && !$this->isSelfRegCustomerAdmin()
        ) {
            $productCollection->addAttributeToSelect('*')
                ->addAttributeToFilter(
                    [
                        ['attribute' => 'end_date_pod', 'null' => true],
                        ['attribute' => 'end_date_pod', 'gteq' => $date],
                    ]
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'start_date_pod', 'null' => true],
                        ['attribute' => 'start_date_pod', 'lteq' => $date],
                    ]
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'published', 'eq' => 1],
                    ]
                );
            return $productCollection->getColumnValues('entity_id');
        }
        return [];
    }

    /**
     * Product assign into new category and also remove product from previous categories
     *
     * @param  int|string|null $productSku
     * @param  int             $newCategoryId
     * @return boolean
     * @throws Exception
     */
    public function assignProductToCategory($productSku, $newCategoryId)
    {

        try {
            return $this->categoryLinkManagementInterface->assignProductToCategories($productSku, [$newCategoryId]);
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ' Error while product assign into new category for product sku: ' . $productSku . ' ' . $error->getMessage()
            );

            return false;
        }
    }

    /**
     * Category assign into another category and also remove product and category from previous categories
     *
     * @param  int|string|null $parentCategoryId
     * @param  int             $categoryId
     * @return boolean
     * @throws Exception
     */
    public function assignCategoryToCategory($parentCategoryId, $categoryId)
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            $category->move($parentCategoryId, $categoryId);
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ' Error while moving category for category ID: ' . $categoryId . ' ' . $error->getMessage()
            );

            return false;
        }
    }

    /**
     * Delete category
     *
     * @param  int $categoryId
     * @return int
     * @throws Exception
     */
    public function deleteCategory($categoryId)
    {
        $categoryDelete = 0;
        try {
            $category= $this->categoryRepository->get($categoryId);
            $this->categoryRepository->delete($category);
            $categoryDelete = 1;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ . " Category not deleted " . $e->getMessage());
        }
        return $categoryDelete;
    }

    /**
     * Get Print Product Sub category
     *
     * @return array|CategoryRepository
     */
    public function getB2BcategoryCollection()
    {
        $category = $this->getCurrentCategory();
        $parentCategories = explode("/", $category->getPath());
        $curentsharedcatalogID = $parentCategories['2'];
        $categoryrepo = $this->categoryRepository->get($curentsharedcatalogID);
        return $categoryrepo->getChildrenCategories();
    }

    /**
     * Get Category Repository
     *
     * @return array|CategoryRepository
     */
    public function getCategoryRepository()
    {
        return $this->categoryRepository;
    }
    /**
     * Get category Url
     *
     * @return string
     */
    public function getCategoryUrl($category)
    {
        return $this->categoryHelper->getCategoryUrl($category);
    }

    /**
     * @param  string $date
     * @return string
     */
    public function convertTimeIntoPST($date)
    {
        $timestamp = strtotime($date);
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $date->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param  string $date
     * @return string
     */
    public function convertTimeIntoPSTWithCustomerTimezone($date, $customerTimezone)
    {
        $date = new \DateTime($date, new \DateTimeZone($customerTimezone));
        $date = $date->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param  string $date
     * @return string
     */
    public function getCurrentPSTDateAndTime()
    {
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param  string $date
     * @return string
     */
    public function getCurrentPSTDate()
    {
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $date->format('Y-m-d');
    }
    /**
     * Get Current Category Id
     *
     * @return string
     */
    public function getCurrentCategoryId()
    {
        $category = $this->getCurrentCategory();
        $parentCategories = explode("/", $category->getPath());
        return $parentCategories['2'];
    }

    /**
     * Get child category count
     *
     * @return int
     */
    public function getChildCategoryCount()
    {
        $categoryCount = 0;
        $category = $this->getCurrentCategory();
        if ($category->getChildrenCount() > 0) {
            $categoryCount = $category->getChildrenCount();
        }
        return $categoryCount;
    }

    /**
     * Get Current Sub Category Id
     *
     * @return string
     */
    public function getCurrentSubCategoryId()
    {
        $category = $this->getCurrentCategory();
        return $category->getId();
    }

    /**
     * Get Current Sub Category Name
     *
     * @return string
     */
    public function getCurrentSubCategoryName()
    {
        $category = $this->getCurrentCategory();
        if ($this->getCurrentCategoryId() == $this->getCurrentSubCategoryId()) {
            return __('Shared Catalog');
        } else {
            return $category->getName();
        }
    }

    /**
     * Get Current Category Path
     *
     * @return array
     */
    public function getCurrentCategoryPath()
    {
        $category = $this->getCurrentCategory();
        $parentCategories = explode("/", $category->getPath());
        return $parentCategories;
    }

    /**
     * Get Current product is POD Edit able
     *
     * @return boolean
     */
    public function isProductPodEditAbleById($productId)
    {
        $pod20editable = 0;
        try {
            if (!empty($productId)) {
                $product = $this->productFactory->create();
                $productCollection = $product->getCollection()->addFieldToFilter('entity_id', $productId)->getFirstItem();
                if ($productCollection->getData()) {
                    $prouctdata = $productCollection->getData();
                    $pod20editable = $prouctdata[self::POD_2_0_EDITABLE];
                }
            }
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . __(' Error while product varification for legacy and pod edit able product: ') . $productId . ' ' . $error->getMessage()
            );
        }

        return $pod20editable;
    }

    /**
     * Get Id From Child Node
     */
    public function getIdFromNode($child)
    {
        return $child->getId();
    }

    /**
     * Get Product FxoMenuId by Id
     *
     * @param  int $productId
     * @return string
     */
    public function getFxoMenuId($productId)
    {
        $fxoMenuId = null;
        $connection = $this->resourceConnection->getConnection();
        $productTable = $connection->getTableName('catalog_product_entity');
        $query = $connection->select()->from($productTable)->where('entity_id = ?', $productId);
        $result = $connection->fetchAll($query);
        if (!empty($result[0])) {
            $fxoMenuId = $result[0]['fxo_menu_id'];
        }
        return $fxoMenuId;
    }
    /**
     * Update Product Fxo Menu Id
     *
     * @param int    $productId
     * @param string $fxoMenuId
     */
    public function updateFxoMenuId($productId, $fxoMenuId)
    {
        try {
            if ($productId && $fxoMenuId) {
                $productEntityTable = $this->moduleDataSetup->getTable('catalog_product_entity');
                $this->moduleDataSetup->getConnection()->update(
                    $productEntityTable,
                    ['fxo_menu_id' => $fxoMenuId],
                    ['entity_id = ?' => $productId]
                );
                $this->moduleDataSetup->endSetup();
            }
        } catch (\Exception $e) {
            $this->logger->debug("Error while setting FXO Menu Id " . $e->getMessage());
        }
    }
    /**
     * B-1828539
     *
     * Checks if Custom Document Toggle Enable
     *
     * @return boolean
     */
    public function customDocumentToggle()
    {
        return true;
    }

    /**
     * getCompanySharedCatName
     *
     * @return string
     */
    public function getCompanySharedCatName()
    {
        $catName = "";
        $company = $this->deliveryHelper->getAssignedCompany();
        $companySharedId = 0;

        if ($company) {
            $companySharedId = $company->getSharedCatalogId();
        }
        if ($companySharedId) {
            $category=$this->categoryRepository->get($companySharedId);
            $catName = $category->getName();
        }
        return $catName;
    }

    /**
     * getCompanySharedCatId
     *
     * @return string
     */
    public function getCompanySharedCatId()
    {
        $companySharedId = 0;
        $company = $this->deliveryHelper->getAssignedCompany();
        if ($company) {
            $companySharedId = $company->getSharedCatalogId();
        }

        return $companySharedId;
    }

    public function getDenyCategoryIdsFromListLogic($sharedCatalogCategoryId)
    {
        $subcategories = $this->getSubCategories(1, $sharedCatalogCategoryId);
        $customerAdmin = $this->isSharedCatalogPermissionEnabled();
        $isEditFolderAccessEnabled = $this->isEditFolderAccessEnabled();
        if ($isEditFolderAccessEnabled) {
            $companyUserGroups = $this->getUserGroupsforCompany();
        }
        $allowed = $denied = [];
        foreach ($subcategories as $subcategorie) {
            $categoryId = $subcategorie->getId();
            $isFolderPermissionAllowed = $this->isFolderPermissionAllowed($categoryId);
            if (!$customerAdmin && !$isFolderPermissionAllowed) {
                $denied[] = $categoryId;
                continue;
            }
            if ($isEditFolderAccessEnabled) {
                $isFolderRestricted = $this->getCategoryPermission($categoryId, $companyUserGroups);
                if ($isFolderRestricted &&
                    !$customerAdmin && $this->isFolderRestrictedToUser($categoryId, $companyUserGroups)) {
                    $denied[] = $categoryId;
                    continue;
                }
            }

            $allowed[] = $categoryId;

            if ($this->isRestictedFoldersSyncEnabled() && !$customerAdmin && $subcategorie->getChildrenCount() > 0) {
                $denied = array_merge($denied, $this->getDenyCategoryIdsFromListLogic($categoryId));
            }
        }

        return $denied;
    }


    /**
     * Get all deny category data for current customer's group
     *
     * @param  string $implodedIds
     * @param  int    $groupId
     * @return array
     */
    public function getDenyCategoryIds($implodedIds, $groupId, $sharedCatalogCategoryId = null)
    {
        if ($this->isD212350FixEnabled()) {
            return $this->getDenyCategoryIdsFromListLogic($sharedCatalogCategoryId);
        }
        $denyCategoryIds = [];
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('magento_catalogpermissions');


        $exodedIds = explode(",", $implodedIds);
        $subquery = $connection->select()
            ->distinct(true)
            ->from(['sub_table' => $connection->getTableName('magento_catalogpermissions')], 'category_id')
            ->where('sub_table.category_id IN (?)', $exodedIds)
            ->where('sub_table.customer_group_id = ?', $groupId)
            ->where('sub_table.grant_catalog_category_view = ?', '-2')
            ->orWhere(
                '(
                    sub_table.category_id IN (?)
                    AND
                    sub_table.customer_group_id IS NULL
                    AND
                    sub_table.grant_catalog_category_view = -2
                )', $exodedIds
            );

        $subQueryTwo = $connection->select()
            ->distinct(true)
            ->from(['main_table' => $connection->getTableName('magento_catalogpermissions')], 'category_id')
            ->where('main_table.category_id IN (?)', $subquery)
            ->where('main_table.grant_catalog_category_view = ?', '-1')
            ->where('main_table.customer_group_id = ?', $groupId);

        // Exclude categories from main query
        $mainQuery = $connection->select()
            ->distinct(true)
            ->from(['main_table' => $connection->getTableName('magento_catalogpermissions')], 'category_id')
            ->where('main_table.category_id IN (?)', $subquery)
            ->where('main_table.category_id NOT IN (?)', $subQueryTwo);

        $result = $connection->fetchAll($mainQuery);


        foreach ($result as $row) {
            $denyCategoryIds[] = $row['category_id'];
        }
        return $denyCategoryIds;
    }

    /**
     * Check If folder permission allowed
     *
     * @param  int $categoryId
     * @return boolean
     */
    public function isFolderPermissionAllowed($categoryId)
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $groupId = $this->getOrCreateCustomerSession()->getCustomer()->getGroupId();
        } else {
            $groupId = $this->customerSession->create()->getCustomer()->getGroupId();
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('magento_catalogpermissions');
        try {
            $select = $connection->select()->from(
                $tableName
            )->where('category_id = ?', $categoryId);

            $permission = $connection->fetchAll($select);
            $folderPermissionFixToggle = $this->getIsFolderPermissionFixToggle();
            if (count($permission)) {
                if ($folderPermissionFixToggle) {
                    if ($this->checkConditionsInPermission($permission, $groupId, '-1')) {
                        return true;
                    } else if ($this->checkConditionsInPermission($permission, $groupId, '-2')) {
                        return false;
                    } else if ($this->checkConditionsInPermission($permission, null, '-2')) {
                        return false;
                    } else if ($this->checkConditionsInPermission($permission, $groupId, '-2', 'neq')) {
                        return true;
                    }
                } else {
                    foreach ($permission as $value) {
                        if ($value['customer_group_id'] == $groupId && $value['grant_catalog_category_view'] == '-1') {
                            return true;
                        }
                    }
                }
            } else {
                $category = $this->categoryRepository->get($categoryId);
                $categoryId = match ($this->getIsSubFolderNotShowingToggleEnabled()) {
                    true => $this->getSharedCatalogIdByPath($category->getPath()),
                    false => $category->getParentId(),
                };
                $select = $connection->select()->from(
                    $tableName
                )->where('customer_group_id = ?', $groupId)
                    ->where('category_id = ?', $categoryId);
                $permission = $connection->fetchAll($select);
                if (count($permission) && $permission[0]['grant_catalog_category_view'] == '-1') {
                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with Folder permission: ' . $categoryId . 'is ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Check Conditions in Category Permission
     * @param  array $permission
     * @param  int $groupId
     * @param  string $view
     * @param  string $condition
     * @return boolean
     */
    public function checkConditionsInPermission($permission, $groupId, $view, $condition = 'eq') {
        foreach ($permission as $value) {
            if ($condition == 'eq') {
                if ($value['customer_group_id'] == $groupId && $value['grant_catalog_category_view'] == $view) {
                    return true;
                }
            } else {
                if ($value['customer_group_id'] != $groupId && $value['grant_catalog_category_view'] == $view) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the shared catalog ID from a given path string.
     *
     * @param  string $path
     * @return string|null
     */
    private function getSharedCatalogIdByPath(string $path): ?string
    {
        $categoryIds = explode('/', $path);

        return $categoryIds[2] ?? null;
    }

    /**
     * Get Subfolder Not Showing Toggle is Enabled
     *
     * @return boolean
     */
    public function getIsSubFolderNotShowingToggleEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::SUB_FOLDER_NOT_SHOWING_TOGGLE);
    }

    /**
     * Get Product Admin Refresh toggle
     *
     * @return boolean
     */
    public function isProductAdminRefreshToggle(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue("mazegeeks_d194278_admin_product_refresh");
    }

    /**
     * Get Parent Group id
     *
     * @param  int $groupId
     * @return int
     */
    public function getParentGroupId($groupId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('parent_customer_group');
        $parentGroupId = 0;
        try {
            $select = $connection->select()->from(
                $tableName,
                ['parent_group_id']
            )->where('customer_group_id = ?', $groupId);
            $parentGroupId = $connection->fetchOne($select);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with Getting Parent Group id: ' . $groupId . 'is ' . $e->getMessage());
        }
        return $parentGroupId;
    }

    /**
     * Get Parent Group id
     *
     * @param  int $groupId
     * @return array
     */
    public function getChildGroupIds($groupId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('parent_customer_group');
        $childGroups = [];
        try {
            $select = $connection->select()->from(
                $tableName,
                ['customer_group_id']
            )->where('parent_group_id = ?', $groupId);
            $groups = $connection->fetchAll($select);
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $childGroups[] = $group['customer_group_id'];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with Getting Child Group ids: ' . $groupId . 'is ' . $e->getMessage());
        }
        return $childGroups;
    }

    /**
     * D-165710
     * Inbranch multiple document
     */
    public function inBranchMultipleDocumentDefect()
    {
        return $this->toggleConfig->getToggleConfigValue('tigers_D_165710_inbranch_multiple_document');
    }

    /**
     * Chech mazegeeks_customer_catalog_updates toggle enable or not
     *
     * @return boolean
     */
    public function isNonStandaredCatalogToggleEnable()
    {
        return $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_NON_STANDARD_CATALOG);
    }

    /**
     * Insert Product Activity
     *
     * @param  int $productId
     * @return void
     */
    public function insertProductActivity($productId, $description = null, $productName = null)
    {
        try {
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customerSession = $this->getOrCreateCustomerSession();
            } else {
                $customerSession = $this->customerSession->create();
            }

            if ($customerSession->isLoggedIn()) {
                $userId = $customerSession->getCustomer()->getId();
                $userName = $customerSession->getCustomer()->getName();

                if ($productId) {
                    if (!$productName) {
                        $product = $this->productRepository->getById($productId);
                        $productName = $product->getName();
                    }

                    $activityData = [];
                    $activityData['user_id'] = $userId;
                    $activityData['product_id'] = $productId;
                    $activityData['user_name'] = $userName;
                    if ($this->isNonStandaredCatalogToggleEnable()) {
                        if ($description == 'CREATE') {
                            $activityData['activity_type'] = 1;
                        } elseif ($description == 'UPDATE') {
                            $activityData['activity_type'] = 2;
                        } elseif ($description == 'DELETE') {
                            $activityData['activity_type'] = 3;
                        } elseif ($description == 'RENEW') {
                            $activityData['activity_type'] = 4;
                        }
                    }
                    $activityData['description'] = $description;
                    $activityData['product_name'] = $productName;
                    $activityData['user_type'] = "1";
                    $this->productActivity->getResource()->save($this->productActivity->setData($activityData));
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .' Error in save product activity: ' . $e->getMessage());
        }
    }

    /**
     * catalog performance toggle
     */
    public function toggleEnbleForPerformance()
    {
        return $this->toggleConfig->getToggleConfigValue('techtitan_performance_improment');
    }
    /**
     * get current category
     */
    public function getCurrentCategory()
    {
        $currentCategory = null;
        if ($this->request->getFullActionName() == 'selfreg_ajax_productlistajax') {
            $categoryId = $this->request->getParam('id');
            if( $this->isD212350FixEnabled() ){
                if ($categoryId) {
                    try {
                        $currentCategory = $this->categoryRepository->get($categoryId);
                    } catch (NoSuchEntityException $e) {
                        $this->logger->error("Category not found", [$e]);
                    }
                }
            }
            else{
                $currentCategory = $this->categoryRepository->get($categoryId);
            }
        } else{
            if ($this->toggleEnbleForPerformance()) {
                $currentCategory =  $this->layerResolver->get()->getCurrentCategory();
            } else {
                $currentCategory = $this->registry->registry('current_category');
            }
        }
        return $currentCategory;
    }
    /**
     * Get customer session id
     *
     * @return boolean
     */
    public function getCustomerSessionId()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            return $this->getOrCreateCustomerSession()->getSessionId();
        }
        return $this->customerSession->create()->getSessionId();

    }

    /**
     * D-174502 Toggle For Default Sorting
     *
     * @return boolean
     */
    public function sortingToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(self::D_174502_CATALOG_DEFAULT_SORTING);
    }
    public function generateCategoryName($name,$currentCategoryId)
    {
        $alreadyexistingChildCategoriesName = [];
        $alreadyexistingChildCategoriesUrlKey = [];
        $count=1;
        $currentCategory= $this->categoryRepository->get($currentCategoryId);
        foreach($currentCategory->getChildrenCategories() as $category){
            $alreadyexistingChildCategoriesName[] = trim(strtolower($category->getName()));
            $alreadyexistingChildCategoriesUrlKey[] = $category->getUrlKey();
        }
        $newname = $name;
        while(in_array(trim(strtolower($newname)), $alreadyexistingChildCategoriesName) || in_array($currentCategory->formatUrlKey($newname), $alreadyexistingChildCategoriesUrlKey))
        {
            $newname = $name."(".$count++.")";
        }
        return $newname;
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     *
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     *
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->session->isLoggedIn()) {
            $this->session = $this->customerSession->create();
        }
        return $this->session;
    }

    /**
     * Get pending review status
     *
     * @return int
     */
    public function getCatalogPendingReviewStatus($productId)
    {
        $productData = $this->productRepository->getById($productId);
        return $productData->getIsPendingReview();
    }

    /**
     * D-183583 Get Folder Permission Fix Toggle
     * @return boolean
     */
    public function getIsFolderPermissionFixToggle(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::FOLDER_PERMISSION_FIX_TOGGLE);
    }

    /**
     *Is shared catalog page for commercial page
     * @return bool
     */
    public function isSharedCatalogPage()
    {
        $isSharedCatalogPage = false;
        if ($this->isMvpSharedCatalogEnable() && !$this->checkPrintCategory() && !$this->sdeHelper->getIsSdeStore())
        {
            $isSharedCatalogPage = true;
        }
        return $isSharedCatalogPage;
    }

    /**
     *Get Request Params
     *
     */
    public function getCatalogRequestParams()
    {
        return $this->request->getParams();
    }

    /**
     * Get Toggle Value epro Custom doc for migrated Document Toggle
     *
     * @return boolean
     */
    public function getEproMigratedCustomDocToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::MILLIONAIRES_EPRO_MIGRATED_CUSTOM_DOC);
    }

    /**
     * Toggle for B-2193925 Product updated at toggle
     * @return bool
     */
    public function getToggleStatusForNewProductUpdatedAtToggle()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_B2193925_PRODUCT_UPDATED_AT);
    }

    /**
     * Get Toggle Value merged shared catalog files Toggle
     *
     * @return boolean
     */
    public function getMergedSharedCatalogFilesToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::MAZEGEEKS_MERGED_SHARED_CATALOG_FILES);
    }

    /**
     * Check E443304_stop_redirect_mvp_addtocart toggle enable or not
     *
     * @return boolean
     */
    public function isEnableStopRedirectMvpAddToCart()
    {
        return $this->toggleConfig->getToggleConfigValue('E443304_stop_redirect_mvp_addtocart');
    }

    /**
     * Check maze_geeks_catalog_mvp_breakpoints_and_ada toggle enable or not
     *
     * @return boolean
     */
    public function getCatalogBreakpointToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('maze_geeks_catalog_mvp_breakpoints_and_ada');
    }

    /**
     * Get User Groups for Company
     *
     * @return array
     */
    public function getUserGroupsforCompany(): array
    {
        return $this->customerGroupPermissionManager->getCustomerGroupsList();
    }

    /**
     * Get category permission
     *
     * @param string $categoryId
     * @param array $companyUserGroups
     *
     * @return bool
     */
    public function getCategoryPermission(string $categoryId, array $companyUserGroups): bool
    {
        return $this->customerGroupPermissionManager->doesDenyAllPermissionExist($categoryId, $companyUserGroups);
    }

    /**
     * Check if folder is restricted for current user
     *
     * @param string $categoryId
     * @param array $companyUserGroups
     *
     * @return bool
     */
    public function isFolderRestrictedToUser(string $categoryId, array $companyUserGroups): bool
    {
        $isRestricted = true;
        $allowedGroups = $this->customerGroupPermissionManager->getAllowedGroups($categoryId, $companyUserGroups);
        if ($allowedGroups) {
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $groupId = $this->getOrCreateCustomerSession()->getCustomer()->getGroupId();
            } else {
                $groupId = $this->customerSession->create()->getCustomer()->getGroupId();
            }
            if (in_array($groupId, $allowedGroups)) {
                $isRestricted = false;
            }
        }

        return $isRestricted;
    }
    /**
     * Check tech_titan_past_expiration_doc_date_fix toggle enable or not
     *
     * @return boolean
     */
    public function toggleD202288()
    {
        return $this->toggleConfig->getToggleConfigValue('tech_titan_past_expiration_doc_date_fix');
    }
    //@codeCoverageIgnoreStart
    /**
     * Get move modal categories
     *
     */
    public function getCategoryLevelone ($childSubcategory){
        $html1 = '';
        if ($childSubcategory->hasChildren()) {
            $childCategoryObj = $this->categoryRepository->get($childSubcategory->getId());
            $childSubcategories = $childCategoryObj->getChildrenCategories();
            foreach ($childSubcategories as $childSubcategory_level1) {
                $childofprintproduct_level1 = $childSubcategory_level1->getName();
                $level2 = $childSubcategory_level1->getLevel() - 3;
                $html1 .= '<ul class = "all-level-category-tree category-tree-level-'.$level2.'">
                <li class="mvp-catalog-move-popup-category-l-'.$level2.' mvp-move-popup-cat-levels" style="display: none;">
                <div class = "sub-category-container-level-all sub-category-container-level-'.$level2.' sub-cat-div" id="move-'.$childSubcategory_level1->getId().'">';
                if ($childSubcategory_level1->hasChildren()) {
                    $html1 .= '<div class = "toggle-icon-level-all toggle-icon-level-'.$level2.'" id="toggle-'.$childSubcategory_level1->getId().'">
                    <div class="disclosere-icon-closed level-all level-'.$level2.' display"></div>
                    <div class="disclosere-icon-open level-all level-'.$level2.'" style="display: none;"></div>
                    </div>';
                } else {
                $html1 .= '<div class = "toggle-icon-level-all toggle-icon-level-'.$level2.'" id="toggle-'.$childSubcategory_level1->getId().'">
                            <div class="disclosere-icon-closed level-all level-'.$level2.' display"
                                style="visibility: hidden;"></div>
                            <div class="disclosere-icon-open level-all level-'.$level2.'" style="display: none;"></div>
                        </div>';
                }
                $html1 .= '<div class="folder-img-icon"></div>
                    <span class = "category-name-level-'.$level2.' category-name-level-all sub-cat-div-name">'
                        .$childofprintproduct_level1. '</span>
                </div>';
                $html1 = $this->getCategoryLeveltwo($childSubcategory_level1,$html1);
                $html1 .='</li>
                </ul>';
            }
        }

        return $html1;
    }

    /**
     * Get move modal categories
     *
     */
    public function getCategoryLeveltwo ($childSubcategory,$html1){
        if ($childSubcategory->hasChildren()) {
            $childCategoryObj = $this->categoryRepository->get($childSubcategory->getId());
            $childSubcategories = $childCategoryObj->getChildrenCategories();
            foreach ($childSubcategories as $childSubcategory_level1) {
                    $childofprintproduct_level1 = $childSubcategory_level1->getName();
                    $level2 = $childSubcategory_level1->getLevel() - 3;
                    $html1 .= '<ul class = "all-level-category-tree category-tree-level-'.$level2.'">
                    <li class="mvp-catalog-move-popup-category-l-'.$level2.' mvp-move-popup-cat-levels" style="display: none;">
                    <div class = "sub-category-container-level-all sub-category-container-level-'.$level2.' sub-cat-div" id="move-'.$childSubcategory_level1->getId().'">';
                    if ($childSubcategory_level1->hasChildren()) {
                        $html1 .= '<div class = "toggle-icon-level-all toggle-icon-level-'.$level2.'" id="toggle-'.$childSubcategory_level1->getId().'">
                        <div class="disclosere-icon-closed level-all level-'.$level2.' display"></div>
                        <div class="disclosere-icon-open level-all level-'.$level2.'" style="display: none;"></div>
                        </div>';
                    } else {
                    $html1 .= '<div class = "toggle-icon-level-all toggle-icon-level-'.$level2.'" id="toggle-'.$childSubcategory_level1->getId().'">
                                <div class="disclosere-icon-closed level-all level-'.$level2.' display"
                                    style="visibility: hidden;"></div>
                                <div class="disclosere-icon-open level-all level-'.$level2.'" style="display: none;"></div>
                            </div>';
                    }
                    $html1 .= '<div class="folder-img-icon"></div>
                        <span class = "category-name-level-'.$level2.' category-name-level-all sub-cat-div-name">'
                            .$childofprintproduct_level1. '</span>
                    </div>';
                    $html1 = $this->getCategoryLeveltwo($childSubcategory_level1,$html1);
                    $html1 .='</li>
                    </ul>';
                }
            }

        return $html1;
    }

    /**
     * Check If D-213910 Selfreg_Unpublished catalog folders are reflecting for normal users
     */
    public function isToggleD213910Enabled(): bool|int
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D213910);
    }
    //@codeCoverageIgnoreEnd

    /**
     * Check if remove preview API calls from Catalog toggle is enabled
     *
     * @return bool
     */
    public function isB2421984Enabled() {
        return (bool) $this->toggleConfig->getToggleConfigValue('tech_titans_b_2421984_remove_preview_calls_from_catalog_flow');
    }

    /**
     * @return bool
     */
    public function isD216406Enabled(){
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D_216406_DUPLICATE_SHARED_CATALOG_ID);
    }

}
