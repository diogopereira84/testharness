<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Helper\Product\ProductList;
use Fedex\CatalogMvp\Plugin\ProductListHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\App\Request\Http;

class ProductListHelperTest extends TestCase
{
    public function testAfterGetAvailableViewModeWithoutMvpSharedCatalogAndSelfRegAdminAndPrintCategory()
    {
        // Mock the CatalogMvpHelper
        $catalogMvpHelperMock = $this->createMock(CatalogMvp::class);
        $catalogMvpHelperMock->method('isMvpSharedCatalogEnable')->willReturn(false);

        $requestMock = $this->createMock(Http::class);
        $requestMock->method('getFullActionName')->willReturn('catalog_product_view');

        // Create the ProductListHelper instance with the mocked CatalogMvpHelper
        $productListHelper = new ProductListHelper($catalogMvpHelperMock, $requestMock);

        // Create a mock for the $subject parameter
        $subjectMock = $this->createMock(ProductList::class);

        // Call the afterGetAvailableViewMode method
        $result = $productListHelper->afterGetAvailableViewMode($subjectMock, ['default' => __('Default')]);

        // Assert that the result matches the original value
        $this->assertEquals(['default' => __('Default')], $result);
    }

    public function testAfterGetAvailableViewModeWithMvpSharedCatalogOrSelfRegAdminOrPrintCategory()
    {
        // Mock the CatalogMvpHelper
        $catalogMvpHelperMock = $this->createMock(CatalogMvp::class);
        $catalogMvpHelperMock->method('isMvpSharedCatalogEnable')->willReturn(true);

        $requestMock = $this->createMock(Http::class);
        $requestMock->method('getFullActionName')->willReturn('catalog_product_view');

        // Create the ProductListHelper instance with the mocked CatalogMvpHelper
        $productListHelper = new ProductListHelper($catalogMvpHelperMock,$requestMock);

        // Create a mock for the $subject parameter
        $subjectMock = $this->createMock(ProductList::class);

        // Create a mock for the $request
        $requestMock = $this->createMock(Http::class);
        $requestMock->method('getFullActionName')->willReturn('catalog_product_view');

        // Call the afterGetAvailableViewMode method
        $result = $productListHelper->afterGetAvailableViewMode($subjectMock, ['default' => __('Default')]);

        // Assert that the result matches the expected value
        $this->assertEquals(['list' => __('List'), 'grid' => __('Grid')], $result);
    }

    public function testAfterGetAvailableViewModeWithSearchPage()
    {
        // Mock the CatalogMvpHelper
        $catalogMvpHelperMock = $this->createMock(CatalogMvp::class);
        $catalogMvpHelperMock->method('isMvpSharedCatalogEnable')->willReturn(true);

        // Create a mock for the $request
        $requestMock = $this->createMock(Http::class);
        $requestMock->method('getFullActionName')->willReturn('catalogsearch_result_index');

        // Create the ProductListHelper instance with the mocked CatalogMvpHelper
        $productListHelper = new ProductListHelper($catalogMvpHelperMock, $requestMock);

        // Create a mock for the $subject parameter
        $subjectMock = $this->createMock(ProductList::class);

        // Create a mock for the $request
        $requestMock = $this->createMock(Http::class);
        $requestMock->method('getFullActionName')->willReturn('catalogsearch_result_index');

        // Call the afterGetAvailableViewMode method
        $result = $productListHelper->afterGetAvailableViewMode($subjectMock, ['default' => __('Default')]);

        // Assert that the result matches the original value
        $this->assertEquals(['default' => __('Default')], $result);
    }
}