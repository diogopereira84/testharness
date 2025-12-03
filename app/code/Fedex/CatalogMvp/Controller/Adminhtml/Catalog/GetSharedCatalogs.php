<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as SharedCatalogCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\GroupFactory;
use Fedex\AccountValidation\Model\AccountValidation;
use Magento\Framework\Controller\Result\Json;

/**
 * Controller for fetching shared catalogs, categories, and creating new categories.
 */
class GetSharedCatalogs extends Action
{
    /**
     * Constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SharedCatalogCollectionFactory $sharedCatalogCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param LoggerInterface $logger
     * @param CompanyFactory $companyFactory
     * @param CategoryFactory $categoryFactory
     * @param GroupFactory $groupFactory
     * @param AccountValidation $accountValidation
     */
    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly SharedCatalogCollectionFactory $sharedCatalogCollectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly LoggerInterface $logger,
        private readonly CompanyFactory $companyFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly GroupFactory $groupFactory,
        private readonly AccountValidation $accountValidation
    ) {
        parent::__construct($context);
    }

    /**
     * Execute method to handle AJAX requests.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $type = (string) $this->getRequest()->getParam('type');

        try {
            return match ($type) {
                'catalogs' => $result->setData([
                    'success'  => true,
                    'catalogs' => $this->getSharedCatalogs(),
                ]),
                'discountNumber' => $result->setData([
                    'success'          => true,
                    'discount_number'  => $this->getDiscountNumberBasedOnSharedCatalogId(
                        (int) $this->getRequest()->getParam('shared_catalog_id')
                    ),
                ]),
                'categories' => $result->setData([
                    'success'               => true,
                    'categories'             => $this->getCategoriesBySharedCatalog(
                        (int) $this->getRequest()->getParam('shared_catalog_id')
                    ),
                    'default_root_category' => $this->getDefaultRootCategory(),
                ]),
                'create_categories' => $this->createNewCategory(
                    $result,
                    (string) $this->getRequest()->getParam('name'),
                    (int) $this->getRequest()->getParam('parent_id')
                ),
                default => $result->setData([
                    'success' => false,
                    'error'   => __('Invalid type parameter.'),
                ]),
            };
        } catch (\Throwable $e) {
            $this->logger->error('Error in GetSharedCatalogs: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch all shared catalogs.
     *
     * @return array
     */
    protected function getSharedCatalogs(): array
    {
        try {
            $collection = $this->sharedCatalogCollectionFactory->create()
                ->setOrder('name', 'ASC');

            $catalogs = [];

            foreach ($collection as $catalog) {
                $catalogs[] = [
                    'id'   => (int) $catalog->getId(),
                    'name' => (string) $catalog->getName(),
                ];
            }

            return $catalogs;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Error fetching shared catalogs: %s',
                $e->getMessage()
            ));
            return [];
        }
    }

    /**
     * Fetch categories based on shared catalog ID.
     *
     * @param int $sharedCatalogId
     * @return array
     */
    protected function getCategoriesBySharedCatalog(int $sharedCatalogId): array
    {
        if ($sharedCatalogId <= 0) {
            return [];
        }

        try {
            $sharedCatalog = $this->sharedCatalogCollectionFactory->create()
                ->addFieldToFilter('entity_id', $sharedCatalogId)
                ->getFirstItem();

            if (!$sharedCatalog->getId()) {
                return [];
            }

            $customerGroupId = (int) $sharedCatalog->getCustomerGroupId();
            $company = $this->companyFactory->create()->getCollection()
                ->addFieldToFilter('customer_group_id', $customerGroupId)
                ->getFirstItem();

            if (!$company->getId()) {
                return [];
            }

            $companySharedCatalogId = (int) $company->getSharedCatalogId();
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect(['name', 'parent_id', 'path']);

            $categories = [];
            foreach ($categoryCollection as $category) {
                $pathIds = explode('/', (string) $category->getPath());
                if (isset($pathIds[2]) && (int) $pathIds[2] === $companySharedCatalogId) {
                    $categories[] = $this->buildCategoryTree($category, $categoryCollection);
                    break;
                }
            }

            return $categories;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Error fetching categories for Shared Catalog ID %d: %s',
                $sharedCatalogId,
                $e->getMessage()
            ));
            return [];
        }
    }

    /**
     * Recursively build category tree.
     *
     * @param \Magento\Catalog\Model\Category $parent
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $collection
     * @return array
     */
    protected function buildCategoryTree($parent, $collection): array
    {
        $children = [];
        foreach ($collection as $category) {
            if ((int) $category->getParentId() === (int) $parent->getId()) {
                $children[] = $this->buildCategoryTree($category, $collection);
            }
        }

        return [
            'value'    => (int) $parent->getId(),
            'label'    => (string) $parent->getName(),
            'optgroup' => $children,
        ];
    }

    /**
     * Fetch the default root category for the 'ondemand' store group.
     *
     * @return array
     */
    protected function getDefaultRootCategory(): array
    {
        $group = $this->groupFactory->create()->load('ondemand', 'code');
        $rootCategoryId = $group->getRootCategoryId();
        $rootCategory = $this->categoryFactory->create()->load($rootCategoryId);

        return [
            'value' => $rootCategory->getId(),
            'label' => $rootCategory->getName(),
        ];
    }

    /**
     * Create a new category.
     *
     * @param Json $result
     * @param string $name
     * @param int|null $parentId
     * @return Json
     */
    public function createNewCategory(Json $result, string $name, ?int $parentId): Json
    {
        try {
            $defaultRootCategory = $this->getDefaultRootCategory();
            $parentCategoryId = $parentId ?: (int) $defaultRootCategory['value']; // Default B2B root category
            $parentCategory = $this->categoryFactory->create()->load($parentCategoryId);

            $existingCategory = $this->categoryFactory->create()->getCollection()
                ->addAttributeToFilter('name', $name)
                ->addAttributeToFilter('parent_id', $parentCategoryId)
                ->getFirstItem();

            if (!$existingCategory->getId()) {
                $category = $this->categoryFactory->create();
                $category->setName($name)
                    ->setParentId($parentCategoryId)
                    ->setPath($parentCategory->getPath())
                    ->setIsActive(true)
                    ->setIncludeInMenu(true)
                    ->setAttributeSetId($category->getDefaultAttributeSetId())
                    ->save();

                $existingCategory = $category;
            }

            return $result->setData([
                'success'     => true,
                'folder_id'   => (int) $existingCategory->getId(),
                'folder_name' => (string) $existingCategory->getName(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CreateFolder error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage() ?: __('Unknown error occurred'),
            ]);
        }
    }

    /**
     * Return discount_account_number or fallback FedEx account number.
     *
     * If both discount_account_number and fedex_account_number are not available
     * for the company associated with the shared catalog, this method returns null.
     *
     * @param int $sharedCatalogId
     * @return string|null
     */
    protected function getDiscountNumberBasedOnSharedCatalogId(int $sharedCatalogId): ?string
    {
        if ($sharedCatalogId <= 0) {
            return null;
        }

        try {
            $sharedCatalog = $this->sharedCatalogCollectionFactory->create()
                ->addFieldToFilter('entity_id', $sharedCatalogId)
                ->setPageSize(1)
                ->getFirstItem();

            if (!$sharedCatalog || !$sharedCatalog->getId()) {
                return null;
            }

            $customerGroupId = (int) $sharedCatalog->getCustomerGroupId();
            if ($customerGroupId <= 0) {
                return null;
            }

            $company = $this->companyFactory->create()->getCollection()
                ->addFieldToFilter('customer_group_id', $customerGroupId)
                ->setPageSize(1)
                ->getFirstItem();

            if (!$company || !$company->getId()) {
                return null;
            }

            $fedexAccount = $company->getData('fedex_account_number');
            if ($fedexAccount) {
                return $fedexAccount;
            }

            $discountAccount = $company->getData('discount_account_number');
            if ($discountAccount) {
                return $discountAccount;
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'getDiscountNumberBasedOnSharedCatalogId error for sharedCatalogId %d: %s',
                $sharedCatalogId,
                $e->getMessage()
            ));
            return null;
        }
    }
}
