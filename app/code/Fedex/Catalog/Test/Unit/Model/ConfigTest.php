<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Model;

use Fedex\Catalog\Model\Config;
use Magento\Catalog\Helper\Output;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Output|MockObject
     */
    private $outputHelperMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->outputHelperMock = $this->createMock(Output::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->config = $this->objectManager->getObject(
            Config::class,
            [
                'outputHelper' => $this->outputHelperMock,
                'scopeConfig' => $this->scopeConfigMock,
            ]
        );
    }

    /**
     * Test for the formatAttribute method
     */
    public function testFormatAttribute(): void
    {
        $productMock = $this->createMock(Product::class);
        $attributeCode = 'some_attribute_code';
        $attributeValue = 'some_attribute_value';
        $formattedValue = 'formatted_value';

        $productMock->expects($this->once())
            ->method('getData')
            ->with($attributeCode)
            ->willReturn($attributeValue);

        $this->outputHelperMock->expects($this->once())
            ->method('productAttribute')
            ->with($productMock, $attributeValue, $attributeCode)
            ->willReturn($formattedValue);

        $result = $this->config->formatAttribute($productMock, $attributeCode);
        $this->assertEquals($formattedValue, $result);
    }

    /**
     * Test for the getPdpGallerySettings method
     */
    public function testGetPdpGallerySettings(): void
    {
        $gallerySettings = 'some_gallery_settings';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_CATALOG_FIRST_PARTY_GALLERY_SETTINGS,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($gallerySettings);

        $result = $this->config->getPdpGallerySettings();
        $this->assertEquals($gallerySettings, $result);
    }

    /**
     * Test for the getPdpGallerySettings method with a store value
     */
    public function testGetPdpGallerySettingsWithNegativeStoreValue(): void
    {
        $result = $this->config->getPdpGallerySettings(-1);
        $this->assertSame('', $result);
    }

    /**
     * Test for the getPdpGalleryInStoreProductSettings method
     */
    public function testGetGalleryInStoreProductSettings(): void
    {
        $gallerySettings = 'some_gallery_settings';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_CATALOG_IN_STORE_PRODUCT_GALLERY_SETTINGS,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($gallerySettings);

        $result = $this->config->getPdpGalleryInStoreProductSettings();
        $this->assertEquals($gallerySettings, $result);
    }

    /**
     * Test for the getPdpGalleryInStoreProductSettings method with a store value
     */
    public function testGetPdpGalleryInStoreProductSettingsWithNegativeStoreValue(): void
    {
        $result = $this->config->getPdpGalleryInStoreProductSettings(-1);
        $this->assertSame('', $result);
    }

    /**
     * Test for the getPdpGallerySettings method with a store value
     */
    public function testFormatAttributeWithNonexistentAttribute(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('nonexistent_attribute')
            ->willReturn(null);

        $result = $this->config->formatAttribute($productMock, 'nonexistent_attribute');
        $this->assertSame('', $result);
    }
}
