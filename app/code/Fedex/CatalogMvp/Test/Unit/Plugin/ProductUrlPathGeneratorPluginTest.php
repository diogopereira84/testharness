<?php
/**
 * Fedex
 * Copyright (C) 2024 Fedex
 * PHPUnit Test for ProductUrlPathGeneratorPlugin
 */

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Plugin\ProductUrlPathGeneratorPlugin;

class ProductUrlPathGeneratorPluginTest extends TestCase
{
    protected $productUrlPathGeneratorPlugin;
    /**
     * @var ProductUrlPathGeneratorPlugin
     */
    protected $plugin;

    /**
     * @var CatalogMvp|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $catalogMvpHelperMock;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $toggleConfigMock;

    protected function setUp(): void
    {
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->productUrlPathGeneratorPlugin = $objectManagerHelper->getObject(
            ProductUrlPathGeneratorPlugin::class,
            [
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
            ]
        );
    }

    public function testAfterGetUrlKey()
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getUrlKey', 'getSku'])
            ->getMock();
        $product->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(1);
        $product->expects($this->once())
            ->method('getUrlKey')
            ->willReturn('');
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn('test-sku');
        $this->catalogMvpHelperMock->expects($this->once())
            ->method('isAttributeSetPrintOnDemand')
            ->with(1)
            ->willReturn(true);
        $subject = $this->createMock(ProductUrlPathGenerator::class);
        $result = 'initial_url_key';
        $actualResult = $this->productUrlPathGeneratorPlugin->afterGetUrlKey($subject, $result, $product);
        $this->assertEquals('test-sku', $actualResult);
    }
}