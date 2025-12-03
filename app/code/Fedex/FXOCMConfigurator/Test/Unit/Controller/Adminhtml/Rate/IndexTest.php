<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Controller\Adminhtml\Rate;

use Magento\Backend\App\Action\Context;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOCMConfigurator\Controller\Adminhtml\Rate\Index;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Fedex\CatalogMvp\Model\ProductPriceWebhook;

class IndexTest extends TestCase
{
    /**
     * @var CatalogPriceSyncHelper|MockObject
     */
    protected $pricesyncMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var ResultJson|MockObject
     */
    protected $resultJson;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var Index
     */
    protected $indexController;

    private const DATA = '{
                            "userProductName":"Indoor Banners",
                            "id":"1445348490823",
                            "version":1,
                            "name":"Banners",
                            "qty":1,
                            "priceable":true,
                            "instanceId":1612338831441,
                            "proofRequired":false,
                            "isOutSourced":false,
                            "features":""
                        }';

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

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var CatalogPriceSyncHelper|MockObject
     */
    protected $pricesync;

    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactory;

    /**
     * @var ProductPriceWebhook|MockObject
     */
    protected $productPriceWebhook;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->pricesyncMock = $this->getMockBuilder(CatalogPriceSyncHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['rateApiCall','getCorrectPriceToggle','isMagegeeksD236791ToggleEnabled'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMock();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMockForAbstractClass();

        $this->productPriceWebhook = $this->getMockBuilder(ProductPriceWebhook::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = $objectManager->getObject(Context::class);

        $this->indexController = $objectManager->getObject(
            Index::class,
            [
                'context' => $context,
                'pricesync' => $this->pricesyncMock,
                'jsonFactory' => $this->jsonFactoryMock,
                '_request' => $this->request,
                'toggleConfig' => $this->toggleConfig,
                'productPriceWebhook' => $this->productPriceWebhook
            ]
        );
    }

    /**
     * Test the execute method of the Index controller.
     *
     * @return void
     * @covers \Fedex\FXOCMConfigurator\Controller\Adminhtml\Rate\Index::execute
     */
    public function testExecute()
    {
        $productJson = json_encode([
            "userProductName" => "Indoor Banners",
            "id" => "1445348490823",
            "version" => 1,
            "name" => "Banners",
            "qty" => 1,
            "priceable" => true,
            "instanceId" => 1612338831441,
            "proofRequired" => false,
            "isOutSourced" => false,
            "features" => ""
            ]);

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['data', null, $productJson],
                ['sharedCatalogId', null, null],
                ['isPopup', null, 1]
            ]);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['explorers_non_standard_catalog', true],
                ['explorers_d196499_fix', true]
            ]);

        $this->pricesyncMock->expects($this->any())
            ->method('getCorrectPriceToggle')
            ->willReturn(false);

        $this->pricesyncMock->expects($this->any())
            ->method('isMagegeeksD236791ToggleEnabled')
            ->willReturn(true);

        $this->pricesyncMock->expects($this->any())
            ->method('rateApiCall')
            ->willReturn([
                'output' => [
                    'rate' => [
                        'rateDetails' => [
                            [
                                'netAmount' => '$100.00',
                                'productLines' => [
                                    [
                                        'productLineDetails' => [
                                            [
                                                'detailCode' => 'D1',
                                                'description' => 'Desc1',
                                                'detailPrice' => '$50.00',
                                                'detailUnitPrice' => '$25.00'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->resultJson->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $result = $this->indexController->execute();
        $this->assertSame($this->resultJson, $result);
    }

    /**
     * Test the execute method of the Index controller with toggle enabled.
     *
     * @return void
     * @covers \Fedex\FXOCMConfigurator\Controller\Adminhtml\Rate\Index::execute
     */
    public function testExecuteWithToggleEnabled()
    {
        $productJson = '{
                            "userProductName":"Indoor Banners",
                            "id":"1445348490823",
                            "version":1,
                            "name":"Banners",
                            "qty":1,
                            "priceable":true,
                            "instanceId":1612338831441,
                            "proofRequired":false,
                            "isOutSourced":false,
                            "features":""
                        }';
        $this->request->expects($this->any())->method('getParam')->willReturn($productJson);
        $this->pricesyncMock->expects($this->any())
            ->method('getCorrectPriceToggle')->willReturn(false);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->pricesyncMock->expects($this->any())
            ->method('rateApiCall')->willReturn(self::ARRAY_DATA);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->resultJson);
        $this->jsonFactoryMock->expects($this->any())
            ->method('setData')->willReturnSelf();
        $this->assertEquals(null, $this->indexController->execute());
    }

    /**
     * Test case to verify that getCustomerGroupIdBySharedCatalogId method returns null
     *
     * @return void
     */
    public function testGetCustomerGroupIdBySharedCatalogIdEmpty()
    {
        $this->request->method('getParam')->with('sharedCatalogId')->willReturn(null);
        $reflection = new \ReflectionClass($this->indexController);
        $method = $reflection->getMethod('getCustomerGroupIdBySharedCatalogId');
        $method->setAccessible(true);
        $result = $method->invoke($this->indexController);
        $this->assertNull($result);
    }
    
    /**
     * Test case for getCustomerGroupIdBySharedCatalogId method with valid shared catalog ID.
     *
     * @return void
     */
    public function testGetCustomerGroupIdBySharedCatalogIdValid()
    {
        $this->request->method('getParam')->with('sharedCatalogId')->willReturn(5);

        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $collectionMock->method('getData')->willReturn([['customer_group_id' => '3']]);

        $this->productPriceWebhook->method('getCustomerGroupIdByShareCatalogId')->willReturn($collectionMock);

        $reflection = new \ReflectionClass($this->indexController);
        $method = $reflection->getMethod('getCustomerGroupIdBySharedCatalogId');
        $method->setAccessible(true);

        $result = $method->invoke($this->indexController);
        $this->assertEquals('3', $result);
    }
}
