<?php

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Observer\Frontend\Catalog;

use Fedex\Cart\Observer\Frontend\Catalog\CartProductAddAfter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\DataObject;
use Magento\Catalog\Model\Product;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;

class CartProductAddAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SerializerInterface|MockObject
     */
    protected $serializer;

    /**
     * @var \Fedex\Delivery\Helper\Data|MockObject
     */
    protected $dataHelper;

    /**
     * @var Product|MockObject
     */
    protected $productInstance;

    /**
     * @var (\Fedex\Cart\Controller\Product\Add & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $_addData;

    /**
     * @var RequestInterface|MockObject
     */
    protected $_request;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;

    /**
     * @var QuoteItem|MockObject
     */
    protected $_quoteItemMock;

    /**
     * @var (\Magento\Catalog\Model\Product & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $_qtyMock;

    /**
     * @var AttributeSetRepositoryInterface|MockObject
     */
    protected $attributeSetRepositoryMock;

    /**
     * @var (\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $item;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * @var FXORate|MockObject
     */
    protected $_fxoRateHelper;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var CartProductAddAfter
     */
    protected $helperData;

    /**
     * @var MockObject
     */
    protected $quoteMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $eventMock;

    /**
     * @var MockObject
     */
    protected $productMock;

    /**
     * @var Company|MockObject
     */
    protected $companyHelper;

    /** @var MockObject */
    protected MockObject $attributeSetMock;

    /** @var MockObject  */
    protected $attributeSetRepositoryInterface;

    /**
     * @var InstoreConfig|MockObject
     */
    private InstoreConfig|MockObject $instoreConfigMock;

    /**
     * @var FXORateQuote|MockObject
     */
    private FXORateQuote|MockObject $_fxoRateQuote;

    /**
     * @var \Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel|MockObject
     */
    protected $uploadToQuoteViewModel;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dataHelper = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer'])
            ->getMock();

        $this->productInstance = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getExternalProd'])
            ->getMock();

        $this->_addData = $this->getMockBuilder(\Fedex\Cart\Controller\Product\Add::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyHelper = $this->getMockBuilder(\Fedex\Company\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue', 'json_decode', 'json_encode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->attributeSetRepositoryInterface = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteMock = $this->createMock(Quote::class);

        $this->observerMock = $this->createMock(Observer::class);

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(
                [
                    'getEvent',
                    'getProduct',
                    'getQuoteItem',
                    'getAttributeSetId',
                    'getAttributeSetName',
                    'removeOption'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->_quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->setMethods([
                'getProduct',
                'getOptionByCode',
                'getQuote',
                'removeOption',
                'save',
                'addOption',
                'getQty',
                'getProductId',
                'setInstanceId',
                'setSiType'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_qtyMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods([
                'getExternalProd',
                'getQty',
                'setIsSuperMode',
                'getAttributeSetId',
                'getAttributeSetName',
                'addOption'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getAttributeSetId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeSetRepositoryMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->attributeSetMock = $this->getMockBuilder(\Magento\Eav\Api\Data\AttributeSetInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetName', 'getCustomizable', 'getExternalProd'])
            ->getMockForAbstractClass();

        $this->attributeSetRepositoryInterface->method('get')
            ->willReturn($this->attributeSetMock);

        $this->attributeSetRepositoryMock->method('get')
            ->willReturn($this->attributeSetMock);

        $this->item = $this->getMockBuilder(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface::class)
            ->addMethods(['getQty'])
            ->setMethods(['getProduct', 'getOptionByCode'])
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->_fxoRateHelper = $this->getMockBuilder(FXORate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORate', 'isEproCustomer'])
            ->getMock();

        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);
        $this->_fxoRateQuote = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORateQuote'])
            ->getMock();

        $this->uploadToQuoteViewModel = $this
            ->getMockBuilder(\Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSiType'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->helperData = $this->objectManager->getObject(
            CartProductAddAfter::class,
            [
                'serializer' => $this->serializer,
                'request' => $this->_request,
                'dataHelper' => $this->dataHelper,
                'addData' => $this->_addData,
                'companyHelper' => $this->companyHelper,
                'logger' => $this->logger,
                'attributeSetRepositoryInterface' => $this->attributeSetRepositoryInterface,
                'eventMock' => $this->eventMock,
                'quoteItemMock' => $this->_quoteItemMock,
                'qtyMock' => $this->_qtyMock,
                'toggleConfig' => $this->toggleConfig,
                'fxoRateHelper' => $this->_fxoRateHelper,
                'fxoRateQuote' => $this->_fxoRateQuote,
                'productInstance' => $this->productInstance,
                'instoreConfig' => $this->instoreConfigMock,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModel
            ]
        );
    }
    /**
     * Test for  execute()
     *
     * @return null
     */
    public function testExecute()
    {
        $orderData = [
            'errors' => [
                0 => [
                    'code' => 'COUPONS.CODE.INVALID',
                ],
            ],
        ];

        $varienObject = new DataObject();

        $varienObject->setData($orderData);
        $this->attributeSetMock->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand');

        $this->basicExecuteTests($varienObject);
        $this->_fxoRateHelper->expects($this->any())
            ->method('getFXORate')
            ->willReturn($varienObject);

        $result = $this->helperData->execute($this->observerMock);

        $this->assertNull($result);
    }

    /**
     * Test for  execute() with GraphQlFujitsuResponseException
     *
     * @return null
     */
    public function testExecuteWithGraphQlFujitsuResponseException(): void
    {
        $orderData = [
            'errors' => [
                0 => [
                    'code' => 'COUPONS.CODE.INVALID',
                ],
            ],
        ];

        $varienObject = new DataObject();

        $varienObject->setData($orderData);
        $this->attributeSetMock->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn('FXOPrintProducts');

        $this->basicExecuteTests($varienObject);
        $exception = new GraphQlFujitsuResponseException(__("Some message"));
        $this->_fxoRateQuote->expects($this->any())
            ->method('getFXORateQuote')
            ->willThrowException($exception);

        $this->instoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectException(GraphQlFujitsuResponseException::class);

        $this->helperData->execute($this->observerMock);
    }

    /**
     * Basic tests for execute method
     *
     * @param DataObject $varienObject
     * @return void
     */
    private function basicExecuteTests(DataObject $varienObject): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->toggleConfig
            ->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->_quoteItemMock
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->_qtyMock);

        $this->_qtyMock
            ->expects($this->any())
            ->method('getQty')
            ->willReturn(4);

        $this->_qtyMock
            ->expects($this->any())
            ->method('getAttributeSetId')
            ->will($this->onConsecutiveCalls(12, 15));

        $this->attributeSetRepositoryInterface
            ->expects($this->any())
            ->method('get')->willReturn($this->attributeSetMock);

        $this->attributeSetMock
            ->expects($this->any())
            ->method('getCustomizable')
            ->willReturn(false);

        $this->_qtyMock
            ->expects($this->any())
            ->method('getExternalProd')
            ->willReturn($this->getRequestData());

        $this->_request
            ->expects($this->any())
            ->method('getPostValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,
                        'returnEmptyParamsCallback'
                    ]
                )
            );

        json_decode($this->getRequestData());

        $prod = [
            'product_id' => null,
            'code' => 'info_buyRequest',
            'value' => true,
        ];

        $this->_quoteItemMock
            ->expects($this->any())
            ->method('addOption')
            ->with($prod)->willReturn(null);

        $this->serializer
            ->expects($this->atLeastOnce())
            ->method('serialize')
            ->willReturn(true);

        $this->dataHelper
            ->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->companyHelper
            ->expects($this->any())
            ->method('getCompanyPaymentMethod')
            ->willReturn('fedexaccountnumber');

        $this->companyHelper
            ->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn(1232342343242);
    }

    /**
     * Test for execute() with TechTitan non-customer and PrintOnDemand products
     *
     * @return void
     */
    public function testTechTitanNonCust(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(42);
        $this->attributeSetMock->method('getAttributeSetName')->willReturn('PrintOnDemand');
        $product->method('getCustomizable')->willReturn(false);
        $rawJson = '{"foo":"bar"}';
        $product->method('getExternalProd')->willReturn($rawJson);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct', 'getQty', 'getProductId', 'addOption', 'setSiType', 'setInstanceId'])
            ->getMock();
        $item->method('getProduct')->willReturn($product);
        $item->method('getQty')->willReturn(3);
        $item->method('getProductId')->willReturn(7);
        $item->expects($this->once())
            ->method('addOption')
            ->with($this->callback(
                fn($opt) =>
                $opt['code'] === 'info_buyRequest'
                    && is_string($opt['value'])
            ));
        $item->expects($this->once())
            ->method('setSiType')
            ->with('SITYPE');
        $item->expects($this->once())
            ->method('setInstanceId')
            ->with(0);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$item]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer
            ->method('serialize')
            ->with(['external_prod' => [0 => ['foo' => 'bar', 'qty' => '3']]])
            ->willReturn('SER');
        $this->uploadToQuoteViewModel
            ->method('getSiType')
            ->with('SER')
            ->willReturn('SITYPE');

        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(false);
        $this->_fxoRateQuote
            ->method('getFXORateQuote')
            ->with($quote)
            ->willReturn([]);

        $result = $this->helperData->execute($this->observerMock);
        $this->assertNull($result);
    }

    /**
     * Test for execute() with TechTitan non-customer and PrintOnDemand products
     *
     * @return void
     */
    public function testTechTitanCust(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(99);
        $this->attributeSetMock->method('getAttributeSetName')->willReturn('FXOPrintProducts');
        $product->method('getCustomizable')->willReturn(true);

        $cfgData = '{"x":1,"instanceId":123}';
        $itmData = '{"previewUrl":"pu","fxoProduct":"{\"instanceId\":123}"}';
        $this->_request
            ->method('getPostValue')
            ->willReturnMap([
                ['isMarketplaceProduct', null],
                ['configutorData', $cfgData],
                ['itemDetails', $itmData],
            ]);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct', 'getQty', 'getProductId', 'addOption', 'setSiType', 'setInstanceId'])
            ->getMock();
        $item->method('getProduct')->willReturn($product);
        $item->method('getQty')->willReturn(5);
        $item->method('getProductId')->willReturn(11);
        $item->expects($this->once())
            ->method('addOption')
            ->with($this->callback(
                fn($opt) =>
                $opt['code'] === 'info_buyRequest'
                    && is_string($opt['value'])
            ));
        $item->expects($this->once())
            ->method('setInstanceId')
            ->with(123);
        $item->expects($this->once())
            ->method('setSiType')
            ->with('SITYPE2');

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$item]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer->method('serialize')->willReturn('SER2');
        $this->uploadToQuoteViewModel
            ->method('getSiType')
            ->with('SER2')
            ->willReturn('SITYPE2');

        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(true);
        $this->_fxoRateHelper
            ->expects($this->once())
            ->method('getFXORate')
            ->with($quote)
            ->willReturn(['ok']);

        $result = $this->helperData->execute($this->observerMock);
        $this->assertNull($result);
    }

    /**
     * Test for execute() when external product data needs to be loaded from product instance
     *
     * @return void
     */
    public function testExecuteLoadingProductInstanceWhenExternalProdNotAvailable(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $this->_request
            ->method('getPostValue')
            ->with('isMarketplaceProduct')
            ->willReturn(null);

        $productId = 42;
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd', 'getId'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(99);
        $product->method('getCustomizable')->willReturn(false);
        $product->method('getExternalProd')->willReturn(null);
        $product->method('getId')->willReturn($productId);

        $this->attributeSetMock
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand');

        $this->_quoteItemMock
            ->method('getProduct')
            ->willReturn($product);
        $this->_quoteItemMock
            ->method('getQty')
            ->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$this->_quoteItemMock]);
        $this->_quoteItemMock
            ->method('getQuote')
            ->willReturn($quote);

        $loadedProduct = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExternalProd'])
            ->getMock();
        $loadedProduct->method('getExternalProd')
            ->willReturn('{"foo":"bar"}');

        $this->productInstance
            ->expects($this->once())
            ->method('load')
            ->with($productId)
            ->willReturn($loadedProduct);

        $this->serializer->method('serialize')->willReturn('serialized-data');
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');
        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(false);
        $this->_fxoRateQuote->method('getFXORateQuote')->willReturn([]);

        $result = $this->helperData->execute($this->observerMock);
        $this->assertNull($result);
    }

    /**
     * Test for execute() with handlePrintReadyFlag functionality
     *
     * @return void
     */
    public function testHandlePrintReadyFlag(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, true], // Enable the flag processing
            ]);

        $productId = 42;
        $rawJson = '{
            "contentAssociations": [
                {
                    "contentReference": "abc-123",
                    "purpose": "PRINT_INTENT",
                    "printReady": true
                },
                {
                    "contentReference": "def-456",
                    "purpose": "MAIN_CONTENT",
                    "printReady": true
                }
            ]
        }';

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAttributeSetId',
                'getCustomizable',
                'getExternalProd',
                'getId',
                'setExternalProd',
                'save'
            ])
            ->getMock();

        $product->method('getAttributeSetId')->willReturn(42);
        $this->attributeSetMock->method('getAttributeSetName')->willReturn('PrintOnDemand');
        $product->method('getCustomizable')->willReturn(false);
        $product->method('getExternalProd')->willReturn($rawJson);
        $product->method('getId')->willReturn($productId);

        $product->expects($this->once())
            ->method('setExternalProd')
            ->with($this->callback(function ($jsonData) {
                $data = json_decode($jsonData, true);
                return isset($data['contentAssociations']) &&
                    $data['contentAssociations'][0]['purpose'] === 'PRINT_INTENT' &&
                    $data['contentAssociations'][0]['printReady'] === false &&
                    $data['contentAssociations'][1]['printReady'] === true;
            }));

        $product->expects($this->once())->method('save');

        $this->_quoteItemMock->method('getProduct')->willReturn($product);
        $this->_quoteItemMock->method('getQty')->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$this->_quoteItemMock]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer->method('serialize')->willReturn('serialized-data');
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');
        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(false);
        $this->_fxoRateQuote->method('getFXORateQuote')->willReturn([]);

        $result = $this->helperData->execute($this->observerMock);
        $this->assertNull($result);
    }

    /**
     * Test for processing of fxoProductInstance data in PrintOnDemand products
     *
     * @return void
     */
    public function testFxoProductInstanceProcessingForPrintOnDemand(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $externalProdJson = '{
            "contentAssociations": [],
            "fxoProductInstance": {
                "id": "123456789",
                "name": "Test Product",
                "productConfig": {
                    "product": {
                        "userProductName": "Test Product Name",
                        "id": "prod-12345",
                        "version": 1,
                        "name": "Test Product",
                        "qty": 1,
                        "priceable": true,
                        "instanceId": "should-be-removed",
                        "proofRequired": false,
                        "isOutSourced": false,
                        "features": "feature1,feature2"
                    }
                }
            }
        }';

        $productId = 42;
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd', 'getId'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(55);
        $product->method('getCustomizable')->willReturn(false);
        $product->method('getExternalProd')->willReturn($externalProdJson);
        $product->method('getId')->willReturn($productId);

        $this->attributeSetMock
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand');

        $this->_quoteItemMock
            ->method('getProduct')
            ->willReturn($product);
        $this->_quoteItemMock
            ->method('getQty')
            ->willReturn(3);

        $this->serializer
            ->method('serialize')
            ->with($this->callback(function ($infoBuyRequest) {
                $externalProd = $infoBuyRequest['external_prod'][0];
                return isset($externalProd['userProductName']) &&
                    isset($externalProd['id']) &&
                    isset($externalProd['name']) &&
                    isset($externalProd['features']) &&
                    isset($externalProd['qty']) &&
                    ($externalProd['qty'] === '3' ||
                        $externalProd['qty'] === 3 ||
                        $externalProd['qty'] === 1) &&
                    isset($externalProd['fxoProductInstance']);
            }))
            ->willReturn('serialized-data');

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$this->_quoteItemMock]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');
        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(false);
        $this->_fxoRateQuote->method('getFXORateQuote')->willReturn([]);

        $result = $this->helperData->execute($this->observerMock);
        $this->assertNull($result);
    }

    /**
     * Test for execute() with product instance
     *
     * @return void
     */
    public function testExecuteWithProductInstance()
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->toggleConfig
            ->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn($this->onConsecutiveCalls(true, false));

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->_quoteItemMock
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->_qtyMock);

        $this->_qtyMock
            ->expects($this->any())
            ->method('getQty')
            ->willReturn(4);

        $this->_qtyMock
            ->expects($this->any())
            ->method('getAttributeSetId')
            ->will($this->onConsecutiveCalls(12, 15));

        $this->attributeSetRepositoryInterface
            ->expects($this->any())
            ->method('get');

        $this->attributeSetMock->expects($this->exactly(2))
            ->method('getAttributeSetName')
            ->will($this->onConsecutiveCalls('PrintOnDemand', 'FXOPrintProducts'));

        $this->attributeSetMock
            ->expects($this->any())
            ->method('getCustomizable')
            ->willReturn(false);

        $this->_qtyMock
            ->expects($this->any())
            ->method('getExternalProd')
            ->willReturn('');

        $this->productInstance
            ->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->productInstance
            ->expects($this->any())
            ->method('getExternalProd')
            ->willReturn($this->getRequestData());

        $this->_request
            ->expects($this->any())
            ->method('getPostValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,
                        'returnParamsCallback'
                    ]
                )
            );

        json_decode($this->getRequestData());

        $prod = [
            'product_id' => null,
            'code' => 'info_buyRequest',
            'value' => true,
        ];

        $this->_quoteItemMock
            ->expects($this->any())
            ->method('addOption')
            ->with($prod)->willReturn(null);

        $this->serializer
            ->expects($this->atLeastOnce())
            ->method('serialize')
            ->willReturn(true);

        $this->dataHelper
            ->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->companyHelper
            ->expects($this->any())
            ->method('getCompanyPaymentMethod')
            ->willReturn('fedexaccountnumber');

        $this->companyHelper
            ->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn(1232342343242);

        $this->helperData
            ->execute($this->observerMock);

        $this->helperData
            ->execute($this->observerMock);
    }

    /**
     * @return string|null
     */
    public function returnParamsCallback(): ?string
    {
        $args = func_get_args();
        if ($args[0] == 'isMarketplaceProduct') {
            return null;
        }
        return $this->getRequestData();
    }

    /**
     * @return string|null
     */
    public function returnEmptyParamsCallback(): ?string
    {
        $args = func_get_args();
        if ($args[0] == 'isMarketplaceProduct') {
            return null;
        }
        return $this->getEmptyRequestData();
    }

    /**
     * @return string
     */
    public function getRequestData(): string
    {
        return '{
            "infoBuyRequest":"",
            "Qty":"4",
            "previewUrl":"previewurl",
            "fxoProduct":"Printondemand",
            "instanceId":"null",
            "fxoMenuId":"1589559973691-2",
            "fxoProductInstance":{
                "id":"1612338831441",
                "name":"Indoor Banners",
                "productConfig":{
                    "product":{
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
                    }
                },
                "link":{
                    "href":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA64AAA"
                }
            },
            "contentAssociations":[{
                "parentContentReference":"06864396-6694-11ef-bc53-d1b3f26773c1",
                "contentReference":"0e46ec62-6694-11ef-a128-910991f40532",
                "contentType":"application\/pdf",
                "fileSizeBytes":0,
                "fileName":"37x17 Blank Check.1_custom (1).pdf",
                "printReady":true,
                "contentReqId":1483999952979,
                "name":"Multi Sheet",
                "purpose":"MAIN_CONTENT",
                "pageGroups":[{
                    "start":1,
                    "end":1,
                    "width":17,
                    "height":11,
                    "orientation":"LANDSCAPE"
                    }],
                "physicalContent":false
            }]
        }';
    }

    /**
     * @return string
     */
    public function getEmptyRequestData(): string
    {
        return '{
            "Qty":"4",
            "previewUrl":"previewurl",
            "fxoProduct":"Printondemand",
            "instanceId":"null",
            "fxoMenuId":"1589559973691-2",
            "fxoProductInstance":{
                "id":"1612338831441",
                "name":"Indoor Banners",
                "productConfig":{
                    "product":{
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
                    }
                },
                "link":{
                    "href":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA64AAA"
                }
            }
        }';
    }

    /**
     * Test exception handling when GraphQlFujitsuResponseException is thrown
     *
     * @return void
     */
    public function testGraphQlFujitsuResponseExceptionHandling(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(42);
        $product->method('getCustomizable')->willReturn(false);
        $product->method('getExternalProd')->willReturn('{"foo":"bar"}');

        $this->_quoteItemMock->method('getProduct')->willReturn($product);
        $this->_quoteItemMock->method('getQty')->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$this->_quoteItemMock]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer->method('serialize')->willReturn('serialized-data');
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');
        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(false);

        $exception = new GraphQlFujitsuResponseException(__('Test GraphQL exception'));
        $this->_fxoRateQuote
            ->method('getFXORateQuote')
            ->willThrowException($exception);

        $this->instoreConfigMock
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->helperData->execute($this->observerMock);
    }

    /**
     * Test suppression of GraphQlFujitsuResponseException based on config
     *
     * @return void
     */
    public function testGraphQlFujitsuResponseExceptionSuppression(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(42);
        $product->method('getCustomizable')->willReturn(false);
        $product->method('getExternalProd')->willReturn('{"foo":"bar"}');

        $this->_quoteItemMock->method('getProduct')->willReturn($product);
        $this->_quoteItemMock->method('getQty')->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$this->_quoteItemMock]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer->method('serialize')->willReturn('serialized-data');
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');
        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(false);

        $exception = new GraphQlFujitsuResponseException(__('Test GraphQL exception'));
        $this->_fxoRateQuote
            ->method('getFXORateQuote')
            ->willThrowException($exception);

        $this->instoreConfigMock
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(false);

        $this->helperData->execute($this->observerMock);

        $this->assertTrue(true);
    }

    /**
     * Test for instance ID generation when none is provided
     *
     * @return void
     */
    public function testGenerateInstanceIdWhenEmpty(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', false],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $productId = 42;
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getId'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(88);
        $product->method('getCustomizable')->willReturn(true);
        $product->method('getId')->willReturn($productId);

        $this->attributeSetMock
            ->method('getAttributeSetName')
            ->willReturn('FXOPrintProducts');

        $configData = '{"color":"blue","instanceId":null}';
        $itemDetails = '{"previewUrl":"preview.jpg","fxoProduct":"{\\"instanceId\\":null}"}';

        $this->_request
            ->method('getPostValue')
            ->willReturnMap([
                ['configutorData', $configData],
                ['itemDetails', $itemDetails],
            ]);

        $this->_quoteItemMock->method('getProduct')->willReturn($product);
        $this->_quoteItemMock->method('getProductId')->willReturn($productId);

        $this->_quoteItemMock->expects($this->once())
            ->method('setInstanceId')
            ->with($this->callback(function ($instanceId) use ($productId) {
                $productIdStr = (string)$productId;
                return is_numeric($instanceId) &&
                    strpos((string)$instanceId, $productIdStr) === 0 &&
                    strlen((string)$instanceId) > strlen($productIdStr);
            }));

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer->method('serialize')->willReturn('serialized-data');
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');
        $this->_fxoRateHelper->method('isEproCustomer')->willReturn(false);
        $this->_fxoRateQuote->method('getFXORateQuote')->willReturn([]);

        $result = $this->helperData->execute($this->observerMock);
        $this->assertNull(
            $result,
            'execute() should return null (void)'
        );
    }

    /**
     * Test FXO Rate behavior for Epro Customers with Tech Titan enabled
     *
     * @return void
     */
    public function testFxoRateForEproCustomersWithTechTitanEnabled(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', true],
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd', 'getId'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(42);
        $product->method('getCustomizable')->willReturn(false);
        $product->method('getExternalProd')->willReturn('{"foo":"bar"}');
        $product->method('getId')->willReturn(123);

        $this->attributeSetMock->method('getAttributeSetName')->willReturn('PrintOnDemand');
        $this->_quoteItemMock->method('getProduct')->willReturn($product);
        $this->_quoteItemMock->method('getQty')->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$this->_quoteItemMock]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer->method('serialize')->willReturn('serialized-data');
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');

        $this->_fxoRateHelper
            ->method('isEproCustomer')
            ->willReturn(true);

        $this->_fxoRateHelper
            ->expects($this->once())
            ->method('getFXORate')
            ->with($quote)
            ->willReturn(['success' => true]);

        $result = $this->helperData->execute($this->observerMock);

        $this->assertNull($result);
    }

    /**
     * Test FXO Rate behavior for Epro Customers with Tech Titan disabled
     *
     * @return void
     */
    public function testFxoRateForEproCustomersWithTechTitanDisabled(): void
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getQuoteItem')
            ->willReturn($this->_quoteItemMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['tech_titan_d_202382', false], // Tech titan disabled
                [CartProductAddAfter::EXPLORERS_HANDLE_PRINTREADYFLAG, false],
            ]);

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCustomizable', 'getExternalProd', 'getId'])
            ->getMock();
        $product->method('getAttributeSetId')->willReturn(42);
        $product->method('getCustomizable')->willReturn(false);
        $product->method('getExternalProd')->willReturn('{"foo":"bar"}');
        $product->method('getId')->willReturn(123);

        $this->attributeSetMock->method('getAttributeSetName')->willReturn('PrintOnDemand');
        $this->_quoteItemMock->method('getProduct')->willReturn($product);
        $this->_quoteItemMock->method('getQty')->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getAllItems')->willReturn([$this->_quoteItemMock]);
        $this->_quoteItemMock->method('getQuote')->willReturn($quote);

        $this->serializer->method('serialize')->willReturn('serialized-data');
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');

        $this->_fxoRateHelper
            ->method('isEproCustomer')
            ->willReturn(true);

        $this->_fxoRateHelper
            ->expects($this->once())
            ->method('getFXORate')
            ->with($quote)
            ->willReturn(['success' => true]);

        $result = $this->helperData->execute($this->observerMock);

        $this->assertNull($result);
    }
}
