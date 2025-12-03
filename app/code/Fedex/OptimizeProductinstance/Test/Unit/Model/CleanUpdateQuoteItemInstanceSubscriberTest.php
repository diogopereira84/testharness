<?php

namespace Fedex\OptimizeProductinstance\Test\Unit\Model;

use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;
use Fedex\OptimizeProductinstance\Model\QuoteCompressionFactory;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\Delivery\Helper\Data as ProductDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Fedex\OptimizeProductinstance\Model\CleanUpdateQuoteItemInstanceSubscriber;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Fedex\OptimizeProductinstance\Model\QuoteCompression;

class CleanUpdateQuoteItemInstanceSubscriberTest extends TestCase
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
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterface;
    protected $quoteFactory;
    protected $quote;
    protected $item;
    protected $itemOptionMock;
    protected $product;
    protected $quoteCompressionFactory;
    protected $quoteCompression;
    protected $negotiableQuoteFactory;
    protected $productDataHelper;
    protected $toggleConfig;
    /**
     * @var (\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $negotiableQuoteInterface;
    protected $negotiableQuote;
    protected $cleanUpdateQuoteItemInstanceSubscriber;
    protected function setUp():void
    {
        $this->objectManager = new ObjectManager($this);
        $this->optimizeInstanceMessageInterface = $this->getMockBuilder(OptimizeInstanceMessageInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->optimizeInstanceSubscriberInterface = $this->getMockBuilder(OptimizeInstanceSubscriberInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
        ->setMethods(['create', 'load'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
        ->setMethods(['getIsActive','getReservedOrderId','getAllItems'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->item = $this->getMockBuilder(Item::class)
        ->setMethods(['getProduct','getOptionByCode'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->itemOptionMock      = $this->getMockBuilder(Option::class)
        ->setMethods(['getValue','getOptionId','save','setValue'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
        ->setMethods(['getAttributeSetId'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->quoteCompressionFactory = $this->getMockBuilder(QuoteCompressionFactory::class)
        ->setMethods(['create','load'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->quoteCompression = $this->getMockBuilder(QuoteCompression::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->negotiableQuoteFactory = $this->getMockBuilder(NegotiableQuoteFactory::class)
        ->setMethods(['create', 'load'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->productDataHelper = $this->getMockBuilder(ProductDataHelper::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
        ->setMethods(['getStatus'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->cleanUpdateQuoteItemInstanceSubscriber = $this->objectManager->getObject(
            CleanUpdateQuoteItemInstanceSubscriber::class,
            [
                'quoteFactory' => $this->quoteFactory,
                'logger' => $this->loggerInterface,
                'quoteCompressionFactory' => $this->quoteCompressionFactory,
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'productDataHelper' => $this->productDataHelper,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test Case for checkNegotiableQuoteApprovedOrNot
     */
    public function testcheckNegotiableQuoteApprovedOrNot()
    {
        $quoteId = 123;
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->any())->method('load')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $this->assertEquals(false, $this->cleanUpdateQuoteItemInstanceSubscriber->checkNegotiableQuoteApprovedOrNot($quoteId));
    }
    /**
     * Test Case for checkNegotiableQuoteApprovedOrNot
     */
    public function testcheckNegotiableQuoteApprovedOrNotWithDifferentMessage()
    {
        $quoteId = 123;
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->any())->method('load')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn('DifferentMesage');
        $this->assertEquals(true, $this->cleanUpdateQuoteItemInstanceSubscriber->checkNegotiableQuoteApprovedOrNot($quoteId));
    }

    /**
     * Test Case for updateQuoteItemOptionData 
     */
    public function testupdateQuoteItemOptionData()
    {
        $additionalOptionData = json_encode(
            [
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
        );

        $this->assertEquals(2, $this->cleanUpdateQuoteItemInstanceSubscriber->updateQuoteItemOptionData($additionalOptionData));
    }
    /**
     * Test Case for updateQuoteItemOptionData 
     */
    public function testupdateQuoteItemOptionDataWithoutExternalProd()
    {
        $additionalOptionData = json_encode(
            [
                'external_prod' => [
                    0 => [
                        'fxo_product' => '',
                        'productionContentAssociations' => 'test2',
                        'contentAssociations' => 'test',

                    ]
                ]
            ]
        );
        $this->assertEquals(2, $this->cleanUpdateQuoteItemInstanceSubscriber->updateQuoteItemOptionData($additionalOptionData));
    }
    /**
     * Test Case for updateQuoteItemOptionData 
     */
    public function testupdateQuoteItemOptionDataWithoutEmptyData()
    {
        $additionalOptionData = json_encode(
            [
                'external_prod' => [
                    0 => [
                        'fxo_product' => json_encode(
                            [
                                'fxoMenuId'=> 123,
                                'fxoProductInstance'=> [
                                    'productConfig'=> [
                                        'productPresetId'=> 123,
                                        'fileCreated' => '4-Oct-2022',
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
        );

        $assertValue = '{"external_prod":[{"productionContentAssociations":"test2","userProductName":"","id":"","version":"","name":"","qty":"","priceable":"","instanceId":"","proofRequired":"","isOutSourced":"","features":[],"pageExceptions":[],"contentAssociations":"test","properties":[],"preview_url":"","isEditable":false,"isEdited":false,"fxoMenuId":123}],"productConfig":{"productPresetId":123,"fileCreated":"4-Oct-2022"},"productRateTotal":"$32.00","quantityChoices":"test","fileManagementState":"Dump","fxoMenuId":123}';
        $this->assertEquals($assertValue, $this->cleanUpdateQuoteItemInstanceSubscriber->updateQuoteItemOptionData($additionalOptionData));
    }
        /**
     * Test case for updateOrderItemsOptionFormat
     */

    public function testupdateQuoteItemOptionDataWithNullData()
    {
        $additionalOptionData = json_encode('');
        $this->assertEquals(false, $this->cleanUpdateQuoteItemInstanceSubscriber->updateQuoteItemOptionData($additionalOptionData));
    }
    /**
     * Test case for cleanUpdateFxoPrintProducts
     */
    public function testcleanUpdateFxoPrintProducts()
    {
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturn([0=> $this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(123);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->any())->method('load')->willReturn($this->negotiableQuote);
        $this->assertEquals(0,$this->cleanUpdateQuoteItemInstanceSubscriber->cleanUpdateFxoPrintProducts($this->item, true, $this->quote));

    }
    /**
     * Test case for cleanUpdateFxoPrintProducts
     */
    public function testcleanUpdateFxoPrintProductsWithProductAttributeName()
    {
        $additionalOption = json_encode(
            [
                'external_prod' => [
                    0 => [
                        'fxo_product' => json_encode(
                            [
                                'fxoMenuId'=> 123,
                                'fxoProductInstance'=> [
                                    'productConfig'=> [
                                        'productPresetId'=> 123,
                                        'fileCreated' => '4-Oct-2022',
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
        );
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturn([0=> $this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(123);
        $this->productDataHelper->expects($this->any())->method('getProductAttributeName')->willReturn('FXOPrintProducts');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->itemOptionMock);
        $this->itemOptionMock->expects($this->any())->method('getOptionId')->willReturn(1);
        $this->itemOptionMock->expects($this->any())->method('setValue')->willReturnSelf();
        $this->itemOptionMock->expects($this->any())->method('save')->willReturnSelf();
        $this->itemOptionMock->expects($this->any())->method('getValue')->willReturn($additionalOption);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->any())->method('load')->willReturn($this->negotiableQuote);
        $this->quote->expects($this->any())->method('getIsActive')->willReturn(0);
        $this->assertEquals(1,$this->cleanUpdateQuoteItemInstanceSubscriber->cleanUpdateFxoPrintProducts($this->item, true, $this->quote));

    }
    /**
     * Test case for cleanUpdateFxoPrintProducts
     */
    public function testcleanUpdateFxoPrintProductsWithGetReseverdIdEmpty()
    {
        $additionalOption = json_encode(
            [
                'external_prod' => [
                    0 => [
                        'fxo_product' => json_encode(
                            [
                                'fxoMenuId'=> 123,
                                'fxoProductInstance'=> [
                                    'productConfig'=> [
                                        'productPresetId'=> 123,
                                        'fileCreated' => '4-Oct-2022',
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
        );
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturn([0=> $this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(123);
        $this->productDataHelper->expects($this->any())->method('getProductAttributeName')->willReturn('FXOPrintProducts');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->itemOptionMock);
        $this->itemOptionMock->expects($this->any())->method('getOptionId')->willReturn(1);
        $this->itemOptionMock->expects($this->any())->method('setValue')->willReturnSelf();
        $this->itemOptionMock->expects($this->any())->method('save')->willReturnSelf();
        $this->itemOptionMock->expects($this->any())->method('getValue')->willReturn($additionalOption);
        $this->quote->expects($this->any())->method('getIsActive')->willReturn(1);
        $this->quote->expects($this->any())->method('getReservedOrderId')->willReturn(null);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->any())->method('load')->willReturn($this->negotiableQuote);
        $this->assertEquals(1,$this->cleanUpdateQuoteItemInstanceSubscriber->cleanUpdateFxoPrintProducts($this->item, false, $this->quote));

    }
    /**
     * Test case for cleanUpdateFxoPrintProducts
     */
    public function testcleanUpdateFxoPrintProductsWithGetReseverdIdEmptyOutput2()
    {
        $additionalOption = json_encode(
            [
                'external_prod' => [
                    0 => [
                        'fxo_product' => '',
                        'productionContentAssociations' => 'test2',
                        'contentAssociations' => 'test',

                    ]
                ]
            ]
        );
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturn([0=> $this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(123);
        $this->productDataHelper->expects($this->any())->method('getProductAttributeName')->willReturn('FXOPrintProducts');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->itemOptionMock);
        $this->itemOptionMock->expects($this->any())->method('getOptionId')->willReturn(1);
        $this->itemOptionMock->expects($this->any())->method('setValue')->willReturnSelf();
        $this->itemOptionMock->expects($this->any())->method('save')->willReturnSelf();
        $this->itemOptionMock->expects($this->any())->method('getValue')->willReturn($additionalOption);
        $this->quote->expects($this->any())->method('getIsActive')->willReturn(1);
        $this->quote->expects($this->any())->method('getReservedOrderId')->willReturn(null);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->any())->method('load')->willReturn($this->negotiableQuote);
        $this->assertEquals(2, $this->cleanUpdateQuoteItemInstanceSubscriber->cleanUpdateFxoPrintProducts($this->item, false, $this->quote));
    }

    /**
     * Test case for processMessage
     */
    public function testProcessMessage()
    {
        $quoteId = 123;
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->optimizeInstanceMessageInterface->expects($this->any())->method('getMessage')->willReturn(1);
        $this->quoteCompressionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteCompressionFactory->expects($this->any())->method('load')->willReturn($this->quoteCompression);
        // $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturnSelf();
        // $this->negotiableQuoteFactory->expects($this->any())->method('load')->willReturn($this->negotiableQuote);
        // $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $this->testcheckNegotiableQuoteApprovedOrNotWithDifferentMessage();
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0=>$this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->itemOptionMock);

        $getValue = json_encode(
            [
                'external_prod' => [
                    '0' => [
                        'catalogReference' => 'value',
                        'preview_url'      => 'value2',
                        'fxo_product'      => 'value3',
                    ],
                ],
            ]
        );

        $this->itemOptionMock->expects($this->any())->method('getValue')->willReturn($getValue);
        $this->testcleanUpdateFxoPrintProductsWithProductAttributeName();
        $this->cleanUpdateQuoteItemInstanceSubscriber->processMessage($this->optimizeInstanceMessageInterface);
    }
    /**
     * Test case for processMessage
     */
    public function testProcessMessageWithException()
    {
        $quoteId = 123;
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->optimizeInstanceMessageInterface->expects($this->any())->method('getMessage')->willReturn(1);
        $this->quoteCompressionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteCompressionFactory->expects($this->any())->method('load')->willReturn($this->quoteCompression);
        $this->testcheckNegotiableQuoteApprovedOrNotWithDifferentMessage();
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturn($this->item);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0=>$this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->itemOptionMock);

        $getValue = json_encode(
            [
                'external_prod' => [
                    '0' => [
                        'catalogReference' => 'value',
                        'preview_url'      => 'value2',
                        'fxo_product'      => 'value3',
                    ],
                ],
            ]
        );

        $this->itemOptionMock->expects($this->any())->method('getValue')->willReturn($getValue);
        $this->testcleanUpdateFxoPrintProductsWithProductAttributeName();
        $this->cleanUpdateQuoteItemInstanceSubscriber->processMessage($this->optimizeInstanceMessageInterface);
    }
}