<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Test\Unit\ViewModel;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\SubmitOrderSidebar\ViewModel\OrderSuccess;

class OrderSuccessTest extends \PHPUnit\Framework\TestCase
{
    protected $cartMock;
    protected $item;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderSuccess;
    public const XML_PATH_UPSELLIT_TRACKING_SCRIPT = 'web/upsellit/upsellit_order_success_script';

    public const XML_PATH_ACTIVE_UPSELLIT = 'web/upsellit/upsellit_active';

    /**
     * @var ToggleConfig
     */
    protected $toggleConfigMock;

    /**
     * @var CartFactory
     */
    protected $cartFactoryMock;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterfaceMock;

    /**
     * @var SsoConfiguration
     */
    protected $ssoConfigurationMock;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->item = $this->getMockBuilder(Item::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue', 'isSetFlag'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isRetail'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->orderSuccess = $this->objectManager->getObject(
            OrderSuccess::class,
            [
                'toggleConfig'                 => $this->toggleConfigMock,
                'cartFactory'                  => $this->cartFactoryMock,
                'scopeConfigInterface'         => $this->scopeConfigInterfaceMock,
                'ssoConfiguration'             => $this->ssoConfigurationMock
            ]
        );
    }

    /**
     * Test getQuoteId.
     *
     * @return int
     */
    public function testGetQuoteId()
    {
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->item);
        $this->item->expects($this->any())->method('getId')->willReturn(123);
        $expectedResult = $this->orderSuccess->getQuoteId();
        $this->assertEquals(123, $expectedResult);
    }

    /**
     * Test getUpSellItTrackingScript.
     *
     * @return void
     */
    public function testGetUpSellItTrackingScript()
    {
        $expectedScript = '<script> orderId = "1234"; orderSubtotal = "100"; orderCurrency = "USD";</script>';

        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(self::XML_PATH_UPSELLIT_TRACKING_SCRIPT)
            ->willReturn($expectedScript);

        $upSellItTrackingScript = $this->orderSuccess->getUpSellItTrackingScript();
        $this->assertEquals($expectedScript, $upSellItTrackingScript);
    }

    /**
     * Test getIsRetail
     *
     * @return void
     */
    public function testGetIsRetail()
    {
        $expectedResult = true;

        $this->ssoConfigurationMock->expects($this->once())
            ->method('isRetail')
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->orderSuccess->getIsRetail());
    }

    /**
     * Test getIsUpsellitActive
     *
     * @return void
     */
    public function testGetIsUpsellitActive()
    {
        $expectedResult = true;

        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('isSetFlag')
            ->with(self::XML_PATH_ACTIVE_UPSELLIT)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->orderSuccess->getIsUpsellitActive());
    }
}
