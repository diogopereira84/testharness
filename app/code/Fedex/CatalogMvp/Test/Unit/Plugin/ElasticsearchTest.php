<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\SharedCatalog\Model\ProductItem;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Collection as ProductItemCollection;
use Magento\Elasticsearch7\Model\Client\Elasticsearch;
use Fedex\CatalogMvp\Plugin\Elasticsearch as ElasticsearchPlugin;
use Fedex\Delivery\Helper\Data as Delivery;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Fedex\SelfReg\Helper\SelfReg;

class ElasticsearchTest extends TestCase
{
    protected $customerSession;
    protected $customer;
    protected $productItem;
    protected $productItemCollection;
    protected $productFactory;
    protected $product;
    protected $productCollection;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registry;
    protected $category;
    protected $request;
    protected $subject;
    protected $deliveryHelper;
    protected $catalogMvpHelper;
    protected $selfreg;
    protected $collectionMock;
    protected $elasticsearchPlugin;
    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->productItem = $this->getMockBuilder(ProductItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection'])
            ->getMock();

        $this->productItemCollection = $this->getMockBuilder(ProductItemCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect','addFieldToFilter','getColumnValues'])
            ->getMock();

        $this->productFactory = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection'])
            ->getMock();

        $this->productCollection = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect','addFieldToFilter','getColumnValues','addAttributeToFilter', 'getSelect'])
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMock();

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductCollection'])
            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFullActionName'])
            ->getMock();

        $this->subject = $this->getMockBuilder(Elasticsearch::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(Delivery::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer'])
            ->getMock();

        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFilteredCategoryItem', 'getCurrentCategory', 'getAttrSetIdByName', 'checkPrintCategory'])
            ->getMock();

        $this->selfreg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer'])
            ->getMock();

        $this->collectionMock  = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create', 'addFieldToFilter', 'getSelect', 'where','getIterator','getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->elasticsearchPlugin = $objectManagerHelper->getObject(
            ElasticsearchPlugin::class,
            [
                'customerSession' => $this->customerSession,
                'productItem' => $this->productItem,
                'productFactory' => $this->productFactory,
                'request' => $this->request,
                'deliveryHelper' => $this->deliveryHelper,
                'catalogMvpHelper' => $this->catalogMvpHelper,
                'productCollectionFactory' => $this->collectionMock,
                'selfreg' => $this->selfreg
            ]
        );
    }

    /**
     * @test testAfterGetMegaMenuHtml
     */
    public function testBeforeQuery()
    {
        $query = [];
        $query['body']['query']['bool']['must_not'] = [];
        $query['body']['query']['bool']['must'] = [];
        $this->request->expects($this->any())->method('getFullActionName')->willReturn("catalog_category_view");
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('getCurrentCategory')->willReturn( $this->category);
        $this->category->expects($this->any())->method('getProductCollection')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getData')->with('group_id')->willReturn('108');
        $this->productItem->expects($this->any())->method('getCollection')->willReturn($this->productItemCollection);
        $this->productItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->productItemCollection->expects($this->any())->method('getColumnValues')
            ->willReturn(['sku1','sku2','sku3']);
        $this->selfreg->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('checkPrintCategory')->willReturn(false);
        $this->productFactory->expects($this->any())->method('create')->willReturn($this->product);
        $this->product->expects($this->any())->method('getCollection')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('create')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('where')->willReturnSelf();

        $categoryItem = ['1727','525','344'];
        $allowItem = ['1727','525','567'];
        $this->productCollection
            ->method('getColumnValues')
            ->withConsecutive(['entity_id'], ['entity_id'])
            ->willReturnOnConsecutiveCalls(
                $categoryItem,
                $allowItem
            );
        $this->catalogMvpHelper->expects($this->any())->method('getFilteredCategoryItem')->willReturn($categoryItem);
        $this->catalogMvpHelper->expects($this->any())->method('getAttrSetIdByName')->willReturn('8');
        $this->assertIsArray($this->elasticsearchPlugin->beforeQuery($this->subject, $query));
    }

    /**
     * @test testAfterGetMegaMenuHtml
     */
    public function testBeforeQueryWithoutSelfReg()
    {
        $query = [];
        $query['body']['query']['bool']['must_not'] = [];
        $query['body']['query']['bool']['must'] = [];
        $this->request->expects($this->any())->method('getFullActionName')->willReturn("catalog_category_view");
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('getCurrentCategory')->willReturn( $this->category);
        $this->category->expects($this->any())->method('getProductCollection')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getData')->with('group_id')->willReturn('108');
        $this->productItem->expects($this->any())->method('getCollection')->willReturn($this->productItemCollection);
        $this->productItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->productItemCollection->expects($this->any())->method('getColumnValues')
            ->willReturn(['sku1','sku2','sku3']);
        $this->selfreg->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        $this->catalogMvpHelper->expects($this->any())->method('checkPrintCategory')->willReturn(false);
        $this->productFactory->expects($this->any())->method('create')->willReturn($this->product);
        $this->product->expects($this->any())->method('getCollection')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('create')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('where')->willReturnSelf();

        $categoryItem = ['1727','525','344'];
        $allowItem = ['1727','525','567'];
        $this->productCollection
            ->method('getColumnValues')
            ->withConsecutive(['entity_id'], ['entity_id'])
            ->willReturnOnConsecutiveCalls(
                $categoryItem,
                $allowItem
            );
        $this->catalogMvpHelper->expects($this->any())->method('getFilteredCategoryItem')->willReturn($categoryItem);
        $this->catalogMvpHelper->expects($this->any())->method('getAttrSetIdByName')->willReturn('8');
        $this->assertIsArray($this->elasticsearchPlugin->beforeQuery($this->subject, $query));
    }
}
