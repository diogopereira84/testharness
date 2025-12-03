<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\B2b\Test\Unit\Model\Quote;

use Magento\Quote\Model\Quote\Address\Total\Collector;
use Magento\Quote\Model\Quote\Address\Total\CollectorFactory;
use Magento\Quote\Model\Quote\Address\Total\CollectorInterface;
use Fedex\B2b\Model\Quote\TotalsCollector;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class TotalsCollector
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TotalsCollectorTest extends TestCase
{
    /**
     * @var (\Magento\Quote\Model\Quote\Address\Total\Collector & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $totalCollectorMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Address\Total\CollectorFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $totalCollectorFactoryMock;
    /**
     * @var (\Magento\Store\Model\StoreManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeManagerInterfaceMock;
    /**
     * @var (\Magento\Store\Api\Data\StoreInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeInterfaceMock;
    protected $addressTotalFactoryMock;
    protected $addressTotalMock;
    protected $eventManagerMock;
    protected $totalsCollectorListMock;
    protected $shippingFactoryMock;
    protected $shippingAssignmentFactoryMock;
    /**
     * @var (\Magento\Quote\Model\QuoteValidator & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteValidatorMock;
    protected $sessionManagerInterfaceMock;
    protected $quoteMock;
    /**
     * @var (\Magento\Quote\Model\Quote\AddressFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteAddressFactoryMock;
    protected $quoteAddressMock;
    protected $shippingMock;
    protected $shippingAssignmentMock;
    protected $abstractTotalMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $totalsCollectorObject;
    /**
     * Total models collector
     *
     * @var \Magento\Quote\Model\Quote\Address\Total\Collector
     */
    protected $totalCollector;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Total\CollectorFactory
     */
    protected $totalCollectorFactory;

    /**
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Model\Quote\Address\TotalFactory
     */
    protected $totalFactory;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollectorList
     */
    protected $collectorList;

    /**
     * Quote validator
     *
     * @var \Magento\Quote\Model\QuoteValidator
     */
    protected $quoteValidator;

    /**
     * @var \Magento\Quote\Model\ShippingFactory
     */
    protected $shippingFactory;

    /**
     * @var \Magento\Quote\Model\ShippingAssignmentFactory
     */
    protected $shippingAssignmentFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManagerInterface;
    
     /**
      * {@inheritdoc}
      */
    protected function setUp(): void
    {
        $this->totalCollectorMock = $this->getMockBuilder(Collector::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->totalCollectorFactoryMock = $this->getMockBuilder(CollectorFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->storeManagerInterfaceMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->storeInterfaceMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
                                    
        $this->addressTotalFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\TotalFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->addressTotalMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->setMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->addressTotalMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
            ->setMethods(['getData', 'getSubtotal', 'getBaseSubtotal', 'getSubtotalWithDiscount',
                'getBaseSubtotalWithDiscount', 'getGrandTotal', 'getBaseGrandTotal'])
            ->disableOriginalConstructor()
            ->getMock();
                                                
        $this->totalsCollectorListMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\TotalsCollectorList::class)
            ->setMethods(['getCollectors'])
            ->disableOriginalConstructor()
            ->getMock();
                                                
        $this->shippingFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\ShippingFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
                                                
        $this->shippingAssignmentFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\ShippingAssignmentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
                                                
        $this->quoteValidatorMock = $this->getMockBuilder(\Magento\Quote\Model\QuoteValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
                                                
        $this->sessionManagerInterfaceMock = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
            ->setMethods(['start', 'getAdminQuoteView'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
                                                                                                    
        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods(['getAllVisibleItems', 'getAllAddresses', 'getStoreId', 'isVirtual'])
            ->disableOriginalConstructor()
            ->getMock();
                                                
        $this->quoteAddressFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\AddressFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
                                                
        $this->quoteAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->shippingFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\ShippingFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
                                    
        $this->shippingMock = $this->getMockBuilder(\Magento\Quote\Model\Shipping::class)
            ->disableOriginalConstructor()
            ->getMock();
                                                    
        $this->shippingAssignmentFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\ShippingAssignmentFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
                                    
        $this->shippingAssignmentMock = $this->getMockBuilder(\Magento\Quote\Model\ShippingAssignment::class)
            ->disableOriginalConstructor()
            ->getMock();
                                                    
        $this->abstractTotalMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total\AbstractTotal::class)
            ->disableOriginalConstructor()
            ->getMock();
                                                                                                    
        $this->objectManager = new ObjectManager($this);
                                                 
        $this->totalsCollectorObject = $this->objectManager->getObject(
            TotalsCollector::class,
            [
                'totalCollector' => $this->totalCollectorMock,
                'totalCollectorFactory' => $this->totalCollectorFactoryMock,
                'eventManager' => $this->eventManagerMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'totalFactory' => $this->addressTotalFactoryMock,
                'collectorList' => $this->totalsCollectorListMock,
                'shippingFactory' => $this->shippingFactoryMock,
                'shippingAssignmentFactory' => $this->shippingAssignmentFactoryMock,
                'quoteValidator' => $this->quoteValidatorMock,
                'sessionManagerInterface' => $this->sessionManagerInterfaceMock
            ]
        );
    }

    /**
     * @test collect
     */
    public function testCollect()
    {
        $this->addressTotalFactoryMock->expects($this->any())->method('create')
            ->with(\Magento\Quote\Model\Quote\Address\Total::class)->willReturn($this->addressTotalMock);
        $this->sessionManagerInterfaceMock->expects($this->any())->method('start')->willReturnSelf();
        $this->sessionManagerInterfaceMock->expects($this->any())->method('getAdminQuoteView')->willReturn([]);
        $this->eventManagerMock->expects($this->any())->method('dispatch');
        $this->quoteMock->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->quoteMock->expects($this->any())->method('getAllAddresses')->willReturn([$this->quoteAddressMock]);
        $this->shippingFactoryMock->expects($this->any())->method('create')->willReturn($this->shippingMock);
        $this->shippingAssignmentFactoryMock->expects($this->any())->method('create')->willReturn($this->shippingAssignmentMock);
        $this->totalsCollectorListMock->expects($this->any())->method('getCollectors')->willReturn([$this->abstractTotalMock]);
        $this->addressTotalMock->expects($this->any())->method('getData')->willReturn([]);
        $this->addressTotalMock->expects($this->any())->method('getSubtotal')->willReturn(0.00);
        $this->addressTotalMock->expects($this->any())->method('getBaseSubtotal')->willReturn(0.00);
        $this->addressTotalMock->expects($this->any())->method('getSubtotalWithDiscount')->willReturn(0.00);
        $this->addressTotalMock->expects($this->any())->method('getBaseSubtotalWithDiscount')->willReturn(0.00);
        $this->addressTotalMock->expects($this->any())->method('getGrandTotal')->willReturn(0.00);
        $this->addressTotalMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(0.00);
        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(true);
        $result = $this->totalsCollectorObject->collect($this->quoteMock);
        
        $this->assertInstanceOf(\Magento\Quote\Model\Quote\Address\Total::class, $result);
    }
}
