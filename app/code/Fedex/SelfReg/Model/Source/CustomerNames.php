<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Model\Source;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class CustomerNames for Model Source
 */
class CustomerNames implements OptionSourceInterface
{
    /**
     * Maximum allowed page size to prevent performance issues
     */
    private const MAX_PAGE_SIZE = 500;

    /**
     * Minimum allowed page size
     */
    private const MIN_PAGE_SIZE = 1;

    /**
     * @var array Cache for customer options
     */
    private array $customerLoad = [];

    /**
     * @var int Default page size for pagination
     */
    private int $pageSize = 50;

    /**
     * @param CollectionFactory $customerCollectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $customerCollectionFactory
    ) {
    }

    /**
     * Normalize page parameter to ensure it's a positive integer
     *
     * @param int $page
     * @return int
     */
    private function normalizePage(int $page): int
    {
        return max(1, $page);
    }

    /**
     * Normalize page size parameter to ensure it's within valid bounds
     *
     * @param int $pageSize
     * @return int
     */
    private function normalizePageSize(int $pageSize): int
    {
        return max(self::MIN_PAGE_SIZE, min(self::MAX_PAGE_SIZE, $pageSize));
    }

    /**
     * Add search conditions to customer collection
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $collection
     * @param string $searchTerm
     * @return void
     */
    private function addSearchConditions($collection, string $searchTerm): void
    {
        if (empty($searchTerm)) {
            return;
        }

        $searchTerm = trim($searchTerm);
        
        // Check if search term contains space (likely searching for full name)
        if (strpos($searchTerm, ' ') !== false) {
            // Split search term for firstname/lastname search
            $nameParts = explode(' ', $searchTerm, 2);
            $firstName = trim($nameParts[0]);
            $lastName = trim($nameParts[1] ?? '');
            
            if (!empty($firstName) && !empty($lastName)) {
                // Search for exact firstname and lastname combination
                $collection->addFieldToFilter([
                    ['firstname', 'lastname'],
                    ['firstname', 'lastname']
                ], [
                    [
                        ['like' => '%' . $firstName . '%'],
                        ['like' => '%' . $lastName . '%']
                    ],
                    [
                        ['like' => '%' . $lastName . '%'],  // Allow reversed name order
                        ['like' => '%' . $firstName . '%']
                    ]
                ]);
            } else {
                // Only one part is meaningful, search in both columns
                $searchValue = '%' . (!empty($firstName) ? $firstName : $lastName) . '%';
                $collection->addFieldToFilter([
                    'firstname',
                    'lastname'
                ], [
                    ['like' => $searchValue],
                    ['like' => $searchValue]
                ]);
            }
        } else {
            // Single term - search in both firstname and lastname columns
            $searchValue = '%' . $searchTerm . '%';
            $collection->addFieldToFilter([
                'firstname',
                'lastname'
            ], [
                ['like' => $searchValue],
                ['like' => $searchValue]
            ]);
        }
    }

    /**
     * Get base customer collection with required filters
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    private function getBaseCollection()
    {
        $collection = $this->customerCollectionFactory->create();
        
        // Add only required attributes for performance
        $collection->addAttributeToSelect(['firstname', 'lastname'])
                   ->addFieldToFilter('firstname', ['notnull' => true])
                   ->addFieldToFilter('lastname', ['notnull' => true])
                   ->addFieldToFilter('firstname', ['neq' => ''])
                   ->addFieldToFilter('lastname', ['neq' => '']);
        
        return $collection;
    }

    /**
     * Get options with pagination
     *
     * @param int $page
     * @param int $pageSize
     * @param string $searchTerm
     * @return array
     */
    public function getCustomersByPage(int $page = 1, int $pageSize = 50, string $searchTerm = ''): array
    {
        // Normalize parameters to prevent edge cases
        $page = $this->normalizePage($page);
        $pageSize = $this->normalizePageSize($pageSize);

        $collection = $this->getBaseCollection();
        
        // Add search functionality
        $this->addSearchConditions($collection, $searchTerm);
        
        // Add pagination
        $collection->setPageSize($pageSize)
                   ->setCurPage($page);
        
        // Add ordering for consistency
        $collection->addOrder('firstname', 'ASC')
                   ->addOrder('lastname', 'ASC');
        
        $result = [];
        foreach ($collection as $customer) {
            $result[] = [
                'label' => trim($customer->getFirstname() . ' ' . $customer->getLastname()),
                'value' => (int) $customer->getId(),
            ];
        }
        
        return $result;
    }

    /**
     * Get total customer count
     *
     * @param string $searchTerm
     * @return int
     */
    public function getTotalCount(string $searchTerm = ''): int
    {
        $collection = $this->getBaseCollection();
        
        // Add search functionality
        $this->addSearchConditions($collection, $searchTerm);
        
        return $collection->getSize();
    }

    /**
     * Get options (for backward compatibility)
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if (empty($this->customerLoad)) {
            $this->customerLoad = $this->getCustomersByPage(1, $this->pageSize);
        }
        
        return $this->customerLoad;
    }

    /**
     * Get paginated response with metadata
     *
     * @param int $page
     * @param int $pageSize
     * @param string $searchTerm
     * @return array
     */
    public function getPaginatedCustomers(int $page = 1, int $pageSize = 50, string $searchTerm = ''): array
    {
        // Normalize parameters to prevent division by zero and other edge cases
        $normalizedPage = $this->normalizePage($page);
        $normalizedPageSize = $this->normalizePageSize($pageSize);

        $customers = $this->getCustomersByPage($normalizedPage, $normalizedPageSize, $searchTerm);
        $totalCount = $this->getTotalCount($searchTerm);
        
        // Safe division - normalizedPageSize is guaranteed to be >= 1
        $totalPages = (int) ceil($totalCount / $normalizedPageSize);
        
        return [
            'customers' => $customers,
            'pagination' => [
                'current_page' => $normalizedPage,
                'page_size' => $normalizedPageSize,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'has_next' => $normalizedPage < $totalPages,
                'has_previous' => $normalizedPage > 1
            ]
        ];
    }

    /**
     * Clear the internal cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->customerLoad = [];
    }

    /**
     * Check if cache is loaded
     *
     * @return bool
     */
    public function isCacheLoaded(): bool
    {
        return !empty($this->customerLoad);
    }

    /**
     * Get cached customer count
     *
     * @return int
     */
    public function getCachedCount(): int
    {
        return count($this->customerLoad);
    }
}