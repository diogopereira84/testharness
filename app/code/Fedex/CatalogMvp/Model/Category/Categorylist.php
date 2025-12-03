<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Model\Category;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

class Categorylist implements OptionSourceInterface
{
    public const TECH_TITANS_E_475721 = 'tech_titans_E_475721';
    /**
     * Categorylist constructor.
     *
     * @param CollectionFactory $categoryCollectionFactory
     * @param LoggerInterface   $logger
     * @param ToggleConfig      $toggleConfig
     */
    public function __construct(
        protected CollectionFactory $categoryCollectionFactory,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Return option array for UI component (product grid filter).
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        if (!$this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_E_475721)) {
            return $options;
        }

        try {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSort('name', 'ASC')
                ->addFieldToFilter('entity_id', ['neq' => 2]); // skip Root Catalog

            $categories = [];
            $childrenMap = []; // parent_id => [childIds]

            foreach ($collection as $category) {
                $id = (int)$category->getId();
                $parentId = (int) $category->getParentId();

                $categories[$id] = $category;
                $childrenMap[$parentId][] = $id; // parent => [child1, child2]
            }

            foreach ($categories as $id => $category) {
                $parentId = (int) $category->getParentId();
                if (!isset($categories[$parentId])) { // if category has no parent
                    $options[] = $this->prepareCategoryHierarchy($categories, $childrenMap, $id);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error in Categorylist toOptionArray: ' . $e->getMessage()
            );
        }

        return $options;
    }

    /**
     * Recursive category tree using prebuilt children map.
     *
     * @param array $categories
     * @param array $childrenMap
     * @param int   $parentId
     * @return array
     */
    protected function prepareCategoryHierarchy(array $categories, array $childrenMap, int $parentId): array
    {
        if (!isset($categories[$parentId])) {
            return [];
        }

        $parent = $categories[$parentId];
        $option = [
            'label' => $parent->getName(),
            'value' => $parent->getId(),
        ];

        if (!empty($childrenMap[$parentId])) {
            $option['optgroup'] = [];
            foreach ($childrenMap[$parentId] as $childId) {
                $option['optgroup'][] = $this->prepareCategoryHierarchy($categories, $childrenMap, $childId);
            }
        }

        return $option;
    }
}
