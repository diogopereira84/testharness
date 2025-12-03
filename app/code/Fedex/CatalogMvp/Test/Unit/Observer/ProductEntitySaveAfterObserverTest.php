<?php

namespace Fedex\CatalogMvp\Test\Unit\Observer;


use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Observer\ProductEntitySaveAfterObserver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductEntitySaveAfterObserverTest extends TestCase
{
    protected $catalogMvpHelperMock;
    protected $catalogDocumentRefranceApiMock;
    protected $toggleConfigMock;
    protected $productMock;
    protected $observerMock;
    protected $productEntitySaveAfterObserver;
    protected function setUp(): void
    {

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable'])
            ->getMock();
        
        $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateProductDocumentEndDate','documentLifeExtendApiCall'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExternalProd','getId','getCreatedAt','getUpdatedAt','getAttributeSetId','setProductCreatedDate','setProductUpdatedDate','setProductAttributeSetsId','save'])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'getProduct'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->productEntitySaveAfterObserver = $objectManagerHelper->getObject(
            ProductEntitySaveAfterObserver::class,
            [
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'product' => $this->productMock,
                'catalogDocumentRefranceApiMock' => $this->catalogDocumentRefranceApiMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * @test testExecute
     */
    public function testexecute()
    {
        $postData = '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}';


        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
            
        $this->toggleConfigMock ->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->productMock->expects($this->any())
            ->method('getExternalProd')
            ->willReturn($postData);
        
        $this->productMock->expects($this->any())
            ->method('getId')
            ->willReturn(5293);
        
        $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('documentLifeExtendApiCall')
            ->willReturn(true);
            
        $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

            $this->assertNotNull($this->productEntitySaveAfterObserver->execute($this->observerMock));
    }
    
    public function testexecuteAscToggleOff()
    {
         $postData = '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}';


        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        
        $this->toggleConfigMock ->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->productMock->expects($this->any())
            ->method('getExternalProd')
            ->willReturn($postData);
        
        $this->productMock->expects($this->any())
            ->method('getId')
            ->willReturn(5293);
        
        $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('documentLifeExtendApiCall')
            ->willReturn(true);
            
        $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

        $this->assertNotNull($this->productEntitySaveAfterObserver->execute($this->observerMock));
    }

    public function testexecuteToggleOff()
    {
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(false);
        $this->assertNotNull($this->productEntitySaveAfterObserver->execute($this->observerMock));
    }

    
}
