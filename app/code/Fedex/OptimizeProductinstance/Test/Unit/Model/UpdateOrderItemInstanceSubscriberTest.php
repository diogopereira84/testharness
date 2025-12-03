<?php

namespace Fedex\OptimizeProductinstance\Test\Unit\Model;

use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Catalog\Model\ProductFactory;
use Fedex\OptimizeProductinstance\Model\OrderCompressionFactory;
use Fedex\Delivery\Helper\Data as ProductDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\OptimizeProductinstance\Model\OrderCompression;
use Magento\Quote\Model\Quote\Item;
use Fedex\OptimizeProductinstance\Model\UpdateOrderItemInstanceSubscriber;

class UpdateOrderItemInstanceSubscriberTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $optimizeInstanceMessageInterface;
    /**
     * @var (\Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $optimizeInstanceSubscriberInterface;
    protected $orderFactory;
    protected $order;
    protected $productFactory;
    protected $product;
    protected $orderCompressionFactory;
    protected $orderCompression;
    protected $productDataHelper;
    protected $toggleConfig;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterface;
    protected $item;
    protected $updateOrderItemInstanceSubscriber;
    protected function setUp():void
    {
        $this->objectManager = new ObjectManager($this);
        $this->optimizeInstanceMessageInterface = $this->getMockBuilder(OptimizeInstanceMessageInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->optimizeInstanceSubscriberInterface = $this->getMockBuilder(OptimizeInstanceSubscriberInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
        ->setMethods(['create', 'load'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->productFactory = $this->getMockBuilder(ProductFactory::class)
        ->setMethods(['create', 'load'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
        ->setMethods(['getAttributeSetId'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->orderCompressionFactory = $this->getMockBuilder(OrderCompressionFactory::class)
        ->setMethods(['create', 'load'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->orderCompression = $this->getMockBuilder(OrderCompression::class)
        ->setMethods(['getOrderId'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->productDataHelper = $this->getMockBuilder(ProductDataHelper::class)
        ->setMethods(['getProductAttributeName'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->item = $this->getMockBuilder(Item::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->updateOrderItemInstanceSubscriber = $this->objectManager->getObject(
            UpdateOrderItemInstanceSubscriber::class,
            [
                'orderFactory' => $this->orderFactory,
                'productFactory' => $this->productFactory,
                'orderCompressionFactory' => $this->orderCompressionFactory,
                'productDataHelper' => $this->productDataHelper,
                'toggleConfig' => $this->toggleConfig,
                'logger' => $this->loggerInterface
            ]
        );
    }

    /**
     * Test case for updateOrderItemsOptionFormat
     */

    public function testupdateOrderItemsOptionFormat()
    {
        $productOptionData = json_encode(
            [
                'info_buyRequest' => [
                    'external_prod' => [
                        0 => [
                            'fxo_product' => json_encode(
                                [
                                    'fxoMenuId'=> 123,
                                    'fxoProductInstance'=> [
                                        'productConfig'=> [
                                            'product' => 'Flyers'
                                        ],
                                        'productRateTotal'=> '$32.00',
                                        'quantityChoices'=>'test',
                                        'fileManagementState' =>'Dump',
                                        'fxoMenuId' => 123
                                    ],
                                ]
                            ),
                            'productionContentAssociations' => 'test2',
                            'contentAssociations' => 'test',

                        ]
                    ]
                ]
            ]
        );

        $assertArray = Array (
            'info_buyRequest' => Array  (
                'external_prod' => Array  (
                    0 => Array  (
                        'productionContentAssociations' => 'test2',
                        'userProductName' => '',
                        'id' => '',
                        'version' => '',
                        'name' => '',
                        'qty' => '',
                        'priceable' => '',
                        'instanceId' => '',
                        'proofRequired' => '',
                        'isOutSourced' => '',
                        'features' => Array  (),
                        'pageExceptions' => Array (),
                        'contentAssociations' => 'test',
                        'properties' => Array  (),
                        'preview_url' => '',
                        'isEditable' => false,
                        'isEdited' => false,
                       'fxoMenuId' => 123,
                    ),
                ),
                'productConfig' => Array (),
                'productRateTotal' => '$32.00',
                'quantityChoices' => 'test',
                'fileManagementState' => 'Dump',
                'fxoMenuId' => 123,
            )
        );
        
        $this->orderFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->orderFactory->expects($this->any())->method('load')->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllItems')->willReturn([0=> $this->item]);
        $this->item->expects($this->any())->method('getData')->willReturn(json_decode($productOptionData, true));
        $this->assertSame($assertArray, $this->updateOrderItemInstanceSubscriber->updateOrderItemsOptionFormat($this->item));
    }
    /**
     * Test case for updateOrderItemsOptionFormat
     */

    public function testupdateOrderItemsOptionFormatWithNullData()
    {
        $productOptionData = json_encode(
            [
                'info_buyRequesting' => [
                    'external_prod' => [
                        0 => [
                            'fxo_product' => json_encode(
                                [
                                    'fxoProductInstance'=> [
                                        'productConfig'=> [
                                            'product' => 'Flyers'
                                        ],
                                        'productRateTotal'=> '$32.00',
                                        'quantityChoices'=>'test',
                                        'fileManagementState' =>'Dump',
                                        'fxoMenuId' => 123
                                    ],
                                ]
                            ),
                            'productionContentAssociations' => 'test2',
                            'contentAssociations' => 'test',

                        ]
                    ]
                ]
            ]
        );
       
        $this->orderFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->orderFactory->expects($this->any())->method('load')->willReturn($this->order);
        $this->order->expects($this->any())->method('getAllItems')->willReturn([0=> $this->item]);
        $this->item->expects($this->any())->method('getData')->willReturn(json_decode($productOptionData, true));
        $this->assertEquals(false, $this->updateOrderItemInstanceSubscriber->updateOrderItemsOptionFormat($this->item));
    }

    /**
     * Test Case for processMessage
     */
    public function testprocessMessage()
    {
        $productOptionData = json_encode(
            [
                'info_buyRequest' => [
                    'external_prod' => [
                        0 => [
                            'fxo_product' => json_encode(
                                [
                                    'fxoMenuId'=> 123,
                                    'fxoProductInstance'=> [
                                        'productConfig'=> [
                                            'product' => 'Flyers'
                                        ],
                                        'productRateTotal'=> '$32.00',
                                        'quantityChoices'=>'test',
                                        'fileManagementState' =>'Dump',
                                        'fxoMenuId' => 123
                                    ],
                                ]
                            ),
                            'productionContentAssociations' => 'test2',
                            'contentAssociations' => 'test',

                        ]
                    ]
                ]
            ]
        );
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->optimizeInstanceMessageInterface->expects($this->any())->method('getMessage')->willReturn(1234);
        $this->orderCompressionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->orderCompressionFactory->expects($this->any())->method('load')->willReturn($this->orderCompression);
        $this->orderCompression->expects($this->any())->method('getOrderId')->willReturn(2);
        $this->orderFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->orderFactory->expects($this->any())->method('load')->willReturn($this->order);
        $this->productFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->productFactory->expects($this->any())->method('load')->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(123);
        $this->productDataHelper->expects($this->any())->method('getProductAttributeName')->willReturn('FXOPrintProducts');
        $this->order->expects($this->any())->method('getAllItems')->willReturn([0=> $this->item]);
        $this->item->expects($this->any())->method('getData')->willReturn(json_decode($productOptionData, true));
        $this->updateOrderItemInstanceSubscriber->updateOrderItemsOptionFormat($this->item);
        $this->assertEquals(null, $this->updateOrderItemInstanceSubscriber->processMessage($this->optimizeInstanceMessageInterface));
    }
}