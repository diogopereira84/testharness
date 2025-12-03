<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\Source;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Config category option source
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class CategoryOption implements OptionSourceInterface
{

    /**
     * CategoryOption Constructor.
     *
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        protected CategoryFactory $categoryFactory
    )
    {
    }

    /**
     * Get category options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->categoryFactory->create()
        ->getCollection()
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('name', ['like' => '%Browse Catalog%'])
        ->setOrder('name', 'ASC');

        $options = [];
        $options[] = [
            'label' => 'Select a category...',
            'value' => ''
        ];

        if (!empty($collection)) {
            foreach ($collection as $key => $categoryData) {
                $options[] = [
                    'label' => $categoryData->getName(),
                    'value' => $categoryData->getId()
                ];
            }
        }

        return $options;
    }
}
