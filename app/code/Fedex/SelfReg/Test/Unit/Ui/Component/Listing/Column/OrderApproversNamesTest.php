<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Ui\Component\Listing\Column\OrderApproversNames;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\Customer;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class OrderApproversNamesTest extends TestCase
{
    /**
     * @var ContextInterface
     */
    protected $contextInterface;

    /**
     * @var UiComponentFactory
     */
    protected $uiComponentFactory;

    /**
     * @var OrderApproversNames
     */
    protected $orderApproversNames;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerCollectionFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextInterface = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->uiComponentFactory = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Mock CustomerCollectionFactory
        $this->customerCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Mock Customer Collection
        $customerCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttributeToSelect', 'addFieldToFilter', 'getIterator'])
            ->getMock();
            
        // Mock Customer objects - separate existing and non-existing methods
        $customer1Mock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId']) // Use onlyMethods for existing methods
            ->addMethods(['getFirstname', 'getLastname']) // Use addMethods for non-existing methods
            ->getMock();
            
        $customer1Mock->method('getId')->willReturn(1);
        $customer1Mock->method('getFirstname')->willReturn('John');
        $customer1Mock->method('getLastname')->willReturn('Doe');
        
        $customer2Mock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId']) // Use onlyMethods for existing methods
            ->addMethods(['getFirstname', 'getLastname']) // Use addMethods for non-existing methods
            ->getMock();
            
        $customer2Mock->method('getId')->willReturn(2);
        $customer2Mock->method('getFirstname')->willReturn('Jane');
        $customer2Mock->method('getLastname')->willReturn('Smith');
        
        // Set up collection mock methods
        $customerCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $customerCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $customerCollectionMock->method('getIterator')->willReturn(
            new \ArrayIterator([$customer1Mock, $customer2Mock])
        );
        
        $this->customerCollectionFactoryMock->method('create')->willReturn($customerCollectionMock);
        
        $objectManagerHelper = new ObjectManager($this);
        
        $this->orderApproversNames = $objectManagerHelper->getObject(
            OrderApproversNames::class,
            [
                'context' => $this->contextInterface,
                'uiComponentFactory' => $this->uiComponentFactory,
                'customerCollectionFactory' => $this->customerCollectionFactoryMock,
                'logger' => $this->loggerMock,
                'components' => [],
                'data' => []
            ]
        );
    }
    
    /**
     * Test Prepare Data Source.
     *
     * @return void
     */
    public function testPrepareDataSource()
    {
        $userNames = "1,2";
        $testData = ['data' => ['items' => [['order_approvers_names' => $userNames]]]];

        $result = $this->orderApproversNames->prepareDataSource($testData);
        
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('items', $result['data']);
        $this->assertIsArray($result['data']['items'][0]['order_approvers_names']);
        $this->assertEquals(['John Doe', 'Jane Smith'], $result['data']['items'][0]['order_approvers_names']);
    }
}