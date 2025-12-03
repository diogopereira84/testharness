<?php
/**
 * Copyright © FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SDE\Test\Unit\Observer;

use Fedex\SDE\Observer\CartMerge;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Login\Helper\Login;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class CartMergeTest extends \PHPUnit\Framework\TestCase
{
    protected $quoteFactory;
    protected $quoteMock;
    protected $quoteItemMock;
    protected $eventMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $observer;
    protected $storeManagerInterface;
    protected $storeMock;
    protected $loginMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $cartMerge;
    private Option|MockObject $itemOptionMock;
    private MockObject|SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'getAllVisibleItems', 'removeItem', 'save', 'load', 'getStoreId'])
            ->getMock();

        $this->quoteItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptionByCode', 'getItemId'])
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getRequest', 'getSource'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction', 'getResponse', 'setRedirect', 'getEvent'])
            ->getMock();

        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getStoreId'])
            ->getMock();
        $this->loginMock = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId', 'isLoggingToggleEnable'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->cartMerge = $this->objectManager->getObject(
            CartMerge::class,
            [
                'logger'       => $this->loggerMock,
                'quoteFactory' => $this->quoteFactory,
                'storeManager' => $this->storeManagerInterface,
                'login'        => $this->loginMock
            ]
        );
    }

    /**
     * Test execute function
     */
    public function testExecute()
    {
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->loginMock->expects($this->any())->method('getCompanyId')->willReturn(1);

        // coverage for isLoggingToggleEnable toggle on behaviour start.
        $this->loginMock->expects($this->any())->method('isLoggingToggleEnable')->willReturn(1);

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()->setMethods(['serialize'])->getMockForAbstractClass();
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([0 => $this->quoteItemMock]);

        $this->itemOptionMock = $this->getMockBuilder(Option::class)
            ->onlyMethods(['getValue', 'save'])
            ->addMethods(['getOptionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock->expects($this->any())->method('getOptionByCode')
            ->with('info_buyRequest')->willReturnSelf($this->itemOptionMock);

        $this->itemOptionMock->expects($this->any())->method('getValue')
            ->willReturnSelf();

        // coverage for isLoggingToggleEnable toggle on behaviour ended here.

        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getSource')->willReturn($this->quoteMock);
        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn(2);
        $this->quoteMock->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->expects($this->any())->method('getItemId')->willReturn(1);
        $this->quoteMock->expects($this->any())->method('removeItem')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('save')->willReturnSelf();
        $result = $this->cartMerge->execute($this->observer);
        $this->assertNotNull($result);
    }

    /**
     * Test execute function with exception
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception();
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getSource')->willThrowException($exception);
        $result = $this->cartMerge->execute($this->observer);
        $this->assertNull($result);
    }
}
