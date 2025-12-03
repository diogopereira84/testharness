<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Plugin\Model;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Plugin\Model\TotalsInformationManagementPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\TotalsInformationManagement;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class TotalsInformationManagementPluginTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected const GET_SUBTOTAL = 'getSubTotal';
    protected const SET_SUBTOTAL = 'setSubTotal';
    protected const GET_GRANDTOTAL = 'getGrandTotal';
    protected const SET_GRANDTOTAL = 'setGrandTotal';
    protected const GET_ITEMS_COUNT = 'getItemsCount';
    protected const TX_FLAT = 'TX_Flat';
    /**
     * @var \Fedex\Cart\Plugin\Model\TotalsInformationManagementPlugin
     */
    private $plugin;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartRepository;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfig;

    /**
     * @var \Magento\Checkout\Model\TotalsInformationManagement|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalsInformationManagement;

    /**
     * @var \Magento\Quote\Api\Data\TotalsInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalsInterface;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quote;

    protected function setUp(): void
    {
        $this->totalsInformationManagement = $this->createMock(TotalsInformationManagement::class);
        $this->totalsInterface = $this->createMock(TotalsInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods([
                self::GET_SUBTOTAL,
                self::GET_GRANDTOTAL,
                self::GET_ITEMS_COUNT
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->plugin = $this->objectManager->getObject(
            TotalsInformationManagementPlugin::class,
            [
                'cartRepository' => $this->cartRepository,
                'logger' => $this->logger,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    public function testAfterCalculateWhenToggleIsOn()
    {
        $cartId = 1;
        $originalSubtotal = 100.00;
        $originalGrandTotal = 120.00;

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn(true);

        $this->cartRepository->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($this->quote);

        $this->quote->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($originalSubtotal);

        $this->quote->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($originalGrandTotal);

        $this->totalsInterface->expects($this->once())
            ->method('setSubtotal')
            ->with($originalSubtotal);

        $this->totalsInterface->expects($this->once())
            ->method('setGrandTotal')
            ->with($originalGrandTotal);

        $result = $this->plugin->aftercalculate($this->totalsInformationManagement, $this->totalsInterface, $cartId);

        $this->assertEquals($this->totalsInterface, $result);
    }

    public function testAfterCalculateWhenToggleIsOff()
    {
        $cartId = 1;

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn(false);

        $this->cartRepository->expects($this->never())
            ->method('get');

        $this->logger->expects($this->never())
            ->method('info');

        $result = $this->plugin->aftercalculate($this->totalsInformationManagement, $this->totalsInterface, $cartId);
        $this->assertEquals($this->totalsInterface, $result);
    }

    public function testAfterCalculateWithEmptyCart()
    {
        $cartId = 1;

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn(true);

        $this->cartRepository->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($this->quote);

        $this->quote->expects($this->once())
            ->method('getItemsCount')
            ->willReturn(0);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Totals calculation is not applicable to empty cart'));

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Totals calculation is not applicable to empty cart');
        $this->plugin->aftercalculate($this->totalsInformationManagement, $this->totalsInterface, $cartId);
    }
}
