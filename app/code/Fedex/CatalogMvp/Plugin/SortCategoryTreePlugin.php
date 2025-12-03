<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Magento\Catalog\Block\Adminhtml\Category\Tree;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SortCategoryTreePlugin
{
    private const B2B_ROOT_CATEGORY = 'b2b-root-category';

    public function __construct(
        private LoggerInterface $logger,
        private CategoryRepositoryInterface $categoryRepository,
        private Json $jsonSerializer,
        private readonly ToggleConfig $toggleConfig,
    ) {
    }

    /**
     * Sorts a specific (B2B Root Category) category's children by URL key match.
     */
    public function afterGetTreeJson(Tree $subject, string $result): string
    {
        if (
            !$this->toggleConfig->getToggleConfigValue(
                'techtitans_D_241651_b2b_categories_alphabetical_sort'
            )
        ) {
            return $result;
        }

        try {
            $tree = $this->jsonSerializer->unserialize($result);
            if (is_array($tree)) { 
                $found = false;
                $sortedTree = $this->sortCategoryByUrlKey($tree, self::B2B_ROOT_CATEGORY, $found);

                return $this->jsonSerializer->serialize($sortedTree);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Fedex: Error in afterGetTreeJson(): ' . $e->getMessage()
            );
        }
        return $result;
    }

    /**
     * Locate category by URL key → sort its children.
     * Traversal stops as soon as the target category is found.
     *
     * @param array $categories
     * @param string $targetUrlKey
     * @param bool $found Flag passed by reference to short-circuit recursion
     * @return array
     */
    private function sortCategoryByUrlKey(array $categories, string $targetUrlKey, bool &$found): array
    {
        foreach ($categories as &$category) {
            $id = $category['id'] ?? null;

            if ($id) {
                $urlKey = $this->getUrlKeyById((int) $id);

                if ($urlKey === $targetUrlKey) {
                    if (!empty($category['children']) && is_array($category['children'])) {
                        $category['children'] = $this->sortCategories($category['children']);
                    }

                    $found = true;
                    return $categories; // stop further traversal once found
                }
            }

            // Recursively scan children
            if (!empty($category['children']) && is_array($category['children'])) {
                $category['children'] = $this->sortCategoryByUrlKey(
                    $category['children'],
                    $targetUrlKey,
                    $found
                );

                if ($found) {
                    return $categories;
                }
            }
        }

        return $categories;
    }

    /**
     * Get url_key for a category ID.
     */
    private function getUrlKeyById(int $categoryId): ?string
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            return (string) $category->getUrlKey();
        } catch (\Exception $e) {
            $this->logger->error(
                'Fedex: Error loading category ' . $categoryId . ': ' . $e->getMessage()
            );
            return null;
        }
    }

    /**
     * Recursively alphabetically sorts categories by label (`text` key).
     */
    private function sortCategories(array $categories): array
    {
        usort(
            $categories,
            static function (array $a, array $b): int {
                return strcmp(
                    strtolower($a['text'] ?? ''),
                    strtolower($b['text'] ?? '')
                );
            }
        );

        foreach ($categories as &$category) {
            if (!empty($category['children']) && is_array($category['children'])) {
                $category['children'] = $this->sortCategories($category['children']);
            }
        }

        return $categories;
    }
}
