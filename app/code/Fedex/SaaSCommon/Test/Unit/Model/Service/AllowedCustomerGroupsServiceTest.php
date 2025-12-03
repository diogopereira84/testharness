<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Service;

use Fedex\SaaSCommon\Model\Service\AllowedCustomerGroupsService;
use Magento\Catalog\Model\Product\Action;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as CategoryPermissionCollectionFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection;
use PHPUnit\Framework\TestCase;

class AllowedCustomerGroupsServiceTest extends TestCase
{
    private $collectionFactory;
    private $productAction;
    private $service;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CategoryPermissionCollectionFactory::class);
        $this->productAction = $this->createMock(Action::class);

        $this->service = new AllowedCustomerGroupsService(
            $this->collectionFactory,
            $this->productAction
        );
    }

    public function testGetAllowedCustomerGroupsFromCategoriesReturnsEmptyForEmptyInput()
    {
        $result = $this->service->getAllowedCustomerGroupsFromCategories([]);
        $this->assertSame([], $result);
    }

    public function testGetAllowedCustomerGroupsFromCategoriesReturnsGroups()
    {
        $categories = [1, 2];
        $collection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['category_id', ['in' => $categories]],
                ['grant_catalog_category_view', Permission::PERMISSION_ALLOW]
            )
            ->willReturnSelf();

        $collection->expects($this->once())
            ->method('addFieldToSelect')
            ->with('customer_group_id')
            ->willReturnSelf();

        $select = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['group'])
            ->getMock();
        $collection->expects($this->once())
            ->method('getSelect')
            ->willReturn($select);
        $select->expects($this->once())
            ->method('group')
            ->with('customer_group_id');

        $collection->expects($this->once())
            ->method('getColumnValues')
            ->with('customer_group_id')
            ->willReturn([3, 4]);

        $result = $this->service->getAllowedCustomerGroupsFromCategories($categories);
        $this->assertSame([3, 4], $result);
    }

    public function testGetAllowedCustomerGroupsFromCategoriesReturnsGroupsWithAllCustomerGroups()
    {
        $categories = [1, 2];
        $collection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['category_id', ['in' => $categories]],
                ['grant_catalog_category_view', Permission::PERMISSION_ALLOW]
            )
            ->willReturnSelf();

        $collection->expects($this->once())
            ->method('addFieldToSelect')
            ->with('customer_group_id')
            ->willReturnSelf();

        $select = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['group'])
            ->getMock();
        $collection->expects($this->once())
            ->method('getSelect')
            ->willReturn($select);
        $select->expects($this->once())
            ->method('group')
            ->with('customer_group_id');

        $collection->expects($this->once())
            ->method('getColumnValues')
            ->with('customer_group_id')
            ->willReturn([null, 3, 4]);

        $result = $this->service->getAllowedCustomerGroupsFromCategories($categories);
        $this->assertSame(['-1'], $result);
    }

    public function testGetAllowedCategoriesFromCustomerGroupReturnsEmptyForZero()
    {
        $result = $this->service->getAllowedCategoriesFromCustomerGroup(0);
        $this->assertSame([], $result);
    }

    public function testGetAllowedCategoriesFromCustomerGroupReturnsCategoryIds()
    {
        $customerGroupId = 5;
        $collection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['customer_group_id', $customerGroupId],
                ['grant_catalog_category_view', Permission::PERMISSION_ALLOW]
            )
            ->willReturnSelf();

        $collection->expects($this->once())
            ->method('addFieldToSelect')
            ->with('category_id')
            ->willReturnSelf();

        $permission1 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCategoryId'])
            ->getMock();
        $permission2 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCategoryId'])
            ->getMock();

        $permission1->expects($this->once())->method('getCategoryId')->willReturn(7);
        $permission2->expects($this->once())->method('getCategoryId')->willReturn(8);

        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$permission1, $permission2]));

        $result = $this->service->getAllowedCategoriesFromCustomerGroup($customerGroupId);
        $this->assertSame([7, 8], $result);
    }

    public function testUpdateAttributesCallsProductAction()
    {
        $products = [1, 2];
        $allowedValue = '3,4';

        $this->productAction->expects($this->once())
            ->method('updateAttributes')
            ->with(
                $products,
                ['allowed_customer_groups' => $allowedValue],
                0
            );

        $this->service->updateAttributes($products, $allowedValue);
    }
}
