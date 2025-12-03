<?php
declare(strict_types=1);

namespace Fedex\ProductUnavailabilityMessage\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Fedex\ProductUnavailabilityMessage\ViewModel\CheckProductAvailability;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplaceProduct\Helper\Data;
use Magento\Catalog\Model\Product;
use Mirakl\Connector\Model\Offer;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
class CheckProductAvailabilityTest extends TestCase
{
    private $checkProductAvailability;
    private $checkProductAvailabilityDataModel;
    private $marketplaceConfig;
    private $helper;
    private $toggleConfig;

    protected function setUp(): void
    {
        $this->checkProductAvailabilityDataModel = $this->createMock(CheckProductAvailabilityDataModel::class);
        $this->marketplaceConfig = $this->createMock(MarketplaceConfig::class);
        $this->helper = $this->createMock(Data::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);


        $this->checkProductAvailability = new CheckProductAvailability(
            $this->checkProductAvailabilityDataModel,
            $this->marketplaceConfig,
            $this->helper,
            $this->toggleConfig
        );
    }

    public function testIsE441563ToggleEnabledReturnsTrue()
    {
        $this->checkProductAvailabilityDataModel->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(true);

        $result = $this->checkProductAvailability->isE441563ToggleEnabled();

        $this->assertTrue($result);
    }

    public function testIsE441563ToggleEnabledReturnsFalse()
    {
        $this->checkProductAvailabilityDataModel->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(false);

        $result = $this->checkProductAvailability->isE441563ToggleEnabled();

        $this->assertFalse($result);
    }

    public function testCheckProductAvailableReturnsTrueWhenToggleEnabledAndProductUnavailable()
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getMiraklMcmProductId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getMiraklMcmProductId')
            ->willReturn(null);
        $product->expects($this->once())
            ->method('getData')
            ->with('is_unavailable')
            ->willReturn(true);

        $this->checkProductAvailabilityDataModel->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(true);

        $result = $this->checkProductAvailability->checkProductAvailable($product);

        $this->assertFalse($result);
    }

    public function testCheckProductAvailableReturnsFalseWhenTigerD232503ToggleEnabledAndProductUnavailable()
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getData')
            ->with('is_unavailable')
            ->willReturn(true);

        $this->checkProductAvailabilityDataModel->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(true);

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(CheckProductAvailability::TIGER_D_232503)
            ->willReturn(true);

        $result = $this->checkProductAvailability->checkProductAvailable($product);

        $this->assertFalse($result);
    }

    public function testCheckProductAvailableReturnsFalseWhenToggleEnabledAndProductHasNoMiraklMcmProductId()
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getMiraklMcmProductId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getMiraklMcmProductId')
            ->willReturn(null);

        $this->checkProductAvailabilityDataModel->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(true);

        $result = $this->checkProductAvailability->checkProductAvailable($product);

        $this->assertTrue($result);
    }

    public function testCheckProductAvailableReturnsTrueWhenToggleEnabledAndProductHasAvailableOffers()
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getMiraklMcmProductId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->once())
            ->method('getMiraklMcmProductId')
            ->willReturn('123');
        $product->expects($this->once())
            ->method('getData')
            ->with('is_unavailable')
            ->willReturn(false);

        $offer1 = $this->createMock(Offer::class);
        $offer1->expects($this->once())
            ->method('getData')
            ->with('quantity')
            ->willReturn(0);

        $offer2 = $this->createMock(Offer::class);
        $offer2->expects($this->once())
            ->method('getData')
            ->with('quantity')
            ->willReturn(1);

        $this->helper->expects($this->once())
            ->method('getAllOffers')
            ->with($product)
            ->willReturn([$offer1, $offer2]);

        $this->checkProductAvailabilityDataModel->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(true);

        $result = $this->checkProductAvailability->checkProductAvailable($product);

        $this->assertTrue($result);
    }

    public function testCheckProductAvailableReturnsFalseWhenToggleEnabledAndProductHasNoAvailableOffers()
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getMiraklMcmProductId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getMiraklMcmProductId')
            ->willReturn('123');
        $product->expects($this->once())
            ->method('getData')
            ->with('is_unavailable')
            ->willReturn(false);

        $offer1 = $this->createMock(Offer::class);
        $offer1->expects($this->once())
            ->method('getData')
            ->with('quantity')
            ->willReturn(0);

        $offer2 = $this->createMock(Offer::class);
        $offer2->expects($this->once())
            ->method('getData')
            ->with('quantity')
            ->willReturn(0);

        $this->helper->expects($this->once())
            ->method('getAllOffers')
            ->with($product)
            ->willReturn([$offer1, $offer2]);

        $this->checkProductAvailabilityDataModel->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(true);

        $result = $this->checkProductAvailability->checkProductAvailable($product);

        $this->assertFalse($result);
    }

}
