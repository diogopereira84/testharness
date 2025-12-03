<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Model\Source;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\GroupFactory;
use Magento\Catalog\Model\CategoryRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Store\Model\StoreRepository;

class CategoryOptions implements \Magento\Framework\Option\ArrayInterface

{
    private array $options = [];
    /**
     * Constructor
     *
     * @param GroupFactory $groupFactory
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        readonly private GroupFactory $groupFactory,
        readonly private CategoryFactory $categoryFactory,
        protected CategoryRepository $categoryRepository,
        protected ToggleConfig $toggleConfig,
        protected StoreRepository $storeRepository
    )
    {
    }
    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        if (empty($this->options)) {
            $rootCategoryId= $this->storeRepository->get('ondemand')->getRootCategoryId();
            $category = $this->categoryRepository->get($rootCategoryId);
            $childrenCategories = $category->getChildrenCategories();
            foreach ($childrenCategories as $childrenCategory) {
                if (($childrenCategory->getName() === 'Print Products' ) &&
                    $childrenCategory->hasChildren() ) {
                    $printProductCategories=$this->categoryRepository->get($childrenCategory->getId());
                    $printProductChildrenCategories = $printProductCategories->getChildrenCategories();
                    foreach ($printProductChildrenCategories as $printProductCategory ) {
                        $this->options[] = [
                            'value' => $printProductCategory->getId(), 'label' => $printProductCategory->getName(),
                        ];
                    }
                }
            }
            array_unshift($this->options, ['value' => '0', 'label' => __('Select print product category')]);
        }
        return $this->options;
    }
}
