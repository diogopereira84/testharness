<?php

namespace Fedex\CatalogMvp\Test\Unit\Helper;


use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data as PunchoutHelperData;
use \Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Catalog\Model\ProductRepository;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CatalogMvp\HTTP\Client\Curl as FedexCurl;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Model\DocRefMessage;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class CatalogDocumentRefranceApiTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $toggleConfigMock;
    protected $scopeConfigMock;
    protected $punchoutHelperMock;
    protected $productFactoryMock;
    protected $productMock;
    protected $curlMock;
    protected $productRepositoryMock;
    protected $catalogMvpMock;
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\Exception\NoSuchEntityException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $noSuchEntityInterfaceMock;
    protected $collectionFactoryMock;
    protected $fedexDeleteMock;
    protected $collectionMock;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerJsonMock;
    /**
     * @var (\Fedex\CatalogMvp\Model\DocRefMessage & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageMock;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $publisherMock;
    protected $scopeConfigInterface;
    protected $toggleConfig;
    protected $punchoutHelper;
    protected $productFactory;
    protected $curl;
    protected $productRepository;
    protected $catalogMvp;
    protected $logger;

    protected $catalogDocumentRefranceApi;
    public const PRODUCT_ID = 4567;
    protected $resourceConnection;
    protected $adapterInterface;
    protected $companyFactory;
    protected $customerFactory;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->punchoutHelperMock = $this->getMockBuilder(PunchoutHelperData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAuthGatewayToken','getTazToken','getGatewayToken'])
            ->getMock();

        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create',
            'load',
            'getAddedBy',
            'getSku',
            'getId',
            'getName',
            'getProductDocumentExpireDate',
            'getFolderPath',
            'getEntityId'
            ])->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','setData','save'])
            ->getMock();

        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['setOptions', 'post', 'getBody','get'])
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->noSuchEntityInterfaceMock = $this->getMockBuilder(NoSuchEntityException::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->collectionFactoryMock  = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

       $this->fedexDeleteMock = $this->getMockBuilder
            (FedexCurl::class)
            ->disableOriginalConstructor()
            ->setMethods(['addHeader', 'delete', 'getBody'])
            ->getMock();

        $this->collectionMock  = $this->getMockBuilder(Collection::class)
            ->setMethods(['addFieldToFilter', 'getSelect', 'where','getIterator','getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'getAttributeSetId','save'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setProductDocumentExpireDate',
                    'load',
                    'setData',
                    'save',
                    'getId',
                    'getExternalProd',
                    'getData',
                    'getAttributeSetId',
                    'setIsDocumentExpire'
                ]
                )
            ->getMock();

            $this->serializerJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMock();

            $this->messageMock = $this->getMockBuilder(DocRefMessage::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMock();

            $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['publish'])
            ->getMockForAbstractClass();

        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection','getTableName'])
            ->getMock();
        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['update'])
            ->getMockForAbstractClass();


        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getCollection','addFieldToFilter', 'getSuperUserId',
            'getFirstItem'])
            ->getMockForAbstractClass();

        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getSecondaryEmail', 'getName'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogDocumentRefranceApi = $objectManagerHelper->getObject(
            CatalogDocumentRefranceApi::class,
            [
                'context' => $this->contextMock,
                'toggleConfig' => $this->toggleConfigMock,
                'scopeConfigInterface' => $this->scopeConfigMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'productFactory' => $this->productFactoryMock,
                'curl' => $this->curlMock,
                'productRepository' => $this->productRepositoryMock,
                'catalogMvp' => $this->catalogMvpMock,
                'logger' => $this->loggerMock,
                'serializerJson' => $this->serializerJsonMock,
                'message' => $this->messageMock,
                'publisher' => $this->publisherMock,
                'productCollectionFactory' => $this->collectionFactoryMock,
                'resourceConnection' => $this->resourceConnection,
                'companyFactory' => $this->companyFactory,
                'customerFactory' => $this->customerFactory

           ]
        );
    }

    /**
     * Function to test add refernce
     */
    public function testAddRefernce()
    {
        $this->testGetApiUrl('test');
        $this->testCurlCall();
        $this->assertNull(
            $this->catalogDocumentRefranceApi->addRefernce('123123123', '123')
        );
    }

    /**
     * Function to test add refernce with catch
     */
    public function testAddRefernceCatch()
    {

         $this->scopeConfigMock
        ->expects($this->any())
        ->method('getValue')
        ->willThrowException(new NoSuchEntityException());

        $this->loggerMock
        ->expects($this->any())
        ->method('error')
        ->willReturnSelf();

        $this->assertNull(
        $this->catalogDocumentRefranceApi->addRefernce('123123123', '123'));
    }


    /**
     * Function to test curl call
     */
    public function testCurlCall() {

        $apiRequestData = [
            "references"=> [
            "POD2.0-123"
            ]
        ];

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn('apireturn');

        $this->assertNull(
            $this->catalogDocumentRefranceApi->addRefernce('123123123', '123')
        );
    }

    public function testCurlCallIfCase() {

        $apiRequestData = [
            "documentId"=> [
            "123123123123"
            ]
        ];

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->fedexDeleteMock
            ->expects($this->any())
            ->method('addHeader')
            ->willReturnSelf();

        $this->fedexDeleteMock
            ->expects($this->any())
            ->method('delete')
            ->willReturnSelf();

        $this->fedexDeleteMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturnSelf();

            $this->assertEquals(null, $this->catalogDocumentRefranceApi->curlCall($apiRequestData, '123', "DELETE"));
    }

    /**
     * @test curlCall
     * @return void
     **/
    public function testCurlCallIfCaseWithTaz() {

        $apiRequestData = [
            'documentId' => '123456789'
        ];

        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($response));

        $this->assertNotNull($this->catalogDocumentRefranceApi->curlCall($apiRequestData, '123', 'POST', true));
    }

    public function testUpdateProductDocumentEndDate() {

        $this->productMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->catalogMvpMock
            ->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(false);

        $this->productMock
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->assertNotNull($this->catalogDocumentRefranceApi->updateProductDocumentEndDate($this->productMock, 'customer-admin'));
    }


    public function testUpdateProductDocumentEndDateElse() {

        $this->productMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->catalogMvpMock
            ->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(false);

        $this->assertNotNull($this->catalogDocumentRefranceApi->updateProductDocumentEndDate($this->productMock, 'admin'));
    }

    /**
     * Function to test get API url
     */
    public function testGetApiUrl() {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('apiurl');
        $this->assertEquals('apiurl',$this->catalogDocumentRefranceApi->getApiUrl('test'));
    }

    /**
     * Function to test getDocument ID
     */
    public function testGetDocumentId()
    {
        $externalProductData = '{
            "contentAssociations": [
                {
                    "parentContentReference": "doc1",
                    "contentReference": "doc2"
                },
                {
                    "parentContentReference": "doc3",
                    "contentReference": "doc4"
                }
            ]
        }';

        $documentIds = $this->catalogDocumentRefranceApi->getDocumentId($externalProductData);

        $expectedDocumentIds = [
            "doc1",
            "doc2",
            "doc3",
            "doc4"
        ];

        $this->assertEquals($expectedDocumentIds, $documentIds);
    }

    /**
     * Function to test getDocument ID if empty
     */
    public function testGetDocumentIdWithEmptyData()
    {
        $externalProductData = '';

        $documentIds = $this->catalogDocumentRefranceApi->getDocumentId($externalProductData);

        $this->assertEquals([], $documentIds);
    }

    public function testDeleteProductRef()
    {
        $this->testGetApiUrl('test');
        $this->catalogDocumentRefranceApi->deleteProductRef('123','test');
    }
    /**
     * Function to test getDocument ID
     */
    public function testgetProductByIdWithException()
    {
        $this->productRepositoryMock->expects($this->any())->method('getById')->willThrowException(new NoSuchEntityException());
        $this->assertNotNull($this->catalogDocumentRefranceApi->getProductObjectById(4521));
    }

    public function testDeleteProductRefCatch()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willThrowException(new NoSuchEntityException());

        $this->loggerMock
            ->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        $this->catalogDocumentRefranceApi->deleteProductRef('123','test');
    }

        /**
     * Function to test getDocument ID
     */
    public function testgetProductById()
    {
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->assertNotNull($this->catalogDocumentRefranceApi->getProductObjectById(4521));
    }


    /**
     * Function to test testdocumentLifeExtendApiCall
     */
    public function testdocumentLifeExtendApiCall()
    {

        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];


        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($response));

        $this->testgetProductById();
        $this->productMock->expects($this->any())
        ->method('setProductDocumentExpireDate')
        ->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())
        ->method('save')
        ->willReturnSelf();

        $this->catalogDocumentRefranceApi->documentLifeExtendApiCall('111112225545545', 455455);
    }

    /**
     * Function to test testdocumentLifeExtendApiCallForExpiredItem
     */
    public function testdocumentLifeExtendApiCallForExpiredItem()
    {
        $response = '{
            "transactionId": "e2fc5f61-8839-46dd-b977-43f9ef3ff87b",
            "output": {
                "alerts": [
                    {
                        "code": "ERROR.METADATA.RETRIEVEFAILED",
                        "message": "Document not found for Id : 94fb2c8a-b8a1-11ef-9783-a6b70da29015",
                        "alertType": "WARNING"
                    }
                ],
                "documents": []
            }
        }';

        $response = json_decode($response, true);

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($response));

        $this->testgetProductById();
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->productMock->expects($this->any())
            ->method('setIsDocumentExpire')
            ->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->catalogDocumentRefranceApi->documentLifeExtendApiCall('111112225545545', 455455);
    }

    /**
     * Function to test testdocumentLifeExtendApiCallWithException
     */
    public function testdocumentLifeExtendApiCallWithException()
    {

        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($response));

        $this->testgetProductById();
        $this->productMock->expects($this->any())
        ->method('setProductDocumentExpireDate')
        ->willReturnSelf();
        $this->productMock->expects($this->any())
        ->method('save')
        ->willThrowException(new NoSuchEntityException());

        $this->catalogDocumentRefranceApi->documentLifeExtendApiCall('111112225545545', 455455);
    }

        /**
     * Function to test testdocumentLifeExtendApiCallWithDocumentId
     */
    public function testdocumentLifeExtendApiCallWithDocumentId()
    {

        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];


        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($response));

        $this->testgetProductById();
        $this->productMock->expects($this->any())
        ->method('setProductDocumentExpireDate')
        ->willReturnSelf();
        $this->productMock->expects($this->any())
        ->method('save')
        ->willReturnSelf();

        $this->catalogDocumentRefranceApi->documentLifeExtendApiCallWithDocumentId('111112225545545');
    }

        /**
     * Function to test testdocumentLifeExtendApiCallWithDocumentIdWithException
     */
    public function testdocumentLifeExtendApiCallWithDocumentIdWithException()
    {

        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
             ->willThrowException(new NoSuchEntityException());

        $this->catalogDocumentRefranceApi->documentLifeExtendApiCallWithDocumentId('111112225545545');
    }
       /**
     * Function to test testgetExtendDocumentLifeForPodEitableProduct
     */
    public function testgetExtendDocumentLifeForPodEitableProduct(){

        $this->collectionFactoryMock
        ->expects($this->any())
        ->method('create')
        ->willReturn($this->collectionMock);

        $this->collectionMock
        ->expects($this->any())
        ->method('addFieldToFilter')
        ->willReturnSelf();

        $this->collectionMock
        ->expects($this->any())
        ->method('getSelect')
        ->willReturnSelf();

        $this->collectionMock
        ->expects($this->any())
        ->method('where')
        ->willReturnSelf();

        $this->collectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn([['entity_id'=>'4567']]);

        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($this->productMock);

        $this->collectionMock->expects($this->any())
        ->method('getIterator') ->willReturn(new \ArrayIterator([$this->productMock]));

        $this->productMock->expects($this->any())
        ->method('getId')
        ->willReturn('4545');

        $this->productMock->expects($this->any())
        ->method('load')
        ->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->productMock
        ->expects($this->any())
        ->method('getExternalProd')
        ->willReturn($this->geProductData());

        $this->productMock
        ->expects($this->any())
        ->method('getData')
        ->willReturn($this->getCustomizeProductData());
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertNotNull($this->catalogDocumentRefranceApi->getExtendDocumentLifeForPodEitableProduct());

    }

    // Helper method for generating sample product data
    private function getCustomizeProductData() {
       $customData = '[{"documentId":"9b9dea4f-c5c9-11ee-9214-2fff2ca2b547","formFields":[{"fieldName":"TextField1","fieldType":"TEXT","pageNumber":1,"label":"fwwf","description":"","hintText":""},{"fieldName":"ImageField1","fieldType":"IMAGE","pageNumber":1,"label":"e2e2e","description":"","hintText":""},{"fieldName":"TextField2","fieldType":"TEXT","pageNumber":1,"label":"2e2e2","description":"","hintText":""},{"fieldName":"ImageField2","fieldType":"IMAGE","pageNumber":1,"label":"wewew","description":"","hintText":""},{"fieldName":"TextField3","fieldType":"TEXT","pageNumber":1,"label":"ddd","description":"","hintText":""},{"fieldName":"ImageField3","fieldType":"IMAGE","pageNumber":1,"label":"r3r3r","description":"","hintText":""},{"fieldName":"TextField4","fieldType":"TEXT","pageNumber":1,"label":"wrwr","description":"","hintText":""},{"fieldName":"ImageField4","fieldType":"IMAGE","pageNumber":1,"label":"dwdwdw","description":"","hintText":""},{"fieldName":"TextField5","fieldType":"TEXT","pageNumber":1,"label":"wdwdw","description":"","hintText":""},{"fieldName":"ImageField5","fieldType":"IMAGE","pageNumber":1,"label":"dwdwd","description":"","hintText":""},{"fieldName":"TextField6","fieldType":"TEXT","pageNumber":1,"label":"wdwdw","description":"","hintText":""},{"fieldName":"ImageField6","fieldType":"IMAGE","pageNumber":1,"label":"wwdwd","description":"","hintText":""},{"fieldName":"TextField7","fieldType":"TEXT","pageNumber":1,"label":"wdwdd","description":"","hintText":""},{"fieldName":"ImageField7","fieldType":"IMAGE","pageNumber":1,"label":"mkkmk","description":"","hintText":""}]}]';
       return $customData;
    }

    /**
     * Function to test testgetExtendDocumentLifeForPodEitableProductWithException
     */
    public function testGetExtendDocumentLifeForPodEitableProductWithException()
    {

        $this->collectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->collectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->collectionMock
            ->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->collectionMock
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->productMock]));

        $this->productFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productMock);

        $this->productMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(4545);

        $this->productRepositoryMock
            ->expects($this->any())
            ->method('getById')
            ->willReturn($this->productMock);

        // Mocking behavior for getExternalProd to throw an exception
        $this->productMock
            ->expects($this->any())
            ->method('getExternalProd')
            ->willThrowException(new NoSuchEntityException());

        // Expectation for testgetExtendDocumentLifeForPodEitableProductWithCustomizableFieldsWithException
        $this->productMock
            ->expects($this->any())
            ->method('getData')
            ->willThrowException(new NoSuchEntityException());

        // Assertion for combined logic
        $this->assertNotNull($this->catalogDocumentRefranceApi->getExtendDocumentLifeForPodEitableProduct());

    }


    /**
     * Function to geProductData
     */
    public function geProductData()
    {

        $postData = '{"id":1456773326927,"version":2,"name":"Multi Sheet","qty":1,"priceable":true,"features":[{"id":1448981554101,"name":"Prints Per Page","choice":{"id":1448990257151,"name":"One","properties":[{"id":1455387404922,"name":"PRINTS_PER_PAGE","value":"1"}]}},{"id":1448981555573,"name":"Hole Punching","choice":{"id":1448999902070,"name":"None","properties":[]}},{"id":1448981549109,"name":"Paper Size","choice":{"id":1448986650332,"name":"8.5x11","properties":[{"id":1571841122054,"name":"DISPLAY_HEIGHT","value":"11"},{"id":1571841164815,"name":"DISPLAY_WIDTH","value":"8.5"},{"id":1449069906033,"name":"MEDIA_HEIGHT","value":"11"},{"id":1449069908929,"name":"MEDIA_WIDTH","value":"8.5"}]}},{"id":1448981549269,"name":"Sides","choice":{"id":1448988124560,"name":"Single-Sided","properties":[{"id":1461774376168,"name":"SIDE","value":"SINGLE"},{"id":1471294217799,"name":"SIDE_VALUE","value":"1"}]}},{"id":1680724699067,"name":"Hole Punching Production","choice":{"id":1681184744573,"name":"Machine Finishing","properties":[]}},{"id":1448984877869,"name":"Cutting","choice":{"id":1448999392195,"name":"None","properties":[]}},{"id":1448984877645,"name":"Folding","choice":{"id":1448999720595,"name":"None","properties":[]}},{"id":1448981532145,"name":"Collation","choice":{"id":1448986654687,"name":"Collated","properties":[{"id":1449069945785,"name":"COLLATION_TYPE","value":"MACHINE"}]}},{"id":1680725097331,"name":"Folding Production","choice":{"id":1680725112004,"name":"Hand Finishing","properties":[]}},{"id":1448984679442,"name":"Lamination","choice":{"id":1448999458409,"name":"None","properties":[]}},{"id":1448984679218,"name":"Orientation","choice":{"id":1449000016327,"name":"Horizontal","properties":[{"id":1453260266287,"name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":1679607670330,"name":"Offset Stacking","choice":{"id":1679607688873,"name":"On","properties":[]}},{"id":1448981549741,"name":"Paper Type","choice":{"id":1448988664295,"name":"Laser (32 lb.)","properties":[{"id":1450324098012,"name":"MEDIA_TYPE","value":"E32"},{"id":1453234015081,"name":"PAPER_COLOR","value":"#FFFFFF"},{"id":1471275182312,"name":"MEDIA_CATEGORY","value":"RESUME"}]}},{"id":1448981549581,"name":"Print Color","choice":{"id":1448988600611,"name":"Full Color","properties":[{"id":1453242778807,"name":"PRINT_COLOR","value":"COLOR"}]}},{"id":1680723151283,"name":"Stapling Production","choice":{"id":1681184744572,"name":"Machine Finishing","properties":[]}}],"properties":[{"id":1453895478444,"name":"MIN_DPI","value":"150.0"},{"id":1455050109631,"name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":1490292304798,"name":"MIGRATED_PRODUCT","value":"true"},{"id":1494365340946,"name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":1470151737965,"name":"TEMPLATE_AVAILABLE","value":"NO"},{"id":1453243262198,"name":"ENCODE_QUALITY","value":"100"},{"id":1455050109636,"name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":1453242488328,"name":"ZOOM_PERCENTAGE","value":"50"},{"id":1453894861756,"name":"LOCK_CONTENT_ORIENTATION","value":"false"},{"id":1470151626854,"name":"SYSTEM_SI"},{"id":1454950109636,"name":"USER_SPECIAL_INSTRUCTIONS"}],"pageExceptions":[],"proofRequired":false,"instanceId":1700574126232,"userProductName":"adsdhsufheuifhjdowskjnhbjvewijdoqksaxlmk jbhewionj","inserts":[],"exceptions":[],"addOns":[],"contentAssociations":[{"parentContentReference":"745315d9-8874-11ee-9bfa-859ce60e527d","contentReference":"7831a0c5-8874-11ee-a1cb-6924b32f57f9","contentType":"IMAGE","fileSizeBytes":0,"fileName":"test_sakshi.jpg","printReady":true,"contentReqId":1483999952979,"name":"Multi Sheet","purpose":"MAIN_CONTENT","specialInstructions":"","pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}],"physicalContent":false}],"productionContentAssociations":[],"products":[],"externalSkus":[],"isOutSourced":false}';

        return $postData;
    }

    /**
     * Function to test curl call for  Preview
     */
    public function testCurlCallForPreviewApi() {

        $this->toggleConfigMock
            ->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getTazToken')
            ->willReturn('apiurl');

        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('get')
            ->willReturnSelf();

        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturnSelf();

        $this->catalogDocumentRefranceApi->curlCallForPreviewApi('123123123');

    }
    /**
     * Function to test getDocument ID
     */
    public function testgetContentReferenceId()
    {
        $externalProductData = '{
            "contentAssociations": [
                {
                    "contentReference": "doc2"
                },
                {
                    "contentReference": "doc4"
                }
            ]
        }';

        $documentIds = $this->catalogDocumentRefranceApi->getContentReferenceId($externalProductData);
        $expectedDocumentIds = [
            "doc2",
            "doc4"
        ];

        $this->assertEquals($expectedDocumentIds, $documentIds);
    }

    public function testgetCustomDocDocumentIds()
    {
        $customizableFields = json_encode([
            [
                'documentId' => 'doc1',
                'formFields' => [
                    ['documentId' => 'formDoc1'],
                    ['documentId' => 'formDoc2'],
                    ['documentId' => 'formDoc3']
                ]
            ]
        ]);

        // Call the method under test
        $documentIds = $this->catalogDocumentRefranceApi->getCustomDocDocumentIds($customizableFields);

        // Define the expected output
        $expectedDocumentIds = [
            'doc1',
            'formDoc1',
            'formDoc2',
            'formDoc3'
        ];

        // Assert that the output matches the expected document IDs
        $this->assertEquals($expectedDocumentIds, $documentIds);
    }
    /**
     * Function to test extendDocLifeApiSyncCall
     */
    public function testextendDocLifeApiSyncCall() {
        $externalProductData = '{
            "contentAssociations": [
                {
                    "contentReference": "doc2"
                },
                {
                    "contentReference": "doc4"
                }
            ]
        }';
 $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];
    $customizableFields = [
        [
            'documentId' => 'doc1',
            'formFields' => [
                ['documentId' => 'formDoc1'],
                ['documentId' => 'formDoc2'],
                ['documentId' => 'formDoc3']
            ]
        ]
    ];

    // Mock the dependencies
    $this->productMock->expects($this->any())
        ->method('getExternalProd')
        ->willReturn($externalProductData);

    $this->productMock->expects($this->any())
        ->method('getData')
        ->willReturn($customizableFields);

    $this->punchoutHelperMock->expects($this->any())
        ->method('getAuthGatewayToken')
        ->willReturn('apiurl');

    $this->curlMock->expects($this->any())
        ->method('setOptions')
        ->willReturnSelf();

    $this->curlMock->expects($this->any())
        ->method('post')
        ->willReturnSelf();

    $this->curlMock->expects($this->any())
        ->method('getBody')
        ->willReturn(json_encode(['output' => ['document' => ['expirationTime' => date("Y-m-d H:i:s")]]]));

    $this->productMock->expects($this->any())
        ->method('setProductDocumentExpireDate')
        ->willReturnSelf();

    $this->productMock->expects($this->any())
        ->method('save')
        ->willReturnSelf();

    $result = $this->catalogDocumentRefranceApi->extendDocLifeApiSyncCall($this->productMock);

    // Assertions
    $this->assertNull($result);
}

    /**
     * Function to test extendDocLifeApiSyncCall
     */
    public function testextendDocLifeApiSyncCallException() {
        $externalProductData = '{
            "contentAssociations": [
                {
                    "contentReference": "doc2"
                },
                {
                    "contentReference": "doc4"
                }
            ]
        }';

        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];

        $this->productMock
            ->expects($this->any())
            ->method('getExternalProd')
            ->willThrowException(new NoSuchEntityException());
        $this->productMock
            ->expects($this->any())
            ->method('getData')
            ->willThrowException(new NoSuchEntityException());
        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('apiurl');
        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();
        $this->curlMock
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();
        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($response));

            $this->productMock->expects($this->any())
            ->method('setProductDocumentExpireDate')
            ->willReturnSelf();
        $this->productMock
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->assertNull($this->catalogDocumentRefranceApi->extendDocLifeApiSyncCall($this->productMock));
    }

    /**
     * Test Case for readZipFileContent
     */
    public function testReadZipFileContent()
    {
        $downloadUrl = "https://test.com/content";

        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn('afawfawf-adwawd');
        $this->curlMock
            ->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();
        $this->curlMock
            ->expects($this->any())
            ->method('get')
            ->willReturnSelf();
        $this->curlMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn("Test Response Content");
        $this->assertEquals("Test Response Content", $this->catalogDocumentRefranceApi->readZipFileContent($downloadUrl));
    }

    /**
     * Function to test ExtendDocumentLifeForProducts Method
     */
    public function testExtendDocumentLifeForProducts()
    {
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('where')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getData')
            ->willReturn([['entity_id'=> static::PRODUCT_ID]]);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnOnConsecutiveCalls(false, true, false, true);
        // $this->testGetDocumentsJson();

        $productDocumentIdsJson[] = [
            'documentId' => '7831a0c5-8874-11ee-a1cb-6924b32f57f9',
            'produtId'  => static::PRODUCT_ID
        ];

        $customizableFields = json_encode([
            [
                'documentId' => 'doc1',
                'formFields' => [
                    ['documentId' => 'formDoc1'],
                    ['documentId' => 'formDoc2'],
                    ['documentId' => 'formDoc3']
                ]
            ]
        ]);

        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($this->productMock);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn($this->geProductData());
        $this->productMock->expects($this->any())->method('getData')->with('customization_fields')->willReturn($customizableFields);
        $this->productMock->expects($this->any())->method('getId')->willReturn(static::PRODUCT_ID);

        $productDocumentIdsJsonData = json_decode('[{"documentId":"7831a0c5-8874-11ee-a1cb-6924b32f57f9","produtId":4567},{"documentId":"doc1","produtId":4567},{"documentId":"formDoc1","produtId":4567},{"documentId":"formDoc2","produtId":4567},{"documentId":"formDoc3","produtId":4567}]', true);

        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);

        $this->assertEquals(true,
            $this->catalogDocumentRefranceApi->extendDocumentLifeForProducts()
        );
    }


    /**
     * Function to test ExtendDocumentLifeForProducts Method
     */
    public function testExtendDocumentLifeForProductsToggleOff()
    {
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('where')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getData')
            ->willReturn([['entity_id'=> static::PRODUCT_ID]]);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnOnConsecutiveCalls(true, true, true, true);
        // $this->testGetDocumentsJson();

        $productDocumentIdsJson[] = [
            'documentId' => '7831a0c5-8874-11ee-a1cb-6924b32f57f9',
            'produtId'  => static::PRODUCT_ID
        ];

        $customizableFields = json_encode([
            [
                'documentId' => 'doc1',
                'formFields' => [
                    ['documentId' => 'formDoc1'],
                    ['documentId' => 'formDoc2'],
                    ['documentId' => 'formDoc3']
                ]
            ]
        ]);

        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($this->productMock);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn($this->geProductData());
        $this->productMock->expects($this->any())->method('getData')->with('customization_fields')->willReturn($customizableFields);
        $this->productMock->expects($this->any())->method('getId')->willReturn(static::PRODUCT_ID);

        $productDocumentIdsJsonData = json_decode('[{"documentId":"7831a0c5-8874-11ee-a1cb-6924b32f57f9","produtId":4567},{"documentId":"doc1","produtId":4567},{"documentId":"formDoc1","produtId":4567},{"documentId":"formDoc2","produtId":4567},{"documentId":"formDoc3","produtId":4567}]', true);

        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);

        $this->assertEquals(true,
            $this->catalogDocumentRefranceApi->extendDocumentLifeForProducts()
        );
    }

    /**
     * Function to test ExtendDocumentLifeForProducts Method with Exception
     */
    public function testExtendDocumentLifeForProductsWithException()
    {
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('where')->willReturnSelf();

        $this->collectionMock->expects($this->any())->method('getData')->willThrowException(new \Exception());
        $this->assertNotNull($this->catalogDocumentRefranceApi->extendDocumentLifeForProducts());
    }

    /**
     * Function to test getDocumentsJson Method
     */
    public function testGetDocumentsJsonToggleOff()
    {
        $productDocumentIdsJson[] = [
            'documentId' => '7831a0c5-8874-11ee-a1cb-6924b32f57f9',
            'produtId'  => static::PRODUCT_ID
        ];

        $customizableFields = json_encode([
            [
                'documentId' => 'doc1',
                'formFields' => [
                    ['documentId' => 'formDoc1'],
                    ['documentId' => 'formDoc2'],
                    ['documentId' => 'formDoc3']
                ]
            ]
        ]);

        $productDocumentIdsJsonData = json_decode('[{"documentId":"7831a0c5-8874-11ee-a1cb-6924b32f57f9","produtId":4567},{"documentId":"doc1","produtId":4567},{"documentId":"formDoc1","produtId":4567},{"documentId":"formDoc2","produtId":4567},{"documentId":"formDoc3","produtId":4567}]', true);

        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($this->productMock);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn($this->geProductData());
        $this->productMock->expects($this->any())->method('getData')->with('customization_fields')->willReturn($customizableFields);
        $this->productMock->expects($this->any())->method('getId')->willReturn(static::PRODUCT_ID);

        $this->assertEquals(
            $productDocumentIdsJsonData,
            $this->catalogDocumentRefranceApi->getDocumentsJson(static::PRODUCT_ID)
        );

        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
    }

    /**
     * Function to test getDocumentsJson Method
     */
    public function testGetDocumentsJsonToggleOffWithNullData()
    {
        $productDocumentIdsJson[] = [
            'documentId' => '7831a0c5-8874-11ee-a1cb-6924b32f57f9',
            'produtId'  => static::PRODUCT_ID
        ];

        $customizableFields = json_encode([
            [
                'documentId' => 'doc1',
                'formFields' => [
                    ['documentId' => 'formDoc1'],
                    ['documentId' => 'formDoc2'],
                    ['documentId' => 'formDoc3']
                ]
            ]
        ]);

        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($this->productMock);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn($this->geProductData());
        $this->productMock->expects($this->any())->method('getData')->with('customization_fields')->willReturn('{}');
        $this->productMock->expects($this->any())->method('getId')->willReturn(static::PRODUCT_ID);

        $this->assertEquals(
            $productDocumentIdsJson,
            $this->catalogDocumentRefranceApi->getDocumentsJson(static::PRODUCT_ID)
        );

        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
    }

    /**
     * Function to test getDocumentsJson Method
     */
    public function testGetDocumentsJson()
    {
        $productDocumentIdsJson[] = [
            'documentId' => '7831a0c5-8874-11ee-a1cb-6924b32f57f9',
            'produtId'  => static::PRODUCT_ID
        ];

        $customizableFields = json_encode([
            [
                'documentId' => 'doc1',
                'formFields' => [
                    ['documentId' => 'formDoc1'],
                    ['documentId' => 'formDoc2'],
                    ['documentId' => 'formDoc3']
                ]
            ]
        ]);

        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($this->productMock);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn($this->geProductData());
        $this->productMock->expects($this->any())->method('getData')->with('customization_fields')->willReturn($customizableFields);
        $this->productMock->expects($this->any())->method('getId')->willReturn(static::PRODUCT_ID);

        $productDocumentIdsJsonData = json_decode('[{"documentId":"7831a0c5-8874-11ee-a1cb-6924b32f57f9","produtId":4567},{"documentId":"doc1","produtId":4567},{"documentId":"formDoc1","produtId":4567},{"documentId":"formDoc2","produtId":4567},{"documentId":"formDoc3","produtId":4567}]', true);
        $this->assertEquals(
            $productDocumentIdsJsonData,
            $this->catalogDocumentRefranceApi->getDocumentsJson(static::PRODUCT_ID)
        );

        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
    }

    /**
     * Function to test getDocumentsJson Method
     */
    public function testGetDocumentsJsonExceptionLocalization()
    {
        $this->productFactoryMock->method('create')->willReturn($this->productMock);
        $this->productMock->method('load')->willReturnSelf();
        $this->productMock->method('getExternalProd')->willReturn('invalid_json');

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->productRepositoryMock->method('getById')->willReturn($this->productMock);

        // Call the method that should throw the exception
        $this->catalogDocumentRefranceApi->getDocumentsJson(self::PRODUCT_ID);
        $this->assertEquals(
           [],
            $this->catalogDocumentRefranceApi->getDocumentsJson(static::PRODUCT_ID)
        );
    }

    /**
     * Function to test getDocumentsJson Method
     */
    public function testGetDocumentsJsonForCustomizeFieldsExceptionLocalization()
    {
        $this->productFactoryMock->method('create')->willReturn($this->productMock);
        $this->productMock->method('load')->willReturnSelf();
        $this->productMock->method('getExternalProd')->willReturn('{}');
        $this->productMock->method('getData')->with('customization_fields')->willReturn('invalid_json');

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->productRepositoryMock->method('getById')->willReturn($this->productMock);

        // Call the method that should throw the exception
        $this->catalogDocumentRefranceApi->getDocumentsJson(self::PRODUCT_ID);
        $this->assertEquals(
           [],
            $this->catalogDocumentRefranceApi->getDocumentsJson(static::PRODUCT_ID)
        );
    }

    public function testGetDocumentsJsonException()
    {
        $this->productFactoryMock->method('create')->willReturn($this->productMock);
        $this->productMock->method('load')->willThrowException(new \Exception('General Error in getDocumentsJson:'));
        $this->productMock->method('getExternalProd')->willReturn('invalid_json');

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->productRepositoryMock->method('getById')->willReturn($this->productMock);

        // Call the method that should throw the exception
        $this->catalogDocumentRefranceApi->getDocumentsJson(self::PRODUCT_ID);
        $this->assertEquals(
           [],
            $this->catalogDocumentRefranceApi->getDocumentsJson(static::PRODUCT_ID)
        );
    }

    /**
     * Test case for setProductDocumentExpireDate
     */
    public function testSetProductDocumentExpireDate()
    {
        $documentExtendDate = "2024-01-01";
        $productId = "23";
        $this->resourceConnection->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterface);
        $this->resourceConnection->expects($this->any())->method('getTableName')->willReturn('catalog_product_entity');
        $this->adapterInterface->expects($this->any())->method('update')->willReturn('1');
        $this->assertEquals(null, $this->catalogDocumentRefranceApi->setProductDocumentExpireDate($documentExtendDate, $productId));
    }

    /**
     * Test case for getExpiryDocuments
     */
    public function testgetExpiryDocuments() {

        $this->collectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->collectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->collectionMock
            ->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->collectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn([['entity_id'=>'4567', 'folder_path' => 'ABCD', 'added_by'=>'98']]);

        $this->productFactoryMock->expects($this->any())->method('create')->willReturnself();
        $this->productFactoryMock->expects($this->any())->method('load')->willReturnself();
        $this->productFactoryMock->expects($this->any())->method('getSku')->willReturnself();

        $this->collectionMock
            ->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->companyFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->companyFactory
            ->expects($this->any())
            ->method('getCollection')
            ->willReturnSelf();

        $this->companyFactory
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companyFactory->expects($this->any())->method('getFirstItem')->willReturnself();

        $this->customerFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerFactory->expects($this->any())->method('load')->willReturnSelf();

    }

    /**
     * Test case for getExpiryDocuments with Exception
     */
    public function testgetExpiryDocumentsException() {

        $this->collectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willThrowException(new \Exception());

        $this->assertNotNull($this->catalogDocumentRefranceApi->getExpiryDocuments());
    }
}
