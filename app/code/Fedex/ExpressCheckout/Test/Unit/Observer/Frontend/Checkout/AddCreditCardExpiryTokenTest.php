<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\ExpressCheckout\Test\Unit\Observer\Frontend\Checkout;

use Fedex\ExpressCheckout\ViewModel\ExpressCheckout;
use Fedex\ExpressCheckout\Observer\Frontend\Checkout\AddCreditCardExpiryToken;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AddCreditCardExpiryTokenTest extends TestCase
{
    protected $observerMock;
    /**
     * @var ExpressCheckout
     */
    protected $expressCheckoutMock;
    /**
     * @var AddCreditCardExpiryToken
     */
    protected $addCreditCardExpiryToken;
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->expressCheckoutMock = $this->getMockBuilder(ExpressCheckout::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsFclCustomer', 'getCustomerProfileSessionWithExpiryToken'])
            ->getMock();
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction', 'getResponse'])
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);
        $this->addCreditCardExpiryToken = $this->objectManager->getObject(
            AddCreditCardExpiryToken::class,
            [
                'expressCheckout' => $this->expressCheckoutMock
            ]
        );
    }

    /**
     * @test testExecuteWithTrue()
     */
    public function testExecuteWithTrue()
    {
        $this->expressCheckoutMock->expects($this->once())
            ->method('getIsFclCustomer')
            ->willReturn('1');

        $this->assertNull($this->addCreditCardExpiryToken->execute($this->observerMock));
    }
}
