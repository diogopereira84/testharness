<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Model\Config\Source;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\GroupFactory;

class CategoryOptions implements \Magento\Framework\Option\ArrayInterface

{
    private array $options = [];

    public function __construct(
        private GroupFactory $groupFactory,
        private CategoryFactory $categoryFactory
    )
    {
    }
    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        if (empty($this->options)) {
            $group = $this->groupFactory->create();
            $group = $group->load('ondemand', 'code');
            $rootCategoryId = $group->getRootCategoryId();
            $category = $this->categoryFactory->create()->load($rootCategoryId);
            $childrenCategories = $category->getChildrenCategories();
            foreach ($childrenCategories as $childrenCategory) {

                $this->options[] = [
                    'value' => $childrenCategory->getId(), 'label' => $childrenCategory->getName(),
                ];
            }
            array_unshift($this->options, ['value' => '0', 'label' => __('Select print product category')]);
        }
        return $this->options;
    }
}
