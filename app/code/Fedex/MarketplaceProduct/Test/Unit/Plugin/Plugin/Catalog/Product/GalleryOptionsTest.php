<?php
declare (strict_types = 1);

namespace Fedex\MarketplaceProduct\Test\Plugin\Catalog\Product;

use Fedex\MarketplaceProduct\Model\Config\Backend\MarketplaceProduct;
use Fedex\MarketplaceProduct\Plugin\Catalog\Product\GalleryOptions;
use Magento\Catalog\Block\Product\View\GalleryOptions as Options;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Result\Page as PageResult;
use Magento\Framework\Serialize\Serializer\Json;

class GalleryOptionsTest extends \PHPUnit\Framework\TestCase
{
    protected $catalogHelper;
    protected $pageConfig;
    protected $marketplaceProductConfig;
    /**
     * Custom layout id.
     */
    private const THIRD_PARTY_LAYOUT = 'third-party-product-full-width';

    /**
     * @var GalleryOptions
     */
    private $galleryOptions;

    /**
     * @var PageResult
     */
    private $pageResult;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->pageResult = $this->getMockBuilder(PageResult::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPageLayout','getConfig'])
            ->getMock();

        $this->catalogHelper = $this->getMockBuilder(\Magento\Catalog\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getPageLayout'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceProductConfig = $this->getMockBuilder(MarketplaceProduct::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonSerializer = $this->createMock(Json::class);

        $this->galleryOptions = new GalleryOptions(
            $this->pageResult,
            $this->catalogHelper,
            $this->jsonSerializer,
            $this->marketplaceProductConfig
        );
    }

    /**
     * Test afterGetOptionsJson() method
     */
    public function testAfterGetOptionsJson()
    {
        $options = '{"navdir":"vertical"}';
        $this->pageResult->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->once())
            ->method('getPageLayout')
            ->willReturn(self::THIRD_PARTY_LAYOUT);
        $this->jsonSerializer->expects($this->any())
            ->method('unserialize')
            ->with($options)
            ->willReturn(['navdir' => 'vertical']);
        $this->jsonSerializer->expects($this->any())
            ->method('serialize')
            ->with(['navdir' => 'horizontal'])
            ->willReturn('{"navdir":"horizontal"}');

        $result = $this->galleryOptions->afterGetOptionsJson($this->createMock(Options::class), $options);
        $this->assertEquals('{"navdir":"horizontal"}', $result);
    }

    /**
     * Test afterGetOptionsJson() method
     */
    public function testAfterGetOptionsJson3p()
    {
        $options = [
            '{"navdir":"horizontal"}',
            '[{"key": "some-key", "value": "some-value"}]'
        ];
        $this->pageResult->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->once())
            ->method('getPageLayout')
            ->willReturn(self::THIRD_PARTY_LAYOUT);
        $this->jsonSerializer->expects($this->any())
            ->method('unserialize')
            ->with($options[0])
            ->willReturn(['navdir' => 'vertical']);
        $this->jsonSerializer->expects($this->any())
            ->method('serialize')
            ->willReturnOnConsecutiveCalls($options);

        $this->catalogHelper->expects($this->once())
            ->method('getProduct')
            ->willReturn(true);

        $this->marketplaceProductConfig->method('get3pPdpGallerySettings')
            ->willReturn($options[1]);

        $result = $this->galleryOptions->afterGetOptionsJson(
            $this->createMock(Options::class),
            $options[0]
        );
        $this->assertEquals($options, $result);
    }
}
