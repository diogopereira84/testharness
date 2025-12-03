<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Consumer;

use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceProduct\Consumer\ProductImage;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mirakl\Mci\Helper\Product\Image;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\SharedCatalog\Model\Management as SharedCatalogManagement;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;

class ProductImageTest extends TestCase
{
    private $productCollectionFactory;
    private $productRepository;
    private $imageHelper;
    private $logger;
    private $scopeConfig;
    private $serializer;
    private $marketplaceCheckoutHelper;
    private $productImage;
    private $sharedCatalogManagement;
    private $typeFactory;
    private $setFactory;

    protected function setUp(): void
    {
        $this->productCollectionFactory = $this->createMock(ProductCollectionFactory::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->imageHelper = $this->createMock(Image::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->marketplaceCheckoutHelper = $this->createMock(Data::class);
        $this->sharedCatalogManagement = $this->createMock(SharedCatalogManagement::class);
        $this->typeFactory = $this->createMock(TypeFactory::class);
        $this->setFactory = $this->createMock(SetFactory::class);

        $entityType = $this->createMock(\Magento\Eav\Model\Entity\Type::class);
        $entityType->method('getId')->willReturn(7);

        $this->typeFactory
            ->method('create')
            ->willReturn($entityType);

        $entityType->method('loadByCode')
            ->willReturn($entityType);

        $attributeSet = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $attributeSet->method('getId')->willReturn(55);

        $attributeSetCollection = $this->createMock(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class);
        $attributeSetCollection->method('addFieldToFilter')->willReturnSelf();
        $attributeSetCollection->method('getFirstItem')->willReturn($attributeSet);

        $attributeSetInstance = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $attributeSetInstance->method('load')->willReturnSelf();
        $attributeSetInstance->method('getAttributeSetName')->willReturn('FXONonCustomizableProducts');
        $attributeSetInstance->method('getCollection')->willReturn($attributeSetCollection);

        $this->setFactory
            ->method('create')
            ->willReturn($attributeSetInstance);


        $this->productImage = new ProductImage(
            $this->productCollectionFactory,
            $this->productRepository,
            $this->imageHelper,
            $this->logger,
            $this->scopeConfig,
            $this->serializer,
            $this->marketplaceCheckoutHelper,
            $this->sharedCatalogManagement,
            $this->setFactory,
            $this->typeFactory
        );
    }

    public function testProcessWithEssendantToggleEnabled()
    {
        $dataReceived = json_encode(['product_ids' => [1, 2]]);
        $collection = $this->createMock(Collection::class);

        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->productCollectionFactory
            ->method('create')
            ->willReturn($collection);

        $collection
            ->method('addIdFilter')
            ->with([1, 2])
            ->willReturnSelf();

        $collection
            ->method('setStoreId')
            ->with(0)
            ->willReturnSelf();

        $collection
            ->method('addAttributeToSelect')
            ->with('*')
            ->willReturnSelf();

        $collection
            ->method('addMediaGalleryData')
            ->willReturnSelf();

        $this->productImage->process($dataReceived);
    }

    public function testProcessWithEssendantToggleDisabled()
    {
        $dataReceived = json_encode(['product_ids' => [1, 2]]);

        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->productImage->process($dataReceived);
    }

    public function testProcessMiraklImportImages()
    {
        $collection = $this->createMock(Collection::class);
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(123);
        $productMediaGalleryEntry = $this->createMock(ProductAttributeMediaGalleryEntryInterface::class);
        $miraklImageAttribute = $this->createMock(EavAttribute::class);

        $publicCatalog = $this->createMock(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class);
        $publicCatalog->method('getId')->willReturn(123);
        $this->sharedCatalogManagement->method('getPublicCatalog')->willReturn($publicCatalog);

        $collection
            ->method('getSize')
            ->willReturn(1);

        $collection
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$product]));

        $product
            ->method('getMediaGalleryEntries')
            ->willReturn([$productMediaGalleryEntry]);

        $this->imageHelper
            ->method('getImagesAttributes')
            ->willReturn([$miraklImageAttribute]);

        $miraklImageAttribute
            ->method('getAttributeCode')
            ->willReturn('mirakl_image_1');

        $product->method('getData')
            ->willReturnMap([
                ['mirakl_image_1', null, 'image_url'],
                ['alt_text_mirakl_image_1', null, 'Some alt text']
            ]);

        $productMediaGalleryEntry->expects($this->once())->method('setDisabled');
        $productMediaGalleryEntry->expects($this->once())->method('setTypes');
        $productMediaGalleryEntry->expects($this->once())->method('setLabel')->with('Some alt text');

        $this->productRepository
            ->expects($this->once())
            ->method('save')
            ->with($product);

