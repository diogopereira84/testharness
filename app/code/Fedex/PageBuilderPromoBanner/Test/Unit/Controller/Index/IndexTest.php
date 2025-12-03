<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Test\Unit\Controller\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Fedex\PageBuilderPromoBanner\Controller\Index\Index;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\Response\RedirectInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @runTestsInSeparateProcesses
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Http|MockObject
     */
    private $requestHttp;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Cart|MockObject
     */
    private $cart;

    /**
     * @var Session|MockObject
     */
    private $checkoutSession;

    /**
     * @var Index|MockObject
     */
    private $controllerIndex;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var RedirectInterface|MockObject
     */
    private $redirectInterface;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
                                ->setMethods(['create', 'setPath'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->requestHttp = $this->getMockBuilder(Http::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->cart = $this->getMockBuilder(Cart::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
                                ->setMethods(['setIsApplyCoupon'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
                                ->setMethods(['getParam'])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->quote = $this->getMockBuilder(Quote::class)
                                ->setMethods(['save', 'setCouponCode'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->redirectInterface = $this->getMockBuilder(RedirectInterface::class)
                                    ->setMethods(['getRefererUrl'])
                                    ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->controllerIndex = $this->objectManager->getObject(
            Index::class,
            [
                'context' => $this->context,
                'resRedirectFactory' => $this->resultRedirectFactory,
                'request' => $this->requestHttp,
                'cart' => $this->cart,
                'checkoutSession' => $this->checkoutSession,
                '_request' => $this->request,
                '_redirect' => $this->redirectInterface
            ]
        );
    }

    /**
     * Test Excute to apply coupon code
     *
     */
    public function testExecute()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn('AK234');
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('setCouponCode')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->redirectInterface->expects($this->any())->method('getRefererUrl')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setIsApplyCoupon')->willReturn(true);
       
        $this->assertNotNull($this->controllerIndex->execute());
    }
}
