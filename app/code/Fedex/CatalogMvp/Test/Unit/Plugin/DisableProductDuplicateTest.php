<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Plugin\DisableProductDuplicate;

class DisableProductDuplicateTest extends TestCase
{
    /**
     * @var CatalogMvp|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockCatalogMvpHelper;

    /**
     * @var Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockProduct;

    /**
     * @var DisableProductDuplicate
     */
    private $disableProductDuplicate;

    protected function setUp(): void
    {
        $this->mockCatalogMvpHelper = $this->createMock(CatalogMvp::class);
        $this->mockProduct = $this->createMock(Product::class);

        $this->disableProductDuplicate = new DisableProductDuplicate(
            $this->mockCatalogMvpHelper
        );
    }

    public function testAfterIsDuplicableReturnsFalseWhenMvpAndLegacy()
    {
        // Mocking the required methods for the test scenario
        $this->mockCatalogMvpHelper
            ->expects($this->once())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->mockProduct
            ->expects($this->once())
            ->method('getSku')
            ->willReturn('fc773cb1-c1c9-4e77-88cc-6b85b3ba34aa');

        $this->mockCatalogMvpHelper
            ->expects($this->once())
            ->method('getIsLegacyItemBySku')
            ->with('fc773cb1-c1c9-4e77-88cc-6b85b3ba34aa')
            ->willReturn(true);

        // Executing the plugin method
        $result = $this->disableProductDuplicate->afterIsDuplicable(
            $this->mockProduct,
            true // Result returned by the original isDuplicable method (assuming true by default)
        );

        // Asserting that the plugin returns false
        $this->assertFalse($result);
    }

    public function testAfterIsDuplicableReturnsOriginalResultWhenNotMvpOrNotLegacy()
    {
        // Mocking the required methods for the test scenario
        $this->mockCatalogMvpHelper
            ->expects($this->once())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(false);

        $this->mockProduct
            ->expects($this->once())
            ->method('getSku')
            ->willReturn('fc773cb1-c1c9-4e77-88cc-6b85b3ba34aa');

        $this->mockCatalogMvpHelper
            ->expects($this->once())
            ->method('getIsLegacyItemBySku')
            ->with('fc773cb1-c1c9-4e77-88cc-6b85b3ba34aa')
            ->willReturn(false);

        // Executing the plugin method
        $result = $this->disableProductDuplicate->afterIsDuplicable(
            $this->mockProduct,
            false // Result returned by the original isDuplicable method (assuming false by default)
        );

        // Asserting that the plugin returns the original result (false)
        $this->assertFalse($result);
    }
}
