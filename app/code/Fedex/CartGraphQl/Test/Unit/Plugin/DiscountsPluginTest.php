<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Unit\Plugin;

use Fedex\CartGraphQl\Plugin\DiscountsPlugin;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;
use Magento\QuoteGraphQl\Model\Resolver\Discounts;
use PHPUnit\Framework\TestCase;
use Fedex\InStoreConfigurations\Api\ConfigInterface;

class DiscountsPluginTest extends TestCase
{
    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     * The TotalsCollector instance used for calculating quote totals in the test.
     */
    protected $totalsCollector;
    /**
     * @var \Fedex\CartGraphQl\Model\RequestQueryValidator
     * This property is used to validate the request query in the unit tests for the DiscountsPlugin.
     */
    protected $requestQueryValidator;
    /**
     * @var DiscountsPlugin
     * This property holds the instance of the DiscountsPlugin class being tested.
     * It is used to test the behavior and functionality of the DiscountsPlugin in unit tests.
     */
    protected $discountsPlugin;

    /**
     * @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $config;

    protected function setUp(): void
    {
        $this->totalsCollector = $this->createMock(TotalsCollector::class);
        $this->requestQueryValidator = $this->createMocK(RequestQueryValidator::class);
        $this->config = $this->createMocK(ConfigInterface::class);
        $this->discountsPlugin = new DiscountsPlugin($this->totalsCollector, $this->requestQueryValidator, $this->config);
    }

    public function testAfterResolve()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestQueryValidator->expects($this->once())
            ->method('isGraphQl')->willReturn(true);

        $result = null;
        $field = $this->createMock(Field::class);
        $context = $this->createMock(\Magento\Framework\GraphQl\Query\Resolver\ContextInterface::class);
        $info = $this->createMock(ResolveInfo::class);
        $value = ['model' => $quote];
        $args = null;

        $cartTotals = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFedexDiscountAmount'])
            ->getMockForAbstractClass();
        $cartTotals->expects($this->once())->method('getFedexDiscountAmount')->willReturn(5.00);

        $this->totalsCollector->expects($this->once())
            ->method('collectQuoteTotals')
            ->with($quote)
            ->willReturn($cartTotals);

        $actualResult = $this->discountsPlugin->afterResolve(
            $this->createMock(Discounts::class),
            $result,
            $field,
            $context,
            $info,
            $value,
            $args
        );

        $expectedResult = [
            'discounts' => [
                'label' => 'Fedex Discount Amount',
                'amount' => ['value' => 5.00, 'currency' => null],
            ],
        ];

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testAfterResolveIsGraphQlFalse()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestQueryValidator->expects($this->any())
            ->method('isGraphQl')->willReturn(false);

        $result = null;
        $field = $this->createMock(Field::class);
        $context = $this->createMock(\Magento\Framework\GraphQl\Query\Resolver\ContextInterface::class);
        $info = $this->createMock(ResolveInfo::class);
        $value = ['model' => $quote];
        $args = null;

        $cartTotals = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFedexDiscountAmount'])
            ->getMockForAbstractClass();
        $cartTotals->expects($this->any())->method('getFedexDiscountAmount')->willReturn(5.00);

        $this->totalsCollector->expects($this->any())
            ->method('collectQuoteTotals')
            ->with($quote)
            ->willReturn($cartTotals);

        $actualResult = $this->discountsPlugin->afterResolve(
            $this->createMock(Discounts::class),
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
