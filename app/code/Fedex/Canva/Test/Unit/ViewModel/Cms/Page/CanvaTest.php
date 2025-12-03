<?php

declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\ViewModel\Cms\Page;

use Fedex\Canva\Api\Data\ConfigInterface;
use Fedex\Canva\Api\Data\SizeCollectionInterface;
use Fedex\Canva\Model\Builder;
use Fedex\Canva\Model\Service\CurrentPageService;
use Fedex\Canva\Model\Size;
use Fedex\Canva\ViewModel\Cms\Page\Canva;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Cms\Model\Page;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CanvaTest extends TestCase
{
    protected $pageMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    public const ATTRIBUTE_CODE_HAS_CANVA_DESIGN = 'has_canva_design';
    public const ATTRIBUTE_CODE_HAS_CANVAS_SIZE = 'canva_size';

    private Canva $canvaMock;
    private LoggerInterface|MockObject $loggerMock;
    private UrlInterface|MockObject $urlBuilderMock;
    private Builder|MockObject $builderMock;
    private SizeCollectionInterface|MockObject $sizeCollectionMock;
    private Size|MockObject $sizeMock;
    private \ArrayIterator|MockObject $arrayIteratorMock;
    private SerializerInterface|MockObject $serializerMock;
    private CurrentPageService|MockObject $currentPageServiceMock;
    private ConfigInterface|MockObject $configMock;
    private ToggleConfig|MockObject $toggleConfigMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sizeCollectionMock = $this->getMockBuilder(SizeCollectionInterface::class)
            ->addMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sizeMock = $this->getMockBuilder(Size::class)
            ->addMethods(['getProductMappingId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->arrayIteratorMock = $this->getMockBuilder(\ArrayIterator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->currentPageServiceMock = $this->getMockBuilder(CurrentPageService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->canvaMock = $this->objectManager->getObject(
            Canva::class,
            [
                'logger' => $this->loggerMock,
                'urlBuilder' => $this->urlBuilderMock,
                'builder' => $this->builderMock,
                'serializer' => $this->serializerMock,
                'currentPageService' => $this->currentPageServiceMock,
                'config' => $this->configMock,
                'toggleConfig' => $this->toggleConfigMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testHasDesign(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];

        $this->pageMock->expects($this->atMost(3))->method('getData')
            ->withConsecutive(['has_canva_sizes'], ['canva_sizes'], ['has_canva_sizes'])->willReturnOnConsecutiveCalls(true, $attributeValue, true);

        $this->serializerMock->expects($this->once())->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(1);
        $this->builderMock->expects($this->once())->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->atMost(2))->method('getCurrentPage')->willReturn($this->pageMock);

        $this->assertEquals(true, $this->canvaMock->hasDesign());
    }

    /**
     * @return void
     */
    public function testHasDesignFalse(): void
    {
        $this->pageMock->expects($this->once())->method('getData')
            ->with('has_canva_sizes')->willReturn(false);
        $this->currentPageServiceMock->expects($this->atMost(2))->method('getCurrentPage')->willReturn($this->pageMock);

        $this->assertEquals(false, $this->canvaMock->hasDesign());
    }

    /**
     * @return void
     * @throw NoSuchEntityException
     */
    public function testHasDesignException(): void
    {
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')
            ->willThrowException(new NoSuchEntityException(__('The CMS page with the "%1" ID doesn\'t exist.')));

        $this->loggerMock->expects($this->once())->method('error')
            ->with('Fedex\Canva\ViewModel\Cms\Page\Canva::hasDesign:78 The CMS page with the "%1" ID doesn\'t exist.');

        $this->assertEquals(false, $this->canvaMock->hasDesign());
    }

    public function testUseModal(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040'], ['product_mapping_id' => 'CVAANC1041']];

        $this->pageMock->expects($this->once())->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->once())->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(2);
        $this->builderMock->expects($this->once())->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')->willReturn($this->pageMock);

        $this->assertEquals(true, $this->canvaMock->useModal());
    }

    public function testUseModalFalse(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];

        $this->pageMock->expects($this->once())->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->once())->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(1);
        $this->builderMock->expects($this->once())->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')->willReturn($this->pageMock);

        $this->assertEquals(false, $this->canvaMock->useModal());
    }

    public function testGetLink(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];

        $this->pageMock->expects($this->atMost(3))->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->atMost(3))->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(1);
        $this->sizeMock->expects($this->atMost(2))->method('getProductMappingId')->willReturn('CVAANC1040');
        $this->arrayIteratorMock->expects($this->atMost(2))->method('current')->willReturn($this->sizeMock);
        $this->sizeCollectionMock->expects($this->atMost(2))->method('getIterator')->willReturn($this->arrayIteratorMock);
        $this->builderMock->expects($this->atMost(3))->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->atMost(3))->method('getCurrentPage')->willReturn($this->pageMock);

        $url = 'https://office.fedex.com/canva/index/index?canvaProductId=CVAANC1040';
        $this->urlBuilderMock->expects($this->once())->method('getUrl')->with('/canva/index/index', ['canvaProductId' => 'CVAANC1040'])->willReturn($url);

        $link = $this->canvaMock->getLink();
        $this->assertEquals($url, $link);
        $this->assertIsString($link);
    }

    public function testGetLinkMoreOptions(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040'], ['product_mapping_id' => 'CVAANC1040']];

        $this->pageMock->expects($this->once())->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->once())->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(2);
        $this->builderMock->expects($this->once())->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')->willReturn($this->pageMock);

        $url = 'https://office.fedex.com/canva/';
        $this->urlBuilderMock->expects($this->once())->method('getUrl')->with('canva')->willReturn($url);

        $link = $this->canvaMock->getLink();
        $this->assertEquals($url, $link);
        $this->assertIsString($link);
    }

    public function testGetDefaultSize(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];

        $this->pageMock->expects($this->atMost(3))->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->atMost(3))->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(1);
        $this->sizeMock->expects($this->atMost(2))->method('getProductMappingId')->willReturn('CVAANC1040');
        $this->arrayIteratorMock->expects($this->atMost(2))->method('current')->willReturn($this->sizeMock);
        $this->sizeCollectionMock->expects($this->atMost(2))->method('getIterator')->willReturn($this->arrayIteratorMock);
        $this->builderMock->expects($this->atMost(3))->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->atMost(3))->method('getCurrentPage')->willReturn($this->pageMock);

        $defaultSize = $this->canvaMock->getDefaultSize();
        $this->assertEquals('CVAANC1040', $defaultSize);
        $this->assertIsString($defaultSize);
    }

    public function testGetDefaultSizeMoreThanOne(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040'], ['product_mapping_id' => 'CVAANC1041']];

        $this->pageMock->expects($this->once())->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->once())->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(2);
        $this->builderMock->expects($this->once())->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')->willReturn($this->pageMock);

        $defaultSize = $this->canvaMock->getDefaultSize();
        $this->assertEquals('', $defaultSize);
        $this->assertIsString($defaultSize);
    }

    public function testGetOptionsAvailable(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];

        $this->pageMock->expects($this->once())->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->once())->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->builderMock->expects($this->once())->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')->willReturn($this->pageMock);

        $this->assertInstanceOf(SizeCollectionInterface::class, $this->canvaMock->getOptionsAvailable());
    }

    public function testGetOptionsAvailableException(): void
    {
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')
            ->willThrowException(new NoSuchEntityException(__('The CMS page with the "%1" ID doesn\'t exist.')));

        $this->loggerMock->expects($this->once())->method('error')
            ->with('Fedex\Canva\ViewModel\Cms\Page\Canva::getOptionsAvailable:144 The CMS page with the "%1" ID doesn\'t exist.');

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(0);
        $this->builderMock->expects($this->once())->method('build')->with([])->willReturn($this->sizeCollectionMock);

        $this->assertInstanceOf(SizeCollectionInterface::class, $this->canvaMock->getOptionsAvailable());
        $this->assertEquals(0, count($this->sizeCollectionMock));
    }

    public function testGetCanvaAppLink(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040'], ['product_mapping_id' => 'CVAANC1041']];

        $this->pageMock->expects($this->once())->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->once())->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->once())->method('count')->willReturn(2);
        $this->builderMock->expects($this->once())->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->once())->method('getCurrentPage')->willReturn($this->pageMock);

        $this->configMock->expects($this->atMost(2))->method('getBaseUrl')->willReturn('https://fedex.office.com/');
        $this->configMock->expects($this->atMost(2))->method('getPath')->willReturn('canva/index/index');

        $canvaLogoUrl = 'https://fedex.office.com/canva/index/index';
        $canvaAppLink = $this->canvaMock->getCanvaAppLink();
        $this->assertIsString($canvaAppLink);
        $this->assertEquals($canvaLogoUrl, $canvaAppLink);
    }

    public function testGetCanvaAppLinkNotUseModal(): void
    {
        $attributeValue = '{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040'], ['product_mapping_id' => 'CVAANC1041']];

        $this->pageMock->expects($this->atMost(3))->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->atMost(3))->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->atMost(3))->method('count')->willReturn(1);
        $this->sizeCollectionMock->expects($this->once())->method('toArray')->willReturn([['product_mapping_id' => 'CVAANC1040']]);
        $this->builderMock->expects($this->atMost(3))->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->atMost(3))->method('getCurrentPage')->willReturn($this->pageMock);

        $this->configMock->expects($this->once())->method('getBaseUrl')->willReturn('https://fedex.office.com/');
        $this->configMock->expects($this->once())->method('getPath')->willReturn('canva/index/index');

        $canvaLogoUrl = 'https://fedex.office.com/canva/index/index?canvaProductId=CVAANC1040';
        $canvaAppLink = $this->canvaMock->getCanvaAppLink();
        $this->assertIsString($canvaAppLink);
        $this->assertEquals($canvaLogoUrl, $canvaAppLink);
    }

    public function testGetCanvaAppLinkEmptyCanvaSizes(): void
    {
        $attributeValue = '';
        $attributeValueUnserialized = [];

        $this->pageMock->expects($this->atMost(2))->method('getData')
            ->with('canva_sizes')->willReturn($attributeValue);

        $this->serializerMock->expects($this->atMost(2))->method('unserialize')
            ->with($attributeValue)->willReturn($attributeValueUnserialized);

        $this->sizeCollectionMock->expects($this->atMost(2))->method('count')->willReturn(0);
        $this->builderMock->expects($this->atMost(2))->method('build')->with($attributeValueUnserialized)->willReturn($this->sizeCollectionMock);
        $this->currentPageServiceMock->expects($this->atMost(2))->method('getCurrentPage')->willReturn($this->pageMock);

        $this->urlBuilderMock->expects($this->once())->method('getBaseUrl')->willReturn('https://fedex.office.com/');

        $canvaLogoUrl = 'https://fedex.office.com/';
        $canvaAppLink = $this->canvaMock->getCanvaAppLink();
        $this->assertIsString($canvaAppLink);
        $this->assertEquals($canvaLogoUrl, $canvaAppLink);
    }
}
