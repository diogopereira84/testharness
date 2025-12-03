<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Unit\Plugin;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\CartItemPrices;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Plugin\CartItemPricesPlugin;

class CartItemPricesPluginTest extends TestCase
{
    private const PRODUCT_LINE_PRICE = 'productLinePrice';

    private const PRODUCT_RETAIL_PRICE = 'productRetailPrice';

    private const PRODUCT_LINE_ADDITIONAL_DATA_AMOUNT = 15.00;

    private const PRODUCT_RETAIL_ADDITIONAL_DATA_AMOUNT = 13.00;

    private const DISCOUNT_AMOUNT = 2.00;

    public function testAfterResolve()
    {
        $subject = $this->createMock(CartItemPrices::class);
        $result = ['price' => ['value' => 10.00], 'total_item_discount' => ['value' => 0.00]];
        $field = $this->createMock(Field::class);
        $info = $this->createMock(ResolveInfo::class);
        $value = [
            'model' => $this->createCartItemMock(),
        ];

        $requestQueryValidator = $this->createMocK(RequestQueryValidator::class);
        $requestQueryValidator->expects($this->once())
            ->method('isGraphQl')
            ->willReturn(true);
        $cartItemPricesPlugin = new CartItemPricesPlugin($requestQueryValidator);

        $updatedResult = $cartItemPricesPlugin->afterResolve($subject, $result, $field, null, $info, $value, null);

        $this->assertEquals(
            self::PRODUCT_LINE_ADDITIONAL_DATA_AMOUNT,
            $updatedResult['row_total_including_discounts']['value']
        );
        $this->assertEquals(self::PRODUCT_RETAIL_ADDITIONAL_DATA_AMOUNT, $updatedResult['row_total']['value']);
        $this->assertEquals(self::DISCOUNT_AMOUNT, $updatedResult['total_item_discount']['value']);
    }

    public function testAfterResolveIfGraphqlIsFalse()
    {
        $subject = $this->createMock(CartItemPrices::class);
        $result = ['price' => ['value' => 10.00], 'total_item_discount' => ['value' => 0.00]];
        $field = $this->createMock(Field::class);
        $info = $this->createMock(ResolveInfo::class);
        $value = [
            'model' => $this->createCartItemMock(),
        ];

        $requestQueryValidator = $this->createMocK(RequestQueryValidator::class);
        $requestQueryValidator->expects($this->once())
            ->method('isGraphQl')
            ->willReturn(false);
        $cartItemPricesPlugin = new CartItemPricesPlugin($requestQueryValidator);

        $updatedResult = $cartItemPricesPlugin->afterResolve($subject, $result, $field, null, $info, $value, null);

        $this->assertEquals($result, $updatedResult);
    }

    private function createCartItemMock()
    {
        $cartItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods(['getAdditionalData', 'getDiscount'])
            ->getMock();

        $cartItem->method('getAdditionalData')->willReturn(
            json_encode([
                self::PRODUCT_LINE_PRICE => self::PRODUCT_LINE_ADDITIONAL_DATA_AMOUNT,
                self::PRODUCT_RETAIL_PRICE => self::PRODUCT_RETAIL_ADDITIONAL_DATA_AMOUNT
            ])
        );

        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuoteCurrencyCode'])
            ->getMock();

        $quote->method('getQuoteCurrencyCode')->willReturn('USD');
        $cartItem->method('getQuote')->willReturn($quote);

        $cartItem->method('getDiscount')
            ->willReturn(self::DISCOUNT_AMOUNT);

        return $cartItem;
    }
}
