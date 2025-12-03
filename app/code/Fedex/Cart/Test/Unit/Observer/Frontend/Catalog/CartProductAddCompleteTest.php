<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare (strict_types = 1);

namespace Fedex\Cart\Test\Unit\Observer\Frontend\Catalog;

use Fedex\Cart\Helper\Data;
use Fedex\Cart\Observer\Frontend\Catalog\CartProductAddComplete;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

class CartProductAddCompleteTest extends TestCase
{
    protected $cartMock;
    protected $dataMock;
    /**
     * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageManagerMock;
    protected $cartProductAddComplete;
    /**
     * @var DeliveryHelper
     */
    protected $deliveryHelper;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataMock = $this
            ->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer'])
            ->getMock();
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->cartProductAddComplete = $objectManager->getObject(
            CartProductAddComplete::class,
            [
                'checkoutCartHelper' => $this->cartMock,
                'cartDataHelper' => $this->dataMock,
                'deliveryHelper' => $this->deliveryHelper,
                'messageManager' => $this->messageManagerMock,
            ]
        );
    }

    /**
     * Test for execute()
     *
     * @return null
     */
    public function testExecute()
    {
        $maxCartItemLimit = 3;
        $this->execute($maxCartItemLimit);
    }
    
    /**
     * Test for execute()
     *
     * @return null
     */
    public function testExecuteFirst()
    {
        $maxCartItemLimit = 2;
        $this->execute($maxCartItemLimit);
    }

    /**
     * execute()
     *
     * @return null
     */
    public function execute($maxCartItemLimit)
    {
        $minCartItemThreshold = 1;
        $quoteItemsCount = 2;
        $cartLimit = [
            'maxCartItemLimit' => $maxCartItemLimit,
            'minCartItemThreshold' => $minCartItemThreshold,
        ];

        $this->deliveryHelper->expects($this->once())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->dataMock->expects($this->once())
            ->method('getMaxCartLimitValue')
            ->willReturn($cartLimit);
        $this->cartMock->expects($this->once())
            ->method('getItemsCount')
            ->willReturn($quoteItemsCount);

        /** @var Observer|MockObject $eventObserver */
        $eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartProductAddComplete->execute($eventObserver);
    }

    /**
     * TestExecuteWithFcl
     *
     * @return null
     */
    public function testExecuteWithFcl()
    {
        $minCartItemThreshold = 1;
        $quoteItemsCount = 2;
        $maxCartItemLimit = 2;
        $cartLimit = [
            'maxCartItemLimit' => $maxCartItemLimit,
            'minCartItemThreshold' => $minCartItemThreshold,
        ];

        $this->deliveryHelper->expects($this->once())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->dataMock->expects($this->any())
            ->method('getMaxCartLimitValue')
            ->willReturn($cartLimit);
        $this->cartMock->expects($this->any())
            ->method('getItemsCount')
            ->willReturn($quoteItemsCount);

        /** @var Observer|MockObject $eventObserver */
        $eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertNull($this->cartProductAddComplete->execute($eventObserver));
    }
}
