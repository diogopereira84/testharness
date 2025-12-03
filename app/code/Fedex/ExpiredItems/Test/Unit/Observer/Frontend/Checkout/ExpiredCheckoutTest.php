<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\ExpiredItems\Test\Unit\Observer\Frontend\Checkout;

use Fedex\ExpiredItems\Helper\ExpiredItem;
use Fedex\ExpiredItems\Observer\Frontend\Checkout\ExpiredCheckout;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Response;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Fedex\Base\Helper\Auth;

class ExpiredCheckoutTest extends TestCase
{
    protected $observerMock;
    /**
     * @var ExpiredItem
     */
    protected $expiredItemMock;

    /**
     * @var ExpiredCheckout
     */
    protected $expiredCheckoutMock;

    /**
     * @var ResponseFactory
     */
    protected $responseFactoryMock;

    /**
     * @var Response
     */
    protected $responseMock;

    /**
     * @var UrlInterface
     */
    protected $urlInterfaceMock;

    /**
     * @var AuthContext $httpContext
     */
    private $httpContext;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->expiredItemMock = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExpiredInstanceIds'])
            ->getMock();

        $this->responseFactoryMock = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect', 'sendResponse'])
            ->getMock();

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction', 'getResponse'])
            ->getMockForAbstractClass();

        $this->httpContext = $this->getMockBuilder(HttpContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->expiredCheckoutMock = $this->objectManager->getObject(
            ExpiredCheckout::class,
            [
                'expiredItemHelper' => $this->expiredItemMock,
                'responseFactory' => $this->responseFactoryMock,
                'urlInterface' => $this->urlInterfaceMock,
                'httpContext' => $this->httpContext,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * @test testExecuteWithTrue()
     */
    public function testExecuteWithTrue()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->expiredItemMock->expects($this->any())
            ->method('getExpiredInstanceIds')
            ->willReturn(['1', '2']);

        $this->urlInterfaceMock->expects($this->any())
            ->method('getUrl')
            ->willReturn('checkout/cart/index');

        $this->responseFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();

        $this->responseMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertIsObject($this->expiredCheckoutMock->execute($this->observerMock));
    }
}
