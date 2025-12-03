<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Controller\Index;

use Fedex\CatalogMvp\Controller\Index\RenameItem;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;

class RenameItemTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $action;
    protected $catalogMvp;
    protected $resultJsonFactory;
    protected $resultJson;
    protected $request;
    protected $storeManager;
    protected $store;
    protected $productRepositoryInterface;
    protected $product;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $renameItem;
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = $this->getMockBuilder(Action::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateAttributes'])
            ->getMock();
        $this->catalogMvp = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable','isSharedCatalogPermissionEnabled', 'insertProductActivity', 'getCustomerSessionId', 'isD231833FixEnabled'])
            ->getMock();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMockForAbstractClass();
        $this->productRepositoryInterface = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();
            
        $this->objectManager = new ObjectManager($this);
        $this->renameItem = $this->objectManager->getObject(
            RenameItem::class,
            [
                'context' => $this->context,
                'action' => $this->action,
                'resultJsonFactory' => $this->resultJsonFactory,
                'catalogMvp' => $this->catalogMvp,
                '_request' => $this->request,
                'storeManager' => $this->storeManager,
                'productRepositoryInterface' => $this->productRepositoryInterface
            ]
        );
    }

    public function testExecute(): void
    {
        $data = [];
        $data['id'] = 234;
        $data['name'] = "Rename item";
        $externalProd = '{"fxoMenuId":"1582146604697-4","fxoProductInstance":{"id":"1697444251870","name":"images","productConfig":{"product":{"userProductName":"Test Product"}}}}';
        $this->request->expects($this->any())->method('getPost')->willReturn($data);
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('getCustomerSessionId')->willReturn(null);
        $this->catalogMvp->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->productRepositoryInterface->expects($this->any())->method('getBYId')->willReturn($this->product);
        $this->product->expects($this->any())->method('getData')->willReturn($externalProd);
        $this->store->expects($this->any())->method('getStoreId')->willReturn(89);
        $this->action->expects($this->any())->method('updateAttributes')->willReturnSelf();
        $this->catalogMvp->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        $this->assertEquals($this->resultJson, $this->renameItem->execute());
    }
    /**
     * Test Execute with new Configurator JSON
     */
    public function testExecuteWithNewdJson(): void
    {
        $data = [];
        $data['id'] = 234;
        $data['name'] = "Rename item";
        $externalProd = '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}';
        $this->request->expects($this->any())->method('getPost')->willReturn($data);
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->productRepositoryInterface->expects($this->any())->method('getBYId')->willReturn($this->product);
        $this->product->expects($this->any())->method('getData')->willReturn($externalProd);
        $this->store->expects($this->any())->method('getStoreId')->willReturn(89);
        $this->action->expects($this->any())->method('updateAttributes')->willReturnSelf();
        $this->assertEquals($this->resultJson, $this->renameItem->execute());
    }
    public function testExecuteWithException(): void
    {
        $data = [];
        $data['id'] = 234;
        $data['name'] = "Rename Folder";
        $this->request->expects($this->any())->method('getPost')->willReturn($data);
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->action->expects($this->any())->method('updateAttributes')->willThrowException(new \Exception());
        $this->assertEquals($this->resultJson, $this->renameItem->execute());
    }
}
