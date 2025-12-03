<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Controller\Rate;

use Magento\Framework\App\Action\Context;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOCMConfigurator\Controller\Rate\Index;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class IndexTest extends TestCase
{
    protected $pricesyncMock;
    protected $jsonFactoryMock;
    protected $resultJson;
    protected $request;
    protected $indexController;
    const DATA = '{
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

    protected $context;
    protected $pricesync;
    protected $jsonFactory;
    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->pricesyncMock = $this->getMockBuilder(CatalogPriceSyncHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['rateApiCall'])
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

        $context = $objectManager->getObject(Context::class);

        $this->indexController = $objectManager->getObject(
            Index::class,
            [
                'pricesync' => $this->pricesyncMock,
                'jsonFactory' => $this->jsonFactoryMock,
                '_request' => $this->request,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     *
     */
    public function testExecute()
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
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->pricesyncMock->expects($this->any())
        ->method('rateApiCall')->willReturn('$20');
        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturn($this->resultJson);
        $this->jsonFactoryMock->expects($this->any())
        ->method('setData')->willReturnSelf();
        $this->assertEquals(null, $this->indexController->execute());
    }

    /**
     *
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
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->pricesyncMock->expects($this->any())
        ->method('rateApiCall')->willReturn(self::ARRAY_DATA);
        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturn($this->resultJson);
        $this->jsonFactoryMock->expects($this->any())
        ->method('setData')->willReturnSelf();
        $this->assertEquals(null, $this->indexController->execute());
    }
    
}
