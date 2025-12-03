<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Unit\Plugin;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Plugin\CartPricesPlugin;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;
use Magento\QuoteGraphQl\Model\Resolver\CartPrices;
use PHPUnit\Framework\TestCase;
use Fedex\InStoreConfigurations\Api\ConfigInterface;

class CartPricesPluginTest extends TestCase
{
    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     * The TotalsCollector instance used for collecting and calculating totals in the cart.
     */
    private $totalsCollector;
    /**
     * @var \Fedex\CartGraphQl\Model\RequestQueryValidator
     * An instance of the RequestQueryValidator used for validating GraphQL queries in the test cases.
     */
    private $requestQueryValidator;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     * Mock instance of the CartRepositoryInterface used for testing purposes.
     */
    private $cartIntegrationRepositoryInterface;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * Mock object for configuration settings used in the test.
     */
    private $configMock;
    /**
     * @var \Fedex\CartGraphQl\Plugin\CartPricesPlugin
     * Instance of the CartPricesPlugin being tested.
     */
    private $cartPricesPlugin;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->totalsCollector = $this->createMock(TotalsCollector::class);
        $this->requestQueryValidator = $this->createMocK(RequestQueryValidator::class);
        $this->cartIntegrationRepositoryInterface = $this->createMocK(CartIntegrationRepositoryInterface::class);
        $this->configMock = $this->createMocK(ConfigInterface::class);
        $this->cartPricesPlugin = new CartPricesPlugin(
            $this->totalsCollector,
            $this->requestQueryValidator,
            $this->cartIntegrationRepositoryInterface,
            $this->configMock
        );
    }

    /**
     * @return void
     */
    public function testAfterResolve()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->addMethods(['getQuoteCurrencyCode', 'getBaseSubtotalWithDiscount'])
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $result = ['model' => $quote];
        $field = $this->createMock(Field::class);
        $context = $this->createMock(ContextInterface::class);
        $info = $this->createMock(ResolveInfo::class);
        $value = null;
        $args = null;

        $this->requestQueryValidator->expects($this->once())
            ->method('isGraphQl')->willReturn(true);
        $quote->expects($this->once())->method('getQuoteCurrencyCode')->willReturn('USD');

        $cartIntegrationItemInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRaqNetAmount'])
            ->getMockForAbstractClass();
        $cartIntegrationItemInterface->expects($this->once())->method('getRaqNetAmount')->willReturn(98.78);
        $quote->expects($this->once())->method('getId')->willReturn('8');
        $this->cartIntegrationRepositoryInterface->expects($this->once())->method('getByQuoteId')
            ->willReturn($cartIntegrationItemInterface);
        $cartTotals = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomTaxAmount', 'getBaseSubtotal'])
            ->getMockForAbstractClass();
        $cartTotals->expects($this->once())->method('getBaseSubtotal')->willReturn(null);

        $cartTotals->expects($this->once())->method('getCustomTaxAmount')->willReturn(10.00);

        $this->totalsCollector->expects($this->once())
            ->method('collectQuoteTotals')
            ->with($quote)
            ->willReturn($cartTotals);
        $this->configMock->expects($this->once())
            ->method('canApplyShippingDiscount')
            ->willReturn(true);
        $quote->expects($this->any())->method('getBaseSubtotalWithDiscount')->willReturn(5);
        $actualResult = $this->cartPricesPlugin->afterResolve(
            $this->createMock(CartPrices::class),
            $result,
            $field,
            $context,
            $info,
            $value,
            $args
        );
        $expectedResult = [
            'model' => $quote,
            'subtotal_excluding_tax' => ['value' => null, 'currency' => 'USD'],
            'subtotal_with_discount_excluding_tax' => ['value' => 98.78, 'currency' => 'USD'],
            'applied_taxes' => [
                [
                    'label' => 'Fedex Tax Amount',
                    'amount' => ['value' => 10.00, 'currency' => 'USD'],
                ],
            ],
            'grand_total' => ['value' => null, 'currency' => 'USD'],
        ];

        $this->assertEquals($expectedResult, $actualResult);
    }

     /**
      * @return void
      */
    public function testAfterResolveIfGraphqlIsFalse()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->addMethods(['getQuoteCurrencyCode'])
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $result = ['model' => $quote];
        $field = $this->createMock(Field::class);
        $context = $this->createMock(ContextInterface::class);
        $info = $this->createMock(ResolveInfo::class);
        $value = null;
        $args = null;

        $this->requestQueryValidator->expects($this->any())
            ->method('isGraphQl')->willReturn(false);
        $quote->expects($this->any())->method('getQuoteCurrencyCode')->willReturn('USD');

        $cartIntegrationItemInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRaqNetAmount'])
            ->getMockForAbstractClass();
        $cartIntegrationItemInterface->expects($this->any())->method('getRaqNetAmount')->willReturn(98.78);
        $quote->expects($this->any())->method('getId')->willReturn('8');
        $this->cartIntegrationRepositoryInterface->expects($this->any())->method('getByQuoteId')
            ->willReturn($cartIntegrationItemInterface);
        $cartTotals = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomTaxAmount', 'getBaseSubtotal'])
            ->getMockForAbstractClass();
        $cartTotals->expects($this->any())->method('getBaseSubtotal')->willReturn(null);
        $cartTotals->expects($this->any())->method('getCustomTaxAmount')->willReturn(10.00);

        $this->totalsCollector->expects($this->any())
            ->method('collectQuoteTotals')
            ->with($quote)
            ->willReturn($cartTotals);

        $actualResult = $this->cartPricesPlugin->afterResolve(
            $this->createMock(CartPrices::class),
            $result,
            $field,
            $context,
            $info,
            $value,
            $args
        );

        $this->assertEquals($result, $actualResult);
    }
}
