<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model\Service;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Session;
use Magento\Catalog\Model\SessionFactory;
use Fedex\Canva\Model\Service\CurrentProductService;

class CurrentProductServiceTest extends TestCase
{
    private const GET_DATA = 'getData';
    private const CREATE = 'create';
    private const GET_BY_ID = 'getById';

    /**
     * @param int $productId
     * @dataProvider getProductDataProvider
     */
    public function testGetProduct($productId)
    {
        $sessionMock = $this->createPartialMock(
            Session::class,
            [self::GET_DATA]
        );
        $sessionFactoryMock = $this->createPartialMock(
            SessionFactory::class,
            [self::CREATE]
        );
        $productMock = $this->getMockForAbstractClass(
            Product::class,
            [],
            '',
            false
        );
        $productRepositoryMock = $this->createPartialMock(
            ProductRepository::class,
            [self::GET_BY_ID]
        );
        if (0 == $productId) {
            $productMock->setId(null);
            $productRepositoryMock->method(self::GET_BY_ID)
                ->willThrowException(
                    new \Exception("The product that was requested doesn't exist. Verify the product and try again.")
                );
            $this->expectException("Exception");
        } else {
            $productMock->setId($productId);
            $productRepositoryMock->expects($this->once())->method(self::GET_BY_ID)
                ->with($productId)
                ->willReturn($productMock);
        }
        $productRepositoryMock->expects($this->once())->method(self::GET_BY_ID)
            ->with($productId)
            ->willReturn($productMock);
        $sessionMock->expects($this->once())->method(self::GET_DATA)
            ->with('last_viewed_product_id')
            ->willReturn($productId);
        $sessionFactoryMock->expects($this->once())->method(self::CREATE)
            ->willReturn($sessionMock);

        $productService = new CurrentProductService($sessionFactoryMock, $productRepositoryMock);
        $product = $productService->getProduct();
        $this->assertEquals($productId, $product->getId());
    }

    /**
     * @param int $productId
     * @dataProvider getProductIdDataProvider
     */
    public function testGetProductId($productId)
    {
        $sessionFactoryMock = $this->createPartialMock(
            SessionFactory::class,
            [self::CREATE]
        );
        $sessionMock = $this->createPartialMock(
            Session::class,
            [self::GET_DATA]
        );
        $productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock->expects($this->once())->method(self::GET_DATA)
            ->with('last_viewed_product_id')
            ->willReturn($productId);
        $sessionFactoryMock->expects($this->once())->method(self::CREATE)
            ->willReturn($sessionMock);
        $productService = new CurrentProductService($sessionFactoryMock, $productRepositoryMock);
        $this->assertEquals('integer', gettype($productService->getProductId()));
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getProductIdDataProvider(): array
    {
        return [
            [123],
            [0],
            [null],
            [''],
        ];
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getProductDataProvider(): array
    {
        return [
            [123, "should work"],
            [0, "The product that was requested doesn't exist. Verify the product and try again."],
        ];
    }
}
