<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Plugin;

use Magento\NegotiableQuote\Model\Quote\Totals;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\Plugin\TotalsTaxPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote;

/**
 * Unit Test Class for TotalsTaxPlugin
 */
class TotalsTaxPluginTest extends TestCase
{
    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    private $adminConfigHelper;

    /**
     * @var TotalsTaxPlugin $totalsTaxPlugin
     */
    private $totalsTaxPlugin;

     /**
      * @var Totals $totals
      */
    private $totals;

     /**
      * @var Quote $quoteMock
      */
    private $quoteMock;
    
    /**
     * Sets up the test environment for the TotalsTaxPluginTest class.
     */
    public function setUp(): void
    {
        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMagentoQuoteDetailEnhancementToggleEnabled'])
            ->getMock();

        $this->totals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->quoteMock = $this->createMock(Quote::class);

        $objectManagerHelper = new ObjectManager($this);

        $this->totalsTaxPlugin = $objectManagerHelper->getObject(
            TotalsTaxPlugin::class,
            [
                'adminConfigHelper' => $this->adminConfigHelper
            ]
        );
    }

    /**
     * Magento Quote Detail Enhancement toggle is disabled.
     *
     * @return void
     */
    public function testAfterGetTaxValueToggleDisabled(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(false);

        $finalResult = $this->totalsTaxPlugin->afterGetTaxValue(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals($result, $finalResult);
    }

    /**
     * Tests the `afterGetTaxValue` method of the TotalsTaxPlugin class when the
     * Magento Quote Detail Enhancement toggle is enabled and a custom tax amount
     * is set on the quote.
     *
     * This test verifies that the plugin correctly overrides the tax value with
     * the custom tax amount when the toggle is enabled.
     *
     * @return void
     */
    public function testAfterGetTaxValueToggleEnabledWithCustomTaxAmount(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->totals->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getData')->with('custom_tax_amount')->willReturn(50.0);

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(true);

        $finalResult = $this->totalsTaxPlugin->afterGetTaxValue(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals(50.0, $finalResult);
    }

    /**
     * Tests the `afterGetTaxValue` method of the TotalsTaxPlugin class.
     *
     * This test verifies the behavior of the `afterGetTaxValue` method when:
     * - The Magento Quote Detail Enhancement toggle is enabled.
     * - The custom tax amount is not set (null).
     *
     * The test ensures that the method returns the original tax value (`$result`)
     * without any modifications when the above conditions are met.
     *
     * @return void
     */
    public function testAfterGetTaxValueToggleEnabledWithoutCustomTaxAmount(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->totals->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getData')->with('custom_tax_amount')->willReturn(null);

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(true);

        $finalResult = $this->totalsTaxPlugin->afterGetTaxValue(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals($result, $finalResult);
    }

    /**
     * Magento Quote Detail Enhancement toggle is disabled.
     *
     * @return void
     */
    public function testAfterGetTotalCost(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(false);

        $finalResult = $this->totalsTaxPlugin->afterGetTotalCost(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals($result, $finalResult);
    }

    /**
     * Tests the `afterGetTotalCost` method of the TotalsTaxPlugin class when the
     * Magento Quote Detail Enhancement toggle is enabled and a custom tax amount
     * is set on the quote.
     *
     * This test verifies that the plugin correctly overrides the tax value with
     * the custom tax amount when the toggle is enabled.
     *
     * @return void
     */
    public function testAfterGetTotalCostValueToggleEnabled(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->totals->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getData')->with('subtotal')->willReturn(50.0);

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(true);

        $finalResult = $this->totalsTaxPlugin->afterGetTotalCost(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals(50.0, $finalResult);
    }

    /**
     * Magento Quote Detail Enhancement toggle is disabled.
     *
     * @return void
     */
    public function testAfterGetSubtotal(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(false);

        $finalResult = $this->totalsTaxPlugin->afterGetSubtotal(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals($result, $finalResult);
    }

    /**
     * Tests the `afterGetSubtotal` method of the TotalsTaxPlugin class when the
     * Magento Quote Detail Enhancement toggle is enabled
     * is set on the quote.
     *
     * This test verifies that the plugin correctly overrides the tax value with
     *
     * @return void
     */
    public function testAfterGetSubtotalValueToggleEnabled(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->totals->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getData')->with('discount')->willReturn(50.0);

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(true);

        $finalResult = $this->totalsTaxPlugin->afterGetSubtotal(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals(50.0, $finalResult);
    }

    /**
     * Magento Quote Detail Enhancement toggle is disabled.
     *
     * @return void
     */
    public function testAfterGetCatalogTotalPrice(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(false);

        $finalResult = $this->totalsTaxPlugin->afterGetCatalogTotalPrice(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals($result, $finalResult);
    }

    /**
     * Tests the `afterGetSubtotal` method of the TotalsTaxPlugin class when the
     * Magento Quote Detail Enhancement toggle is enabled
     * is set on the quote.
     *
     * This test verifies that the plugin correctly overrides the tax value with
     *
     * @return void
     */
    public function testAfterGetCatalogTotalPriceToggleEnabled(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->totals->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getData')->with('subtotal_with_discount')->willReturn(50.0);

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(true);

        $finalResult = $this->totalsTaxPlugin->afterGetCatalogTotalPrice(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals(50.0, $finalResult);
    }

    /**
     * Magento Quote Detail Enhancement toggle is disabled.
     *
     * @return void
     */
    public function testAfterGetCatalogTotalPriceWithoutTax(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(false);

        $finalResult = $this->totalsTaxPlugin->afterGetCatalogTotalPriceWithoutTax(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals($result, $finalResult);
    }

    /**
     * Tests the `afterGetCatalogTotalPriceWithoutTax` method of the TotalsTaxPlugin class when the
     * Magento Quote Detail Enhancement toggle is enabled
     * is set on the quote.
     *
     * This test verifies that the plugin correctly overrides the tax value with
     *
     * @return void
     */
    public function testAfterGetCatalogTotalPriceWithoutTaxToggleEnabled(): void
    {
        $result = 100.0;
        $useQuoteCurrency = false;

        $this->totals->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getData')->with('base_subtotal')->willReturn(50.0);

        $this->adminConfigHelper->method('isMagentoQuoteDetailEnhancementToggleEnabled')
            ->willReturn(true);

        $finalResult = $this->totalsTaxPlugin->afterGetCatalogTotalPriceWithoutTax(
            $this->totals,
            $result,
            $useQuoteCurrency
        );
        $this->assertEquals(50.0, $finalResult);
    }
}
