<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Plugin\Mirakl\Helper\Product;

use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceProduct\Plugin\Mirakl\Helper\Product\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\MessageQueue\PublisherInterface;
use Mirakl\Mci\Helper\Product\Image as ImageCore;
use Mirakl\Process\Model\Process;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    private $marketplaceCheckoutHelper;
    private $publisher;
    private $imagePlugin;
    private $imageCore;
    private $process;
    private $productCollection;

    protected function setUp(): void
    {
        $this->marketplaceCheckoutHelper = $this->createMock(Data::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->imagePlugin = new Image($this->marketplaceCheckoutHelper, $this->publisher);
        $this->imageCore = $this->createMock(ImageCore::class);
        $this->process = $this->createMock(Process::class);
        $this->productCollection = $this->createMock(ProductCollection::class);
    }

    public function testAfterImportProductsImages()
    {
        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);

        $this->productCollection
            ->method('getSize')
            ->willReturn(2);
        $this->productCollection
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$product1, $product2]));

        $this->publisher
            ->expects($this->once())
            ->method('publish')
            ->with(
                'fedex.marketplaceproduct.image',
                json_encode(['product_ids' => [1, 2]])
            );

        $result = $this->imagePlugin->afterImportProductsImages($this->imageCore, null, $this->process, $this->productCollection);

        $this->assertNull($result);
    }

    public function testAfterImportProductsImagesToggleDisabled()
    {
        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->publisher
            ->expects($this->never())
            ->method('publish');

        $result = $this->imagePlugin->afterImportProductsImages($this->imageCore, null, $this->process, $this->productCollection);

        $this->assertNull($result);
    }
}
