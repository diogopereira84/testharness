<?php
declare(strict_types=1);

namespace Fedex\ProductUnavailabilityMessage\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Checkout\Model\Cart;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplaceProduct\Helper\Data;

class CheckProductAvailabilityDataModelTest extends TestCase
{
    private $toggleConfig;
    private $cart;
    private $stockItemRepository;
    private $config;
    private $helper;
    private $checkProductAvailabilityDataModel;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->cart = $this->createMock(Cart::class);
        $this->stockItemRepository = $this->createMock(StockItemRepository::class);
        $this->config = $this->createMock(MarketplaceConfig::class);
        $this->helper = $this->createMock(Data::class);

        $this->checkProductAvailabilityDataModel = new CheckProductAvailabilityDataModel(
            $this->toggleConfig,
            $this->cart,
            $this->stockItemRepository,
            $this->config,
            $this->helper
        );
    }

    public function testIsE441563ToggleEnabled()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_team_e_441563')
            ->willReturn(true);

        $result = $this->checkProductAvailabilityDataModel->isE441563ToggleEnabled();

        $this->assertTrue($result);
    }

    public function testGetProductPDPErrorMessageTitle()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with('fedex/product_error_message_setting/unavailable_items_generic_message/unavailable_items_generic_message_title')
            ->willReturn('Product PDP Error Message Title');

        $result = $this->checkProductAvailabilityDataModel->getProductPDPErrorMessageTitle();

        $this->assertEquals('Product PDP Error Message Title', $result);
    }

    public function testGetProductCartlineErrorMessageTitle()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with('fedex/product_error_message_setting/unavailable_items_cart_line_generic_message/unavailable_items_cart_line_generic_message_title')
            ->willReturn('Product Cartline Error Message Title');

        $result = $this->checkProductAvailabilityDataModel->getProductCartlineErrorMessageTitle();

        $this->assertEquals('Product Cartline Error Message Title', $result);
    }

    public function testGetProductCartlineErrorMessage()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with('fedex/product_error_message_setting/unavailable_items_cart_line_generic_message/unavailable_items_cart_line_generic_error_message')
            ->willReturn('Product Cartline Error Message');

        $result = $this->checkProductAvailabilityDataModel->getProductCartlineErrorMessage();

        $this->assertEquals('Product Cartline Error Message', $result);
    }

    public function testGetProductPDPErrorMessage()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with('fedex/product_error_message_setting/unavailable_items_generic_message/unavailable_items_generic_error_message')
            ->willReturn('Product PDP Error Message');

        $result = $this->checkProductAvailabilityDataModel->getProductPDPErrorMessage();

        $this->assertEquals('Product PDP Error Message', $result);
    }



    public function testGetStockStatus()
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $stockItem = $this->createMock(\Magento\CatalogInventory\Api\Data\StockItemInterface::class);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->stockItemRepository->expects($this->once())
            ->method('get')
            ->with(123)
            ->willReturn($stockItem);

        $stockItem->expects($this->once())
            ->method('getIsInStock')
            ->willReturn(true);

        $result = $this->checkProductAvailabilityDataModel->getStockStatus($product);

        $this->assertTrue($result);
    }
}
