<?php

/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2022 FedEx
 * @author      Eduardo Diogo Dias <edias@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\ThirdPartyProduct;

use Fedex\Cart\Model\Quote\ThirdPartyProduct\Update;
use Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\ExternalProd;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Api\Data\ProductInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/** @covers \Fedex\Cart\Model\Quote\ThirdPartyProduct\Update */
class UpdateTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private Config|MockObject $eavConfig;

    /**
     * @var (\Fedex\MarketplaceProduct\Api\Data\ShopManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shopManagement;

    /**
     * @var (\Magento\Checkout\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $checkoutSession;

    /**
     * @var (\Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $marketplaceConfigProvider;

    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helper;

    /**
     * @var (\Fedex\Cart\Model\Quote\ThirdPartyProduct\ExternalProd & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $externalProd;

    /**
     * @var (\Fedex\MarketplaceProduct\Model\NonCustomizableProduct & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $nonCustomizableProductModel;

    /**
     * @var (\Fedex\MarketplaceRates\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $marketPlaceRatesHelper;

    /** @var MockObject|RequestInterface */
    private MockObject|RequestInterface $request;

    /** @var MockObject|Product */
    private MockObject|Product $productMock;

    /** @var MockObject|Item  */
    private MockObject|Item $quoteItem;

    /** @var MockObject|Quote  */
    private MockObject|Quote $quote;

    /** @var MockObject|ElementFactory  */
    private MockObject|ElementFactory $xmlFactory;

    /** @var MockObject|Element */
    private MockObject|Element $itemXmlMock;

    /** @var MockObject|UrlInterface  */
    private MockObject|UrlInterface $urlBuilder;

    /** @var MockObject|File  */
    private MockObject|File $file;

    /** @var MockObject|Filesystem  */
    private MockObject|Filesystem $filesystem;

    /** @var MockObject|ReadInterface  */
    private MockObject|ReadInterface $read;

    /** @var Update  */
    private Update $update;

    /** @var MockObject|ShopInterface */
    private MockObject|ShopInterface $shopMock;

    /** @var MockObject|StoreManagerInterface  */
    private MockObject|StoreManagerInterface $storeManager;

    /**
     * @var ProductInterface|MockObject
     */
    private ProductInterface|MockObject $product;

    /** @var MockObject|CollectionFactory */
    private MockObject|CollectionFactory $category;

    /** @var MockObject|Data */
    private MockObject|Data $marketplaceCheckoutHelper;

    /** @var MockObject|LoggerInterface */
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->file = $this->createMock(File::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->read = $this->createMock(ReadInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->any())->method('getId')->willReturn(1);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($storeMock);
        $this->shopManagement = $this->createMock(ShopManagement::class);
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->marketplaceConfigProvider = $this->createMock(MarketplaceConfigProvider::class);
        $this->helper = $this->createMock(Data::class);
        $this->externalProd = $this->createMock(ExternalProd::class);
        $this->nonCustomizableProductModel = $this->createMock(NonCustomizableProduct::class);
        $this->product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getResource', 'getAttribute'])
            ->getMockForAbstractClass();

        $this->marketPlaceRatesHelper = $this->getMockBuilder(MarketplaceRatesHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFreightShippingEnabled'])
            ->getMock();

        $this->eavConfig = $this->createMock(Config::class);

        $resourceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAttribute'])
            ->getMock();
        $attributeMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getFrontend'])
            ->getMock();
        $frontendMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getValue'])
            ->getMock();

        $attributeMock->method('getFrontend')
            ->willReturn($frontendMock);

        $resourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $this->product->method('getResource')
            ->willReturn($resourceMock);

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributeText'])
            ->addMethods(['getBrand'])
            ->getMock();
        $this->quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getId',
                    'setCustomPrice',
                    'setQty',
                    'addOption',
                    'saveItemOptions',
                    'getProduct',
                    'getQuote',
                    'setQuote',
                    'getOptionByCode',
                ]
            )
            ->addMethods([
                'setOriginalCustomPrice',
                'setBaseRowTotal',
                'setRowTotal',
                'setIsSuperMode',
                'getAttribute',
                'getAdditionalData',
                'getProductId',

            ])
            ->addMethods(['setAdditionalData'])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getModuleName',
                    'setModuleName',
                    'getActionName',
                    'setActionName',
                    'getParam',
                    'setParams',
                    'getParams',
                    'getCookie',
                    'isSecure',
                ]
            )
            ->addMethods(['setPostValue'])
            ->getMock();

        $this->xmlFactory->method('create')
            ->willReturn($this->returnXml());
        $this->filesystem->method('getDirectoryRead')
            ->willReturn($this->read);
        $this->read->method('getAbsolutePath')
            ->willReturn('/blabla/testfolder/folder123');
        $this->quoteItem->method('getId')
            ->willReturn(1);

        $this->marketplaceConfigProvider->method('getCartQuantityTooltip')
            ->willReturn('tooltip message');
        $this->shopMock = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getCartExpire',
                'getCartExpireSoon',
                'getAdditionalInfo',
                'getShippingRateOption',
            ])
            ->getMockForAbstractClass();

        $this->externalProd = $this->getMockBuilder(ExternalProd::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProduct'])
            ->getMock();

        $this->quote = $this->createMock(Quote::class);
        // $this->itemXmlMock = $this->getMockBuilder(\Magento\Framework\Simplexml\Element::class)
        //                 ->disableOriginalConstructor()
        //                 ->onlyMethods(['getAttribute'])
        //                 ->getMock();

        $this->category = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceCheckoutHelper = $this->createMock(Data::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );
    }

    public function testAdd()
    {
        $this->assertInstanceOf(Update::class, $this->update);
    }

    /**
     * @throws LocalizedException
     */
    public function testupdateThirdPartyItem()
    {
        $this->request->expects(self::any())
            ->method('getParam')
            ->will(
                $this->returnCallback(
                    [
                        $this,
                        'returnParamsCallback'
                    ]
                )
            );
        $this->quoteItem->expects($this->once())
            ->method('setCustomPrice');
        $this->quoteItem->expects($this->once())
            ->method('setQty');
        $this->quoteItem->expects($this->once())
            ->method('addOption');
        $this->quoteItem->expects($this->once())
            ->method('saveItemOptions');

        $this->quoteItem->expects($this->once())->method('getProduct')->willReturn($this->productMock);

        $this->quoteItem->expects($this->once())->method('getQuote')->willReturn(null);

        $this->quoteItem->expects($this->once())
            ->method('setQuote')
            ->willReturnSelf();

        $this->checkoutSession->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->quoteItem->expects($this->any())->method('getProduct')->willReturn($this->productMock);
        $this->quoteItem->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn('{"expire_soon":1,"expire":1,"allow_edit_reorder":true}');
        $this->shopManagement
            ->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($this->shopMock);
        $this->quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->shopMock->expects($this->any())
            ->method('getId')
            ->willReturn("1");
        $this->shopMock->expects($this->any())
            ->method('getCartExpire')
            ->willReturn(1);
        $this->shopMock->expects($this->any())
            ->method('getCartExpireSoon')
            ->willReturn(1);
        $this->shopMock->expects($this->any())
            ->method('getAdditionalInfo')
            ->willReturn(["additional_field_values" => [["code" => "allow-edit-reorder", "value" => 'true']]]);
        $this->marketplaceConfigProvider->expects($this->any())
            ->method('getCartQuantityTooltip')
            ->willReturn('tooltip message');

        $result = $this->update
            ->updateThirdPartyItem($this->quoteItem, $this->product);

        $this->assertInstanceOf(Item::class, $result);
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testupdateThirdPartyItem2()
    {
        $this->helper->expects($this->any())
            ->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);
        $this->nonCustomizableProductModel->expects($this->any())
            ->method('isMktCbbEnabled')
            ->willReturn(true);
        $this->request->expects(self::any())
            ->method('getParam')
            ->will(
                $this->returnCallback(
                    [
                        $this,
                        'returnParamsCallback2'
                    ]
                )
            );
        $this->quoteItem->expects($this->once())
            ->method('setCustomPrice');
        $this->quoteItem->expects($this->once())
            ->method('setQty');
        $this->quoteItem->expects($this->once())
            ->method('addOption');
        $this->quoteItem->expects($this->once())
            ->method('saveItemOptions');
        $this->quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->quoteItem->expects($this->any())->method('getProduct')->willReturn($this->productMock);
        $this->quoteItem->expects($this->once())->method('getQuote')->willReturn($this->quote);

        $this->shopManagement
            ->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($this->shopMock);
        $this->shopMock->expects($this->any())
            ->method('getId')
            ->willReturn("1");
        $this->shopMock->expects($this->any())
            ->method('getCartExpire')
            ->willReturn(1);
        $this->shopMock->expects($this->any())
            ->method('getCartExpireSoon')
            ->willReturn(1);
        $this->shopMock->expects($this->any())
            ->method('getAdditionalInfo')
            ->willReturn(["additional_field_values" => [["code" => "allow-edit-reorder", "value" => 'true']]]);
        $this->marketPlaceRatesHelper->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);
        $this->shopMock->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn('freight_enabled');

        $result = $this->update
            ->updateThirdPartyItem($this->quoteItem);

        $this->assertInstanceOf(Item::class, $result);
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testAddItemToCart()
    {
        $this->request->expects(self::any())
            ->method('getParam')
            ->will(
                $this->returnCallback(
                    [
                        $this,
                        'returnParamsCallback'
                    ]
                )
            );
        $this->quoteItem->expects($this->any())
            ->method('setAdditionalData')
            ->willReturnSelf();
        $this->quoteItem->expects($this->once())
            ->method('setCustomPrice');
        $this->quoteItem->expects($this->once())
            ->method('addOption');
        $this->quoteItem->expects($this->once())
            ->method('saveItemOptions');

        $productModel = $this->createMock(Product::class);
        $this->quoteItem->expects($this->once())->method('getProduct')->willReturn($productModel);

        $quote = $this->createMock(Quote::class);
        $this->quoteItem->expects($this->once())->method('getQuote')->willReturn($quote);

        $shopInterface = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getCartExpire',
                'getCartExpireSoon'
            ])
            ->getMockForAbstractClass();

        $this->shopManagement->expects($this->any())->method('getShopByProduct')->willReturn($shopInterface);

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem);
    }

    /**
     * Callback function to return parameters based on the key.
     *
     * @return string|Element|bool
     */
    public function returnParamsCallback()
    {
        $args = func_get_args();
        if ($args[0] == 'seller_sku') {
            return 'testsku';
        }
        if ($args[0] == 'cxml-urlencoded') {
            return $this->returnXml();
        }
        if ($args[0] == 'offer_id') {
            return '1111';
        }
        if ($args[0] == 'punchout_disabled') {
            return true;
        }
    }

    /**
     * Callback function to return parameters based on the key.
     *
     * @return string|Element|bool
     */
    public function returnParamsCallback2()
    {
        $args = func_get_args();
        if ($args[0] == 'seller_sku') {
            return 'testsku';
        }

        if ($args[0] == 'offer_id') {
            return '1111';
        }
        if ($args[0] == 'punchout_disabled') {
            return true;
        }
    }

    /**
     * Returns a sample XML element for testing.
     *
     * @return Element
     */
    public function returnXml(): Element
    {
        return new Element(
            "<?xml version='1.0' encoding='UTF-8'?>
        <cXML xmlns:xsd='http://www.w3.org/2001/XMLSchema' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
        xml:lang='en-US' payloadID='89cd766a-a71f-49bf-92a3-561a5e64a7c3' timestamp='1/27/2023 10:15 AM'>
            <Header>
                <From>
                    <Credential domain='DUNS'>
                        <Identity>*********</Identity>
                    </Credential>
                </From>
                <To>
                    <Credential domain='DUNS'>
                        <Identity>*********</Identity>
                    </Credential>
                </To>
                <Sender>
                    <Credential domain='NetworkId'>
                        <Identity />
                        <SharedSecret>***********</SharedSecret>
                    </Credential>
                    <UserAgent>navINK 1.0</UserAgent>
                </Sender>
            </Header>
            <Message>
                <PunchOutOrderMessage>
                    <BuyerCookie>Fri, 13 Jan 2023 19:59:17 GMT</BuyerCookie>
                    <PunchOutOrderMessageHeader operationAllowed='edit'>
                        <Total>
                            <Money currency='USD'>832.00</Money>
                        </Total>
                    </PunchOutOrderMessageHeader>
                    <ItemIn itemType='item' lineNumber='1' quantity='500'>
                        <ItemID>
                            <SupplierPartID>RFST-SP9-FF-NAVI</SupplierPartID>
                            <SupplierPartAuxiliaryID>89cd766a-a71f-49bf-92a3-561a5e64a7c3</SupplierPartAuxiliaryID>
                        </ItemID>
                        <ItemDetail>
                            <Description xml:lang='en-US'>
                                <ShortName>Full Color Standard Two Pocket Folder</ShortName>
                                &amp;lt;p&amp;gt;&amp;amp;bull; Folded size: 9&amp;quot; X 12&amp;quot; &amp;lt;br
                                /&amp;gt;&amp;amp;bull; 4 color process imprint &amp;lt;br /&amp;gt;&amp;amp;bull;
                                Two 4&amp;quot; deep pockets &amp;lt;br /&amp;gt;&amp;amp;bull; Includes standard
                                business card slits on right pocket &amp;lt;br /&amp;gt;&amp;amp;bull; Class 1
                                Stocks&amp;lt;/p&amp;gt;
                            </Description>
                            <UnitOfMeasure>Folders</UnitOfMeasure>
                            <Classification domain='domain1'>NA</Classification>
                            <UnitPrice>
                                <Money currency='USD'>832.00</Money>
                            </UnitPrice>
                            <Extrinsic name='AccountNumber'>397752D</Extrinsic>
                            <Extrinsic name='Customized'>True</Extrinsic>
                            <Extrinsic name='ConfigurationCost'>0.0000</Extrinsic>
                            <Extrinsic name='UnitPrice'>1.6640</Extrinsic>
                            <Extrinsic name='UnitCost'>0.0000</Extrinsic>
                            <Extrinsic name='ConfigEngine'>UFPv1</Extrinsic>
                            <Extrinsic name='ProductionTime'>1 Business Day</Extrinsic>
                            <Extrinsic name='Aspect'>
                                <Extrinsic name='Name'>Shape</Extrinsic>
                                <Extrinsic name='Description'>Rectangle</Extrinsic>
                            </Extrinsic>
                            <URL>
                            https://navinkcuat.navitor.com/api/Files/GetByBrokerConfigId/89cd766a-a71f-49bf-92a3
                            </URL>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>"
        );
    }

    /**
     * Creates a mock for specific values.
     *
     * @param int $optionId
     * @param string $optionText
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getAttributeSourceMock(int $optionId, string $optionText)
    {
        $sourceMock = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\Source\AbstractSource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sourceMock->method('getOptionText')->with($optionId)->willReturn($optionText);

        return $sourceMock;
    }

    /**
     * Test saveImage method
     *
     * @covers \Fedex\Cart\Model\Quote\ThirdPartyProduct\Update::saveImage
     */
    public function testSaveImage(): void
    {
        $imageUrl = 'https://example.com/image.jpg';
        $imageName = 'image.jpg';
        $expectedPath = '/blabla/testfolder/folder123temp/catalog/';
        $expectedMediaUrl = 'https://media.example.com/temp/catalog/' . $imageName;

        $this->filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->read);

        $this->read->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn($expectedPath);

        $this->file->expects($this->once())
            ->method('checkAndCreateFolder')
            ->with($expectedPath);

        $this->file->expects($this->once())
            ->method('read')
            ->with($imageUrl, $expectedPath . $imageName);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->createMock(Store::class));

        $this->storeManager->getStore()->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA, true)
            ->willReturn('https://media.example.com/');

        $result = $this->update->saveImage($imageUrl, $imageName);

        $this->assertEquals($expectedMediaUrl, $result);
    }

    /**
     * Test addBrandToFeatures method
     *
     * @covers \Fedex\Cart\Model\Quote\ThirdPartyProduct\Update::addBrandToFeatures
     */
    public function testAddBrandToFeaturesWithExistingBrand(): void
    {
        $features = [
            ['name' => 'Brand', 'choice' => ['name' => 'ExistingBrand']],
        ];

        $this->productMock->expects($this->never())->method('getAttributeText');
        $this->productMock->expects($this->never())->method('getBrand');

        $result = $this->update->addBrandToFeatures($features, $this->productMock);

        $this->assertEquals($features, $result);
    }

    /**
     * Tests that the system correctly adds a brand to the product features
     * when no existing brand is present.
     *
     * @return void
     */
    public function testAddBrandToFeaturesWithoutExistingBrand(): void
    {
        $features = [
            ['name' => 'Color', 'choice' => ['name' => 'Red']],
        ];

        $this->productMock->expects($this->once())
            ->method('getAttributeText')
            ->with('brand')
            ->willReturn('NewBrand');
        $this->productMock->expects($this->never())->method('getBrand');

        $expectedFeatures = [
            ['name' => 'Color', 'choice' => ['name' => 'Red']],
            ['name' => 'Brand', 'choice' => ['name' => 'NewBrand']],
        ];

        $result = $this->update->addBrandToFeatures($features, $this->productMock);

        $this->assertEquals($expectedFeatures, $result);
    }

    /**
     * Tests that the system correctly adds a brand to the product features
     * when no existing brand is present and the attribute text is empty.
     *
     * @return void
     */
    public function testAddBrandToFeaturesWithoutExistingBrandAndEmptyAttributeText(): void
    {
        $features = [
            ['name' => 'Size', 'choice' => ['name' => 'Large']],
        ];

        $this->productMock->expects($this->once())
            ->method('getAttributeText')
            ->with('brand')
            ->willReturn(null);
        $this->productMock->expects($this->once())
            ->method('getBrand')
            ->willReturn('FallbackBrand');

        $expectedFeatures = [
            ['name' => 'Size', 'choice' => ['name' => 'Large']],
            ['name' => 'Brand', 'choice' => ['name' => 'FallbackBrand']],
        ];

        $result = $this->update->addBrandToFeatures($features, $this->productMock);

        $this->assertEquals($expectedFeatures, $result);
    }

    /**
     * Tests adding a brand to the features list when the features are initially empty.
     *
     * @return void
     */
    public function testAddBrandToFeaturesWithEmptyFeatures(): void
    {
        $features = [];

        $this->productMock->expects($this->once())
            ->method('getAttributeText')
            ->with('brand')
            ->willReturn('OnlyBrand');
        $this->productMock->expects($this->never())->method('getBrand');

        $expectedFeatures = [
            ['name' => 'Brand', 'choice' => ['name' => 'OnlyBrand']],
        ];

        $result = $this->update->addBrandToFeatures($features, $this->productMock);

        $this->assertEquals($expectedFeatures, $result);
    }

    /**
     * Tests adding a brand to the features list when the features are objects.
     *
     * @return void
     */
    public function testAddBrandToFeaturesWithObjectFeatures(): void
    {
        $features = [(object)["name" => "Brand"]];

        $this->productMock->expects($this->any())
            ->method('getAttributeText')
            ->with('brand')
            ->willReturn('OnlyBrand');
        $this->productMock->expects($this->never())->method('getBrand');

        $expectedFeatures = [
            ['name' => 'Brand', 'choice' => ['name' => 'OnlyBrand']],
        ];

        $result = $this->update->addBrandToFeatures($features, $this->productMock);
        $this->assertEquals($features, $result);
    }

    /**
     * Tests that the system does not add a brand to the features
     * when the product does not have a brand attribute.
     *
     * @return void
     */
    public function testAddBrandToFeaturesFalse(): void
    {
        $features = ["Test"];
        $result = $this->update->addBrandToFeatures($features, $this->productMock);
        $this->assertEquals(["Test"], $result);
    }

    /**
     * Covers the branch where getOptionByCode(...) returns null—or has no value—
     * so no addOption() call should happen.
     */
    public function testNoInfoBuyRequestOptionNoChange(): void
    {
        $this->quoteItem
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn(null);

        $this->quoteItem
            ->expects(self::never())
            ->method('addOption');

        $ref = new \ReflectionClass($this->update);
        $method = $ref->getMethod('updateInfoBuyRequestQuantity');
        $method->setAccessible(true);

        $quoteItem = $this->quoteItem;
        $method->invokeArgs($this->update, [&$quoteItem, 10]);
    }

    /**
     * Covers the branch where an existing info_buyRequest has a qty
     * that differs from the passed $quantity, so addOption() is called
     * with the updated JSON.
     */
    public function testInfoBuyRequestQuantityDiffersAddsOption(): void
    {
        $origData = ['qty' => 1, 'foo' => 'bar'];
        $optMock = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);
        $optMock->method('getValue')->willReturn(json_encode($origData));

        $this->quoteItem
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($optMock);

        $this->quoteItem
            ->method('getProductId')
            ->willReturn(42);

        $captured = null;
        $this->quoteItem
            ->expects(self::once())
            ->method('addOption')
            ->with(self::callback(function (array $arg) use (&$captured) {
                $captured = $arg;
                return true;
            }));

        $jsonSerializer = new class implements \Magento\Framework\Serialize\SerializerInterface {
            public function serialize($data)
            {
                return json_encode($data);
            }
            public function unserialize($data)
            {
                return json_decode($data, true);
            }
        };
        $refProp = (new \ReflectionClass($this->update))->getProperty('serializer');
        $refProp->setAccessible(true);
        $refProp->setValue($this->update, $jsonSerializer);

        $method = (new \ReflectionClass($this->update))
            ->getMethod('updateInfoBuyRequestQuantity');
        $method->setAccessible(true);
        $quoteItem = $this->quoteItem;
        $method->invokeArgs($this->update, [&$quoteItem, 5]);

        $this->assertSame(42, $captured['product_id']);
        $this->assertSame('info_buyRequest', $captured['code']);

        $newData = json_decode($captured['value'], true);
        $this->assertEquals(['qty' => 5, 'foo' => 'bar'], $newData);
    }

    /**
     * Test getFormattedSuperAttributes method
     *
     * @covers \Fedex\Cart\Model\Quote\ThirdPartyProduct\Update::getFormattedSuperAttributes
     */
    public function testGetFormattedSuperAttributes(): void
    {
        $attribute1 = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSource'])
            ->addMethods(['getFrontendLabel'])
            ->getMockForAbstractClass();

        $attribute2 = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSource'])
            ->addMethods(['getFrontendLabel'])
            ->getMockForAbstractClass();

        $nonExistentAttribute = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSource'])
            ->addMethods(['getFrontendLabel'])
            ->getMockForAbstractClass();

        $source1 = $this->getAttributeSourceMock(10, 'Red');
        $source2 = $this->getAttributeSourceMock(20, 'Large');

        $attribute1->expects($this->once())
            ->method('getId')
            ->willReturn(101);
        $attribute1->expects($this->once())
            ->method('getFrontendLabel')
            ->willReturn('Color');
        $attribute1->expects($this->once())
            ->method('getSource')
            ->willReturn($source1);

        $attribute2->expects($this->once())
            ->method('getId')
            ->willReturn(102);
        $attribute2->expects($this->once())
            ->method('getFrontendLabel')
            ->willReturn('Size');
        $attribute2->expects($this->once())
            ->method('getSource')
            ->willReturn($source2);

        $nonExistentAttribute->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $nonExistentAttribute->expects($this->never())
            ->method('getFrontendLabel');
        $nonExistentAttribute->expects($this->never())
            ->method('getSource');

        $this->eavConfig->expects($this->exactly(3))
            ->method('getAttribute')
            ->willReturnMap([
                ['catalog_product', 101, $attribute1],
                ['catalog_product', 102, $attribute2],
                ['catalog_product', 999, $nonExistentAttribute]
            ]);

        $result1 = $this->update->getFormattedSuperAttributes([]);
        $this->assertIsArray($result1);
        $this->assertEmpty($result1, 'Empty input should return empty array');

        $superAttributes = [
            101 => 10,  // Color => Red
            102 => 20,  // Size => Large
            999 => 30   // Non-existent attribute should be skipped
        ];

        $result2 = $this->update->getFormattedSuperAttributes($superAttributes);

        $expected = [
            [
                'name' => 'Color',
                'choice' => [
                    'name' => 'Red'
                ]
            ],
            [
                'name' => 'Size',
                'choice' => [
                    'name' => 'Large'
                ]
            ]
        ];

        $this->assertEquals($expected, $result2, 'Formatted attributes do not match expected structure');
        $this->assertCount(2, $result2, 'Result should contain only the valid attributes');
    }

    /**
     * Test updateThirdPartyItemSellerPunchout handles different XML structure variations
     *
     * @dataProvider xmlStructureVariationsProvider
     */
    public function testUpdateThirdPartyItemSellerPunchoutXmlStructureVariations(
        string $xmlString,
        string $expectedSupplierPartID,
        string $expectedSupplierPartAuxiliaryID,
        float $expectedTotal,
        int $expectedQuantity
    ): void {
        $this->xmlFactory = $this->createMock(ElementFactory::class);

        $xmlElement = new Element($xmlString);

        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                if ($param === 'seller_sku') {
                    return null;
                }
                if ($param === 'offer_id') {
                    return null;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($this->productMock);
        $this->quoteItem->method('getProductId')->willReturn(123);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem);

        $this->assertEquals($expectedSupplierPartID, $capturedAdditionalData['supplierPartID']);
        $this->assertEquals($expectedSupplierPartAuxiliaryID, $capturedAdditionalData['supplierPartAuxiliaryID']);
        $this->assertEquals($expectedTotal, $capturedAdditionalData['total']);
        $this->assertEquals($expectedQuantity, $capturedAdditionalData['quantity']);
    }

    /**
     * Data provider for XML structure variations
     */
    public function xmlStructureVariationsProvider(): array
    {
        return [
            'ItemId variant' => [
                $this->getXmlWithItemId(),
                'SUPPLIER-123',
                'AUX-456',
                100.00,
                5
            ],
            'ItemID variant' => [
                $this->getXmlWithUppercaseItemID(),
                'SUPPLIER-789',
                'AUX-012',
                200.00,
                10
            ],
            'TotalPrice variant' => [
                $this->getXmlWithTotalPrice(),
                'SUPPLIER-345',
                'AUX-678',
                300.00,
                15
            ],
            'ItemIn quantity attribute variant' => [
                $this->getXmlWithItemInQuantity(),
                'SUPPLIER-901',
                'AUX-234',
                400.00,
                20
            ]
        ];
    }

    /**
     * Return XML with ItemId structure
     */
    private function getXmlWithItemId(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total>
                            <Money>100.00</Money>
                        </Total>
                        <TotalQuantity>5</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-123</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-456</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description>
                                <ShortName>Test Product</ShortName>
                            </Description>
                            <URL>https://example.com/image.jpg</URL>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Return XML with ItemID structure (uppercase ID)
     */
    private function getXmlWithUppercaseItemID(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total>
                            <Money>200.00</Money>
                        </Total>
                        <TotalQuantity>10</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemID>
                            <SupplierPartID>SUPPLIER-789</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-012</SupplierPartAuxiliaryID>
                        </ItemID>
                        <ItemDetail>
                            <Description>
                                <ShortName>Test Product</ShortName>
                            </Description>
                            <URL>https://example.com/image.jpg</URL>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Return XML with TotalPrice structure
     */
    private function getXmlWithTotalPrice(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <TotalPrice>
                            <Money>300.00</Money>
                        </TotalPrice>
                        <TotalQuantity>15</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-345</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-678</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description>
                                <ShortName>Test Product</ShortName>
                            </Description>
                            <URL>https://example.com/image.jpg</URL>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Return XML with ItemIn quantity attribute
     */
    private function getXmlWithItemInQuantity(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total>
                            <Money>400.00</Money>
                        </Total>
                    </PunchOutOrderMessageHeader>
                    <ItemIn quantity='20'>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-901</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-234</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description>
                                <ShortName>Test Product</ShortName>
                            </Description>
                            <URL>https://example.com/image.jpg</URL>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that the ARTWORK case is properly handled in updateThirdPartyItem
     */
    public function testUpdateThirdPartyItemWithArtwork(): void
    {
        $xmlString = $this->getXmlWithArtworkForThirdPartyItem();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($this->productMock);
        $this->quoteItem->method('getProductId')->willReturn(123);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItem($this->quoteItem);

        $this->assertEquals('custom-artwork-file.jpg', $capturedAdditionalData['marketplace_name']);
        $this->assertEquals('SUPPLIER-ARTWORK', $capturedAdditionalData['supplierPartID']);
    }

    /**
     * Return XML with ARTWORK and FileName1 structure for updateThirdPartyItem test
     */
    private function getXmlWithArtworkForThirdPartyItem(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>500.00</Money></Total>
                    </PunchOutOrderMessageHeader>
                    <ItemIn quantity='1'>
                        <ItemID>
                            <SupplierPartID>SUPPLIER-ARTWORK</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-ARTWORK</SupplierPartAuxiliaryID>
                        </ItemID>
                        <ItemDetail>
                            <Description><ShortName>Original Product Name</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                            <Extrinsic name='Artwork'>
                                <Extrinsic name='FileName1'>custom-artwork-file.jpg</Extrinsic>
                            </Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that features are correctly extracted from composite items
     */
    public function testUpdateThirdPartyItemWithCompositeItem(): void
    {
        $xmlString = $this->getXmlWithCompositeItem();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($this->productMock);
        $this->quoteItem->method('getProductId')->willReturn(123);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItem($this->quoteItem);

        $this->assertArrayHasKey('features', $capturedAdditionalData);
        $features = $capturedAdditionalData['features'];

        $this->assertNotEmpty($features);

        $compositeFeature = null;
        foreach ($features as $feature) {
            if ($feature['name'] === 'Material') {
                $compositeFeature = $feature;
                break;
            }
        }

        $this->assertNotNull($compositeFeature, 'Material feature from composite item not found');
        $this->assertEquals('Cotton', $compositeFeature['choice']['name']);
    }

    /**
     * Return XML with a composite item that has Aspect features
     */
    private function getXmlWithCompositeItem(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>500.00</Money></Total>
                    </PunchOutOrderMessageHeader>
                    <ItemIn quantity='1'>
                        <ItemID>
                            <SupplierPartID>SUPPLIER-MAIN</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-MAIN</SupplierPartAuxiliaryID>
                        </ItemID>
                        <ItemDetail>
                            <Description><ShortName>Main Product</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                        </ItemDetail>
                    </ItemIn>
                    <ItemIn itemType='composite'>
                        <ItemDetail>
                            <Description><ShortName>Composite Part</ShortName></Description>
                            <Extrinsic name='Aspect'>
                                <Extrinsic name='Name'>Material</Extrinsic>
                                <Extrinsic name='Description'>Cotton</Extrinsic>
                            </Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that features are correctly extracted from composite items in seller punchout
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithCompositeItem(): void
    {
        $xmlString = $this->getXmlWithCompositeItemForSellerPunchout();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);
        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);

        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertIsArray($capturedAdditionalData);
        $this->assertArrayHasKey('supplierPartID', $capturedAdditionalData);
        $this->assertArrayHasKey('features', $capturedAdditionalData);
        $this->assertNotEmpty($capturedAdditionalData['features']);

        $found = false;
        foreach ($capturedAdditionalData['features'] as $feature) {
            if ($feature['name'] === 'Color' && $feature['choice']['name'] === 'Blue') {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Feature from composite item not found in additionalData');
    }

    /**
     * Return XML with a composite item that has Aspect features for seller punchout
     */
    private function getXmlWithCompositeItemForSellerPunchout(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>500.00</Money></Total>
                        <TotalQuantity>1</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-MAIN</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-MAIN</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description><ShortName>Main Product</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                        </ItemDetail>
                    </ItemIn>
                    <ItemIn itemType='composite'>
                        <ItemDetail>
                            <Description><ShortName>Composite Part</ShortName></Description>
                            <Extrinsic name='Aspect'>
                                <Extrinsic name='Name'>Color</Extrinsic>
                                <Extrinsic name='Description'>Blue</Extrinsic>
                            </Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that unit price is correctly extracted from TotalUnitCost in seller punchout
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithTotalUnitCost(): void
    {
        $xmlString = $this->getXmlWithTotalUnitCost();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);
        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);

        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertEquals(45.99, $capturedAdditionalData['unit_price']);
    }

    /**
     * Return XML with TotalUnitCost structure
     */
    private function getXmlWithTotalUnitCost(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>500.00</Money></Total>
                        <TotalQuantity>10</TotalQuantity>
                        <TotalUnitCost><Money>45.99</Money></TotalUnitCost>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-UNITCOST</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-UNITCOST</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description><ShortName>Unit Cost Test Product</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                            <Extrinsic name='UnitPrice'>39.99</Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that ARTWORK with FileName1 is properly handled in seller punchout
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithArtwork(): void
    {
        $xmlString = $this->getXmlWithArtworkForSellerPunchout();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);

        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertEquals('seller-artwork.jpg', $capturedAdditionalData['marketplace_name']);
    }

    /**
     * Return XML with ARTWORK and FileName1 structure for seller punchout
     */
    private function getXmlWithArtworkForSellerPunchout(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>600.00</Money></Total>
                        <TotalQuantity>1</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-SELLER-ARTWORK</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-SELLER-ARTWORK</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description><ShortName>Original Product Name</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                            <Extrinsic name='Artwork'>
                                <Extrinsic name='FileName1'>seller-artwork.jpg</Extrinsic>
                            </Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that Size aspect features are correctly handled in seller punchout
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithSizeAspect(): void
    {
        $xmlString = $this->getXmlWithSizeAspect();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);
        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);
        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertArrayHasKey('features', $capturedAdditionalData);
        $this->assertNotEmpty($capturedAdditionalData['features']);

        $sizeFeature = null;
        foreach ($capturedAdditionalData['features'] as $feature) {
            if ($feature['name'] === 'Size') {
                $sizeFeature = $feature;
                break;
            }
        }

        $this->assertNotNull($sizeFeature, 'Size feature not found in features array');
        $this->assertEquals('Large', $sizeFeature['choice']['name']);
    }

    /**
     * Return XML with an Aspect that has Size feature
     */
    private function getXmlWithSizeAspect(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>500.00</Money></Total>
                        <TotalQuantity>1</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-SIZE</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-SIZE</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description><ShortName>Size Test Product</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                            <Extrinsic name='Aspect'>
                                <Extrinsic name='Name'>Size</Extrinsic>
                                <Extrinsic name='Description'>Large</Extrinsic>
                            </Extrinsic>
                            <Extrinsic name='Aspect'>
                                <Extrinsic name='Name'>Color</Extrinsic>
                                <Extrinsic name='Description'>Red</Extrinsic>
                            </Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that VariantID and ProductImage are correctly processed in seller punchout
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithVariantIDAndImage(): void
    {
        $xmlString = $this->getXmlWithVariantIDAndImage();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);

        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertArrayHasKey('variantId', $capturedAdditionalData);
        $this->assertIsArray($capturedAdditionalData['variantId']);
        $this->assertCount(1, $capturedAdditionalData['variantId']);
        $this->assertEquals(12345.0, $capturedAdditionalData['variantId'][0]);

        $this->assertEquals('https://example.com/custom-product-image.jpg', $capturedAdditionalData['image']);
    }

    /**
     * Return XML with VariantID and ProductImage elements
     */
    private function getXmlWithVariantIDAndImage(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>750.00</Money></Total>
                        <TotalQuantity>3</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-VARIANT</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-VARIANT</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description><ShortName>Variant Product</ShortName></Description>
                            <URL>https://example.com/default-image.jpg</URL>
                            <Extrinsic name='VariantID'>12345</Extrinsic>
                            <Extrinsic name='ProductImage'>https://example.com/custom-product-image.jpg</Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that updateThirdPartyItemSellerPunchout initializes the quote when it's null
     */
    public function testUpdateThirdPartyItemSellerPunchoutInitializesQuoteWhenNull(): void
    {
        $xmlString = $this->getXmlWithBasicStructure();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $sessionQuote = $this->createMock(Quote::class);

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $this->checkoutSession->expects($this->once())
            ->method('getQuote')
            ->willReturn($sessionQuote);

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);

        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getQuote',
                'setQuote',
                'getProduct',
                'setCustomPrice',
                'setQty',
                'addOption',
                'saveItemOptions',
                'getOptionByCode'
            ])
            ->addMethods([
                'getProductId',
                'setAdditionalData',
                'setOriginalCustomPrice',
                'setBaseRowTotal',
                'setRowTotal',
                'setIsSuperMode',
                'getAdditionalData'
            ])
            ->getMock();

        $this->quoteItem->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->quoteItem->expects($this->once())
            ->method('setQuote')
            ->with($sessionQuote)
            ->willReturnSelf();

        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);
        $this->quoteItem->method('getOptionByCode')->willReturn(null);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->willReturnSelf();

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);
    }

    /**
     * Return XML with basic structure for the test
     */
    private function getXmlWithBasicStructure(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>100.00</Money></Total>
                        <TotalQuantity>1</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemId>
                            <SupplierPartID>SUPPLIER-TEST</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-TEST</SupplierPartAuxiliaryID>
                        </ItemId>
                        <ItemDetail>
                            <Description><ShortName>Test Product</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that updateThirdPartyItemSellerPunchout properly preserves existing additional data
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithExistingAdditionalData(): void
    {
        $xmlString = $this->getXmlWithBasicStructure();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);

        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $existingAdditionalData = json_encode([
            'existing_key' => 'existing_value',
            'custom_option' => 'should be preserved',
            'cart_quantity_tooltip' => 'existing tooltip'
        ]);

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);

        $this->quoteItem->method('getAdditionalData')
            ->willReturn($existingAdditionalData);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertArrayHasKey('existing_key', $capturedAdditionalData);
        $this->assertEquals('existing_value', $capturedAdditionalData['existing_key']);
        $this->assertArrayHasKey('custom_option', $capturedAdditionalData);
        $this->assertEquals('should be preserved', $capturedAdditionalData['custom_option']);
        $this->assertArrayHasKey('cart_quantity_tooltip', $capturedAdditionalData);
        $this->assertEquals('existing tooltip', $capturedAdditionalData['cart_quantity_tooltip']);

        $this->assertArrayHasKey('supplierPartID', $capturedAdditionalData);
        $this->assertEquals('SUPPLIER-TEST', $capturedAdditionalData['supplierPartID']);
    }

    /**
     * Test that freight shipping packaging data is correctly processed in seller punchout
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithFreightShippingPackagingData(): void
    {
        $xmlString = $this->getXmlWithPackagingData();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);
        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(true);

        $this->marketPlaceRatesHelper->method('isFreightShippingEnabled')
            ->willReturn(true);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $shippingRateOption = ['freight_enabled' => true];
        $this->shopMock->method('getShippingRateOption')
            ->willReturn($shippingRateOption);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertArrayHasKey('packaging_data', $capturedAdditionalData);

        $packagingData = $capturedAdditionalData['packaging_data'];
        $this->assertNotNull($packagingData);

        $this->assertEquals('Box', $packagingData['type']);
        $this->assertEquals(10, $packagingData['length']);
        $this->assertEquals(8, $packagingData['width']);
        $this->assertEquals(6, $packagingData['height']);
        $this->assertEquals(5, $packagingData['weight']);
    }

    /**
     * Return XML with packaging data for freight shipping
     */
    private function getXmlWithPackagingData(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>750.00</Money></Total>
                        <TotalQuantity>3</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemID>
                            <SupplierPartID>SUPPLIER-FREIGHT</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-FREIGHT</SupplierPartAuxiliaryID>
                            <PackagingData>{\"type\":\"Box\",\"length\":10,\"width\":8,\"height\":6,\"weight\":5}</PackagingData>
                        </ItemID>
                        <ItemDetail>
                            <Description><ShortName>Freight Shipping Product</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                            <Extrinsic name='UnitPrice'>250.00</Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that super attributes are correctly extracted from info_buyRequest in seller punchout
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithSuperAttributesFromBuyRequest(): void
    {
        $xmlString = $this->getXmlWithBasicStructure();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');
        $formattedSuperAttributes = [
            [
                'name' => 'Color',
                'choice' => ['name' => 'Red']
            ],
            [
                'name' => 'Size',
                'choice' => ['name' => 'Large']
            ]
        ];

        $this->update = $this->getMockBuilder(Update::class)
            ->setConstructorArgs([
                $this->request,
                $this->createMock(SerializerInterface::class),
                $this->xmlFactory,
                $this->file,
                $this->filesystem,
                $this->storeManager,
                $this->shopManagement,
                $this->checkoutSession,
                $this->marketplaceConfigProvider,
                $this->helper,
                $this->externalProd,
                $this->nonCustomizableProductModel,
                $this->marketPlaceRatesHelper,
                $this->eavConfig,
                $this->category,
                $this->marketplaceCheckoutHelper,
                $this->logger
            ])
            ->onlyMethods(['getFormattedSuperAttributes', 'addBrandToFeatures'])
            ->getMock();

        $this->update->expects($this->once())
            ->method('getFormattedSuperAttributes')
            ->with(['93' => '52', '142' => '167'])
            ->willReturn($formattedSuperAttributes);

        $this->update->expects($this->any())
            ->method('addBrandToFeatures')
            ->willReturnCallback(function ($features, $product) {
                return $features;
            });

        $this->helper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);

        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $buyRequestJson = json_encode([
            'qty' => 1,
            'super_attribute' => [
                '93' => '52',  // Color => Red
                '142' => '167' // Size => Large
            ]
        ]);

        $buyRequestOption = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();
        $buyRequestOption->method('getValue')
            ->willReturn($buyRequestJson);

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $this->quoteItem->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestOption);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct, []);

        $this->assertArrayHasKey('features', $capturedAdditionalData);
        $this->assertEquals($formattedSuperAttributes, $capturedAdditionalData['features']);

        $colorFeature = null;
        $sizeFeature = null;
        foreach ($capturedAdditionalData['features'] as $feature) {
            if ($feature['name'] === 'Color') {
                $colorFeature = $feature;
            } elseif ($feature['name'] === 'Size') {
                $sizeFeature = $feature;
            }
        }

        $this->assertNotNull($colorFeature, 'Color feature not found in features array');
        $this->assertEquals('Red', $colorFeature['choice']['name']);

        $this->assertNotNull($sizeFeature, 'Size feature not found in features array');
        $this->assertEquals('Large', $sizeFeature['choice']['name']);
    }

    /**
     * Test that freight shipping handles missing packaging data correctly
     */
    public function testUpdateThirdPartyItemSellerPunchoutWithMissingPackagingData(): void
    {
        $xmlString = $this->getXmlWithoutPackagingData();
        $xmlElement = new Element($xmlString);

        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $xmlString])
            ->willReturn($xmlElement);

        $attributeFrontendMock = $this
            ->createMock(\Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend::class);

        $attributeFrontendMock->method('getValue')->willReturn('kg');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attributeMock->method('getFrontend')->willReturn($attributeFrontendMock);

        $productResourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productResourceMock->method('getAttribute')
            ->with('weight_unit')
            ->willReturn($attributeMock);

        $completeMockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $completeMockProduct->method('getResource')
            ->willReturn($productResourceMock);
        $completeMockProduct->method('getAttributeText')
            ->with('brand')
            ->willReturn('TestBrand');
        $completeMockProduct->method('getId')
            ->willReturn(123);
        $completeMockProduct->method('getTypeId')
            ->willReturn('simple');

        $this->update = new Update(
            $this->request,
            $this->createMock(SerializerInterface::class),
            $this->xmlFactory,
            $this->file,
            $this->filesystem,
            $this->storeManager,
            $this->shopManagement,
            $this->checkoutSession,
            $this->marketplaceConfigProvider,
            $this->helper,
            $this->externalProd,
            $this->nonCustomizableProductModel,
            $this->marketPlaceRatesHelper,
            $this->eavConfig,
            $this->category,
            $this->marketplaceCheckoutHelper,
            $this->logger
        );

        $this->helper->method('isCartIntegrationPrintfulEnabled')
            ->willReturn(true);
        $this->nonCustomizableProductModel->method('isMktCbbEnabled')
            ->willReturn(true);

        $this->marketPlaceRatesHelper->method('isFreightShippingEnabled')
            ->willReturn(true);

        $this->request->method('getParam')
            ->willReturnCallback(function ($param, $default = null) use ($xmlString) {
                if ($param === 'cxml-urlencoded') {
                    return $xmlString;
                }
                return $default;
            });

        $this->quoteItem->method('getQuote')->willReturn($this->quote);
        $this->quoteItem->method('getProduct')->willReturn($completeMockProduct);
        $this->quoteItem->method('getProductId')->willReturn(123);
        $this->quoteItem->method('getAdditionalData')->willReturn(null);

        $shippingRateOption = ['freight_enabled' => true];
        $this->shopMock->method('getShippingRateOption')
            ->willReturn($shippingRateOption);

        $this->shopManagement->method('getShopByProduct')->willReturn($this->shopMock);
        $this->shopMock->method('getId')->willReturn('1');
        $this->shopMock->method('getAdditionalInfo')->willReturn(['additional_field_values' => []]);

        $this->externalProd->method('getProduct')
            ->willReturn($completeMockProduct);

        $capturedAdditionalData = null;
        $this->quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use (&$capturedAdditionalData) {
                $capturedAdditionalData = json_decode($json, true);
                return true;
            }));

        $this->update->updateThirdPartyItemSellerPunchout($this->quoteItem, $completeMockProduct);

        $this->assertArrayHasKey('packaging_data', $capturedAdditionalData);
        $this->assertNull($capturedAdditionalData['packaging_data']);
    }

    /**
     * Return XML without packaging data for freight shipping
     */
    private function getXmlWithoutPackagingData(): string
    {
        return "<?xml version='1.0' encoding='UTF-8'?>
        <cXML>
            <Message>
                <PunchOutOrderMessage>
                    <PunchOutOrderMessageHeader>
                        <Total><Money>750.00</Money></Total>
                        <TotalQuantity>3</TotalQuantity>
                    </PunchOutOrderMessageHeader>
                    <ItemIn>
                        <ItemID>
                            <SupplierPartID>SUPPLIER-FREIGHT</SupplierPartID>
                            <SupplierPartAuxiliaryID>AUX-FREIGHT</SupplierPartAuxiliaryID>
                            <!-- No PackagingData element here -->
                        </ItemID>
                        <ItemDetail>
                            <Description><ShortName>Freight Shipping Product</ShortName></Description>
                            <URL>https://example.com/image.jpg</URL>
                            <Extrinsic name='UnitPrice'>250.00</Extrinsic>
                        </ItemDetail>
                    </ItemIn>
                </PunchOutOrderMessage>
            </Message>
        </cXML>";
    }

    /**
     * Test that setMapSkuToProduct returns the product's map_sku when available directly
     *
     * @covers \Fedex\Cart\Model\Quote\ThirdPartyProduct\Update::setMapSkuToProduct
     */
    public function testSetMapSkuToProductReturnsProductMapSku(): void
    {
        $expectedMapSku = 'DIRECT-PRODUCT-MAP-SKU';

        // Create a mock product
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock->expects($this->once())
            ->method('getData')
            ->with('map_sku')
            ->willReturn($expectedMapSku);

        $productMock->expects($this->never())
            ->method('getCategoryIds');

        $this->category->expects($this->never())
            ->method('create');

        $reflectionMethod = new \ReflectionMethod(Update::class, 'setMapSkuToProduct');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->update, [null, $productMock]);

        $this->assertEquals($expectedMapSku, $result, 'Method should return the map_sku directly from the product');
    }

    /**
     * Test the setMapSkuToProduct method returns the correct map SKU from multiple categories
     *
     * @covers \Fedex\Cart\Model\Quote\ThirdPartyProduct\Update::setMapSkuToProduct
     */
    public function testSetMapSkuToProductWithMultipleCategories(): void
    {
        $expectedMapSku = 'TEST-MAP-SKU-123';

        $categoryCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->category->expects($this->once())
            ->method('create')
            ->willReturn($categoryCollectionMock);

        $categoryCollectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['entity_id', 'map_sku'])
            ->willReturnSelf();

        $categoryCollectionMock->expects($this->exactly(2))
            ->method('addAttributeToFilter')
            ->withConsecutive(
                ['entity_id', ['in' => [42, 43]]],
                [[
                    ['attribute' => 'map_sku', 'neq' => ''],
                    ['attribute' => 'map_sku', 'notnull' => true]
                ]]
            )
            ->willReturnSelf();

        $categoryCollectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(1)
            ->willReturnSelf();

        $categoryCollectionMock->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(2);

        $categoryMock1 = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryMock1->expects($this->once())
            ->method('getData')
            ->with('map_sku')
            ->willReturn($expectedMapSku);

        $categoryMock2 = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryMock2->expects($this->never())
            ->method('getData');

        $categoryCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$categoryMock1, $categoryMock2]));

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionMethod = new \ReflectionMethod(Update::class, 'setMapSkuToProduct');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->update, [[42, 43], $productMock]);

        $this->assertEquals($expectedMapSku, $result, 'Method should return the map SKU from the first category found');
    }

    /**
     * Test the setMapSkuToProduct method when map SKU is empty
     *
     * @covers \Fedex\Cart\Model\Quote\ThirdPartyProduct\Update::setMapSkuToProduct
     */
    public function testSetMapSkuToProductHandlesEmptyMapSku(): void
    {
        $categoryCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->category->expects($this->once())
            ->method('create')
            ->willReturn($categoryCollectionMock);

        $categoryCollectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['entity_id', 'map_sku'])
            ->willReturnSelf();

        $categoryCollectionMock->expects($this->exactly(2))
            ->method('addAttributeToFilter')
            ->withConsecutive(
                ['entity_id', ['in' => [42]]],
                [[
                    ['attribute' => 'map_sku', 'neq' => ''],
                    ['attribute' => 'map_sku', 'notnull' => true]
                ]]
            )
            ->willReturnSelf();

        $categoryCollectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(1)
            ->willReturnSelf();

        $categoryCollectionMock->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(0);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock->expects($this->never())
            ->method('getSku');

        $reflectionMethod = new \ReflectionMethod(Update::class, 'setMapSkuToProduct');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->update, [[42], $productMock]);

        $this->assertNull($result, 'Method should return null when no categories are found');
    }
}
