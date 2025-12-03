<?php
/**
 * @category  Fedex
 * @package   Fedex_SharedCatalogCustomization
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Model\Source;

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Fedex\SharedCatalogCustomization\Ui\Component\Form\Field\AdvancedSearchSharedCatalog;
use Fedex\Company\Api\Data\ConfigInterface;
use Psr\Log\LoggerInterface;

class SharedCatalogs implements OptionSourceInterface
{
    /** @var string  */
    private const ONDEMAND_CATEGORY_CODE = 'ondemand';

    /**
     * @param CategoryListInterface $categoryList
     * @param SortOrderBuilder $sortOrderBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param RequestInterface $request
     * @param ConfigInterface $companyConfigInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CategoryListInterface $categoryList,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly GroupCollectionFactory $groupCollectionFactory,
        private readonly RequestInterface $request,
        private readonly ConfigInterface $companyConfigInterface,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $categoriesOptions = [];
        if ($this->isNewCompany()) {
            $categoriesOptions[] = [
                'label' => AdvancedSearchSharedCatalog::CREATE_NEW_LABEL,
                'value' => AdvancedSearchSharedCatalog::CREATE_NEW_VALUE,
            ];
        }

        $rootCategoryId = $this->getOnDemandRootCategoryId();
        $sortOrder = $this->sortOrderBuilder
            ->setField(CategoryInterface::KEY_NAME)
            ->setAscendingDirection()
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_id', $rootCategoryId)
            ->addSortOrder($sortOrder)
            ->create();
        $categories = $this->categoryList->getList($searchCriteria);
        foreach ($categories->getItems() as $category) {
            $categoriesOptions[] = [
                'label' => $category->getName(),
                'value' => $category->getId(),
            ];
        }

        return $categoriesOptions;
    }

    /**
     * @return int
     */
    private function getOnDemandRootCategoryId(): int
    {
        try {
            $collection = $this->groupCollectionFactory->create();
            $collection->addFieldToFilter('code', self::ONDEMAND_CATEGORY_CODE);
            $group = $collection->getFirstItem();
            if ($group && $group->getId()) {
                return (int) $group->getRootCategoryId();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $this->logger->critical('Ondemand root category code not found');
        return 0;
    }

    /**
     * @return bool
     */
    public function isNewCompany(): bool
    {
        return !$this->request->getParam('id');
    }
}
