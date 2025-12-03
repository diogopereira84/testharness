<?php

namespace Fedex\FXOPricing\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOPricing\Model\FXOProductDataModel;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Quote\Model\Quote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data AS MarketplaceCheckoutHelper;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\InStoreConfigurations\Api\ConfigInterface;

/**
 * Test cases for FXOProductDataModel
 */
class FXOProductDataModelTest extends TestCase
{

    /**
     * @var ProductModel|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productModel;

    /**
     * @var AttributeSetRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $attributeSetRepositoryInterface;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializer;

    /**
     * @var (\Magento\Checkout\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $checkoutSession;

    /**
     * @var CartDataHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cartDataHelper;

    /**
     * @var Item|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $item;

    /**
     * @var DataObject|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $product;

    /**
     * @var Option|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $option;

    /**
     * @var Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $quote;

    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;

    /**
     * @var (\Fedex\MarketplaceProduct\Helper\Quote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteHelper;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var object
     */
    protected $fxoProduct;

    /**
     * @var AdminConfigHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $adminConfigHelper;

    /**
     * @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $config;

    /**
     * @var ProductRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productRepositoryMock;

    /**
     * @var MarketplaceCheckoutHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private MarketplaceCheckoutHelper $marketplaceCheckoutHelper;

    /**
     * @return void
     */
    protected function setUp():void
    {
        $this->productModel = $this->getMockBuilder(ProductModel::class)
            ->setMethods(['getId', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeSetRepositoryInterface = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->setMethods(['getAttributeSetName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartDataHelper = $this->createMock(CartDataHelper::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);

        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getItemId',
                    'setDiscountAmount',
                    'setBaseDiscountAmount',
                    'setRowTotal',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setIsSuperMode',
                    'getOptionByCode',
                    'removeOption',
                    'getQty',
                    'setDiscount',
                    'getProduct',
                    'save',
                    'getMiraklOfferId'
                ]
            )
            ->getMock();
        $this->product = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct', 'setIsSuperMode', 'getId', 'getAttributeSetId','getCustomizable'])
            ->getMock();
        $this->option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptionId', 'setValue', 'getValue', 'getOptionByCode'])
            ->getMock();
        $this->quote  = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isUploadToQuoteGloballyEnabled', 'getProductValue'])
            ->getMock();

        $this->config = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteHelper = $this->createMock(QuoteHelper::class);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->objectManager = new ObjectManager($this);
        $this->fxoProduct = $this->objectManager->getObject(
            FXOProductDataModel::class,
            [
                'productModel' => $this->productModel,
                'attributeSetRepositoryInterface' => $this->attributeSetRepositoryInterface,
                'serializer' => $this->serializer,
                'checkoutSession' => $this->checkoutSession,
                'toggleConfig' => $this->toggleConfig,
                'quoteHelper' => $this->quoteHelper,
                'adminConfigHelper' => $this->adminConfigHelper,
                'config' => $this->config,
                'productRepository'=> $this->productRepositoryMock,
                'marketplaceCheckoutHelper'=> $this->marketplaceCheckoutHelper
            ]
        );
    }

    /**
     * Test case for iterateItems
     * @return void
     */
    public function testIterateItems()
    {
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'fxo_product' => [
                        'fxoProductInstance' => []
                    ]
                ],
            ],
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')
            ->willReturnOnConsecutiveCalls(true, true);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(12021);
        $this->item->expects($this->any())->method('getQty')->willReturn(12);
        $productId = 12021;
        $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(false);
        $this->productModel->expects($this->any())->method('load')
            ->with($productId)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(23);
        $this->attributeSetRepositoryInterface->expects($this->any())->method('getAttributeSetName')->willreturn('XYZ');
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willreturnSelf();
        $this->product->expects($this->any())->method('getCustomizable')->willReturn(false);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->adminConfigHelper->expects($this->any())->method('isUploadToQuoteGloballyEnabled')
            ->willReturn(true);
        $this->adminConfigHelper->expects($this->any())->method('getProductValue')
            ->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn('');

        $this->cartDataHelper->expects($this->any())->method('getDltThresholdHours')->willReturn(1);
        $this->cartDataHelper->expects($this->any())->method('setDltThresholdHours')->willReturn($decodedData);
        $this->cartDataHelper->expects($this->any())->method('setFxoProductNull')->willReturn($decodedData);
        $this->product->expects($this->any())->method('getCustomizable')->willReturn(false);
        $this->item->expects($this->any())->method('getItemId')->willReturn(1);

        $this->assertNotNull($this->fxoProduct->iterateItems(
            $this->cartDataHelper,
            [0 => $this->item],
            123,
            123,
            true
        ));
    }

