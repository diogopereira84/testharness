<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Plugin\CartItemsPlugin;
use Mirakl\Connector\Model\Quote\Cache;
use Mirakl\GraphQl\Model\Resolver\CartItems;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class CartItemsPluginTest extends TestCase
{
    protected $cacheMock;
    protected $cartItemsPlugin;
    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(Cache::class);
        $this->cartItemsPlugin = new CartItemsPlugin($this->cacheMock);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testBeforeResolve(): void
    {
        $cartMock = $this->createMock(\Magento\Quote\Api\Data\CartInterface::class);
        $cartMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $cartItemsMock = $this->createMock(CartItems::class);
        $fieldMock = $this->createMock(Field::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $resolveInfoMock = $this->createMock(ResolveInfo::class);
        $value = ['model' => $cartMock];
        $args = [];

        $this->cacheMock->expects($this->once())
            ->method('register')
            ->with('mirakl_quote_items_1', null);

        $result = $this->cartItemsPlugin->beforeResolve(
            $cartItemsMock,
            $fieldMock,
            $contextMock,
            $resolveInfoMock,
            $value,
            $args
        );

        $this->assertEquals([$fieldMock, $contextMock, $resolveInfoMock, $value, $args], $result);
    }
}
