<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Plugin\Block\Product\View;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\ImagesConfigFactoryInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\Data\Collection;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use PHPUnit\Framework\TestCase;
use Fedex\Catalog\Plugin\Block\Product\View\GalleryPlugin;
use Magento\Catalog\Block\Product\View\Gallery;

class GalleryPluginTest extends TestCase
{
    protected $galleryPlugin;
    /**
     * @var Gallery
     */
    private $imageUrlBuilder;

    /**
     * @var Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private $product;

    /**
     * @var Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $images;

    /**
     * @var Product\Type\AbstractType|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productTypeInstance;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ArrayUtils|\PHPUnit\Framework\MockObject\MockObject
     */
    private $arrayUtils;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var EncoderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jsonEncoder;

    /**
     * @var ImagesConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imagesConfigFactory;

    /**
     * @var array
     */
    private $galleryImagesConfig;

    /**
     * @var UrlBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlBuilder;

    /**
     * @var Gallery
     */
    private $gallery;

    /**
     * @var \Magento\Framework\DataObject
     */
    private $galleryImageConfig;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->imageUrlBuilder = $this->getMockBuilder(UrlBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->images = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productTypeInstance = $this->getMockBuilder(Product\Type\AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreFilter'])
            ->getMockForAbstractClass();
        $this->productTypeInstance->expects($this->any())
            ->method('getStoreFilter')
            ->with($this->product)
            ->willReturn(null);
        $this->product->expects($this->any())
            ->method('getTypeInstance')
            ->willReturn($this->productTypeInstance);
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->addMethods(['getLayer'])
            ->onlyMethods(['getStoreManager', 'getCatalogHelper'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->any())
            ->method('getStoreManager')
            ->willReturn($this->storeManager);
        $this->context->expects($this->any())
            ->method('getLayer')
            ->willReturn($this->getMockBuilder(\Magento\Catalog\Model\Layer::class)
                ->disableOriginalConstructor()
                ->getMock());
        $this->context->expects($this->any())
            ->method('getCatalogHelper')
            ->willReturn($this->getMockBuilder(Image::class)
                ->disableOriginalConstructor()
                ->getMock());
        $this->arrayUtils = $this->getMockBuilder(ArrayUtils::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonEncoder = $this->getMockBuilder(EncoderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->imagesConfigFactory = $this->getMockBuilder(ImagesConfigFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->galleryImagesConfig = [];
        $this->urlBuilder = $this->getMockBuilder(UrlBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMock();
        $this->galleryPlugin = new GalleryPlugin($this->urlBuilder);
    }

    /**
     * @param $imageConfig
     */
    public function createGalleryWithImageConfig($imageConfig): void
    {
        $this->gallery = new Gallery(
            $this->context,
            $this->arrayUtils,
            $this->jsonEncoder,
            $imageConfig,
            $this->imagesConfigFactory,
            $this->galleryImagesConfig,
            $this->urlBuilder
        );

        $this->gallery->setData("gallery_images_config", $this->galleryImageConfig);
    }

    /**
     * @param string $imageId
     */
    public function createImagesCollection($productLayout, $imageId = "product_page_image_medium")
    {
        $actualImageId = $imageId;
        if ($productLayout == 'first-party-product-full-width' && $imageId == 'product_page_image_medium') {
            $actualImageId = 'product_page_image_medium_one_p';
        } elseif ($productLayout != 'first-party-product-full-width' && $imageId == 'product_page_image_medium_one_p') {
            $actualImageId = 'product_page_image_medium';
        }

        $image1 = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->addMethods(['getFile', 'getMediumImageUrl'])
            ->getMock();
        $image2 = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->addMethods(['getFile', 'getMediumImageUrl'])
            ->getMock();

        $image1->expects($this->any())
            ->method('getFile')
            ->willReturn('http://example.com/image1.jpg');
        $image2->expects($this->any())
            ->method('getFile')
            ->willReturn('http://example.com/image2.jpg');

        $this->urlBuilder->expects($this->any())
            ->method('getUrl')
            ->withConsecutive(['http://example.com/image1.jpg', $actualImageId], ['http://example.com/image2.jpg', $actualImageId])
            ->willReturnOnConsecutiveCalls('http://example.com/image1.jpg', 'http://example.com/image2.jpg');

        $this->images->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$image1, $image2]));

        $this->product->expects($this->once())
            ->method('getMediaGalleryImages')
            ->willReturn($this->images);

        $this->galleryImageConfig = new \Magento\Framework\DataObject([
            'items' => [
                new \Magento\Framework\DataObject([
                    'image_id' => $imageId,
                    'file' => 'http://example.com/image1.jpg',
                    'data_object_key' => 'medium_image_url',
                ])
            ],
            'items' => [
                new \Magento\Framework\DataObject([
                    'image_id' => $imageId,
                    'file' => 'http://example.com/image2.jpg',
                    'data_object_key' => 'medium_image_url',
                ])
            ]
        ]);

        $this->galleryImagesConfig = ['product_page_image_medium' => 'medium_image_url'];
    }

    /**
     * Test getGalleryImages with invalid return collection
     */
    public function testGetGalleryImagesWithInvalidReturnCollection(): void
    {
        $images = [
            $this->getMockBuilder(\Magento\Framework\DataObject::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(\Magento\Framework\DataObject::class)
                ->disableOriginalConstructor()
                ->getMock(),
        ];
        $this->product->expects($this->once())
            ->method('getMediaGalleryImages')
            ->willReturn($images);
        $this->product->expects($this->any())
            ->method('getTypeInstance')
            ->willReturn($this->productTypeInstance);

        $this->createGalleryWithImageConfig([]);

        $this->gallery->setProduct($this->product);

        // Expected result
        $expected = [
            $this->imageUrlBuilder->getUrl('image1.jpg', 'product_page_image_medium'),
            $this->imageUrlBuilder->getUrl('image2.jpg', 'product_page_image_medium'),
        ];

        // Assert that getGalleryImages returns the correct result
        $result = $this->galleryPlugin->afterGetGalleryImages($this->gallery, '');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        foreach ($result as $key => $image) {
            $this->assertInstanceOf(\Magento\Framework\DataObject::class, $image);
            $this->assertEquals($expected[$key], $image->getData('medium_image_url'));
        }
    }

    /**
     *  Test getGalleryImages with collection and new image id
     */
    public function testGetGalleryImagesWithCollectionAndNewImageId(): void
    {
        $this->product->expects($this->any())
            ->method('getData')
            ->with('page_layout')
            ->willReturn("first-party-product-full-width");

        $this->createImagesCollection("first-party-product-full-width");

        $this->createGalleryWithImageConfig($this->galleryImagesConfig);

        $this->gallery->setProduct($this->product);

        $result = $this->galleryPlugin->afterGetGalleryImages($this->gallery, '');

        $i = 1;

        foreach ($result as $image) {
            $this->assertEquals('http://example.com/image' . $i . '.jpg', $image->getData('medium_image_url'));
            $i++;
        }
    }

    /**
     * Test getGalleryImages with collection and old image id
     */
    public function testGetGalleryImagesWithCollectionAndOldImageId(): void
    {
        $this->product->expects($this->any())
            ->method('getData')
            ->with('page_layout')
            ->willReturn("first-party-product");

        $this->createImagesCollection("first-party-product", "product_page_image_medium_one_p");

        $this->createGalleryWithImageConfig($this->galleryImagesConfig);

        $this->gallery->setProduct($this->product);

        $result = $this->galleryPlugin->afterGetGalleryImages($this->gallery, '');

        $i = 1;

        foreach ($result as $image) {
            $this->assertEquals('http://example.com/image' . $i . '.jpg', $image->getData('medium_image_url'));
            $i++;
        }
    }
}