        $this->productImage->processMiraklImportImages($collection);
    }

    public function testProcessMiraklImportImagesWithException()
    {
        $collection = $this->createMock(Collection::class);
        $product = $this->createMock(Product::class);
        $productMediaGalleryEntry = $this->createMock(ProductAttributeMediaGalleryEntryInterface::class);
        $miraklImageAttribute = $this->createMock(EavAttribute::class);

        $publicCatalog = $this->createMock(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class);
        $publicCatalog->method('getId')->willReturn(123);
        $this->sharedCatalogManagement->method('getPublicCatalog')->willReturn($publicCatalog);

        $collection
            ->method('getSize')
            ->willReturn(1);

        $collection
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$product]));

        $product
            ->method('getMediaGalleryEntries')
            ->willReturn([$productMediaGalleryEntry]);

        $this->imageHelper
            ->method('getImagesAttributes')
            ->willReturn([$miraklImageAttribute]);

        $miraklImageAttribute
            ->method('getAttributeCode')
            ->willReturn('mirakl_image_1');

        $product->method('getData')
            ->willReturnMap([
                ['mirakl_image_1', null, 'image_url'],
                ['alt_text_mirakl_image_1', null, '']
            ]);

        // Mock setter methods on the gallery entry
        $productMediaGalleryEntry->expects($this->once())->method('setDisabled');
        $productMediaGalleryEntry->expects($this->once())->method('setTypes');
        $productMediaGalleryEntry->expects($this->once())->method('setLabel');

        $this->productRepository
            ->method('save')
            ->will($this->throwException(new \Exception('Test exception')));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Fedex\MarketplaceProduct\Consumer\ProductImage::processMiraklImportImages'),
                $this->anything()
            );

        $this->productImage->processMiraklImportImages($collection);
    }

    public function testGetProductImageAttributeListForMiraklImage()
    {
        $imageMappingArray = [
            'mirakl_image_1' => [
                'image_attribute' => ['image', 'small_image'],
                'exclude' => '0'
            ]
        ];

        $result = $this->productImage->getProductImageAttributeListForMiraklImage($imageMappingArray, 'mirakl_image_1');
        $this->assertEquals([['image', 'small_image'], '0'], $result);

        $result = $this->productImage->getProductImageAttributeListForMiraklImage($imageMappingArray, 'mirakl_image_2');
        $this->assertEquals([[], false], $result);
    }

    public function testGetMiraklProductImagesCombined()
    {
        $miraklImagesWithValue = ['mirakl_image_1' => true];
        $productImagesFilename = ['image_url'];

        $result = $this->productImage->getMiraklProductImagesCombined($miraklImagesWithValue, $productImagesFilename);
        $this->assertEquals(['mirakl_image_1' => 'image_url'], $result);
    }

    public function testGetProductImages()
    {
        $productMediaGalleryEntry = $this->createMock(ProductAttributeMediaGalleryEntryInterface::class);
        $productMediaGalleryEntries = [$productMediaGalleryEntry];

        $result = $this->productImage->getProductImages($productMediaGalleryEntries);
        $this->assertEquals([$productMediaGalleryEntry], $result);
    }

    public function testGetMiraklImagesWithValue()
    {
        $product = $this->createMock(Product::class);
        $miraklImageAttribute = $this->createMock(EavAttribute::class);

        $miraklImageAttribute
            ->method('getAttributeCode')
            ->willReturn('mirakl_image_1');

        $product
            ->method('getData')
            ->with('mirakl_image_1')
            ->willReturn('image_url');

        $this->imageHelper
            ->method('getImagesAttributes')
            ->willReturn([$miraklImageAttribute]);

        $result = $this->productImage->getMiraklImagesWithValue($product, [$miraklImageAttribute]);
        $this->assertEquals(['mirakl_image_1' => true], $result);
    }

    public function testGetMiraklImageAttributes()
    {
        $miraklImageAttribute = $this->createMock(EavAttribute::class);

        $this->imageHelper
            ->method('getImagesAttributes')
            ->willReturn([$miraklImageAttribute]);

        $result = $this->productImage->getMiraklImageAttributes();
        $this->assertEquals([$miraklImageAttribute], $result);
    }

    public function testGetImageMappingArray()
    {
        $imageMappingJson = '[{"mirakl_attribute":"mirakl_image_1","image_attribute":["image","small_image"],"exclude":"0"}]';
        $imageMappingArray = [
            'mirakl_image_1' => [
                'mirakl_attribute' => 'mirakl_image_1',
                'image_attribute' => ['image', 'small_image'],
                'exclude' => '0'
            ]
        ];

        $this->scopeConfig
            ->method('getValue')
            ->with(ProductImage::XML_PATH_MARKETPLACE_IMAGE_MAPPING)
            ->willReturn($imageMappingJson);

        $this->serializer
            ->method('unserialize')
            ->with($imageMappingJson)
            ->willReturn(json_decode($imageMappingJson, true));

        $result = $this->productImage->getImageMappingArray();
        $this->assertEquals($imageMappingArray, $result);
    }

    public function testSetupProductCollection()
    {
        $productIds = [1, 2];
        $collection = $this->createMock(Collection::class);

        $this->productCollectionFactory
            ->method('create')
            ->willReturn($collection);

        $collection
            ->method('addIdFilter')
            ->with($productIds)
            ->willReturnSelf();

        $collection
            ->method('setStoreId')
            ->with(0)
            ->willReturnSelf();

        $collection
            ->method('addAttributeToSelect')
            ->with('*')
            ->willReturnSelf();

        $collection
            ->method('addMediaGalleryData')
            ->willReturnSelf();

        $result = $this->productImage->setupProductCollection($productIds);
        $this->assertSame($collection, $result);
    }

    /**
     * @dataProvider imageAltTextDataProvider
     */
    public function testGetImageAltText(string $miraklAttributeCode, ?string $altText, string $expectedResult)
    {
        $product = $this->createMock(Product::class);

        $product->method('getData')
            ->with('alt_text_' . $miraklAttributeCode)
            ->willReturn($altText);

        $result = $this->productImage->getImageAltText($miraklAttributeCode, $product);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function imageAltTextDataProvider(): array
    {
        return [
            'with alt text' => ['mirakl_image_1', 'Sample Alt Text', 'Sample Alt Text'],
            'with null alt text' => ['mirakl_image_2', null, ''],
        ];
    }
}
