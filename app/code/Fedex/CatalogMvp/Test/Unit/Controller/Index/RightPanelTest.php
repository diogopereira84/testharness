<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Controller\Index;

use Fedex\CatalogMvp\Controller\Index\RightPanel;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Helper\Image;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Model\ProductActivity;
use Fedex\CatalogMvp\Model\ResourceModel\ProductActivity\Collection as ProductActivityCollection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class RightPanelTest extends TestCase
{

    /**
     * @var RightPanel
     */
    private RightPanel $rightPanel;

    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactoryMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var Product|MockObject
     */
    private $productModelMock;

    private $imageHelper;
    protected $catalogMvpHelper;
    protected $productActivity;
    protected $productActivityCollection;
    protected $productRepository;
    protected $toggleConfigMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productModelMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->imageHelper = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->setMethods(['init','setImageFile','keepFrame','getUrl','getDefaultPlaceholderUrl'])
            ->getMock();
        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEnableCatalogUpdate'])
            ->getMock();
        $this->productActivity = $this->getMockBuilder(ProductActivity::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getCreatedAt','getUserName'])
            ->getMock();
        $this->productActivityCollection = $this->getMockBuilder(ProductActivityCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','setOrder','getFirstItem'])
            ->getMock();

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();         
            
            
        $this->rightPanel = $objectManager->getObject(
            RightPanel::class,
            [
                'jsonFactory'  => $this->jsonFactoryMock,
                'context'      => $this->contextMock,
                'productModel' => $this->productModelMock,
                'imageHelper' => $this->imageHelper,
                'catalogMvpHelper' => $this->catalogMvpHelper,
                'productActivity' => $this->productActivity,
                'productRepository' => $this->productRepository,
                'toggleConfig'=>$this->toggleConfigMock
            ]
        );

    }

    public function testExecuteWithSku(): void
    {
        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($requestMock);

        $requestMock
            ->method('getParam')
            ->withConsecutive(
                ['item_id'],
                ['itemsku']
            )
            ->willReturnOnConsecutiveCalls(
                '',
                '23456'
            );
        $this->productRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->productModelMock); 
        $jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($jsonMock);

        $jsonMock->expects($this->any())
            ->method('setData')
            ->with(['external_prod' => null, 'pod2_0_editable' => null,'attribute_set_id' => null , 'customizable' => null,'catalog_description'=>null,'related_keywords'=>null]);
        $result = $this->rightPanel->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
    public function testExecute(): void
    {
        $itemId = 123;

        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMock();

        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('item_id')
            ->willReturn($itemId);

        $this->contextMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);

        $jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($jsonMock);

        $productDataMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData','getUpdatedAt'])
            ->getMock();

        $productDataMock->expects($this->any())
            ->method('getData')
            ->willReturn(['id' => $itemId, 'name' => 'Sample Product']);

        $this->productRepository->expects($this->any())
            ->method('getById')
            ->willReturn($productDataMock);   

        $productDataMock->expects($this->any())
            ->method('getUpdatedAt')
            ->willReturn('04/04/2024 12:12:12');

        $this->imageHelper->expects($this->once())
            ->method('init')
            ->willReturnSelf();
        $this->imageHelper->expects($this->once())
            ->method('setImageFile')
            ->willReturnSelf();
        $this->imageHelper->expects($this->once())
            ->method('keepFrame')
            ->willReturnSelf();
        $this->imageHelper->expects($this->once())
            ->method('getUrl')
            ->willReturn("https://staging3.office.fedex.com/media/test.jpeg");

        $this->productActivity->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productActivityCollection);
        $this->productActivityCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->productActivityCollection->expects($this->any())
            ->method('setOrder')
            ->willReturnSelf();
        $this->productActivityCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->productActivity);
        $this->productActivity->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('04/04/2024 12:12:12');
        $this->productActivity->expects($this->any())
            ->method('getUserName')
            ->willReturn('Test Test');
        $jsonMock->expects($this->any())
            ->method('setData')
            ->with(['id' => $itemId, 'name' => 'Sample Product']);
        $result = $this->rightPanel->execute();
        $this->assertInstanceOf(Json::class, $result);

    }


    public function testGetProduct(): void
    {
        $itemId = 123;

        $productDataMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository->expects($this->any())
            ->method('getById')
            ->willReturn($productDataMock);        
        $result = $this->rightPanel->getProduct($itemId);
        $this->assertInstanceOf(\Magento\Framework\DataObject::class, $result);
        $this->assertSame($productDataMock, $result);

    }
}
