<?php
/**
 * @category  Fedex
 * @package   Fedex_CatalogMvp
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\Block\Product;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Rule\Model\Condition\Combine;
use Magento\CatalogWidget\Model\Rule\Condition\Combine as WidgetCombineRule;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Widget\Helper\Conditions;
use Magento\CatalogWidget\Model\Rule;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class ProductsListPlugin extends AbstractProduct
{
    public const TIGER_D_211698 = 'tiger_d_211698';
    protected ProductsList $subject;

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param CategoryRepositoryInterface $categoryRepository
     * @param SqlBuilder $sqlBuilder
     * @param Conditions $conditionsHelper
     * @param Rule $rule
     * @param CatalogMvp $catalogMvpHelper
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        protected Context                     $context,
        protected CollectionFactory           $productCollectionFactory,
        protected Visibility                  $catalogProductVisibility,
        protected CategoryRepositoryInterface $categoryRepository,
        protected SqlBuilder                  $sqlBuilder,
        protected Conditions                  $conditionsHelper,
        protected Rule                        $rule,
        protected CatalogMvp                  $catalogMvpHelper,
        protected ToggleConfig                $toggleConfig,
        array                                 $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * If catalog performance is enabled and price index is disabled product widgets
     * queries should not retrieve prices from price index for filtering or sorting.
     * @param ProductsList $subject
     * @param callable $proceed
     * @return ProductCollection
     * @throws LocalizedException
     */
    public function aroundCreateCollection(ProductsList $subject, callable $proceed): ProductCollection
    {
        $this->subject = $subject;
        $collection = $this->productCollectionFactory->create();
        if ($this->getData('store_id') !== null) {
            $collection->setStoreId($this->getData('store_id'));
        }
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        $pageSize = $subject->showPager() ? $subject->getProductsPerPage() : $subject->getProductsCount();
        if($this->toggleConfig->getToggleConfigValue(self::TIGER_D_211698)){
            $collection = $collection->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
                ->addUrlRewrite()
                ->addStoreFilter()
                ->addAttributeToSort('entity_id', 'desc')
                ->setPageSize($pageSize)
                ->setCurPage((int)$this->getRequest()->getParam($this->getData('page_var_name'), 1));
        }else{
            $collection = $collection->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
                ->addUrlRewrite()
                ->addStoreFilter()
                ->addAttributeToSort('entity_id', 'desc')
                ->setPageSize($pageSize)
                ->setCurPage($this->getRequest()->getParam($this->getData('page_var_name'), 1));
        }

        $conditions = $this->getConditions();
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        $collection->distinct(true);
        return $collection;
    }

    /**
     * @param array $condition
     * @return array
     */
    private function updateAnchorCategoryConditions(array $condition): array
    {
        if (array_key_exists('value', $condition)) {
            $categoryId = $condition['value'];

            try {
                $category = $this->categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
            } catch (NoSuchEntityException $e) {
                return $condition;
            }

            $children = $category->getIsAnchor() ? $category->getChildren(true) : [];
            if ($children) {
                $children = explode(',', $children);
                $condition['operator'] = "()";
                $condition['value'] = array_merge([$categoryId], $children);
            }
        }

        return $condition;
    }

    /**
     * @return Combine|WidgetCombineRule
     */
    protected function getConditions(): Combine|WidgetCombineRule
    {
        $conditions = $this->subject->getData('conditions_encoded')
            ? $this->subject->getData('conditions_encoded')
            : $this->subject->getData('conditions');

        if ($conditions) {
            $conditions = $this->conditionsHelper->decode($conditions);
        }
        foreach ($conditions as $key => $condition) {
            if (!empty($condition['attribute'])) {
                if (in_array($condition['attribute'], ['special_from_date', 'special_to_date'])) {
                    $conditions[$key]['value'] = date('Y-m-d H:i:s', strtotime($condition['value']));
                }

                if ($condition['attribute'] == 'category_ids') {
                    $conditions[$key] = $this->updateAnchorCategoryConditions($condition);
                }
            }
        }
        $this->rule->loadPost(['conditions' => $conditions]);
        return $this->rule->getConditions();
    }

}
