<?php

namespace Fedex\SaaSCommon\Test\Unit\Model;

use Fedex\SaaSCommon\Model\CustomerGroupAttributeHandler;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Fedex\SaaSCommon\Api\CustomerGroupDiffServiceInterface;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterfaceFactory;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Fedex\SaaSCommon\Model\Entity\Attribute\Source\CustomerGroupsOptions;
use Fedex\SaaSCommon\Model\Queue\Publisher;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Group;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Eav\Api\Data\AttributeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomerGroupAttributeHandlerTest extends TestCase
{
    private $logger;
    private $attributeRepository;
    private $attributeOptionFactory;
    private $attributeOptionManagement;
    private $customerGroupsOptions;
    private $customerGroupDiffServiceInterface;
    private $publisher;
    private $allowedCustomerGroupsRequestFactory;
    private $handler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->attributeRepository = $this->createMock(AttributeRepositoryInterface::class);
        $this->attributeOptionFactory = $this->createMock(AttributeOptionInterfaceFactory::class);
        $this->attributeOptionManagement = $this->createMock(AttributeOptionManagementInterface::class);
        $this->customerGroupsOptions = $this->createMock(CustomerGroupsOptions::class);
        $this->customerGroupDiffServiceInterface = $this->createMock(CustomerGroupDiffServiceInterface::class);
        $this->publisher = $this->createMock(Publisher::class);
        $this->allowedCustomerGroupsRequestFactory = $this->createMock(AllowedCustomerGroupsRequestInterfaceFactory::class);

        $this->handler = new CustomerGroupAttributeHandler(
            $this->logger,
            $this->attributeRepository,
            $this->attributeOptionFactory,
            $this->attributeOptionManagement,
            $this->customerGroupsOptions,
            $this->customerGroupDiffServiceInterface,
            $this->publisher,
            $this->allowedCustomerGroupsRequestFactory
        );
    }

    public function testGetAttributeIdByCodeReturnsId()
    {
        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getAttributeId')->willReturn(123);
        $this->attributeRepository->expects($this->once())
            ->method('get')
            ->with('entity', 'code')
            ->willReturn($attribute);

        $result = $this->handler->getAttributeIdByCode('entity', 'code');
        $this->assertSame(123, $result);
    }

    public function testGetAllCustomerGroupsReturnsArray()
    {
        $expected = [['value' => 1, 'label' => 'General']];
        $this->customerGroupsOptions->expects($this->once())
            ->method('getAllOptions')
            ->willReturn($expected);

        $result = $this->handler->getAllCustomerGroups();
        $this->assertSame($expected, $result);
    }

    public function testGetAllCustomerGroupsValuesReturnsArray()
    {
        $expected = [1, 2, 3];
        $this->customerGroupsOptions->expects($this->once())
            ->method('getAllOptionsValues')
            ->willReturn($expected);

        $result = $this->handler->getAllCustomerGroupsValues();
        $this->assertSame($expected, $result);
    }

    public function testAddAttributeOptionWithProvidedOptions()
    {
        $attributeOption = $this->getMockBuilder(AttributeOptionInterface::class)
            ->onlyMethods(['setLabel', 'setSortOrder'])
            ->addMethods(['setAttributeId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeOptionFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($attributeOption);

        $attributeOption->expects($this->exactly(2))
            ->method('setAttributeId')
            ->with(10)
            ->willReturnSelf();
        $attributeOption->expects($this->exactly(2))
            ->method('setLabel')
            ->withConsecutive(['A'], ['B'])
            ->willReturnSelf();
        $attributeOption->expects($this->exactly(2))
            ->method('setSortOrder')
            ->withConsecutive([5], [6])
            ->willReturnSelf();

        $this->attributeRepository->expects($this->once())
            ->method('get')
            ->with(Product::ENTITY, CustomerGroupAttributeHandler::ATTRIBUTE_CODE)
            ->willReturn(new class {
                public function getAttributeId() { return 10; }
            });

        $this->customerGroupDiffServiceInterface->expects($this->once())
            ->method('allowedCustomerGroupsAttributeOptionLength')
            ->willReturn(5);

        $this->attributeOptionManagement->expects($this->exactly(2))
            ->method('add')
            ->with(Product::ENTITY, CustomerGroupAttributeHandler::ATTRIBUTE_CODE, $attributeOption);

        $this->handler->addAttributeOption(['A', 'B']);
    }

    public function testAddAttributeOptionWithNullOptionsUsesDiffService()
    {
        $attributeOption = $this->getMockBuilder(AttributeOptionInterface::class)
            ->onlyMethods(['setLabel', 'setSortOrder'])
            ->addMethods(['setAttributeId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeOptionFactory->expects($this->once())
            ->method('create')
            ->willReturn($attributeOption);

        $this->customerGroupsOptions->expects($this->once())
            ->method('getAllOptionsValues')
            ->willReturn(['X']);

        $this->customerGroupDiffServiceInterface->expects($this->once())
            ->method('findMissingCustomerGroupOptions')
            ->with(['X'])
            ->willReturn(['Y']);

        $this->attributeRepository->expects($this->once())
            ->method('get')
            ->willReturn(new class {
                public function getAttributeId() { return 11; }
            });

        $this->customerGroupDiffServiceInterface->expects($this->once())
            ->method('allowedCustomerGroupsAttributeOptionLength')
            ->willReturn(7);

        $attributeOption->expects($this->once())->method('setAttributeId')->with(11)->willReturnSelf();
        $attributeOption->expects($this->once())->method('setLabel')->with('Y')->willReturnSelf();
        $attributeOption->expects($this->once())->method('setSortOrder')->with(7)->willReturnSelf();

        $this->attributeOptionManagement->expects($this->once())
            ->method('add')
            ->with(Product::ENTITY, CustomerGroupAttributeHandler::ATTRIBUTE_CODE, $attributeOption);

        $this->handler->addAttributeOption(null);
    }

    public function testAddAttributeOptionThrowsInputExceptionOnCouldNotSave()
    {
        $attributeOption = $this->getMockBuilder(AttributeOptionInterface::class)
            ->onlyMethods(['setLabel', 'setSortOrder'])
            ->addMethods(['setAttributeId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeOptionFactory->expects($this->once())
            ->method('create')
            ->willReturn($attributeOption);

        $attributeOption->expects($this->once())
            ->method('setAttributeId')
            ->with(12)
            ->willReturnSelf();
        $attributeOption->expects($this->once())
            ->method('setLabel')
            ->with('fail')
            ->willReturnSelf();
        $attributeOption->expects($this->once())
            ->method('setSortOrder')
            ->with(1)
            ->willReturnSelf();

        $this->attributeRepository->expects($this->once())
            ->method('get')
            ->willReturn(new class {
                public function getAttributeId() { return 12; }
            });

        $this->customerGroupDiffServiceInterface->expects($this->once())
            ->method('allowedCustomerGroupsAttributeOptionLength')
            ->willReturn(1);

        $this->attributeOptionManagement->expects($this->once())
            ->method('add')
            ->willThrowException(new CouldNotSaveException(__('fail')));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error creating attribute options:'));

        $this->expectException(InputException::class);
        $this->handler->addAttributeOption(['fail']);
    }

    public function testAddAttributeOptionThrowsInputExceptionOnLocalizedException()
    {
        $attributeOption = $this->getMockBuilder(AttributeOptionInterface::class)
            ->onlyMethods(['setLabel', 'setSortOrder'])
            ->addMethods(['setAttributeId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeOptionFactory->expects($this->once())
            ->method('create')
            ->willReturn($attributeOption);

        $attributeOption->expects($this->once())
            ->method('setAttributeId')
            ->with(13)
            ->willReturnSelf();
        $attributeOption->expects($this->once())
            ->method('setLabel')
            ->with('fail2')
            ->willReturnSelf();
        $attributeOption->expects($this->once())
            ->method('setSortOrder')
            ->with(2)
            ->willReturnSelf();

        $this->attributeRepository->expects($this->once())
            ->method('get')
            ->willReturn(new class {
                public function getAttributeId() { return 13; }
            });

        $this->customerGroupDiffServiceInterface->expects($this->once())
            ->method('allowedCustomerGroupsAttributeOptionLength')
            ->willReturn(2);

        $this->attributeOptionManagement->expects($this->once())
            ->method('add')
            ->willThrowException(new LocalizedException(__('fail2')));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error creating attribute options:'));

        $this->expectException(InputException::class);
        $this->handler->addAttributeOption(['fail2']);
    }

    public function testpushEntityToQueuePublishesRequest()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $this->allowedCustomerGroupsRequestFactory->expects($this->once())
            ->method('create')
            ->willReturn($request);

        $request->expects($this->once())->method('setEntityId')->with(99);
        $request->expects($this->once())->method('setEntityType')->with(Group::ENTITY);

        $this->publisher->expects($this->once())->method('publish')->with($request);

        $this->handler->pushEntityToQueue(99, Group::ENTITY);
    }

    public function testpushEntityToQueueThrowsAndLogsOnInvalidArgumentException()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $this->allowedCustomerGroupsRequestFactory->expects($this->once())
            ->method('create')
            ->willReturn($request);

        $request->expects($this->once())->method('setEntityId')->with(101);
        $request->expects($this->once())->method('setEntityType')->with(Group::ENTITY);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with($request)
            ->willThrowException(new \InvalidArgumentException('bad'));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error pushing entity to queue: bad'));

        $this->expectException(\InvalidArgumentException::class);
        $this->handler->pushEntityToQueue(101, Group::ENTITY);
    }

    public function testUpdateAllAttributeOptionsAddsMissingOptions()
    {
        // Arrange attribute option mock
        $attributeOption = $this->getMockBuilder(AttributeOptionInterface::class)
            ->onlyMethods(['setLabel', 'setSortOrder'])
            ->addMethods(['setAttributeId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->attributeOptionFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($attributeOption);

        $attributeOption->expects($this->exactly(2))
            ->method('setAttributeId')
            ->with(20)
            ->willReturnSelf();
        $attributeOption->expects($this->exactly(2))
            ->method('setLabel')
            ->withConsecutive(['A'], ['B'])
            ->willReturnSelf();
        $attributeOption->expects($this->exactly(2))
            ->method('setSortOrder')
            ->withConsecutive([3], [4])
            ->willReturnSelf();

        // getAllCustomerGroupsValues() => used by updateAllAttributeOptions
        $this->customerGroupsOptions->expects($this->once())
            ->method('getAllOptionsValues')
            ->willReturn(['X', 'Y']);

        // Diff service returns missing labels to add
        $this->customerGroupDiffServiceInterface->expects($this->once())
            ->method('findMissingCustomerGroupOptions')
            ->with(['X', 'Y'])
            ->willReturn(['A', 'B']);

        // Attribute ID and current length
        $this->attributeRepository->expects($this->once())
            ->method('get')
            ->with(Product::ENTITY, CustomerGroupAttributeHandler::ATTRIBUTE_CODE)
            ->willReturn(new class {
                public function getAttributeId() { return 20; }
            });

        $this->customerGroupDiffServiceInterface->expects($this->once())
            ->method('allowedCustomerGroupsAttributeOptionLength')
            ->willReturn(3);

        // Expect adds using attribute code
        $this->attributeOptionManagement->expects($this->exactly(2))
            ->method('add')
            ->with(Product::ENTITY, CustomerGroupAttributeHandler::ATTRIBUTE_CODE, $attributeOption);

        // Act
        $this->handler->updateAllAttributeOptions();
    }
}

