<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Observer\Frontend\Catalog;

use Fedex\Cart\Observer\Frontend\Catalog\RefereshQuote;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\CartFactory;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class RefereshQuoteTest extends TestCase
{
    /**
     * @var FXORate|MockObject
     */
    protected $fxoRateHelper;

    /**
     * @var CartFactory|MockObject
     */
    protected $cartFactory;

    /**
     * @var RefereshQuote|MockObject
     */
    protected $refereshQuote;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var Session|MockObject
     */
    protected $checkoutSession;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * @var \Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle|MockObject
     */
    protected $addToCartPerformanceOptimizationToggle;

    /**
     * @var \Fedex\EnvironmentManager\Model\Config\RateQuoteOptimizationToggle|MockObject
     */
    protected $rateQuoteOptimizationToggle;

    /**
     * @var \Fedex\FXOPricing\Model\FXORateQuoteApi|MockObject
     */
    protected $fxoRateQuoteApi;

    /**
     * @var \Fedex\Cart\Helper\Data|MockObject
     */
    protected $cartDataHelper;

    /**
     * @var \Fedex\FXOPricing\Model\FXORateQuote|MockObject
     */
    protected $fxoRateQuote;

    /**
     * Function setUp
     */
    protected function setUp(): void
    {
        $this->fxoRateHelper = $this->getMockBuilder(FXORate::class)
            ->setMethods(['getFXORate', 'isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods([
                'create',
                'getQuote',
                'getShippingAddress',
                'getShippingMethod',
                'getCustomTaxAmount',
                'setGrandTotal',
                'getGrandTotal',
                'setBaseGrandTotal',
                'setCustomTaxAmount',
                'setShippingMethod',
                'setData',
                'getData'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->setMethods([
                'getProductionLocationId',
                'unsProductionLocationId',
                'getRemoveFedexAccountNumber',
                'getAppliedFedexAccNumber',
                'getRemoveFedexAccountNumberWithSi',
                'getQuote'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->addToCartPerformanceOptimizationToggle = $this
            ->getMockBuilder(\Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle::class)
            ->setMethods(['isActive'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rateQuoteOptimizationToggle = $this
            ->getMockBuilder(\Fedex\EnvironmentManager\Model\Config\RateQuoteOptimizationToggle::class)
            ->setMethods(['isActive'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoRateQuoteApi = $this->getMockBuilder(\Fedex\FXOPricing\Model\FXORateQuoteApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartDataHelper = $this->getMockBuilder(\Fedex\Cart\Helper\Data::class)
            ->setMethods(['getDefaultFedexAccountNumber'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoRateQuote = $this->getMockBuilder(\Fedex\FXOPricing\Model\FXORateQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORateQuote'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->observerMock = $this->createMock(Observer::class);

        $this->refereshQuote = $this->objectManager->getObject(
            RefereshQuote::class,
            [
                'fxoRateHelper' => $this->fxoRateHelper,
                'cartFactory' => $this->cartFactory,
                'checkoutSession' => $this->checkoutSession,
                'toggleConfig' => $this->toggleConfig,
                'addToCartPerformanceOptimizationToggle' => $this->addToCartPerformanceOptimizationToggle,
                'rateQuoteOptimizationToggle' => $this->rateQuoteOptimizationToggle,
                'fXORateQuoteApi' => $this->fxoRateQuoteApi,
                'cartDataHelper' => $this->cartDataHelper,
                'fxoRateQuote' => $this->fxoRateQuote
            ]
        );
    }

    /**
     * Test execute()
     *
     */
    public function testExecute()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getCustomTaxAmount')->willReturn(1);
        $this->cartFactory->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setCustomTaxAmount')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('getProductionLocationId')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsProductionLocationId')->willReturnSelf();
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->fxoRateHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->assertEquals(null, $this->refereshQuote->execute($this->observerMock));
    }

    /**
     * Test execute with no shipping method
     *
     */
    public function testSessQuote(): void
    {
        $this->addToCartPerformanceOptimizationToggle->method('isActive')->willReturn(true);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $address = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $address->expects($this->once())->method('getShippingMethod')->willReturn('flatrate');
        $quote->method('getShippingAddress')->willReturn($address);

        $this->checkoutSession->expects($this->atLeastOnce())->method('getQuote')->willReturn($quote);
        $this->cartFactory->expects($this->never())->method('create');

        $result = $this->refereshQuote->execute($this->observerMock);
        $this->assertNull($result);
    }

    /**
     * Test execute with shipping method
     *
     */
    public function testFactoryQuote(): void
    {
        $this->addToCartPerformanceOptimizationToggle->method('isActive')->willReturn(true);

        $this->checkoutSession->expects($this->once())->method('getQuote')->willReturn(null);

        $cart = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $address = $this->createMock(\Magento\Quote\Model\Quote\Address::class);

        $address->expects($this->once())->method('getShippingMethod')->willReturn('tablerate');
        $quote->method('getShippingAddress')->willReturn($address);

        $cart->method('getQuote')->willReturn($quote);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($cart);

        $result = $this->refereshQuote->execute($this->observerMock);
        $this->assertNull($result);
    }

    /**
     * Test execute with no quote
     *
     * @return void
     */
    public function testNoQuote(): void
    {
        $this->addToCartPerformanceOptimizationToggle->method('isActive')->willReturn(true);
        $this->checkoutSession->method('getQuote')->willReturn(null);

        $cart = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $cart->method('getQuote')->willReturn(null);
        $this->cartFactory->method('create')->willReturn($cart);

        $this->fxoRateHelper->expects($this->never())->method('isEproCustomer');

        $result = $this->refereshQuote->execute($this->observerMock);
        $this->assertNull($result, 'execute() should exit early when no quote is present');
    }

    /**
     * Test execute with Non-Epro commercial
     *
     * @return void
     */
    public function testExecuteWithNonEproCommercial()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getCustomTaxAmount')->willReturn(1);
        $this->cartFactory->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setCustomTaxAmount')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('getProductionLocationId')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsProductionLocationId')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('getRemoveFedexAccountNumberWithSi')->willReturn(false);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->fxoRateHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);

        $this->assertEquals(null, $this->refereshQuote->execute($this->observerMock));
    }

    /**
     * Test execute()
     *
     */
    public function testExecuteWithToggle()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getCustomTaxAmount')->willReturn(1);
        $this->cartFactory->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setCustomTaxAmount')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('getProductionLocationId')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsProductionLocationId')->willReturnSelf();
        $this->assertEquals(null, $this->refereshQuote->execute($this->observerMock));
    }

    /**
     * Test execute with toggle and remove and quote false
     *
     * @return volid
     */
    public function testExecuteWithToggleAndRemoveQuoteFalse()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getCustomTaxAmount')->willReturn(1);
        $this->cartFactory->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setCustomTaxAmount')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getData')->willReturn(0);
        $this->checkoutSession->expects($this->any())->method('getAppliedFedexAccNumber')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('getProductionLocationId')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsProductionLocationId')->willReturnSelf();
        $this->assertEquals(null, $this->refereshQuote->execute($this->observerMock));
    }

    /**
     * Test execute()
     *
     */
    public function testExecuteWithToggleOff()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willreturn(false);
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingMethod')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getCustomTaxAmount')->willReturn(1);
        $this->cartFactory->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setCustomTaxAmount')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('getProductionLocationId')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsProductionLocationId')->willReturnSelf();
        $this->assertEquals(null, $this->refereshQuote->execute($this->observerMock));
    }

    /**
     * Test that default Fedex account number is applied when conditions are met
     *
     * @dataProvider getDefaultAccountNumberScenarioProvider
     * @param bool $removeFedexAccountNumber
     * @param bool $appliedFedexAccNumber
     * @param string|null $quoteFedexAccountNumber
     * @param bool $shouldSetDefault
     * @return void
     */
    public function testApplyDefaultFedexAccountNumber(
        bool $removeFedexAccountNumber,
        bool $appliedFedexAccNumber,
        ?string $quoteFedexAccountNumber,
        bool $shouldSetDefault
    ): void {
        $this->addToCartPerformanceOptimizationToggle
            ->method('isActive')
            ->willReturn(true);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(RefereshQuote::XML_PATH_TIGER_D207139_TOGGLE)
            ->willReturn(true);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $address = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $address->method('getShippingMethod')->willReturn('flatrate');
        $quote->method('getShippingAddress')->willReturn($address);

        $quote->method('getData')
            ->willReturnMap([
                ['fedex_account_number', $quoteFedexAccountNumber]
            ]);

        $this->checkoutSession
            ->expects($this->atLeastOnce())
            ->method('getQuote')
            ->willReturn($quote);

        $this->checkoutSession
            ->method('getRemoveFedexAccountNumber')
            ->willReturn($removeFedexAccountNumber);

        $this->checkoutSession
            ->method('getAppliedFedexAccNumber')
            ->willReturn($appliedFedexAccNumber);

        $defaultAccountNumber = '123456789';

        if ($shouldSetDefault) {
            $this->cartDataHelper
                ->expects($this->once())
                ->method('getDefaultFedexAccountNumber')
                ->willReturn($defaultAccountNumber);

            $quote->expects($this->once())
                ->method('setData')
                ->with('fedex_account_number', $defaultAccountNumber);
        } else {
            $this->cartDataHelper
                ->expects($this->never())
                ->method('getDefaultFedexAccountNumber');

            $quote->expects($this->never())
                ->method('setData')
                ->with('fedex_account_number', $this->anything());
        }

        $this->fxoRateHelper
            ->method('isEproCustomer')
            ->willReturn(false);

        $this->rateQuoteOptimizationToggle
            ->method('isActive')
            ->willReturn(false);

        $this->fxoRateQuote
            ->method('getFXORateQuote')
            ->willReturn(null);

        $result = $this->refereshQuote->execute($this->observerMock);
        $this->assertNull($result, 'execute() should return null (void)');
    }

    /**
     * Data provider for default account number test scenarios
     *
     * @return array
     */
    public function getDefaultAccountNumberScenarioProvider(): array
    {
        return [
            'both removeFedex and appliedFedex are false' => [
                'removeFedexAccountNumber' => false,
                'appliedFedexAccNumber' => false,
                'quoteFedexAccountNumber' => 'existing-number',
                'shouldSetDefault' => true
            ],
            'fedexAccountNumber and removeFedex are false' => [
                'removeFedexAccountNumber' => false,
                'appliedFedexAccNumber' => true,
                'quoteFedexAccountNumber' => null,
                'shouldSetDefault' => true
            ],
            'removeFedex is true, appliedFedex is false' => [
                'removeFedexAccountNumber' => true,
                'appliedFedexAccNumber' => false,
                'quoteFedexAccountNumber' => 'existing-number',
                'shouldSetDefault' => false
            ],
            'both removeFedex and appliedFedex are true' => [
                'removeFedexAccountNumber' => true,
                'appliedFedexAccNumber' => true,
                'quoteFedexAccountNumber' => 'existing-number',
                'shouldSetDefault' => false
            ],
            'fedexAccountNumber exists and removeFedex is true' => [
                'removeFedexAccountNumber' => true,
                'appliedFedexAccNumber' => false,
                'quoteFedexAccountNumber' => 'existing-number',
                'shouldSetDefault' => false
            ]
        ];
    }

    /**
     * Test rate quote optimization toggle is used for non-Epro customers
     *
     * @return void
     */
    public function testRateQuoteOptimizationForNonEproCustomers(): void
    {
        $this->addToCartPerformanceOptimizationToggle
            ->method('isActive')
            ->willReturn(true);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $address = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $address->method('getShippingMethod')->willReturn('flatrate');
        $quote->method('getShippingAddress')->willReturn($address);

        $quote->method('getData')
            ->willReturnMap([
                ['fedex_account_number', 'existing-account-number'],
                ['custom_tax_amount', 0]
            ]);

        $this->checkoutSession
            ->expects($this->atLeastOnce())
            ->method('getQuote')
            ->willReturn($quote);

        $this->checkoutSession
            ->method('getRemoveFedexAccountNumber')
            ->willReturn(true);

        $this->checkoutSession
            ->method('getAppliedFedexAccNumber')
            ->willReturn(true);

        $this->fxoRateHelper
            ->method('isEproCustomer')
            ->willReturn(false);

        $this->rateQuoteOptimizationToggle
            ->method('isActive')
            ->willReturn(true);

        $this->fxoRateQuoteApi
            ->expects($this->once())
            ->method('getFXORateQuote')
            ->with($quote);

        $this->fxoRateQuote
            ->expects($this->never())
            ->method('getFXORateQuote');

        $result = $this->refereshQuote->execute($this->observerMock);
        $this->assertNull($result);
    }
}
