<?php
namespace Fedex\ReorderInstance\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\ReorderInstance\Api\ReorderMessageInterface;
use Fedex\ReorderInstance\Api\ReorderSubscriberInterface;
use Magento\Framework\HTTP\ClientInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\ReorderInstance\Model\ReorderSubscriber;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Fedex\ReorderInstance\Helper;
use Fedex\Punchout\Helper\Data as PunchOutHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriodFactory;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriod;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\CollectionFactory as OrderRetationPeriodCollectionFactory;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\Collection as OrderRetationPeriodCollection;

class ReorderSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $order;
    /**
     * @var (\Magento\Sales\Model\OrderFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderFactory;
    protected $orderInterface;
    protected $toggleConfigMock;
    protected $collection;
    protected $punchOutHelper;
    protected $item;
    protected $option;
    protected $catalogDocumentRefranceApiMock;
    protected $orderRetationPeriodCollectionFactoryMock;
    protected $orderRetationPeriodCollectionMock;
    protected $orderRetationPeriodFactoryMock;
    protected $orderRetationPeriodMock;
    protected $reorderSubscriber;
    public const CONTENT_REFERENCE = '13150681870163322246406238580950042962581';
    /**
     * @var ClientInterface
     */
    protected $curlMock;

    /**
     * @var LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var ScopeConfigInterface
     */
    protected $configInterfaceMock;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactoryMock;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var CatalogDocumentRefranceApi
     */
    protected $catalogDocumentRefranceApi;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->curlMock = $this->getMockBuilder(ClientInterface::class)
            ->setMethods(['setOptions','post','getBody'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->setMethods(['get','setReorderable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','loadByIncrementId','load','getId','getExtOrderId',
            'setExtOrderId', 'save', 'getAllVisibleItems', 'getEntityId'])
            ->getMock();
        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderInterface = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->setMethods(['setReorderable', 'setExtOrderId', 'save', 'getEntityId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create','post'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['addFieldToFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->configInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error', 'info'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->punchOutHelper = $this->getMockBuilder(PunchOutHelper::class)
            ->setMethods(['getTazToken', 'getAuthGatewayToken'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item       = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getProductOptions',
                    'getFirstItem',
                    'getId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->option           = $this->getMockBuilder(Option::class)
            ->setMethods(
                [
                    'getData',
                    'setData',
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
        ->setMethods(['documentLifeExtendApiCallWithDocumentId'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->orderRetationPeriodCollectionFactoryMock = $this->getMockBuilder(OrderRetationPeriodCollectionFactory::class)
        ->setMethods(['create'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->orderRetationPeriodCollectionMock = $this->getMockBuilder(OrderRetationPeriodCollection::class)
        ->setMethods(['addFieldToFilter', 'getSize', 'getFirstItem', 'getId'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->orderRetationPeriodFactoryMock = $this->getMockBuilder(OrderRetationPeriodFactory::class)
        ->setMethods(['create'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->orderRetationPeriodMock = $this->getMockBuilder(OrderRetationPeriod::class)
        ->setMethods(['load', 'setOrderItemId', 'setExtendedDate', 'setExtendedFlag','save', 'setDocumentId'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->reorderSubscriber    = $this->objectManager->getObject(
            ReorderSubscriber::class,
            [
                'curl'                           => $this->curlMock,
                'logger'                         => $this->loggerMock,
                'configInterface'                => $this->configInterfaceMock,
                'orderRepository'                => $this->orderRepository,
                'collectionFactory'              => $this->collectionFactoryMock,
                'order'                          => $this->order,
                'punchOutHelper'                 => $this->punchOutHelper,
                'orderFactory'                   => $this->orderFactory,
                'item'                           => $this->item,
                'toggleConfig'                   => $this->toggleConfigMock,
                'catalogDocumentRefranceApi'     => $this->catalogDocumentRefranceApiMock,
                'orderRetationPeriodCollectionFactory' => $this->orderRetationPeriodCollectionFactoryMock,
                'orderRetationPeriod' => $this->orderRetationPeriodFactoryMock
            ]
        );
    }

    /**
     * Assert processMessage.
     *
     * @return null
     */
    public function testProcessMessage()
    {
        $reorderMessageInterfaceMock = $this->getMockBuilder(ReorderMessageInterface::class)
            ->setMethods(['getMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderId = '6697';
        $orderItemId = 7447;
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $reorderMessageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($orderId);
        $this->orderRepository->expects($this->any())->method('get')->with($orderId)->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $option =
            [
             'info_buyRequest' => [
                    'external_prod' => [
                        '0' => [
                            "contentAssociations"=> [
                                  'contentReference'=> ['contentReference'=> self::CONTENT_REFERENCE],
                              ],
                        ],
                    ],
                ],
            ];
        $this->item->expects($this->any())->method('getProductOptions')->willReturn($option);
        $this->reorderSubscriber->getOrderData($orderId);
        $allContentReferencesData = array (
            7447 => "'".self::CONTENT_REFERENCE."'",
        );
        $this->order->expects($this->any())->method('load')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('setReorderable')->with(1)->willReturnSelf();
        $this->orderInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->punchOutHelper->expects($this->any())
            ->method('getTazToken')->willReturn(json_encode(
            [
                'access_token' => '123',
                'token_type'   => 'Cookie',
            ]
        ));
        $this->punchOutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn(252671717176116717221);
        $this->configInterfaceMock->expects($this->any())
        ->method('getValue')->with('fedex/general/reorder_instance_api_url')
        ->willReturn('https://dunc6.dmz.fedex.com/document/fedexoffice/v2/reorderabledocuments');
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{
            "output":{
                "reorderableDocumentVO":{
                    "0":{
                            "id":"2099"
                        }
                }
            }
        }');
        $output = ["0" => [
                            "id" => "2099",
                            ],
        ];
        $this->reorderSubscriber->callReorderApi(self::CONTENT_REFERENCE);
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collection);
        $this->collection->expects($this->any())->method('addFieldToFilter')->willReturn($this->item);
        $this->item->expects($this->any())->method('getFirstItem')->willReturn($this->option);
        $option =[
                'info_buyRequest' => [
                    'fileManagementState' => [
                        'projects' => [
                            '0' => [
                                'fileItems' =>  [
                                    '0' => [
                                        'convertedFileItem' => [
                                            'fileId' => 'testfileID',
                                        ],
                                        'conversionResult' => [
                                            'documentId' => 'testDocumentId',
                                            'previewURI' => 'testuri.com/abc/cbc/vbv/ghg/bhb/bdk/bkb/cba',
                                        ],
                                        'contentAssociation' => [
                                            'contentReference' => [7447 => self::CONTENT_REFERENCE],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'external_prod' => [
                        '0' => [
                            'contentAssociations' => [
                                'contentReference'=> [7447 => self::CONTENT_REFERENCE],
                            ],
                            'preview_url' => [
                                0 => [
                                    'id' => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        $this->option->expects($this->any())->method('getData')->willReturn($option);
        $this->reorderSubscriber->updateOrderItem($orderId, $orderItemId  ,$output);
        $this->reorderSubscriber->manageReorderInstance($orderId, $allContentReferencesData);
        $this->assertEquals(null, $this->reorderSubscriber->processMessage($reorderMessageInterfaceMock));
    }

        /**
     * Assert processMessage.
     *
     * @return null
     */
    public function testProcessMessageForCustomDocProduct()
    {
        $reorderMessageInterfaceMock = $this->getMockBuilder(ReorderMessageInterface::class)
            ->setMethods(['getMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderId = '6697';
        $orderItemId = 7447;
        $reorderMessageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($orderId);
        $this->orderRepository->expects($this->any())->method('get')->with($orderId)->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $option =
            [
             'info_buyRequest' => [
                    'external_prod' => [
                        '0' => [
                            "contentAssociations"=> [
                                  'contentReference'=> ['contentReference'=> self::CONTENT_REFERENCE],
                              ],
                        ],
                    ],
                ],
            ];
        $this->item->expects($this->any())->method('getProductOptions')->willReturn($option);
        $this->reorderSubscriber->getOrderData($orderId);
        $allContentReferencesData = array (
            7447 => "'".self::CONTENT_REFERENCE."'",
        );
        $this->order->expects($this->any())->method('load')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('setReorderable')->with(1)->willReturnSelf();
        $this->orderInterface->expects($this->any())->method('save')->willReturnSelf();
        $contentReferences= "13150681870163322246406238580950042962581";
        $this->punchOutHelper->expects($this->any())
            ->method('getTazToken')->willReturn(json_encode(
            [
                'access_token' => '123',
                'token_type'   => 'Cookie',
            ]
        ));
        $this->punchOutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn(252671717176116717221);
        $this->configInterfaceMock->expects($this->any())
        ->method('getValue')->with('fedex/general/reorder_instance_api_url')
        ->willReturn('https://dunc6.dmz.fedex.com/document/fedexoffice/v2/reorderabledocuments');
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{
            "output":{
                "reorderableDocumentVO":{
                    "0":{
                            "id":"2099"
                        }
                }
            }
        }');
        $output = ["0" => [
                            "id" => "2099",
                            ],
        ];
        $this->reorderSubscriber->callReorderApi($contentReferences);
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collection);
        $this->collection->expects($this->any())->method('addFieldToFilter')->willReturn($this->item);
        $this->item->expects($this->any())->method('getFirstItem')->willReturn($this->option);
        $option =[
                'info_buyRequest' => [
                    'fileManagementState' => [],
                    'external_prod' => [
                        '0' => [
                            'contentAssociations' => [
                                'contentReference'=> [7447 => self::CONTENT_REFERENCE],
                            ],
                            'preview_url' => [
                                0 => [
                                    'id' => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        $this->option->expects($this->any())->method('getData')->willReturn($option);
        $this->reorderSubscriber->updateOrderItem($orderId, $orderItemId  ,$output);
        $this->reorderSubscriber->manageReorderInstance($orderId, $allContentReferencesData);
        $this->assertNull($this->reorderSubscriber->processMessage($reorderMessageInterfaceMock));
    }

    /**
     * Assert processMessage.
     *
     * @return null
     */
    public function testProcessMessageWithError()
    {
        $reorderMessageInterfaceMock = $this->getMockBuilder(ReorderMessageInterface::class)
            ->setMethods(['getMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $reorderMessageInterfaceMock->expects($this->any())->method('getMessage')
        ->willThrowException($exception);
        $this->assertEquals(null, $this->reorderSubscriber->processMessage($reorderMessageInterfaceMock));
    }

    /**
     * Assert getOrderData.
     *
     * @return null
     */
    public function testGetOrderData()
    {
        $orderId = '6697';
        $this->orderRepository->expects($this->any())->method('get')->with($orderId)->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $option =
            [
             'info_buyRequest' => [
                    'external_prod' => [
                        '0' => [
                            "contentAssociations"=> [
                                  'contentReference'=> ['contentReference'=> self::CONTENT_REFERENCE],
                              ],
                        ],
                    ],
                ],
            ];
        $this->item->expects($this->once())->method('getProductOptions')->willReturn($option);
        $this->assertEquals(
            ['' => '"13150681870163322246406238580950042962581"'],
            $this->reorderSubscriber->getOrderData($orderId)
        );
    }

    /**
     * Assert getOrderData.
     *
     * @return null
     */
    public function testGetOrderDataForBrowseCatalogItem()
    {
        $orderId = '6697';
        $this->orderRepository->expects($this->any())->method('get')->with($orderId)->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $option =
            [
             'info_buyRequest' => [
                    'external_prod' => [
                        '0' => [
                        ],
                    ],
                ],
            ];
        $this->item->expects($this->once())->method('getProductOptions')->willReturn($option);
        $this->assertEquals([], $this->reorderSubscriber->getOrderData($orderId));
    }

    /**
     * Assert egtOrderDataWithError.
     *
     * @return null
     */
    public function testGetOrderDataWithError()
    {
        $exception = new NoSuchEntityException;
        $orderId = '6697';
        $this->orderRepository->expects($this->any())->method('get')->with($orderId)->willThrowException($exception);
        $this->assertNull($this->reorderSubscriber->getOrderData($orderId));
    }

    /**
     * Assert testCallReorderApi.
     *
     * @return array
     */
    public function testCallReorderApi()
    {
        $this->punchOutHelper->expects($this->any())
            ->method('getTazToken')->willReturn(json_encode(
            [
                'access_token' => '123',
                'token_type'   => 'Cookie',
            ]
        ));
        $this->punchOutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn(252671717176116717221);
        $this->configInterfaceMock->expects($this->any())
        ->method('getValue')->with('fedex/general/reorder_instance_api_url')
        ->willReturn('https://dunc6.dmz.fedex.com/document/fedexoffice/v2/reorderabledocuments');
        $this->curlMock->expects($this->once())->method('getBody')->willReturn('{
            "errors":{
                "0":{
                    "message":"1579"
                }
            }
        }');
        $this->assertNull(null, $this->reorderSubscriber->callReorderApi(self::CONTENT_REFERENCE));
        $this->assertEquals(false, $this->reorderSubscriber->callReorderApi([]));
    }

    /**
     * Assert callReorderApi.
     *
     * @return array
     */
    public function testCallReorderApiWithAlerts()
    {
        $this->punchOutHelper->expects($this->any())
            ->method('getTazToken')->willReturn(json_encode(
            [
                'access_token' => '123',
                'token_type'   => 'Cookie',
            ]
        ));
        $this->punchOutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn(252671717176116717221);
        $this->configInterfaceMock->expects($this->any())
        ->method('getValue')->with('fedex/general/reorder_instance_api_url')
        ->willReturn('https://dunc6.dmz.fedex.com/document/fedexoffice/v2/reorderabledocuments');

        $this->curlMock->expects($this->once())->method('getBody')->willReturn('{
            "alerts":{
                "0":{
                    "message":"1579"
                }
            }
        }');
        $this->assertFalse(false, $this->reorderSubscriber->callReorderApi(self::CONTENT_REFERENCE));
   }


   /**
     * testDocumentLifeExtendApiCallAndInsert for if case.
     *
     */
   public function testDocumentLifeExtendApiCallAndInsertIfCase()
   {
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $option =
            [
             'info_buyRequest' => [
                    'external_prod' => [
                        '0' => [
                            "contentAssociations"=> [
                                  'contentReference'=> ['contentReference'=> self::CONTENT_REFERENCE],
                              ],
                        ],
                    ],
                ],
            ];
        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];

        $this->item->expects($this->any())->method('getProductOptions')->willReturn($option);
        $this->catalogDocumentRefranceApiMock->expects($this->any())->
        method('documentLifeExtendApiCallWithDocumentId')->willReturn($response);

        $this->orderRetationPeriodCollectionFactoryMock->expects($this->any())->
        method('create')->willReturn($this->orderRetationPeriodCollectionMock);

        $this->orderRetationPeriodCollectionMock->expects($this->any())->
        method('addFieldToFilter')->willReturn($this->orderRetationPeriodCollectionMock);

        $this->orderRetationPeriodCollectionMock->expects($this->any())->
        method('getSize')->willReturn(10);

        $this->orderRetationPeriodCollectionMock->expects($this->any())->
        method('getFirstItem')->willReturnSelf();

        $this->orderRetationPeriodFactoryMock->expects($this->any())->
        method('create')->willReturn($this->orderRetationPeriodMock);

        $this->orderRetationPeriodMock->expects($this->any())->
        method('load')->willReturnSelf();

        $this->orderRetationPeriodCollectionMock->expects($this->any())->
        method('getId')->willReturn(123123);

        $this->orderRetationPeriodMock->expects($this->any())->
        method('setOrderItemId')->willReturnSelf();

        $this->orderRetationPeriodMock->expects($this->any())->
        method('setExtendedDate')->willReturnSelf();

        $this->orderRetationPeriodMock->expects($this->any())->
        method('setExtendedFlag')->willReturnSelf();

        $this->orderRetationPeriodMock->expects($this->any())->
        method('save')->willReturnSelf();
        $this->assertNull($this->reorderSubscriber->documentLifeExtendApiCallAndInsert(123));
   }

    /**
     * testDocumentLifeExtendApiCallAndInsert CheckLegacyDoc
     *
     */
    public function testDocumentLifeExtendApiCallAndInsertCheckLegacyDoc()
    {
         $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
         $this->order->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
         $option =
             [
              'info_buyRequest' => [
                     'external_prod' => [
                         '0' => [
                             "contentAssociations"=> [
                                   'contentReference'=> ['contentReference'=> 'sdgssgsgsgs'],
                               ],
                         ],
                     ],
                 ],
             ];
         $response = [
             'output' => [
                 'document' => [
                     'expirationTime' => date("Y-m-d H:i:s")
                 ]
             ]
         ];
 
         $this->item->expects($this->any())->method('getProductOptions')->willReturn($option);
         $this->catalogDocumentRefranceApiMock->expects($this->any())->
         method('documentLifeExtendApiCallWithDocumentId')->willReturn($response);
         $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

         $this->orderRetationPeriodCollectionFactoryMock->expects($this->any())->
         method('create')->willReturn($this->orderRetationPeriodCollectionMock);
 
         $this->orderRetationPeriodCollectionMock->expects($this->any())->
         method('addFieldToFilter')->willReturn($this->orderRetationPeriodCollectionMock);
 
         $this->orderRetationPeriodCollectionMock->expects($this->any())->
         method('getSize')->willReturn(10);
 
         $this->orderRetationPeriodCollectionMock->expects($this->any())->
         method('getFirstItem')->willReturnSelf();
 
         $this->orderRetationPeriodFactoryMock->expects($this->any())->
         method('create')->willReturn($this->orderRetationPeriodMock);
 
         $this->orderRetationPeriodMock->expects($this->any())->
         method('load')->willReturnSelf();
 
         $this->orderRetationPeriodCollectionMock->expects($this->any())->
         method('getId')->willReturn(123123);
 
         $this->orderRetationPeriodMock->expects($this->any())->
         method('setOrderItemId')->willReturnSelf();
 
         $this->orderRetationPeriodMock->expects($this->any())->
         method('setExtendedDate')->willReturnSelf();
 
         $this->orderRetationPeriodMock->expects($this->any())->
         method('setExtendedFlag')->willReturnSelf();
 
         $this->orderRetationPeriodMock->expects($this->any())->
         method('save')->willReturnSelf();
         $this->assertNull($this->reorderSubscriber->documentLifeExtendApiCallAndInsert(123));
    }

    /**
     * testDocumentLifeExtendApiCallAndInsert for Else case.
     *
     */
    public function testDocumentLifeExtendApiCallAndInsertElseCase()
    {
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $option =
            [
            'info_buyRequest' => [
                    'external_prod' => [
                        '0' => [
                            "contentAssociations"=> [
                                'contentReference'=> ['contentReference'=> self::CONTENT_REFERENCE],
                            ],
                        ],
                    ],
                ],
            ];
        $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];

        $this->item->expects($this->any())->method('getProductOptions')->willReturn($option);
        $this->catalogDocumentRefranceApiMock->expects($this->any())->
        method('documentLifeExtendApiCallWithDocumentId')->willReturn($response);

        $this->orderRetationPeriodCollectionFactoryMock->expects($this->any())->
        method('create')->willReturn($this->orderRetationPeriodCollectionMock);

        $this->orderRetationPeriodCollectionMock->expects($this->any())->
        method('addFieldToFilter')->willReturn($this->orderRetationPeriodCollectionMock);

        $this->orderRetationPeriodCollectionMock->expects($this->any())->
        method('getSize')->willReturn(0);

        $this->orderRetationPeriodFactoryMock->expects($this->any())->
        method('create')->willReturn($this->orderRetationPeriodMock);

        $this->orderRetationPeriodMock->expects($this->any())->
        method('setOrderItemId')->willReturnSelf();

        $this->orderRetationPeriodMock->expects($this->any())->
        method('setDocumentId')->willReturnSelf();

        $this->orderRetationPeriodMock->expects($this->any())->
        method('setExtendedDate')->willReturnSelf();

        $this->orderRetationPeriodMock->expects($this->any())->
        method('setExtendedFlag')->willReturnSelf();

        $this->orderRetationPeriodMock->expects($this->any())->
        method('save')->willReturnSelf();

        $this->assertNull($this->reorderSubscriber->documentLifeExtendApiCallAndInsert(123));
    }

    /**
     * testDocumentLifeExtendApiCallAndInsert for Exception case.
     *
     */
    public function testDocumentLifeExtendApiCallAndInsertException()
    {
        $exception = new NoSuchEntityException;
        $this->orderRepository->expects($this->any())->method('get')->willThrowException($exception);;
        $this->assertNull($this->reorderSubscriber->documentLifeExtendApiCallAndInsert(123));
    }

}
