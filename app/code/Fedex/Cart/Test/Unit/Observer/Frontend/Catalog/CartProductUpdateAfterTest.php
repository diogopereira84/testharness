<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Observer\Frontend\Catalog;

use Fedex\Cart\Observer\Frontend\Catalog\CartProductUpdateAfter;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Checkout\Model\Cart;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\Message\ManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;

class CartProductUpdateAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;
    
    /**
     * @var Cart|MockObject
     */
    protected $cartModel;

    /**
     * @var FXORate|MockObject
     */
    protected $fxorateHelper;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $managerInterface;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * @var CartProductUpdateAfter|MockObject
     */
    protected $cartProductUpdateAfter;

    /**
     * @var InstoreConfig|MockObject
     */
    private InstoreConfig|MockObject $instoreConfigMock;

    /**
     * @var FXORateQuote|MockObject
     */
    private FXORateQuote|MockObject $fxoRateQuoteMock;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {

        $this->cartModel = $this->getMockBuilder(Cart::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxorateHelper = $this->getMockBuilder(FXORate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerInterface = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->objectManager = new ObjectManager($this);
        $this->observerMock = $this->createMock(Observer::class);

        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);
        $this->fxoRateQuoteMock = $this->createMock(FXORateQuote::class);

        $this->cartProductUpdateAfter = $this->objectManager->getObject(
            CartProductUpdateAfter::class,
            [
                'fxoRateHelper' => $this->fxorateHelper,
                'fxoRateQuote' => $this->fxoRateQuoteMock,
                'messageManager' => $this->managerInterface,
                'toggleConfig' => $this->toggleConfig,
                'instoreConfig' => $this->instoreConfigMock
            ]
        );
    }

    /**
     * Test execute with Invalid coupon
     *
     * @return null
     */
    public function testExecuteFxoRate()
    {
        $orderData = [
            'coupon_code' => 'MGT001',
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.INVALID',
                ],
            ],
        ];
        $varienObject = new DataObject();
        $varienObject->setData($orderData);

        $this->observerMock->expects($this->any())->method('getData')->willReturn($this->cartModel);
        $this->cartModel->expects($this->any())->method('getData')->willReturn($varienObject);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->fxorateHelper->expects($this->any())->method('getFXORate')->willReturn($varienObject);

        $this->assertEquals(null, $this->cartProductUpdateAfter->execute($this->observerMock));
    }

    /**
     * Test execute with minimum purchase required
     *
     * @return null
     */
    public function testExecuteWithGraphQlFujitsuResponseException()
    {
        $orderData = [
            'coupon_code' => 'MGT001',
            'alerts' => [
                0 => [
                    'code' => 'MINIMUM.PURCHASE.REQUIRED',
                ],
            ],
        ];

        $varienObject = new DataObject();
        $varienObject->setData($orderData);
        $this->observerMock->expects($this->any())->method('getData')->willReturn($this->cartModel);
        $this->cartModel->expects($this->any())->method('getData')->willReturn($varienObject);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $exception = new GraphQlFujitsuResponseException(__("Some message"));
        $this->fxoRateQuoteMock->expects($this->any())->method('getFXORateQuote')->willThrowException($exception);

        $this->instoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->cartProductUpdateAfter->execute($this->observerMock);
    }

    /**
     * Test execute with minimum purchase required
     *
     * @return null
     */
    public function testEproCustomerUsesGetFXORate(): void
    {
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);

        $cartData = new \Magento\Framework\DataObject(['quote' => $quote]);
        $this->observerMock
            ->expects($this->once())
            ->method('getData')
            ->with('cart')
            ->willReturn($cartData);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->fxorateHelper
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->fxorateHelper
            ->expects($this->once())
            ->method('getFXORate')
            ->with($quote);

        $result = $this->cartProductUpdateAfter->execute($this->observerMock);

        $this->assertNull($result);
    }
}
