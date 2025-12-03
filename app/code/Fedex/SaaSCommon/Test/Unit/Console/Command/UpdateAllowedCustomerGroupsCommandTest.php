<?php

namespace Fedex\SaaSCommon\Test\Unit\Console\Command;

use Fedex\SaaSCommon\Console\Command\UpdateAllowedCustomerGroupsCommand;
use Magento\Framework\App\State;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as CategoryPermissionCollectionFactory;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;

class UpdateAllowedCustomerGroupsCommandTest extends TestCase
{
    public function testExecuteUpdatesProducts()
    {
        $stateMock = $this->createMock(State::class);
        $stateMock->method('setAreaCode')->willReturn(null);

        $productMock = $this->createMock(Product::class);
        $productMock->method('getCategoryIds')->willReturn([1, 2]);
        $productMock->method('getName')->willReturn('Test Product');

        $productCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $productCollectionMock->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$productMock]));

        $productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $productCollectionFactoryMock->method('create')->willReturn($productCollectionMock);

        $permissionsCollectionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'addFieldToSelect', 'getSelect', 'getIterator', 'getColumnValues'])
            ->addMethods(['group'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionsCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $permissionsCollectionMock->method('addFieldToSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getColumnValues')->willReturn([10, 20]);

        $categoryPermissionCollectionFactoryMock = $this->createMock(CategoryPermissionCollectionFactory::class);
        $categoryPermissionCollectionFactoryMock->method('create')->willReturn($permissionsCollectionMock);

        $productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $productActionMock = $this->createMock(Action::class);
        $productActionMock->expects($this->once())
            ->method('updateAttributes')
            ->with([$productMock->getId()], ['allowed_customer_groups' => '10,20'], 0);

        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->atLeastOnce())->method('writeln');

        $command = new UpdateAllowedCustomerGroupsCommand(
            $stateMock,
            $productCollectionFactoryMock,
            $categoryPermissionCollectionFactoryMock,
            $productRepositoryMock,
            $productActionMock
        );

        $result = $command->run($inputMock, $outputMock);
        $this->assertEquals(0, $result);
    }

    public function testExecuteHandlesProductWithNoCategories()
    {
        $stateMock = $this->createMock(State::class);
        $stateMock->method('setAreaCode')->willReturn(null);

        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $productMock->method('getCategoryIds')->willReturn([]);
        $productMock->method('getName')->willReturn('No Category Product');

        $productCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $productCollectionMock->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$productMock]));

        $productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $productCollectionFactoryMock->method('create')->willReturn($productCollectionMock);

        $permissionsCollectionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'addFieldToSelect', 'getSelect', 'getIterator', 'getColumnValues'])
            ->addMethods(['group'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionsCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $permissionsCollectionMock->method('addFieldToSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getColumnValues')->willReturn([]);

        $categoryPermissionCollectionFactoryMock = $this->createMock(CategoryPermissionCollectionFactory::class);
        $categoryPermissionCollectionFactoryMock->method('create')->willReturn($permissionsCollectionMock);

        $productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $productActionMock = $this->createMock(Action::class);
        $productActionMock->expects($this->once())
            ->method('updateAttributes')
            ->with([$productMock->getId()], ['allowed_customer_groups' => ''], 0);

        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->atLeastOnce())->method('writeln');

        $command = new UpdateAllowedCustomerGroupsCommand(
            $stateMock,
            $productCollectionFactoryMock,
            $categoryPermissionCollectionFactoryMock,
            $productRepositoryMock,
            $productActionMock
        );

        $result = $command->run($inputMock, $outputMock);
        $this->assertEquals(0, $result);
    }

    public function testExecuteSkipsProductIfGroupsUnchanged()
    {
        $stateMock = $this->createMock(State::class);
        $stateMock->method('setAreaCode')->willReturn(null);

        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $productMock->method('getCategoryIds')->willReturn([1]);
        $productMock->method('getName')->willReturn('Already Updated Product');
        $productMock->method('getData')->with('allowed_customer_groups')->willReturn('5');

        $productCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $productCollectionMock->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$productMock]));

        $productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $productCollectionFactoryMock->method('create')->willReturn($productCollectionMock);

        $permissionsCollectionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'addFieldToSelect', 'getSelect', 'getIterator', 'getColumnValues'])
            ->addMethods(['group'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionsCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $permissionsCollectionMock->method('addFieldToSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getColumnValues')->willReturn([5]);

        $categoryPermissionCollectionFactoryMock = $this->createMock(CategoryPermissionCollectionFactory::class);
        $categoryPermissionCollectionFactoryMock->method('create')->willReturn($permissionsCollectionMock);

        $productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $productActionMock = $this->createMock(Action::class);
        $productActionMock->expects($this->never())->method('updateAttributes');

        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->atLeastOnce())->method('writeln');

        $command = new UpdateAllowedCustomerGroupsCommand(
            $stateMock,
            $productCollectionFactoryMock,
            $categoryPermissionCollectionFactoryMock,
            $productRepositoryMock,
            $productActionMock
        );

        $result = $command->run($inputMock, $outputMock);
        $this->assertEquals(0, $result);
    }

    public function testExecuteHandlesLocalizedException()
    {
        $stateMock = $this->createMock(State::class);
        $stateMock->method('setAreaCode')->willReturn(null);

        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $productMock->method('getCategoryIds')->willReturn([1]);
        $productMock->method('getName')->willReturn('Exception Product');
        $productMock->method('getId')->willReturn(123);
        $productMock->method('getData')->with('allowed_customer_groups')->willReturn('');

        $productCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $productCollectionMock->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$productMock]));

        $productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $productCollectionFactoryMock->method('create')->willReturn($productCollectionMock);

        $permissionsCollectionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'addFieldToSelect', 'getSelect', 'getIterator', 'getColumnValues'])
            ->addMethods(['group'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionsCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $permissionsCollectionMock->method('addFieldToSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getSelect')->willReturnSelf();
        $permissionsCollectionMock->method('getColumnValues')->willReturn([7]);

        $categoryPermissionCollectionFactoryMock = $this->createMock(CategoryPermissionCollectionFactory::class);
        $categoryPermissionCollectionFactoryMock->method('create')->willReturn($permissionsCollectionMock);

        $productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);

        $productActionMock = $this->createMock(Action::class);
        $productActionMock->expects($this->once())
            ->method('updateAttributes')
            ->willThrowException(new \Magento\Framework\Exception\LocalizedException(__('Test exception')));

        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->atLeastOnce())->method('writeln');

        $command = new UpdateAllowedCustomerGroupsCommand(
            $stateMock,
            $productCollectionFactoryMock,
            $categoryPermissionCollectionFactoryMock,
            $productRepositoryMock,
            $productActionMock
        );

        // Capture output for assertion
        $writtenLines = [];
        $outputMock->method('writeln')->willReturnCallback(function($message) use (&$writtenLines) {
            $writtenLines[] = $message;
        });

        $result = $command->run($inputMock, $outputMock);
        $this->assertEquals(0, $result);

        $errorFound = false;
        foreach ($writtenLines as $line) {
            if (stripos($line, 'error updating product exception product - id 123: test exception') !== false) {
                $errorFound = true;
                break;
            }
        }
        $this->assertTrue($errorFound, 'Expected error message not found in output.');
    }
}
