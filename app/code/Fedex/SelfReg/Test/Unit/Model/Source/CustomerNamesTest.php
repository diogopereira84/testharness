<?php
declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Model\Source;

use Fedex\SelfReg\Model\Source\CustomerNames;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CustomerNamesTest extends TestCase
{
    private CustomerNames $customerNames;
    private CollectionFactory|MockObject $collectionFactoryMock;
    private Collection|MockObject $collectionMock;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->collectionMock = $this->createMock(Collection::class);

        $this->customerNames = new CustomerNames($this->collectionFactoryMock);
    }

    /**
     * Test getCustomersByPage method with default parameters
     */
    public function testGetCustomersByPageWithDefaults(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe'],
            ['id' => 2, 'firstname' => 'Jane', 'lastname' => 'Smith']
        ]);

        $expectedResult = [
            ['label' => 'John Doe', 'value' => 1],
            ['label' => 'Jane Smith', 'value' => 2]
        ];

        $this->setupBasicCollectionMocks($mockCustomers);

        $result = $this->customerNames->getCustomersByPage();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test getCustomersByPage method with pagination
     */
    public function testGetCustomersByPageWithPagination(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 3, 'firstname' => 'Bob', 'lastname' => 'Johnson']
        ]);

        $expectedResult = [
            ['label' => 'Bob Johnson', 'value' => 3]
        ];

        $this->setupBasicCollectionMocks($mockCustomers, 25, 2);

        $result = $this->customerNames->getCustomersByPage(2, 25);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test getTotalCount method
     */
    public function testGetTotalCount(): void
    {
        $expectedCount = 150;

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(4))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']]
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($expectedCount);

        $result = $this->customerNames->getTotalCount();

        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Test getTotalCount method with search term
     */
    public function testGetTotalCountWithSearchTerm(): void
    {
        $expectedCount = 25;
        $searchTerm = 'smith';

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']],
                [['firstname', 'lastname'], [['like' => '%smith%'], ['like' => '%smith%']]]
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($expectedCount);

        $result = $this->customerNames->getTotalCount($searchTerm);

        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Test toOptionArray method
     */
    public function testToOptionArray(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe'],
            ['id' => 2, 'firstname' => 'Jane', 'lastname' => 'Smith']
        ]);

        $expectedResult = [
            ['label' => 'John Doe', 'value' => 1],
            ['label' => 'Jane Smith', 'value' => 2]
        ];

        $this->setupBasicCollectionMocks($mockCustomers);

        $result = $this->customerNames->toOptionArray();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test with empty results
     */
    public function testGetCustomersByPageWithEmptyResults(): void
    {
        $mockCustomers = [];
        $expectedResult = [];

        $this->setupBasicCollectionMocks($mockCustomers);

        $result = $this->customerNames->getCustomersByPage();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test with customers having valid names
     */
    public function testHandleCustomersWithValidNames(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe']
        ]);

        $this->setupBasicCollectionMocks($mockCustomers);

        $result = $this->customerNames->getCustomersByPage();

        $this->assertNotEmpty($result);
        $this->assertEquals('John Doe', $result[0]['label']);
    }

    /**
     * Test getPaginatedCustomers method
     */
    public function testGetPaginatedCustomers(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe']
        ]);

        $totalCount = 100;
        $page = 2;
        $pageSize = 25;

        // Setup mocks for both getCustomersByPage and getTotalCount calls
        $dataCollection = $this->createMock(Collection::class);
        $countCollection = $this->createMock(Collection::class);

        $this->collectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($dataCollection, $countCollection);

        // Setup mocks for getCustomersByPage collection
        $dataCollection->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        $dataCollection->expects($this->exactly(4))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']]
            )
            ->willReturnSelf();

        $dataCollection->expects($this->once())
            ->method('setPageSize')
            ->with($pageSize)
            ->willReturnSelf();

        $dataCollection->expects($this->once())
            ->method('setCurPage')
            ->with($page)
            ->willReturnSelf();

        $dataCollection->expects($this->exactly(2))
            ->method('addOrder')
            ->withConsecutive(
                ['firstname', 'ASC'],
                ['lastname', 'ASC']
            )
            ->willReturnSelf();

        $dataCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($mockCustomers));

        // Setup mocks for getTotalCount collection
        $countCollection->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        $countCollection->expects($this->exactly(4))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']]
            )
            ->willReturnSelf();

        $countCollection->expects($this->once())
            ->method('getSize')
            ->willReturn($totalCount);

        $expectedResult = [
            'customers' => [
                ['label' => 'John Doe', 'value' => 1]
            ],
            'pagination' => [
                'current_page' => 2,
                'page_size' => 25,
                'total_count' => 100,
                'total_pages' => 4,
                'has_next' => true,
                'has_previous' => true
            ]
        ];

        $result = $this->customerNames->getPaginatedCustomers($page, $pageSize);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test getCustomersByPage method with search term
     */
    public function testGetCustomersByPageWithSearchTerm(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe']
        ]);

        $expectedResult = [
            ['label' => 'John Doe', 'value' => 1]
        ];

        $this->setupCollectionMocksWithSearch($mockCustomers, 'john');

        $result = $this->customerNames->getCustomersByPage(1, 50, 'john');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test search with multiple terms
     */
    public function testGetCustomersByPageWithMultipleSearchTerms(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe']
        ]);

        $expectedResult = [
            ['label' => 'John Doe', 'value' => 1]
        ];

        $this->setupCollectionMocksWithComplexSearch($mockCustomers, 'john doe');

        $result = $this->customerNames->getCustomersByPage(1, 50, 'john doe');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test parameter normalization
     */
    public function testParameterNormalization(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe']
        ]);

        // Test with negative page and zero page size - should be normalized
        $this->setupBasicCollectionMocks($mockCustomers, 1, 1); // Normalized values

        $result = $this->customerNames->getCustomersByPage(-1, 0);

        $this->assertNotEmpty($result);
    }

    /**
     * Test cache methods
     */
    public function testCacheMethods(): void
    {
        // Test initial state
        $this->assertFalse($this->customerNames->isCacheLoaded());
        $this->assertEquals(0, $this->customerNames->getCachedCount());

        // Clear cache (should not fail on empty cache)
        $this->customerNames->clearCache();

        $this->assertFalse($this->customerNames->isCacheLoaded());
    }

    /**
     * Helper method to setup basic collection mocks
     */
    private function setupBasicCollectionMocks(array $mockCustomers, int $pageSize = 50, int $page = 1): void
    {
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(4))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']]
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with($pageSize)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setCurPage')
            ->with($page)
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(2))
            ->method('addOrder')
            ->withConsecutive(
                ['firstname', 'ASC'],
                ['lastname', 'ASC']
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($mockCustomers));
    }

    /**
     * Helper method to setup collection mocks with simple search
     */
    private function setupCollectionMocksWithSearch(array $mockCustomers, string $searchTerm): void
    {
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']],
                [['firstname', 'lastname'], [['like' => '%' . $searchTerm . '%'], ['like' => '%' . $searchTerm . '%']]]
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(50)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setCurPage')
            ->with(1)
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(2))
            ->method('addOrder')
            ->withConsecutive(
                ['firstname', 'ASC'],
                ['lastname', 'ASC']
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($mockCustomers));
    }

    /**
     * Helper method to setup collection mocks with complex search (multiple terms)
     */
    private function setupCollectionMocksWithComplexSearch(array $mockCustomers, string $searchTerm): void
    {
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        // For multi-term search, expect complex filter conditions
        $this->collectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']],
                [
                    [['firstname', 'lastname'], ['firstname', 'lastname']],
                    [
                        [['like' => '%john%'], ['like' => '%doe%']],
                        [['like' => '%doe%'], ['like' => '%john%']]
                    ]
                ]
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(50)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setCurPage')
            ->with(1)
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(2))
            ->method('addOrder')
            ->withConsecutive(
                ['firstname', 'ASC'],
                ['lastname', 'ASC']
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($mockCustomers));
    }

    /**
     * Helper method to create mock customers using DataObject
     */
    private function createMockCustomers(array $customerData): array
    {
        $customers = [];
        foreach ($customerData as $data) {
            // Use DataObject which supports magic methods like getFirstname(), getLastname()
            // DataObject's getId() method looks for 'id' property, not 'entity_id'
            $customer = new DataObject([
                'id' => $data['id'],           // This is what getId() method looks for
                'entity_id' => $data['id'],    // Keep this for compatibility
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname']
            ]);
            $customers[] = $customer;
        }
        return $customers;
    }
    /**
     * Test search with multiple terms where one part is empty
     */
    public function testGetCustomersByPageWithMultipleSearchTermsOneEmpty(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe']
        ]);

        $expectedResult = [
            ['label' => 'John Doe', 'value' => 1]
        ];

        // Test case where search term is "john " (with trailing space)
        $this->setupCollectionMocksWithSingleTermFromMultiple($mockCustomers, 'john ');

        $result = $this->customerNames->getCustomersByPage(1, 50, 'john ');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test search with multiple terms where first part is empty
     */
    public function testGetCustomersByPageWithMultipleSearchTermsFirstEmpty(): void
    {
        $mockCustomers = $this->createMockCustomers([
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe']
        ]);

        $expectedResult = [
            ['label' => 'John Doe', 'value' => 1]
        ];

        // Test case where search term is " doe" (with leading space)
        $this->setupCollectionMocksWithSingleTermFromMultiple($mockCustomers, ' doe');

        $result = $this->customerNames->getCustomersByPage(1, 50, ' doe');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test getTotalCount with multiple terms where one part is empty
     */
    public function testGetTotalCountWithMultipleSearchTermsOneEmpty(): void
    {
        $expectedCount = 10;
        $searchTerm = 'john ';

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']],
                [['firstname', 'lastname'], [['like' => '%john%'], ['like' => '%john%']]]
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($expectedCount);

        $result = $this->customerNames->getTotalCount($searchTerm);

        $this->assertEquals($expectedCount, $result);
    }

    // Add this helper method after the existing helper methods

    /**
     * Helper method to setup collection mocks for single term extracted from multiple terms
     */
    private function setupCollectionMocksWithSingleTermFromMultiple(array $mockCustomers, string $searchTerm): void
    {
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['firstname', 'lastname'])
            ->willReturnSelf();

        // Determine the expected search value based on the input
        $trimmedTerm = trim($searchTerm);
        $nameParts = explode(' ', $trimmedTerm, 2);
        $firstName = trim($nameParts[0]);
        $lastName = trim($nameParts[1] ?? '');
        
        $expectedSearchValue = '%' . (!empty($firstName) ? $firstName : $lastName) . '%';

        $this->collectionMock->expects($this->exactly(5))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['firstname', ['notnull' => true]],
                ['lastname', ['notnull' => true]],
                ['firstname', ['neq' => '']],
                ['lastname', ['neq' => '']],
                [['firstname', 'lastname'], [['like' => $expectedSearchValue], ['like' => $expectedSearchValue]]]
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(50)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('setCurPage')
            ->with(1)
            ->willReturnSelf();

        $this->collectionMock->expects($this->exactly(2))
            ->method('addOrder')
            ->withConsecutive(
                ['firstname', 'ASC'],
                ['lastname', 'ASC']
            )
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($mockCustomers));
    }
}