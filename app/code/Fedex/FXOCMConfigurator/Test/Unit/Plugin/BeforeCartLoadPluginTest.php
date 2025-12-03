<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartItemInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\FXOCMConfigurator\Plugin\BeforeCartLoadPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Request\Http as HttpRequest;

class BeforeCartLoadPluginTest extends TestCase
{
    protected $catalogDocumentRefranceApiMock;
    protected $serializerMock;
    protected $toggleConfigMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $quoteMock;
    protected $quoteItemMock;
    protected $itemOptionMock;
    protected $productRepositoryMock;
    protected $productInterfaceMock;
    protected $httpMock;
    protected $beforecartload;
    protected $catalogDocumentRefranceApi;
    protected $logger;
    protected $toggleConfig;
    protected $serializer;

    private const EXTERNAL_PROD_DATA = [
        'external_prod' => [
            0 => [
                'contentAssociations' => [
                    [
                        'contentReference' => 123,
                        'preview_url' => '17746107420130251744419876699421440978331'
                    ]
                ],
                'preview_url' => '17746107420130251744419876699421440978331'
            ]
        ]
    ];

    private const REPORT = [
        'output' => [
            'document' => [
                'documentId' => 17746107420130251744419876699421440978331
            ]
        ]
    ];

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->setMethods(['documentLifeExtendApiCallWithDocumentId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->setMethods(['unserialize', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMockForAbstractClass();
        
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->quoteItemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getId', 'getOptionByCode'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->itemOptionMock = $this->getMockBuilder(Option::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

       $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getData','getCustomizable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->httpMock = $this->getMockBuilder(HttpRequest::class)
            ->setMethods(['getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->beforecartload = $objectManager->getObject(
            BeforeCartLoadPlugin::class,
            [
                'catalogDocumentRefranceApi' => $this->catalogDocumentRefranceApiMock,
                'serializer' => $this->serializerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'logger' => $this->loggerMock,
                'productRepositoryInterface' => $this->productRepositoryMock,
                'http' => $this->httpMock
            ]
        );
    }
    
    public function testBeforeGetItems()
    {
        $items = [];

        $this->httpMock
            ->expects($this->any())
            ->method('getFullActionName')
            ->willReturn('checkout_cart_index');
        
        $this->toggleConfigMock
            ->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        
        $this->quoteMock
            ->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$this->quoteItemMock]);
        
        $this->quoteItemMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        
        $this->quoteItemMock
            ->expects($this->any())
            ->method('getOptionByCode')
            ->willReturn($this->itemOptionMock);
        
        $this->itemOptionMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(self::EXTERNAL_PROD_DATA);
        
        $this->serializerMock
            ->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::EXTERNAL_PROD_DATA);

         $this->productRepositoryMock->expects($this->any())->method('get')->willReturn($this->productInterfaceMock); 
         $this->productInterfaceMock->expects($this->any())->method('getData')->willReturn('[]');
         $this->productInterfaceMock->expects($this->any())->method('getCustomizable')->willReturn(1);     

        $this->catalogDocumentRefranceApiMock
            ->expects($this->any())
            ->method('documentLifeExtendApiCallWithDocumentId')
            ->willReturn(self::REPORT);

        $result = $this->beforecartload->beforeGetItems($this->quoteMock, $items);
        $this->assertNotNull($result);
    }
}