    /**
     * Test case for iterateItems
     * @return void
     */
    public function testIterateItemsCase2()
    {
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'fxo_product' => [
                        'fxoProductInstance' => []
                    ]
                ],
            ],
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->item->expects($this->any())->method('getMiraklOfferId')->willReturn(1);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(12021);
        $this->item->expects($this->any())->method('getQty')->willReturn(12);
        $productId = 12021;
        $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')
            ->with($productId)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(23);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('getAttributeSetName')->willreturn('FXOPrintProducts');
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willreturnSelf();
        $this->product->expects($this->any())->method('getCustomizable')->willReturn(true);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($decodedData));
        $this->cartDataHelper->expects($this->any())->method('setFxoProductNull')->willReturn($decodedData);
        $this->assertNotNull($this->fxoProduct->iterateItems(
            $this->cartDataHelper,
            [0 => $this->item],
            123,
            123,
            true
        ));
    }

    /**
     * Test case for iterateItemsWithDifferentId
     * @return void
     */
    public function testIterateItemsDifferentId()
    {
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(12021);
        $this->item->expects($this->any())->method('getQty')->willReturn(12);
        $productId = 12021;
        $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')
            ->with($productId)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(23);
        $this->attributeSetRepositoryInterface->expects($this->any())->method('getAttributeSetName')->willreturn('XYZ');
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willreturnSelf();
        $this->product->expects($this->any())->method('getCustomizable')->willReturn(false);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($decodedData));
        $this->assertNotNull($this->fxoProduct->iterateItems(
            $this->cartDataHelper,
            [0 => $this->item],
            12,
            123,
            false
        ));
    }

    /**
     * Test case for iterateItemsWithAttributeName
     * @return void
     */
    public function testIterateItemsAttributeName()
    {
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'fxo_product' => "{'fxoProductInstance': ''}"
                ],
            ],
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(12021);
        $this->item->expects($this->any())->method('getQty')->willReturn(12);
        $productId = 12021;
        $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')
            ->with($productId)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(23);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('getAttributeSetName')->willreturn('FXOPrintProducts');
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willreturnSelf();
        $this->product->expects($this->any())->method('getCustomizable')->willReturn(false);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($decodedData));
        $this->cartDataHelper->expects($this->any())->method('setFxoProductNull')->willReturn($decodedData);
        $this->assertNotNull($this->fxoProduct->iterateItems(
            $this->cartDataHelper,
            [0 => $this->item],
            12,
            123,
            true
        ));
    }

    /**
     * Test case for iterateItemsWithAttributeName
     * @return void
     */
    public function testIterateItemsAttributeNameCase2()
    {
        $expectedReturn = [
            'quoteObjectItemsCount' => 12,
            'rateApiProdRequestData' => [
                 [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'fxo_product' => "{'fxoProductInstance': ''}"
                ]
            ],
            'productAssociations' => [
                0 => NULL
            ],
            'itemsUpdatedData' => [
                [
                    'external_prod' => [
                        [
                            'userProductName' => 'Poster Prints',
                            'id' => 1466693799380,
                            'version' => 2,
                            'name' => 'Posters',
                            'qty' => 12,
                            'priceable' => 1,
                            'instanceId' => 1632939962051,
                            'fxo_product' => "{'fxoProductInstance': ''}"
                        ],
                    ],
                ],
            ],
            'dbQuoteItemCount' => 12
        ];
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'fxo_product' => "{'fxoProductInstance': ''}"
                ],
            ],
        ];
        $this->config->expects($this->any())->method('isRateQuoteProductAssociationEnabled')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(12021);
        $this->item->expects($this->any())->method('getQty')->willReturn(12);
        $productId = 12021;
        $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')
            ->with($productId)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(23);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('getAttributeSetName')->willreturn('FXOPrintProducts');
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willreturnSelf();
        $this->product->expects($this->any())->method('getCustomizable')->willReturn(false);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($decodedData));
        $this->cartDataHelper->expects($this->any())->method('setFxoProductNull')->willReturn($decodedData);
        $this->assertEquals($expectedReturn, $this->fxoProduct->iterateItems(
            $this->cartDataHelper,
            [0 => $this->item],
            12,
            12,
            true
        ));
    }

    /**
     * Test case for manageAdditionalItem
     * @return void
     */
    public function testManageAdditionalItem()
    {
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->option->expects($this->any())->method('getOptionId')->willReturn(2);
        $this->serializer->expects($this->any())->method('serialize')->willReturn('AnyString');
        $this->option->expects($this->any())->method('setvalue')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $this->assertNull($this->fxoProduct->manageAdditionalItem(
            $this->item,
            $itemsUpdatedData,
            0
        ));
    }

    /**
     * Test case for manageAdditionalItemElseCase
     * @return void
     */
    public function testManageAdditionalItemElseCase()
    {
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->item->expects($this->any())->method('removeOption')->willReturnSelf();
        $this->option->expects($this->any())->method('getOptionId')->willReturn(null);
        $this->serializer->expects($this->any())->method('serialize')->willReturn('AnyString');
        $this->option->expects($this->any())->method('setvalue')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $this->assertNull($this->fxoProduct->manageAdditionalItem(
            $this->item,
            $itemsUpdatedData,
            0
        ));
    }

}
