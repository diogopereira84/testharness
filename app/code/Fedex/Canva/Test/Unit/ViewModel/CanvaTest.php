<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\ViewModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\AttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\Canva\Model\Service\CurrentProductService;
use Fedex\Canva\ViewModel\Canva;
use Fedex\Canva\Api\Data\ConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CanvaTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    public const CANVA_LOGO_FALLBACK_PATH = 'Fedex_Canva::images/canva-logo.png';
    private Canva $canvaMock;
    private LoggerInterface|MockObject $logger;
    private SerializerInterface|MockObject $serializer;
    private CurrentProductService|MockObject $currentProductServiceMock;
    private UrlInterface|MockObject $urlBuilder;
    private MockObject|ConfigInterface $configMock;
    private StoreManagerInterface|MockObject $storeManagerMock;
    private StoreInterface|MockObject $storeMock;
    private Repository|MockObject $repositoryMock;
    private MockObject|RequestInterface $requestMock;
    private MockObject|ToggleConfig $toggleMock;
    private MockObject|Product $productMock;
    private MockObject|AttributeInterface $attributeInterfaceMock;

    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getCustomAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeInterfaceMock = $this->getMockBuilder(AttributeInterface::class)
            ->onlyMethods(['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockForAbstractClass(
            LoggerInterface::class,
            [],
            '',
            false
        );
        $this->serializer = $this->getMockForAbstractClass(
            SerializerInterface::class,
            [],
            '',
            false
        );
        $this->currentProductServiceMock = $this->createPartialMock(
            CurrentProductService::class,
            ['getProduct']
        );
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->setMethods([
                'getUseSession',
                'getBaseUrl',
                'getCurrentUrl',
                'getRouteUrl',
                'addSessionParam',
                'addQueryParams',
                'setQueryParam',
                'getUrl',
                'escape',
                'getDirectUrl',
                'sessionUrlVar',
                'isOwnOriginUrl',
                'getRedirectUrl',
                'setScope',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->addMethods(['getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->repositoryMock = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['getUrlWithParams'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->onlyMethods(['isSecure'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->canvaMock = $this->objectManager->getObject(
            Canva::class,
            [
                'logger' => $this->logger,
                'urlBuilder' => $this->urlBuilder,
                'serializer' => $this->serializer,
                'currentProductService' => $this->currentProductServiceMock,
                'config' => $this->configMock,
                'storeManager' => $this->storeManagerMock,
                'assetRepository' => $this->repositoryMock,
                'request' => $this->requestMock,
                'toggleConfig' => $this->toggleMock,
            ]
        );
    }

    /**
     * @param int $productId
     * @dataProvider hasDesignDataProvider
     */
    public function testHasDesign($return, $product, $attribute): void
    {
        $canva = $this->getMockBuilder(Canva::class)
            ->setConstructorArgs([
                $this->logger,
                $this->urlBuilder,
                $this->serializer,
                $this->currentProductServiceMock,
                $this->configMock,
                $this->storeManagerMock,
                $this->repositoryMock,
                $this->requestMock,
                $this->toggleMock,
            ])
            ->setMethods(['getOptionsAvailable'])
            ->getMock();
        if (null != $attribute) {
            $canva->expects($this->once())
                ->method('getOptionsAvailable')
                ->willReturn(['option_1', 'option_2', 'option_3', 'option_4']);
            $attribute->expects($this->once())->method('getValue')->willReturn($return);
        }
        if (null != $product) {
            $product->expects($this->once())->method('getCustomAttribute')
                ->with(Canva::ATTRIBUTE_CODE_HAS_CANVA_DESIGN)
                ->willReturn($attribute);
            $this->currentProductServiceMock->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        } else {
            $this->currentProductServiceMock
                ->expects($this->atLeastOnce())
                ->method('getProduct')->willThrowException(new \Exception());
        }
        $this->assertEquals($return, $canva->hasDesign());
    }

    /**
     * @param array $items
     * @dataProvider useModalDataProvider
     */
    public function testUseModal(array $items, bool $return): void
    {
        $canvaMock = $this->createPartialMock(
            Canva::class,
            ['getOptionsAvailable']
        );
        $canvaMock->expects($this->once())->method('getOptionsAvailable')->willReturn($items);
        $this->assertEquals($return, $canvaMock->useModal());
    }

    /**
     * @return void
     */
    public function testGetLink(): void
    {
        $attributeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}]';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];
        $this->attributeInterfaceMock->expects($this->atMost(3))->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->atMost(3))->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->atMost(3))->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->atMost(3))->method('unserialize')->with($attributeValue)->willReturn($attributeValueUnserialized);
        $expectedUrl = 'https://www.fedex.com/canva/index/index?product_mapping_id=CVAANC1040';
        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->with('/canva/index/index', ['canvaProductId' => 'CVAANC1040'])->willReturn($expectedUrl);
        $this->assertEquals($expectedUrl, $this->canvaMock->getLink());
    }

    /**
     * @return void
     */
    public function testGetLinkEmptyOptions(): void
    {
        $attributeValue = '[]';
        $attributeValueUnserialized = [];
        $this->attributeInterfaceMock->expects($this->atMost(3))->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->atMost(3))->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->atMost(3))->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->atMost(3))->method('unserialize')->with($attributeValue)->willReturn($attributeValueUnserialized);
        $expectedUrl = 'https://www.fedex.com/canva';
        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->with('canva')->willReturn($expectedUrl);
        $this->assertEquals($expectedUrl, $this->canvaMock->getLink());
    }

    /**
     * @return void
     */
    public function testGetDefaultSize(): void
    {
        $attributeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}]';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];
        $this->attributeInterfaceMock->expects($this->atMost(3))->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->atMost(3))->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->atMost(3))->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->atMost(3))->method('unserialize')->with($attributeValue)->willReturn($attributeValueUnserialized);
        $returnGetDefaultSize = $this->canvaMock->getDefaultSize();
        $this->assertIsString($returnGetDefaultSize);
        $this->assertEquals('CVAANC1040', $returnGetDefaultSize);
    }

    /**
     * @return void
     */
    public function testGetDefaultSizeEmpty(): void
    {
        $attributeValue = '[]';
        $attributeValueUnserialized = [];
        $this->attributeInterfaceMock->expects($this->atMost(3))->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->atMost(3))->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->atMost(3))->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->atMost(3))->method('unserialize')->with($attributeValue)->willReturn($attributeValueUnserialized);
        $returnGetDefaultSize = $this->canvaMock->getDefaultSize();
        $this->assertIsString($returnGetDefaultSize);
        $this->assertEquals('', $returnGetDefaultSize);
    }

    /**
     * @return void
     */
    public function testGetDefaultSizeMultiple(): void
    {
        $attributeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false},{"id":"option_2","sort_order":1,"product_mapping_id":"CVAANC1055","display_width":"4\"","display_height":"8\"","orientation":"Portrait","is_default":false}]';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040'], ['product_mapping_id' => 'CVAANC1041']];
        $this->attributeInterfaceMock->expects($this->atMost(3))->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->atMost(3))->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->atMost(3))->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->atMost(3))->method('unserialize')->with($attributeValue)->willReturn($attributeValueUnserialized);
        $returnGetDefaultSize = $this->canvaMock->getDefaultSize();
        $this->assertIsString($returnGetDefaultSize);
        $this->assertEquals('', $returnGetDefaultSize);
    }

    /**
     * @param bool $shouldUseModal
     * @dataProvider getOptionsAvailableDataProvider
     */
    public function testGetOptionsAvailable($attributeValue, $return, $product, $attribute): void
    {
        if (null != $attribute) {
            $attribute->expects($this->once())->method('getValue')->willReturn($attributeValue);
            $this->serializer->expects($this->once())->method('unserialize')->willReturn($return);
        }
        if (null != $product) {
            $product->expects($this->once())->method('getCustomAttribute')
                ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
                ->willReturn($attribute);
            $this->currentProductServiceMock->expects($this->once())->method('getProduct')->willReturn($product);
        } else {
            $this->currentProductServiceMock
                ->expects($this->atLeastOnce())
                ->method('getProduct')->willThrowException(new \Exception());
        }
        $canva = new Canva(
            $this->logger,
            $this->urlBuilder,
            $this->serializer,
            $this->currentProductServiceMock,
            $this->configMock,
            $this->storeManagerMock,
            $this->repositoryMock,
            $this->requestMock,
            $this->toggleMock
        );
        $this->assertEquals($return, $canva->getOptionsAvailable());
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetCanvaLogoUrl(): void
    {
        $this->configMock->expects($this->once())->method('getCanvaLogoPath')->willReturn('canva-logo.png');
        $this->storeMock->expects($this->once())->method('getBaseUrl')
            ->with(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)->willReturn('https://fedex.office.com/media/');
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $logoUrl = $this->canvaMock->getCanvaLogoUrl();
        $this->assertIsString($logoUrl);
        $this->assertEquals('https://fedex.office.com/media/canva/canva_design/canva_logo/canva-logo.png', $logoUrl);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetCanvaLogoUrlNull(): void
    {
        $this->configMock->expects($this->once())->method('getCanvaLogoPath')->willReturn(null);
        $this->requestMock->expects($this->once())->method('isSecure')->willReturn(true);
        $canvaLogoUrl = 'https://fedex.office.com/static/frontend/Fedex/poc/en_US/Fedex_Canva/web/images/canva-logo.png';
        $params = ['_secure' => true];
        $this->repositoryMock->expects($this->once())->method('getUrlWithParams')
            ->with(self::CANVA_LOGO_FALLBACK_PATH, $params)->willReturn($canvaLogoUrl);
        $logoUrl = $this->canvaMock->getCanvaLogoUrl();
        $this->assertIsString($logoUrl);
        $this->assertEquals($canvaLogoUrl, $logoUrl);
    }

    /**
     * @return void
     */
    public function testGetCanvaAppLink(): void
    {
        $attributeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false},{"id":"option_1","sort_order":1,"product_mapping_id":"CVAANC1041","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}]';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040'], ['product_mapping_id' => 'CVAANC1041']];
        $this->attributeInterfaceMock->expects($this->once())->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->once())->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->once())->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->once())->method('unserialize')->with($attributeValue)->willReturn($attributeValueUnserialized);
        $this->configMock->expects($this->atMost(2))->method('getBaseUrl')->willReturn('https://fedex.office.com/');
        $this->configMock->expects($this->atMost(2))->method('getPath')->willReturn('canva/index/index');
        $canvaLogoUrl = 'https://fedex.office.com/canva/index/index';
        $canvaAppLink = $this->canvaMock->getCanvaAppLink();
        $this->assertIsString($canvaAppLink);
        $this->assertEquals($canvaLogoUrl, $canvaAppLink);
    }

    /**
     * @return void
     */
    public function testGetCanvaAppLinkNotUseModal(): void
    {
        $attributeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false}]';
        $attributeValueUnserialized = [['product_mapping_id' => 'CVAANC1040']];
        $this->attributeInterfaceMock->expects($this->atMost(4))->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->atMost(4))->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->atMost(4))->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->atMost(4))->method('unserialize')->with($attributeValue)->willReturn($attributeValueUnserialized);
        $this->configMock->expects($this->once())->method('getBaseUrl')->willReturn('https://fedex.office.com/');
        $this->configMock->expects($this->once())->method('getPath')->willReturn('canva/index/index');
        $canvaLogoUrl = 'https://fedex.office.com/canva/index/index?canvaProductId=CVAANC1040';
        $canvaAppLink = $this->canvaMock->getCanvaAppLink();
        $this->assertIsString($canvaAppLink);
        $this->assertEquals($canvaLogoUrl, $canvaAppLink);
    }

    /**
     * @return void
     */
    public function testGetCanvaAppLinkEmptyCanvaSizes(): void
    {
        $this->attributeInterfaceMock->expects($this->atMost(2))->method('getValue')->willReturn('[]');
        $this->productMock->expects($this->atMost(2))->method('getCustomAttribute')
            ->with(Canva::ATTRIBUTE_CODE_HAS_CANVAS_SIZE)
            ->willReturn($this->attributeInterfaceMock);
        $this->currentProductServiceMock->expects($this->atMost(2))->method('getProduct')->willReturn($this->productMock);
        $this->serializer->expects($this->atMost(2))->method('unserialize')->with('[]')->willReturn([]);
        $this->urlBuilder->expects($this->once())->method('getBaseUrl')->willReturn('https://fedex.office.com/');
        $canvaLogoUrl = 'https://fedex.office.com/';
        $canvaAppLink = $this->canvaMock->getCanvaAppLink();
        $this->assertIsString($canvaAppLink);
        $this->assertEquals($canvaLogoUrl, $canvaAppLink);
    }

    /**
     * @codeCoverageIgnore
     * @return array[]
     */
    public function hasDesignDataProvider(): array
    {
        return [
            [
                true,
                $this->createPartialMock(
                    Product::class,
                    ['getCustomAttribute'],
                ),
                $this->createPartialMock(
                    AttributeInterface::class,
                    ['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode']
                )
            ],
            [
                false,
                $this->createPartialMock(
                    Product::class,
                    ['getCustomAttribute'],
                ),
                $this->createPartialMock(
                    AttributeInterface::class,
                    ['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode']
                )
            ],
            [
                false,
                $this->createPartialMock(
                    Product::class,
                    ['getCustomAttribute'],
                ),
                null
            ],
            [
                false,
                null,
                null
            ]
        ];
    }

    /**
     * @codeCoverageIgnore
     * @return array[]
     */
    public function useModalDataProvider(): array
    {
        return [
            [
                ['option_1', 'option_2', 'option_3', 'option_4'],
                true
            ],
            [
                ['option_1'],
                false
            ],
            [
                [],
                false
            ],
        ];
    }

    /**
     * @codeCoverageIgnore
     * @return array[]
     */
    public function getLinkDataProvider(): array
    {
        return [
            [
                true,
                '#',
            ],
            [
                false,
                'https://www.fedex.com/',
            ],
        ];
    }

    /**
     * @codeCoverageIgnore
     * @return array[]
     */
    public function getOptionsAvailableDataProvider(): array
    {
        return [
            [
                '[{"id":"option_0","sort_order":0,"product_mapping_id":"CVAANC1040","display_width":"5\"","display_height":"7\"","orientation":"Portrait","is_default":false},{"id":"option_2","sort_order":1,"product_mapping_id":"CVAANC1055","display_width":"4\"","display_height":"8\"","orientation":"Portrait","is_default":false}]',// phpcs:ignore
                ['option_1', 'option_2'],
                $this->createPartialMock(
                    Product::class,
                    ['getCustomAttribute'],
                ),
                $this->createPartialMock(
                    AttributeInterface::class,
                    ['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode']
                )
            ],
            [
                '',
                [],
                $this->createPartialMock(
                    Product::class,
                    ['getCustomAttribute'],
                ),
                $this->createPartialMock(
                    AttributeInterface::class,
                    ['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode']
                )
            ],
            [
                '',
                [],
                null,
                null
            ],
        ];
    }
}
