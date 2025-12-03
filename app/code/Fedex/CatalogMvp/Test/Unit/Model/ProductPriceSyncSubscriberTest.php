<?php

namespace Fedex\CatalogMvp\Model\Test\Unit;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\CatalogMvp\Api\ProductPriceSyncSubscriberInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\Serialize\Serializer\Json as serializerJson;
use Fedex\CatalogMvp\Model\ProductPriceSyncSubscriber;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\CatalogMvp\Helper\EmailHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class ProductPriceSyncSubscriberTest extends TestCase
{
    protected $MessageInterface;
    protected $StoreManagerInterfaceMock;
    protected $productRepositoryInterfaceMock;
    /**
     * @var (\Magento\Catalog\Model\Product & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ProductModelMock;
    protected $catalogPriceSyncHelperMock;
    /**
     * @var (\Fedex\CatalogMvp\Model\Test\Unit\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $CollectionMock;
    protected $companyFactoryMock;
    protected $CompanyCollectionMock;
    protected $serializerJson;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterface;
    protected $isDataObjectMock;
    /**
     * @var (\Fedex\CatalogMvp\Helper\EmailHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $emailHelper;
    protected $toggleConfig;
    protected $registry;
    /**
     * @var (\Magento\Framework\Pricing\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $priceHelper;
    protected $ProductPriceSyncSubscriber;
    private const ARRAY_DATA = [
        'output' => [
            'rate' => [
                'rateDetails' => [
                    0=>[
                        'productLines' => [
                            0=>[
                                'priceable'=>true
                                ]
                            ],
                        'netAmount'=>'$12'
                        ]
                    ]
                ]
            ]
        ];

    private const ARRAY_DATA_NOT_priceable = [
        'output' => [
            'rate' => [
                'rateDetails' => [
                    0=> [
                        'productLines' => [
                            0 => [
                                'priceable'=>false
                                ]
                            ],
                        'netAmount'=>'$12'
                        ]
                    ]
                ]
            ]
        ];

    protected function setUp(): void
    {

        $this->MessageInterface = $this->getMockBuilder(MessageInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

        $this->StoreManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getStore', 'getWebsiteId'])
            ->getMockForAbstractClass();


        $this->productRepositoryInterfaceMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->ProductModelMock = $this->getMockBuilder(ProductModel::class)
            ->DisableOriginalConstructor()
            ->setMethods(['load', 'setStatus', 'save', 'addFieldToFilter', 'get', 'getExternalProd', 'setExternalProd'])
            ->getMock();

        $this->catalogPriceSyncHelperMock = $this->getMockBuilder(CatalogPriceSyncHelper::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create', 'getProductPrice', 'QuequcleanUp', 'getCorrectPriceToggle', 'customerAdminCheck'])
            ->getMock();

        $this->CollectionMock = $this->getMockBuilder(Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->DisableOriginalConstructor()
            ->setMethods(['load', 'setStatus', 'save', 'addFieldToFilter', 'getData'])
            ->getMock();

        $this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->CompanyCollectionMock = $this->getMockBuilder(Magento\Company\Model\Collection::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create', 'addFieldToFilter', 'getData', 'getCollection'])
            ->getMock();

        $this->serializerJson = $this->getMockBuilder(serializerJson::class)
            ->DisableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->isDataObjectMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getReorderable',
                'getDiff',
                'getStatus',
                'setData',
                'save',
                'getFolderPath',
                'getAddedBy',
                'getExternalProd',
                'getPublished',
                'getSentToCustomer'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailHelper = $this->getMockBuilder(EmailHelper::class)
            ->setMethods([
                'sendReadyForReviewEmailCustomerAdmin',
                'sendReadyForOrderEmailCustomerAdmin',
                'getCustomerDetails'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->setMethods(['register', 'registry'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceHelper = $this->getMockBuilder(PriceHelper::class)
            ->setMethods(['currency'])
            ->disableOriginalConstructor()
            ->getMock();


        $objectManagerHelper = new ObjectManager($this);
        $this->ProductPriceSyncSubscriber = $objectManagerHelper->getObject(
            ProductPriceSyncSubscriber::class,
            [
                'serializerJson' => $this->serializerJson,
                'logger' => $this->loggerInterface,
                'catalogPriceSyncHelper' =>  $this->catalogPriceSyncHelperMock,
                'productRepositoryInterface' => $this->productRepositoryInterfaceMock,
                'companyFactory' => $this->companyFactoryMock,
                'storeManager' => $this->StoreManagerInterfaceMock,
                'emailHelper' => $this->emailHelper,
                'toggleConfig' => $this->toggleConfig,
                'registry' => $this->registry,
                'priceHelper' => $this->priceHelper
            ]
        );
    }
    public function testgetSiteNameByCustomerGroupId()
    {
        $messageData = [
            'site_name' => 'test',
            'sku' => 'adfajsdfkjasdf'
        ];

        $this->companyFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->CompanyCollectionMock);

        $this->CompanyCollectionMock->expects($this->any())
            ->method('getCollection')
            ->willReturnSelf();

        $this->CompanyCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn([$this->CompanyCollectionMock]);

        $this->CompanyCollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($messageData);

        $result = $this->ProductPriceSyncSubscriber->getSiteNameByCustomerGroupId(35);
        $this->assertNotNull($result);

    }

    public function testgetSiteNameByCustomerGroupIdelse()
    {
        $messageData = [
            'site_name' => 'test',
            'sku' => 'adfajsdfkjasdf'
        ];

        $this->companyFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->CompanyCollectionMock);

        $this->CompanyCollectionMock->expects($this->any())
            ->method('getCollection')
            ->willReturnSelf();

        $this->CompanyCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn(null);

        $this->CompanyCollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($messageData);

        $result = $this->ProductPriceSyncSubscriber->getSiteNameByCustomerGroupId(35);
        $this->assertNotNull($result);
    }

    public function testprocessMessage()
    {

        $messageData = [
            0 => [
                'sku' => 'test',
                'customer_group_id' => 35,
                'shared_catalog_id' => 32

            ]
        ];

        $jsonData = json_encode($messageData);
        $jsonArray = json_decode($jsonData, true);

        $this->MessageInterface->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);

        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);

        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willReturn($this->isDataObjectMock);

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('getProductPrice')
            ->willReturn(self::ARRAY_DATA);

        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->isDataObjectMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->isDataObjectMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('QuequcleanUp')
            ->willReturnSelf();

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('getCorrectPriceToggle')
            ->willReturn(true);

        $this->testgetSiteNameByCustomerGroupId();

        $result = $this->ProductPriceSyncSubscriber->processMessage($this->MessageInterface);
        $this->assertNull($result);
    }

    public function testprocessMessageWithException()
    {

        $messageData = [
            0 => [
                'site' => 'test',
                'customer_group_id' => 35,
                'shared_catalog_id' => 32

            ]

        ];

        $jsonData = json_encode($messageData);
        $jsonArray = json_decode($jsonData, true);

        $this->MessageInterface->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);

        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);


        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willReturn($this->isDataObjectMock);

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('getProductPrice')
            ->willReturn(self::ARRAY_DATA);

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('QuequcleanUp')
            ->willReturnSelf();

        $this->testgetSiteNameByCustomerGroupId();

        $result = $this->ProductPriceSyncSubscriber->processMessage($this->MessageInterface);
        $this->assertNull($result);
    }

    public function testprocessMessageUpdateForSameProduct()
    {

        $messageData = [
            0 => [
                'sku' => 'test',
                'customer_group_id' => 35,
                'shared_catalog_id' => 32,
                'is_for_same_product' => true

            ]

        ];

        $jsonData = json_encode($messageData);
        $jsonArray = json_decode($jsonData, true);

        $this->MessageInterface->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);

        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);


        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willReturn($this->isDataObjectMock);

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('getProductPrice')
            ->willReturn(self::ARRAY_DATA);

        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->isDataObjectMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->isDataObjectMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('QuequcleanUp')
            ->willReturnSelf();

        $this->testgetSiteNameByCustomerGroupId();

        $result = $this->ProductPriceSyncSubscriber->processMessage($this->MessageInterface);
        $this->assertNull($result);
    }

    public function testProcessMessageWithToggleOn()
    {

        $messageData = [
            0 => [
                'sku' => 'test',
                'customer_group_id' => 35,
                'shared_catalog_id' => 32

            ]

        ];

        $jsonData = json_encode($messageData);
        $jsonArray = json_decode($jsonData, true);

        $this->MessageInterface->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);

        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);


        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willReturn($this->isDataObjectMock);

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('getProductPrice')
            ->willReturn(self::ARRAY_DATA);

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('customerAdminCheck')
            ->willReturn(false);

        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->isDataObjectMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->isDataObjectMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('QuequcleanUp')
            ->willReturnSelf();

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->isDataObjectMock->expects($this->any())
            ->method('getFolderPath')
            ->willReturn('TestPath');

        $this->isDataObjectMock->expects($this->any())
            ->method('getAddedBy')
            ->willReturn(98);

        $this->isDataObjectMock->expects($this->any())
            ->method('getSentTocustomer')
            ->willReturn(true);
        $this->isDataObjectMock->expects($this->any())
            ->method('getPublished')
            ->willReturn(true);

        $this->isDataObjectMock->expects($this->any())
            ->method('getExternalProd')
            ->willReturn('{"id":1456773326927,"version":2,"name":"Multi Sheet","qty":1,"priceable":false,"externalSkus":[{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false},{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false},{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false}]}');

        $this->testgetSiteNameByCustomerGroupId();

        $result = $this->ProductPriceSyncSubscriber->processMessage($this->MessageInterface);
        $this->assertNull($result);
    }

    public function testSendReadyForReviewEmailWithSentEmail()
    {
        $responsePrice = '$100';
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willReturn($this->isDataObjectMock);
        $this->registry->expects($this->any())
            ->method('registry')
            ->willReturn(true);
        $result = $this->ProductPriceSyncSubscriber->sendReadyForReviewEmail($this->isDataObjectMock, true, $responsePrice);
        $this->assertNotNull($result);
    }

    public function testSendReadyForReviewEmail()
    {
        $responsePrice = '$100';
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willReturn($this->isDataObjectMock);

        $this->isDataObjectMock->expects($this->any())
            ->method('getFolderPath')
            ->willReturn('TestPath');

        $this->isDataObjectMock->expects($this->any())
            ->method('getAddedBy')
            ->willReturn(98);
        $this->isDataObjectMock->expects($this->any())
            ->method('getSentTocustomer')
            ->willReturn(true);
        $this->isDataObjectMock->expects($this->any())
            ->method('getPublished')
            ->willReturn(false);

        $this->isDataObjectMock->expects($this->any())
            ->method('getExternalProd')
            ->willReturn('{"id":1456773326927,"version":2,"name":"Multi Sheet","qty":1,"priceable":false,"externalSkus":[{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false},{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false},{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false}]}');

        $result = $this->ProductPriceSyncSubscriber->sendReadyForReviewEmail($this->isDataObjectMock, true, $responsePrice);
        $this->assertNotNull($result);
    }

    // test when responce price is null
    public function testProcessMessageWithoutResponcePrice()
    {
        $messageData = [
            0 => [
                'sku' => 'test',
                'customer_group_id' => 35,
                'shared_catalog_id' => 32

            ]

        ];
        $jsonData = json_encode($messageData);
        $jsonArray = json_decode($jsonData, true);
        $this->MessageInterface->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willReturn($this->isDataObjectMock);
            $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('customerAdminCheck')
            ->willReturn(false);
        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();
        $this->StoreManagerInterfaceMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->isDataObjectMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->isDataObjectMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->catalogPriceSyncHelperMock->expects($this->any())
            ->method('QuequcleanUp')
            ->willReturnSelf();
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->isDataObjectMock->expects($this->any())
            ->method('getFolderPath')
            ->willReturn('TestPath');
        $this->isDataObjectMock->expects($this->any())
            ->method('getAddedBy')
            ->willReturn(98);
        $this->isDataObjectMock->expects($this->any())
            ->method('getSentTocustomer')
            ->willReturn(true);
        $this->isDataObjectMock->expects($this->any())
            ->method('getPublished')
            ->willReturn(true);
        $this->isDataObjectMock->expects($this->any())
            ->method('getExternalProd')
            ->willReturn('{"id":1456773326927,"version":2,"name":"Multi Sheet","qty":1,"priceable":false,"externalSkus":[{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false},{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false},{"code":"abc","unitPrice":"$1.00","price":"$21.00","qty":20,"applyProductQty":false}]}');
        $this->testgetSiteNameByCustomerGroupId();    
        $result = $this->ProductPriceSyncSubscriber->processMessage($this->MessageInterface);
        $this->assertNull($result);
    }

}
