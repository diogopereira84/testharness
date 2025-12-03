<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Model\EproCustomer;

use Fedex\SelfReg\Model\EproCustomer\OrderHistory;
use Magento\Customer\Model\Session;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Helper\Data as Deliveryhelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

class OrderHistoryTest extends TestCase
{
    protected $localeDate;
    protected $customerSession;
    /**
     * @var (\Magento\Framework\Model\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registry;
    protected $selfreghelper;
    protected $orderCollectionFactory;
    protected $orderCollection;
    protected $quoteCollectionFactory;
    protected $quoteCollection;
    protected $toggleConfig;
    protected $deliveryHelperMock;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManagerInstance;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderHistoryMock;
    protected function setUp(): void
    {
        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'convertConfigTimeToUtc', 'format', 'sub'])
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getId'])
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->selfreghelper = $this
            ->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
            ->setMethods(['isSelfRegCompany','isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollectionFactory =  $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->orderCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count', 'addFieldToSelect', 'getColumnValues'])
            ->getMock();

        $this->quoteCollectionFactory = $this->getMockBuilder(QuoteCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteCollection = $this->getMockBuilder(QuoteCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->deliveryHelperMock = $this
            ->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['getCustomer', 'getAssignedCompany', 'getProductAttributeName', 'isCommercialCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerInstance = \Magento\Framework\App\ObjectManager::getInstance();

        $this->objectManager = new ObjectManager($this);

        $this->orderHistoryMock = $this->objectManager->getObject(
            OrderHistory::class,
            [
                'customerSession' => $this->customerSession,
                'localeDate' => $this->localeDate,
                'orderCollectionFactory' => $this->orderCollectionFactory,
                '_registry' => $this->registry,
                'context' => $this->context,
                'deliveryHelper' => $this->deliveryHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'quoteCollectionFactory' => $this->quoteCollectionFactory,
                'selfreghelper' => $this->selfreghelper
            ]
        );
    }

    /**
     * Test function for getSubmittedOrderCount
     */
    public function testGetSubmittedOrderCount()
    {
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(1);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(1);
        /** B-1857860 */
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->localeDate->expects($this->any())->method('convertConfigTimeToUtc')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('format')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('sub')->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(1);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->customerSession->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->orderCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('count')
            ->willReturn(2);

        $this->assertEquals(2, $this->orderHistoryMock->getSubmittedOrderCount());
    }

    /**
     * Test function for getSubmittedOrderCount
     */
    public function testGetSubmittedOrderCountWithToggleOff()
    {
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(0);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(0);
        /** B-1857860 */

        $this->assertEquals(0, $this->orderHistoryMock->getSubmittedOrderCount());
    }

    /**
     * Test function for getInProgressOrderCount
     */
    public function testGetInProgressOrderCount()
    {
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->localeDate->expects($this->any())->method('convertConfigTimeToUtc')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('format')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('sub')->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(1);
        $this->customerSession->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->orderCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('count')
            ->willReturn(2);

        $this->assertEquals(2, $this->orderHistoryMock->getInProgressOrderCount());
    }

    /**
     * Test function for getCompletedOrderCount
     */
    public function testGetCompletedOrderCount()
    {
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->localeDate->expects($this->any())->method('convertConfigTimeToUtc')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('format')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('sub')->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(1);
        $this->customerSession->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->orderCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('count')
            ->willReturn(2);

        $this->assertEquals(2, $this->orderHistoryMock->getCompletedOrderCount());
    }

    /**
     * Test function for getOrderCountForHomepage
     */
    public function testGetOrderCountForHomepage()
    {
        $orderCountData = ['submitted' => 2, 'quote' => 2, 'progress' => 2, 'completed' => 2];
        $this->testGetSubmittedOrderCount();
        $this->testGetQuoteCount();
        $this->testGetInProgressOrderCount();
        $this->testGetCompletedOrderCount();

        $this->assertEquals($orderCountData, $this->orderHistoryMock->getOrderCountForHomepage());
    }

    /**
     * Test function for getQuoteCount
     */
    public function testGetQuoteCount()
    {
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->localeDate->expects($this->any())->method('convertConfigTimeToUtc')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('format')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('sub')->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(1);
        $this->customerSession->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->orderCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())
            ->method('getColumnValues')
            ->willReturn(['345', '456']);
        $this->quoteCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteCollection);
        $this->quoteCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->quoteCollection);
        $this->quoteCollection->expects($this->any())
            ->method('count')
            ->willReturn(2);

        $this->assertEquals(2, $this->orderHistoryMock->getQuoteCount());
    }
}
