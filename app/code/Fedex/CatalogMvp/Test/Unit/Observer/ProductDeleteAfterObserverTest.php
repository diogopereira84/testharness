<?php

namespace Fedex\CatalogMvp\Test\Unit\Observer;


use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Catalog\Model\Session as catalogSession;
use Fedex\CatalogMvp\Observer\ProductDeleteAfterObserver;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CatalogMvp\Model\ProductActivity;
use Magento\Backend\Model\Auth\Session as AdminSession;

class ProductDeleteAfterObserverTest extends TestCase
{
    protected $catalogMvpHelperMock;
    protected $loggerMock;
    protected $attributeSetRepositoryMock;
    protected $catalogDocumentRefranceApiMock;
    protected $catalogSessionMock;
    protected $observerMock;
    protected $attributeSetInterfaceMock;
    protected $productMock;
    protected $adminSessionMock;
    protected $productActivityMock;
    /**
     * @var (\Magento\Framework\Event & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eventMock;
    /**
     * @var (\Magento\Framework\Exception\NoSuchEntityException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $noSuchEntityInterfaceMock;
    protected $productDeleteAfterObserver;
    protected function setUp(): void
    {

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();

        $this->attributeSetRepositoryMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteProductRef'])
            ->getMock();

        $this->catalogSessionMock = $this->getMockBuilder(catalogSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDocumentId', 'unsDocumentId', 'getProductName', 'unsProductName'])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();

        $this->attributeSetInterfaceMock = $this->getMockBuilder(AttributeSetInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getId'])
            ->getMock();

        $this->adminSessionMock = $this->getMockBuilder(AdminSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUser', 'isLoggedIn', 'getName', 'getId'])
            ->getMock();

        $this->productActivityMock = $this->getMockBuilder(ProductActivity::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'save'])
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->noSuchEntityInterfaceMock = $this->getMockBuilder(NoSuchEntityException::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $objectManagerHelper = new ObjectManager($this);
            $this->productDeleteAfterObserver = $objectManagerHelper->getObject(
                ProductDeleteAfterObserver::class,
                [
                    'catalogMvpHelper' => $this->catalogMvpHelperMock,
                    'logger' => $this->loggerMock,
                    'attributeSetRepository' => $this->attributeSetRepositoryMock,
                    'catalogDocumentRefranceApi'=> $this->catalogDocumentRefranceApiMock,
                    'catalogSession' => $this->catalogSessionMock,
                    'productActivity' => $this->productActivityMock,
                    'adminSession' => $this->adminSessionMock
                ]
            );

    }

    /**
     * @test testExecute
     */
    public function testExecute()
    {
        $this->catalogMvpHelperMock->expects($this->any())
        ->method('isMvpCtcAdminEnable')
        ->willReturn(true);

        $this->observerMock->expects($this->any())
        ->method('getProduct')
        ->willReturn($this->productMock);

        $this->catalogSessionMock
            ->expects($this->any())
            ->method('getProductName')
            ->willReturn("Check");

        $this->catalogSessionMock
            ->expects($this->any())
            ->method('unsProductName')
            ->willReturnSelf();

        $this->attributeSetRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->attributeSetInterfaceMock);

        $this->attributeSetInterfaceMock
        ->expects($this->any())
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand');

        $documentIdArray =  [123,456];

        $this->catalogSessionMock
            ->expects($this->any())
            ->method('getDocumentId')
            ->willReturn($documentIdArray);
        
        $this->catalogSessionMock
            ->expects($this->any())
            ->method('unsDocumentId')
            ->willReturnSelf();

        $this->adminSessionMock->expects($this->any())
        ->method('isLoggedIn')
        ->willReturn(true);

        $this->adminSessionMock->expects($this->any())
        ->method('getUser')
        ->willReturnSelf();

        $this->adminSessionMock->expects($this->any())
        ->method('getName')
        ->willReturnSelf();

        $this->adminSessionMock->expects($this->any())
        ->method('getId')
        ->willReturnSelf();

        $this->productActivityMock->expects($this->any())
        ->method('setData')
        ->willReturnSelf();

        $this->productActivityMock->expects($this->any())
        ->method('save')
        ->willReturnSelf();

        $this->productMock
        ->expects($this->any())
            ->method('getId')
            ->willReturn('123');
        
        $this->catalogDocumentRefranceApiMock
            ->expects($this->any())
            ->method('deleteProductRef')
            ->willReturn(null);

        $this->assertNotNull($this->productDeleteAfterObserver->execute($this->observerMock));
    }

    /**
     * @test testExecute
     */
    public function testExecuteCatch()
    {
        $this->catalogMvpHelperMock->expects($this->any())
        ->method('isMvpCtcAdminEnable')
        ->willReturn(true);

        $this->observerMock->expects($this->any())
        ->method('getProduct')
        ->willReturn($this->productMock);

        $this->attributeSetRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->attributeSetInterfaceMock);

        $this->attributeSetInterfaceMock
        ->expects($this->any())
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand');

        $this->catalogSessionMock
            ->expects($this->any())
            ->method('getDocumentId')
            ->willThrowException(new NoSuchEntityException());
        
        $this->loggerMock
        ->expects($this->any())
        ->method('error')
        ->willReturnSelf();
        
        $this->assertNull($this->productDeleteAfterObserver->execute($this->observerMock));
    }

    /**
     * @test testExecute
     */
    public function testExecuteWithSaveActivity()
    {
        $this->catalogMvpHelperMock->expects($this->any())
        ->method('isMvpCtcAdminEnable')
        ->willReturn(true);

        $this->observerMock->expects($this->any())
        ->method('getProduct')
        ->willReturn($this->productMock);

        $this->catalogSessionMock
            ->expects($this->any())
            ->method('getProductName')
            ->willReturn("Check");

        $this->catalogSessionMock
            ->expects($this->any())
            ->method('unsProductName')
            ->willReturnSelf();

        $this->attributeSetRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->attributeSetInterfaceMock);

        $this->attributeSetInterfaceMock
        ->expects($this->any())
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand');

        $this->catalogSessionMock
            ->expects($this->any())
            ->method('getDocumentId')
            ->willReturnSelf();
        
        $this->adminSessionMock->expects($this->any())
        ->method('isLoggedIn')
        ->willReturn(true);

        $this->adminSessionMock->expects($this->any())
        ->method('getUser')
        ->willReturnSelf();

        $this->adminSessionMock->expects($this->any())
        ->method('getName')
        ->willReturnSelf();

        $this->adminSessionMock->expects($this->any())
        ->method('getId')
        ->willReturnSelf();

        $this->productActivityMock->expects($this->any())
        ->method('setData')
        ->willReturnSelf();

        $this->productActivityMock->expects($this->any())
        ->method('save')
        ->willThrowException(new NoSuchEntityException());

        $this->loggerMock
        ->expects($this->any())
        ->method('error')
        ->willReturnSelf();
        
        $this->assertIsObject($this->productDeleteAfterObserver->execute($this->observerMock));
    }
}
