<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit\ViewModel;

use ArrayIterator;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Catalog\Helper\Image;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Mirakl\MMP\Common\Domain\Order\State\OrderStatus;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session;
use Fedex\Cart\Controller\Dunc\Index;
use Mirakl\Api\Helper\Order as MiraklHelper;
use Fedex\Cart\ViewModel\CheckoutConfig;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper as OrderDetailsDataMapper;
use Fedex\MarketplaceRates\Helper\Data as RateHelper;
use Fedex\Shipment\Model\ShipmentFactory;
use Fedex\Shipment\Model\Shipment;
use Magento\Framework\App\Response\RedirectInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper as OrderApprovalAdminConfigHelper;
use Fedex\Cart\Helper\Data as CartHelper;
use Fedex\CustomizedMegamenu\Helper\Data as CustomizedMegamenuDataHelper;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Fedex\InstoreConfigurations\Api\ConfigInterface;

class OrderHistoryEnhacementTest extends TestCase
{
    protected $orderRepositoryMock;
    protected $quoteRepositoryMock;
    protected $redirectInterfaceMock;
    /**
     * @var (\Magento\Catalog\Helper\Image & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $imageHelperMock;
    protected $orderHistoryhelperMock;
    protected $sdeHelperMock;
    protected $orderMock;
    protected $shipmentCollectionMock;
    protected $selfRegHelperMock;
    protected $customerSessionMock;
    protected $toggleConfigMock;
    protected $scopeConfigInterfaceMock;
    protected $duncMock;
    protected $miraklHelper;
    protected $helperRate;
    protected $cartCheckoutConfigMock;
    protected $orderItem;
    /**
     * @var (\Magento\Sales\Model\Order\Item & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderDetailsDataMapper;
    protected $orderItemCollectionFactory;
    protected $orderItemCollectionMock;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonSerializer;
    protected $miraklOrderHelperMock;
    /**
     * @var (\Fedex\Shipment\Model\ShipmentFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shipmentFactoryMock;
    /**
     * @var (\Fedex\Shipment\Model\Shipment & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shipmentMock;
    protected $catalogDocRef;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /** @var OrderHistoryEnhacement */
    protected $orderHistoryEnhancementMock;
    const SHIPPING_TYPE_PICKUP = 'fedexshipping_PICKUP';

    /**
     * @var OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper
     */
    protected $orderApprovalAdminConfigHelper;

    protected $cartHelper;
    protected $customizedMegamenuDataHelper;
    protected $dataObjectFactoryMock;
    protected $handleMktCheckoutMock;
    protected $marketplaceConfigMock;
    protected $priceHelperMock;
    protected $shopManagementMock;
    protected $helperDataMock;
    protected $urlBuilderMock;
    protected $orderDetailsDataMapperMock;
    protected $instoreConfigInterfaceMock;
    protected $instoreConfigMock;


    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRepositoryMock = $this->getMockBuilder(QuoteRepositoryInterface::class)
            ->setMethods(['get', 'getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartHelper = $this->getMockBuilder(CartHelper::class)
            ->setMethods(['isRemoveBase64ImageToggleEnabled'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customizedMegamenuDataHelper = $this->getMockBuilder(CustomizedMegamenuDataHelper::class)
            ->setMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->redirectInterfaceMock = $this->getMockBuilder(RedirectInterface::class)
            ->setMethods(['getRefererUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->imageHelperMock = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderHistoryhelperMock = $this->getMockBuilder(OrderHistoryHelper::class)
            ->setMethods([
                'isEnhancementEnabled',
                'isEnhancementEnabeled',
                'isModuleEnabled',
                'isModuleEnabledForPrint',
                'isPrintReceiptRetail',
                'getShipmentOrderCompletionDate',
                'getContactAddress',
                'getContactAddressForRetail',
                'getAlternateShippingAddress',
                'isReOrderable',
                'getIsSdeStore',
                'isRetailOrderHistoryEnabled',
                'isRetailOrderHistoryReorderEnabled',
                'isCommercialReorderEnabled',
                'productAttributeSetName',
                'getProductCustomAttributeValue',
                'loadProductById',
                'getCustomerSession',
                'getPoNumber',
                'getAlternateAddress',
                'checkOrderHasLegacyDocument',
                'getOrderShippingAddress'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore', 'getSdeMaskSecureImagePath', 'isMixedOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(
                [
                    'getAllItems',
                    'getId',
                    'getMiraklShippingFee',
                    'getShippingAmount',
                    'formatPrice',
                    'getShippingMethod',
                    'getData',
                    'getStatus',
                    'getState',
                    'getShipmentsCollection',
                    'getEstimatedPickupTime',
                    'getShippingDescription',
                    'getQuoteId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentCollectionMock = $this->getMockBuilder(ShipmentCollection::class)
            ->setMethods(['getFirstItem', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
            ->setMethods(['isSelfRegCompany'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getDuncResponse', 'setDuncResponse', 'getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->duncMock = $this->getMockBuilder(Index::class)
            ->setMethods(['callDuncApi'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->miraklHelper = $this->getMockBuilder(MiraklHelper::class)
            ->setMethods(['getOrdersByCommercialId', 'getOrders'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperRate = $this->getMockBuilder(RateHelper::class)
            ->setMethods(['getMktShippingTotalAmount'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartCheckoutConfigMock = $this->getMockBuilder(CheckoutConfig::class)
            ->setMethods(['isTermsAndConditionsEnabled', 'isReorderEnabled','getDocumentImagePreviewUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItem = $this->getMockBuilder(OrderDetailsDataMapper::class)
            ->setMethods(['setStatus','getStatus'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderDetailsDataMapper = $this->getMockBuilder(OrderItem::class)
            ->setMethods(['isOrderDelayed'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemCollectionFactory = $this->getMockBuilder(OrderItemCollectionFactory::class)
            ->setMethods(
                [
                    'addFieldToFilter',
                    'getIterator',
                    'getSelect',
                    'create',
                    'getTable'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderItemCollectionMock = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\Item\Collection::class)
            ->setMethods(['addFieldToFilter', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonSerializer = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->miraklOrderHelperMock = $this->getMockBuilder(\Mirakl\Connector\Helper\Order::class)
            ->onlyMethods(['isMiraklOrder', 'isFullMiraklOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentFactoryMock = $this->getMockBuilder(ShipmentFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentMock = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogDocRef = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['curlCallForPreviewApi'])
            ->getMock();

        $this->orderApprovalAdminConfigHelper = $this->getMockBuilder(OrderApprovalAdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isOrderApprovalB2bEnabled', 'checkIsReviewActionSet'])
            ->getMock();

        $this->handleMktCheckoutMock = $this->getMockBuilder(HandleMktCheckout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceConfigMock = $this->getMockBuilder(MarketplaceConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceHelperMock = $this->getMockBuilder(PriceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shopManagementMock = $this->getMockBuilder(ShopManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperDataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderDetailsDataMapperMock = $this->getMockBuilder(OrderDetailsDataMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectFactoryMock = $this->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instoreConfigMock = $this->getMockBuilder(\Fedex\InstoreConfigurations\Api\ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instoreConfigInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->orderHistoryEnhancementMock = $this->objectManager->getObject(
            OrderHistoryEnhacement::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                'imageHelper' => $this->imageHelperMock,
                'orderHistoryhelper' => $this->orderHistoryhelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'selfRegHelper' => $this->selfRegHelperMock,
                'customerSession' => $this->customerSessionMock,
                'duncCall' => $this->duncMock,
                'toggleConfig' => $this->toggleConfigMock,
                'miraklHelper' => $this->miraklHelper,
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock,
                'checkoutConfig' => $this->cartCheckoutConfigMock,
                'itemCollectionFactory' => $this->orderItemCollectionFactory,
                'jsonSerializer' => $this->jsonSerializer,
                'orderDetailsDataMapper' => $this->orderDetailsDataMapperMock,
                'miraklOrderHelper' => $this->miraklOrderHelperMock,
                'dataObjectFactory' => $this->dataObjectFactoryMock,
                'helperRate' => $this->helperRate,
                'shipmentStatusFactory' => $this->shipmentFactoryMock,
                'redirectInterface' => $this->redirectInterfaceMock,
                'catalogDocumentRefranceApi' => $this->catalogDocRef,
                'orderApprovalAdminConfigHelper' => $this->orderApprovalAdminConfigHelper,
                'shopManagement' => $this->shopManagementMock,
                'helper' => $this->helperDataMock,
                '_urlBuilder' => $this->urlBuilderMock,
                'handleMktCheckout' => $this->handleMktCheckoutMock,
                'config' => $this->marketplaceConfigMock,
                'priceHelper' => $this->priceHelperMock,
                'cartHelper' => $this->cartHelper,
                'quoteRepository' => $this->quoteRepositoryMock,
                'configInterface' => $this->instoreConfigInterfaceMock,
                'instoreConfig' => $this->instoreConfigMock,
            ]
        );
    }

    /**
     * Assert testIsTermsAndConditionsEnabled
     *
     * @return boolean
     */
    public function testIsTermsAndConditionsEnabled()
    {
        $this->cartCheckoutConfigMock->expects($this->any())->method('isTermsAndConditionsEnabled')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isTermsAndConditionsEnabled());
    }

    /**
     * Assert testIsReorderEnabled
     *
     * @return boolean
     */
    public function testIsReorderEnabled()
    {
        $this->cartCheckoutConfigMock->expects($this->any())->method('isReorderEnabled')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isReorderEnabled());
    }

    /**
     * Assert getItemsByOrderId
     *
     * @return [Object]
     */
    public function testGetItemsByOrderId()
    {
        $orderId = 45;
        $this->orderRepositoryMock->expects($this->any())->method('get')->willReturn($this->orderMock);
        $this->orderMock->expects($this->any())->method('getAllItems')->willReturn([]);
        $this->orderHistoryEnhancementMock->getItemsByOrderId($orderId);
    }

    /**
     * Assert isSdeStore
     *
     * @return bool
     */
    public function testIsSdeStore()
    {
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->orderHistoryEnhancementMock->isSdeStore();
    }

    /**
     * Assert testGetCurrentCustomerIdFromSession
     *
     * @return int
     */
    public function testGetCurrentCustomerIdFromSession()
    {
        $this->customerSessionMock->expects($this->any())->method('getCustomerId')->willReturn(12);
        $this->assertEquals(12, $this->orderHistoryEnhancementMock->getCurrentCustomerIdFromSession());
    }

    /**
     * Assert testIsLastPageUrlSharedOrderListing
     *
     * @return bool
     */
    public function testIsLastPageUrlSharedOrderListing()
    {
        $lastPageUrl = 'ondemand/srs/shared/order/history';
        $this->redirectInterfaceMock->expects($this->any())->method('getRefererUrl')->willReturn($lastPageUrl);
        $this->assertTrue($this->orderHistoryEnhancementMock->isLastPageUrlSharedOrderListing());
    }

    /**
     * Assert testIsLastPageUrlSharedOrderListingWithFalse
     *
     * @return bool
     */
    public function testIsLastPageUrlSharedOrderListingWithFalse()
    {
        $lastPageUrl = 'ondemand/srs/shared/';
        $this->redirectInterfaceMock->expects($this->any())->method('getRefererUrl')->willReturn($lastPageUrl);
        $this->assertFalse($this->orderHistoryEnhancementMock->isLastPageUrlSharedOrderListing());
    }

    /**
     * Assert testIsModuleEnabled
     *
     * @return bool
     */
    public function testIsModuleEnabled()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryEnhancementMock->isModuleEnabled();
    }

    /**
     * Assert testIsSelfRegCompany
     *
     * @return bool
     */
    public function testIsSelfRegCompany()
    {
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isSelfRegCompany());
    }

    /**
     * Assert isEnhancementEnabeled
     *
     * @return boolean
     */
    public function testIsEnhancementEnabeled()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->orderHistoryEnhancementMock->isEnhancementEnabeled();
    }

    /**
     * Assert isModuleEnabledForPrint
     *
     * @return boolean
     */
    public function testIsModuleEnabledForPrint()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isModuleEnabledForPrint')->willReturn(true);
        $this->orderHistoryEnhancementMock->isModuleEnabledForPrint();
    }

    /**
     * Assert isPrintReceiptRetail
     *
     * @return boolean
     */
    public function testIsPrintReceiptRetail()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $this->orderHistoryEnhancementMock->isPrintReceiptRetail();
    }

    /**
     * Assert Get sde masked image path
     *
     * @return bool|string
     */
    public function testGetSdeMaskSecureImagePath()
    {
        $maskedImagePath = 'sde_mask.png';
        $this->sdeHelperMock->expects($this->any())->method('getSdeMaskSecureImagePath')->willReturn($maskedImagePath);
        $this->assertEquals($maskedImagePath, $this->orderHistoryEnhancementMock->getSdeMaskSecureImagePath());
    }

    /**
     * Assert shipmentOrderCompletionDate
     *
     */
    public function testShipmentOrderCompletionDate()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('getShipmentOrderCompletionDate')
        ->willReturn('22');
        $this->orderHistoryEnhancementMock->shipmentOrderCompletionDate(11);
    }

    /**
     * Assert getContactAddress
     *
     */
    public function testGetContactAddress()
    {
        $this->orderMock->expects($this->any())->method('getQuoteId')->willReturn(123);
        $this->orderHistoryhelperMock->expects($this->any())->method('getContactAddress')->with(123)->willReturnSelf();
        $this->orderHistoryEnhancementMock->getContactAddress($this->orderMock);
    }

    /**
     * Assert getContactAddressFor Retail
     *
     */
    public function testGetContactAddressForRetail()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('getContactAddressForRetail')
        ->with($this->orderMock)->willReturnSelf();
        $this->orderHistoryEnhancementMock->getContactAddressForRetail($this->orderMock);
    }

    /**
     * Assert getPoNumber
     *
     */
    public function testGetPoNumber()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('getPoNumber')->with(12)->willReturnSelf();
        $this->orderHistoryEnhancementMock->getPoNumber(12);
    }

    /**
     * Assert getAlternateAddress
     *
     */
    public function testGetAlternateAddress()
    {
        $this->orderMock->expects($this->any())->method('getQuoteId')->willReturn(12);
        $this->orderHistoryhelperMock->expects($this->any())->method('getAlternateAddress')->with(12)->willReturnSelf();
        $this->orderHistoryEnhancementMock->getAlternateAddress($this->orderMock);
    }

    /**
     * Assert getContactAddressWithAlternateConditionCheck
     *
     */
    public function testGetContactAddressWithAlternateConditionCheck()
    {
        $this->orderMock->expects($this->any())->method('getQuoteId')->willReturn(12);
        $this->quoteRepositoryMock->expects($this->any())->method('get')->willReturnSelf();
        $this->quoteRepositoryMock->expects($this->any())->method('getData')->willReturn(['is_alternate' => true]);
        $this->orderHistoryhelperMock->expects($this->any())->method('getOrderShippingAddress')->willReturnSelf();
        $this->orderHistoryEnhancementMock->getContactAddressWithAlternateConditionCheck($this->orderMock);
    }

    /**
     * Assert getAlternateShippingAddress
     *
     */
    public function testGetAlternateShippingAddress()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('getAlternateShippingAddress')
            ->with(123)->willReturnSelf();
        $this->orderHistoryEnhancementMock->getAlternateShippingAddress(123);
    }

    /**
     * Assert isReOrderable
     *
     */
    public function testIsReOrderable()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isReOrderable')->with(22)->willReturnSelf();
        $this->orderHistoryEnhancementMock->isReOrderable(22);
    }

    /**
     * Assert isRetailOrderHistoryEnabled
     *
     * @return boolean
     */
    public function testIsRetailOrderHistoryEnabled()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isRetailOrderHistoryEnabled());
    }

    /**
     * Assert isRetailOrderHistoryReorderEnabled
     *
     * @return boolean
     */
    public function testIsRetailOrderHistoryReorderEnabled()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isRetailOrderHistoryReorderEnabled')
        ->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isRetailOrderHistoryReorderEnabled());
    }

    /**
     * Assert isCommercialOrderHistoryReorderEnabled
     *
     * @return boolean
     */
    public function testIsCommercialOrderHistoryReorderEnabled()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('isCommercialReorderEnabled')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isCommercialOrderHistoryReorderEnabled());
    }

    /**
     * Assert getProductAttributeSetName
     *
     * @return boolean
     */
    public function testGetProductAttributeSetName()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('productAttributeSetName')
            ->with(123)
            ->willReturn('FXOPrint_Products');
        $this->assertEquals('FXOPrint_Products', $this->orderHistoryEnhancementMock->getProductAttributeSetName(123));
    }

    /**
     * Assert getProductCustomAttributeValue
     *
     * @return boolean
     */
    public function testGetProductCustomAttributeValue()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('getProductCustomAttributeValue')
            ->with(12, 'customize')
            ->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->getProductCustomAttributeValue(12, 'customize'));
    }

    /**
     * Assert loadProductObj
     *
     * @return boolean
     */
    public function testLoadProductObj()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('loadProductById')
            ->with(12)
            ->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->loadProductObj(12));
    }

    /**
     * Assert testGetCustomerSession
     *
     * @return boolean
     */
    public function testGetCustomerSession()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('getCustomerSession')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->getCustomerSession());
    }

    /**
     * test serializeProductData
     *
     * @return void
     */
    public function testSerializeProductData()
    {
        $externalProductData = '{"external_prod":[{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1661222574939,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1534920174638","name":"Envelope","choice":{"id":"1634129308274","name":"None","properties":[]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988666879","name":"Gloss Text","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"CT"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"Gloss Text"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"TEXT_GLOSS"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"13169008873132051940009207686081156257085","contentReference":"13169046574124984641609941565731181136468","contentType":"IMAGE","fileName":"aviso.jpg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"2821"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 Gloss Text (CT), Full Page."},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"},{"id":"1614715469176","name":"IMPOSE_TEMPLATE_ID","value":"0"}],"preview_url":"13169046574124984641609941565731181136468","fxo_product":""}]}';

        $serializedProductData = '{"serializedProductData":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1661222574939,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1534920174638","name":"Envelope","choice":{"id":"1634129308274","name":"None","properties":[]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988666879","name":"Gloss Text","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"CT"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"Gloss Text"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"TEXT_GLOSS"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"13169008873132051940009207686081156257085","contentReference":"13169046574124984641609941565731181136468","contentType":"IMAGE","fileName":"aviso.jpg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"2821"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 Gloss Text (CT), Full Page."},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"},{"id":"1614715469176","name":"IMPOSE_TEMPLATE_ID","value":"0"}]},"productPresetId":true}';

        $fxoP = '{"fxoMenuId":"1614105200640-4","fxoProductInstance":{"id":"1661222574939","name":"Flyers","productConfig":{"product":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1661222574939,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1534920174638","name":"Envelope","choice":{"id":"1634129308274","name":"None","properties":[]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988666879","name":"Gloss Text","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"CT"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"Gloss Text"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"TEXT_GLOSS"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"13169008873132051940009207686081156257085","contentReference":"13169008874182887338515073287980402046350","contentType":"IMAGE","fileName":"aviso.jpg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"2821"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 Gloss Text (CT), Full Page."},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"},{"id":"1614715469176","name":"IMPOSE_TEMPLATE_ID","value":"0"}]},"productPresetId":"1602518818916","fileCreated":"2022-08-23T02:43:21.013Z"},"productRateTotal":{"unitPrice":null,"currency":"USD","quantity":50,"price":"$50.82","priceAfterDiscount":"$50.82","unitOfMeasure":"EACH","totalDiscount":"$0.00","productLineDetails":[{"detailCode":"40005","description":"Full Pg Clr Flyr 50","detailCategory":"PRINTING","unitQuantity":1,"detailPrice":"$50.82","detailDiscountPrice":"$0.00","detailUnitPrice":"$50.8200","detailDiscountedUnitPrice":"$0.00"}]},"isUpdateButtonVisible":false,"quantityChoices":["50","100","250","500","1000"],"isEditable":true,"isEdited":false,"fileManagementState":{"availableFileItems":[{"file":[],"fileItem":{"fileId":"13169008873132051940009207686081156257085","fileName":"aviso.jpg","fileExtension":"jpg","fileSize":145267,"createdTimestamp":"2022-08-23T02:43:28.366Z"},"uploadStatus":"Success","errorMsg":"","uploadProgressPercentage":100,"uploadProgressBytesLoaded":145455,"selected":false,"httpRsp":{"successful":true,"output":{"document":{"documentId":"13169008873132051940009207686081156257085","documentName":"aviso.jpg","documentSize":145265,"printReady":false}}}}],"projects":[{"fileItems":[{"uploadStatus":"Success","errorMsg":"","selected":false,"originalFileItem":{"fileId":"13169008873132051940009207686081156257085","fileName":"aviso.jpg","fileExtension":"jpg","fileSize":145267,"createdTimestamp":"2022-08-23T02:43:28.366Z"},"convertStatus":"Success","convertedFileItem":{"fileId":"13169008874182887338515073287980402046350","fileName":"aviso.jpg","fileExtension":"pdf","fileSize":147402,"createdTimestamp":"2022-08-23T02:43:29.754Z","numPages":1},"orientation":"LANDSCAPE","conversionResult":{"parentDocumentId":"13169008873132051940009207686081156257085","originalDocumentName":"aviso.jpg","printReadyFlag":true,"previewURI":"https:\/\/dunc6.dmz.fedex.com\/document\/fedexoffice\/v1\/documents\/13169008874182887338515073287980402046350\/preview","documentSize":147402,"documentType":"IMAGE","lowResImage":true,"documentId":"13169008874182887338515073287980402046350","metrics":{"pageCount":1,"pageGroups":[{"startPageNum":1,"endPageNum":1,"pageWidthInches":11,"pageHeightInches":8.5}]}},"contentAssociation":{"parentContentReference":"13169008873132051940009207686081156257085","contentReference":"13169008874182887338515073287980402046350","contentType":"IMAGE","fileSizeBytes":"147402","fileName":"aviso.jpg","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}],"contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":""}}],"projectName":"Flyers","productId":"1463680545590","productPresetId":"1602518818916","productVersion":null,"controlId":"4","maxFiles":2,"productType":"Flyers","availableSizes":"8.5\"x11\"","convertStatus":"Success","showInList":true,"firstInList":false,"accordionOpen":true,"needsToBeConverted":false,"selected":false,"mayContainUserSelections":false,"hasUserChangedProjectNameManually":false,"supportedProductSizes":{"featureId":"1448981549109","featureName":"Size","choices":[{"choiceId":"1448986650332","choiceName":"8.5\"x11\"","properties":[{"name":"MEDIA_HEIGHT","value":"11"},{"name":"MEDIA_WIDTH","value":"8.5"},{"name":"DISPLAY_HEIGHT","value":"11"},{"name":"DISPLAY_WIDTH","value":"8.5"}]}]},"productConfig":{"product":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1661222574939,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1534920174638","name":"Envelope","choice":{"id":"1634129308274","name":"None","properties":[]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988666879","name":"Gloss Text","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"CT"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"Gloss Text"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"TEXT_GLOSS"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"13169008873132051940009207686081156257085","contentReference":"13169008874182887338515073287980402046350","contentType":"IMAGE","fileName":"aviso.jpg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"2821"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 Gloss Text (CT), Full Page."},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"},{"id":"1614715469176","name":"IMPOSE_TEMPLATE_ID","value":"0"}]},"productPresetId":"1602518818916","fileCreated":"2022-08-23T02:43:21.013Z"}}],"catalogManageFilesToggle":true,"displayErrorIds":false}},"productType":"PRINT_PRODUCT","instanceId":"5783865047230648"}';
        $externalProductData = json_decode($externalProductData, true);
        $serializedProductData = json_decode($serializedProductData, true);
        $externalProductData['external_prod'][0]['fxo_product'] = $fxoP;

        $this->assertEquals(
            $serializedProductData,
            $this->orderHistoryEnhancementMock->serializeProductData($externalProductData)
        );
    }

    /**
     * test serializeProductData with new json
     *
     * @return void
     */
    public function testSerializeProductDataWithNewJson()
    {
        $externalProductData = '{ "external_prod": [ { "productionContentAssociations": [], "userProductName": "Flyers", "id": "1463680545590", "version": 1, "name": "Flyer", "qty": 50, "priceable": true, "instanceId": 1661222574939, "proofRequired": false, "isOutSourced": false, "features": [ { "id": "1448981549109", "name": "Paper Size", "choice": { "id": "1448986650332", "name": "8.5x11", "properties": [ { "id": "1449069906033", "name": "MEDIA_HEIGHT", "value": "11" }, { "id": "1449069908929", "name": "MEDIA_WIDTH", "value": "8.5" }, { "id": "1571841122054", "name": "DISPLAY_HEIGHT", "value": "11" }, { "id": "1571841164815", "name": "DISPLAY_WIDTH", "value": "8.5" } ] } }, { "id": "1448981549581", "name": "Print Color", "choice": { "id": "1448988600611", "name": "Full Color", "properties": [ { "id": "1453242778807", "name": "PRINT_COLOR", "value": "COLOR" } ] } }, { "id": "1448981549269", "name": "Sides", "choice": { "id": "1448988124560", "name": "Single-Sided", "properties": [ { "id": "1470166759236", "name": "SIDE_NAME", "value": "Single Sided" }, { "id": "1461774376168", "name": "SIDE", "value": "SINGLE" } ] } }, { "id": "1448984679218", "name": "Orientation", "choice": { "id": "1449000016327", "name": "Horizontal", "properties": [ { "id": "1453260266287", "name": "PAGE_ORIENTATION", "value": "LANDSCAPE" } ] } }, { "id": "1534920174638", "name": "Envelope", "choice": { "id": "1634129308274", "name": "None", "properties": [] } }, { "id": "1448981549741", "name": "Paper Type", "choice": { "id": "1448988666879", "name": "Gloss Text", "properties": [ { "id": "1450324098012", "name": "MEDIA_TYPE", "value": "CT" }, { "id": "1453234015081", "name": "PAPER_COLOR", "value": "#FFFFFF" }, { "id": "1470166630346", "name": "MEDIA_NAME", "value": "Gloss Text" }, { "id": "1471275182312", "name": "MEDIA_CATEGORY", "value": "TEXT_GLOSS" } ] } } ], "pageExceptions": [], "contentAssociations": [ { "parentContentReference": "13169008873132051940009207686081156257085", "contentReference": "13169046574124984641609941565731181136468", "contentType": "IMAGE", "fileName": "aviso.jpg", "contentReqId": "1455709847200", "name": "Front_Side", "desc": null, "purpose": "SINGLE_SHEET_FRONT", "specialInstructions": "", "printReady": true, "pageGroups": [ { "start": 1, "end": 1, "width": 11, "height": 8.5, "orientation": "LANDSCAPE" } ] } ], "properties": [ { "id": "1453242488328", "name": "ZOOM_PERCENTAGE", "value": "50" }, { "id": "1453243262198", "name": "ENCODE_QUALITY", "value": "100" }, { "id": "1453894861756", "name": "LOCK_CONTENT_ORIENTATION", "value": false }, { "id": "1453895478444", "name": "MIN_DPI", "value": "150.0" }, { "id": "1454950109636", "name": "USER_SPECIAL_INSTRUCTIONS", "value": null }, { "id": "1455050109636", "name": "DEFAULT_IMAGE_WIDTH", "value": "8.5" }, { "id": "1455050109631", "name": "DEFAULT_IMAGE_HEIGHT", "value": "11" }, { "id": "1464709502522", "name": "PRODUCT_QTY_SET", "value": "50" }, { "id": "1459784717507", "name": "SKU", "value": "2821" }, { "id": "1470151626854", "name": "SYSTEM_SI", "value": "ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 Gloss Text (CT), Full Page." }, { "id": "1494365340946", "name": "PREVIEW_TYPE", "value": "DYNAMIC" }, { "id": "1470151737965", "name": "TEMPLATE_AVAILABLE", "value": "YES" }, { "id": "1459784776049", "name": "PRICE", "value": null }, { "id": "1490292304798", "name": "MIGRATED_PRODUCT", "value": "true" }, { "id": "1558382273340", "name": "PNI_TEMPLATE", "value": "NO" }, { "id": "1602530744589", "name": "CONTROL_ID", "value": "4" }, { "id": "1614715469176", "name": "IMPOSE_TEMPLATE_ID", "value": "0" } ] } ], "fxoMenuId": "1614105200640-4", "productConfig": { "productPresetId": "1602518818916", "fileCreated": "2022-08-23T02:43:21.013Z" } }';

        $serializedProductData = '{"serializedProductData":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1661222574939,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1534920174638","name":"Envelope","choice":{"id":"1634129308274","name":"None","properties":[]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988666879","name":"Gloss Text","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"CT"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"Gloss Text"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"TEXT_GLOSS"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"13169008873132051940009207686081156257085","contentReference":"13169046574124984641609941565731181136468","contentType":"IMAGE","fileName":"aviso.jpg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"2821"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 Gloss Text (CT), Full Page."},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"},{"id":"1614715469176","name":"IMPOSE_TEMPLATE_ID","value":"0"}]},"productPresetId":"1602518818916"}';

        $externalProductData = json_decode($externalProductData, true);
        $serializedProductData = json_decode($serializedProductData, true);

        $this->assertEquals(
            $serializedProductData,
            $this->orderHistoryEnhancementMock->serializeProductData($externalProductData)
        );
    }

    /**
     * Assert getSortedDiscounts
     *
     * @return bool
     */
    public function testGetSortedDiscounts()
    {
        $data = [
                    ['label'=>"Account Discount",'price'=>1],
                    ['label'=>"Volume Discount",'price'=>2],
                    ['label'=>"Promo Discount",'price'=>3]
                ];
        $expecteddata = [
                    ['label'=>"Promo Discount",'price'=>3],
                    ['label'=>"Volume Discount",'price'=>2],
                    ['label'=>"Account Discount",'price'=>1]
                ];

        $this->assertEquals($expecteddata, $this->orderHistoryEnhancementMock->getSortedDiscounts($data));
    }

    /**
     * Assert getSortedDiscounts
     *
     * @return bool
     */
    public function testGetSortedTotals()
    {
        $data = [
                    'subtotal' => 'test',
                    'discount' => 'test',
                    'shipping' => 'test',
                    'tax' => 'test',
                    'grand_total' => 'test'
                ];
        $expecteddata = [
                    'subtotal' => 'test',
                    'shipping' => 'test',
                    'tax' => 'test',
                    'discount' => 'test',
                    'grand_total' => 'test'
                ];

        $this->assertEquals($expecteddata, $this->orderHistoryEnhancementMock->getSortedTotals($data));
    }

    /**
     * Assert getProductImage
     *
     * @return bool
     */
    public function testgetProductImage()
    {
        $imgRowData = 'imagedataer';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getDuncResponse')
         ->willReturn(['imagedataer'=> "kajdfkjakdsfjakdjfkajdfkadf"]);

        $this->assertNotNull($this->orderHistoryEnhancementMock->getProductImage($imgRowData));
    }

    /**
     * Assert getProductImage
     *
     * @return bool
     */
    public function testgetProductImageDocumentApi()
    {
        $imgRowData = 'imagedataer';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->cartHelper->expects($this->any())->method('isRemoveBase64ImageToggleEnabled')->willReturn(true);

        $this->customizedMegamenuDataHelper->expects($this->any())->method('getStoreId')->willReturn(1);
        $this->cartCheckoutConfigMock->expects($this->any())->method('getDocumentImagePreviewUrl')->willReturn('test');
        $this->assertNotNull($this->orderHistoryEnhancementMock->getProductImage($imgRowData));
    }

    /**
     * Assert getProductImage
     *
     * @return bool
     */
    public function testgetProductImageDocumentApi1()
    {
        $imgRowData = 'imagedataer';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->cartHelper->expects($this->any())->method('isRemoveBase64ImageToggleEnabled')->willReturn(true);

        $this->customizedMegamenuDataHelper->expects($this->any())->method('getStoreId')->willReturn(1);
        $this->cartCheckoutConfigMock->expects($this->any())->method('getDocumentImagePreviewUrl')->willReturn(null);
        $this->assertNotNull($this->orderHistoryEnhancementMock->getProductImage($imgRowData));
    }

    /**
     * Assert getProductImage
     *
     * @return bool
     */
    public function testgetProductImageWithDunc()
    {
        $imgRowData = 'imagedataerdd';
        $imageResponse = [
            'sucessful' => true,
            'output'=>[
                'imageByteStream' =>'adKAJSDKJASKDjAKSJDLKASFlaksdjflkajdflkjakdfjakfdjalkjfklajdf'
            ]
            ];
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getDuncResponse')
         ->willReturn(['imagedataer'=> "kajdfkjakdsfjakdjfkajdfkadf"]);

         $this->duncMock->expects($this->any())->method('callDuncApi')
         ->willReturn($imageResponse);

        $this->assertNotNull($this->orderHistoryEnhancementMock->getProductImage($imgRowData));
    }

    /**
     * Assert getProductImage
     *
     * @return bool
     */
    public function testgetProductImageForCustomize()
    {
        $imgRowData = 'imagedataerdd';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getDuncResponse')
         ->willReturn(['imagedataer'=> "kajdfkjakdsfjakdjfkajdfkadf"]);
         $this->catalogDocRef->expects($this->any())->method('curlCallForPreviewApi')
         ->willReturn($imgRowData);

        $this->assertNotNull($this->orderHistoryEnhancementMock->getProductImage($imgRowData, 1));
    }

    /**
     * Assert testGetSelectedShippingMethodName
     */
    public function testGetSelectedShippingMethodName()
    {
        $shippingDate = 'Express Saver - Monday, November 02 04:30 PM';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodName($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodNameWithElse
     */
    public function testGetSelectedShippingMethodNameWithElse()
    {
        $shippingDate = 'Express Saver - Express Saver - Monday, November 02 04:30 PM';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodName($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate
     */
    public function testGetSelectedShippingMethodDate()
    {
        $shippingDate = 'Express Saver - Express Saver - Monday, November 02 04:30 PM';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate1
     */
    public function testGetSelectedShippingMethodDate1()
    {
        $shippingDate = 'Express Saver - Monday, November 02 04:30 PM';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate2
     */
    public function testGetSelectedShippingMethodDate2()
    {
        $shippingDate = 'FedEx 2 Day - Thursday, April 27, 4:30pm';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate3
     */
    public function testGetSelectedShippingMethodDate3()
    {
        $shippingDate = 'FedEx Ground US - Sunday, April 16 11:59 pm';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate4
     */
    public function testGetSelectedShippingMethodDate4()
    {
        $shippingDate = 'FedEx Ground US - Sunday, April 16 End of Day';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate5
     */
    public function testGetSelectedShippingMethodDate5()
    {
        $shippingDate = 'FedEx Ground US - FedEx Ground US - Sunday, April 16 End of Day';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate6
     */
    public function testGetSelectedShippingMethodDate6()
    {
        $shippingDate = 'FedEx EXPRESS SAVER - FedEx Express Saver - Thursday, April 20, 4:30pm';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    /**
     * Assert testGetSelectedShippingMethodDate7
     */
    public function testGetSelectedShippingMethodDate7()
    {
        $shippingDate = 'FedEx Ground US - 1 Business Day(s)';
        $this->assertNotNull($this->orderHistoryEnhancementMock->getSelectedShippingMethodDate($shippingDate));
    }

    public function testGetMiraklOrderStatusReturnsStatusWhenOrdersExist()
    {
        $commercialId = '123';
        $productSku = 'SKU123';
        $offerId = 'OFFER1';
        $expectedStatus = 'SHIPPED';

        $shopCustomAttributes = ['shop_id' => 42];
        $miraklOrderMock = $this->getMockBuilder(\Mirakl\MMP\FrontOperator\Domain\Order::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $statusMock = $this->getMockBuilder(OrderStatus::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $statusMock->method('getState')->willReturn($expectedStatus);
        $miraklOrderMock->method('getStatus')->willReturn($statusMock);

        $this->marketplaceConfigMock
            ->method('getShopCustomAttributesByProductSku')
            ->with($productSku)
            ->willReturn($shopCustomAttributes);

        $this->miraklHelper
            ->method('getOrders')
            ->with([
                'commercial_ids' => $commercialId,
                'shop_ids' => $shopCustomAttributes['shop_id'],
                'offer_ids' => $offerId,
            ])
            ->willReturn([$miraklOrderMock]);

        $result = $this->orderHistoryEnhancementMock->getMiraklOrderStatus($commercialId, $productSku, $offerId);
        $this->assertEquals($expectedStatus, $result);
    }

    public function testGetMiraklOrderStatusReturnsEmptyStringWhenNoOrders()
    {
        $commercialId = '123';
        $productSku = 'SKU123';
        $offerId = 'OFFER1';

        $shopCustomAttributes = ['shop_id' => 42];

        $this->marketplaceConfigMock
            ->method('getShopCustomAttributesByProductSku')
            ->with($productSku)
            ->willReturn($shopCustomAttributes);

        $this->miraklHelper
            ->method('getOrders')
            ->with([
                'commercial_ids' => $commercialId,
                'shop_ids' => $shopCustomAttributes['shop_id'],
                'offer_ids' => $offerId,
            ])
            ->willReturn([]);

        $result = $this->orderHistoryEnhancementMock->getMiraklOrderStatus($commercialId, $productSku, $offerId);
        $this->assertEquals('', $result);
    }

    public function testGetRecipientEmailAddressLimit()
    {
        $this->assertIsInt($this->orderHistoryEnhancementMock->getRecipientEmailAddressLimit());
    }

    public function testFormatShippingMethodName()
    {
        // Positive test case
        $this->assertEquals(
            "FedEx Standard Shipping",
            $this->orderHistoryEnhancementMock->formatShippingMethodName("fedex standard shipping")
        );
        $this->assertNotEquals(
            "FedEX_Standard_Shipping",
            $this->orderHistoryEnhancementMock->formatShippingMethodName("fedex standard shipping")
        );
        $this->assertEquals(
            "FedEx Free Shipping",
            $this->orderHistoryEnhancementMock->formatShippingMethodName("fedex free shipping")
        );
        $this->assertEquals(
            "FedEx Express Saver",
            $this->orderHistoryEnhancementMock->formatShippingMethodName("fedex express saver")
        );
    }

    public function testGetOrderShippingDescription()
    {
        $this->orderMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->orderMock->expects($this->any())->method('getShipmentsCollection')
        ->willReturn($this->shipmentCollectionMock);
        $this->shipmentCollectionMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->shipmentCollectionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->testShipmentOrderCompletionDate();
        $this->orderMock->expects($this->any())->method('getEstimatedPickupTime')->willReturn('22');
        $result = '22';
        $this->assertEquals($result, $this->orderHistoryEnhancementMock->getOrderShippingDescription($this->orderMock));
    }

    public function testGetOrderShippingDescriptionWithElse()
    {
        $this->orderMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_Shipping');
        $this->orderMock->expects($this->any())->method('getShippingDescription')->willReturn('05-04-2024');
        $this->assertNotNull($this->orderHistoryEnhancementMock->getOrderShippingDescription($this->orderMock));
    }

    public function testGetOrderShippingDescriptionWithElse1()
    {
        $this->orderMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_Shipping');
        $this->orderMock->expects($this->any())->method('getShippingDescription')->willReturn(null);
        $this->assertEquals('', $this->orderHistoryEnhancementMock->getOrderShippingDescription($this->orderMock));
    }

    public function testOrderStatusLabel()
    {
        $this->assertNotNull($this->orderHistoryEnhancementMock->orderStatusLabel('processing'));
    }

    public function testOrderLineItem3P()
    {
        $this->assertNotNull($this->orderHistoryEnhancementMock->orderLineItem3P('processing'));
    }

    public function testGetOrderItems()
    {
        $jsonColumn = 'additional_data';
        $searchElement = 'deliveryDate';

        $this->orderMock->method('getId')->willReturn(1);

        $this->orderItemCollectionMock->expects($this->exactly(3))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['order_id', $this->orderMock->getId()],
                ['mirakl_offer_id', ['notnull' => true]],
                ['additional_data', ['like' => '%deliveryDate%']]
            );

        // Set up the order item collection factory mock to return the order item collection mock
        $this->orderItemCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->orderItemCollectionMock);

        // Call the method being tested
        $actualOrderItems = $this->orderHistoryEnhancementMock->getOrderItems(
            $this->orderMock,
            $jsonColumn,
            $searchElement
        );

        // Assertions
        $this->assertSame($this->orderItemCollectionMock, $actualOrderItems);
    }

    public function testGetOrderShippingTotal()
    {

        // Set the expected return values for the mocked methods
        $this->orderMock->expects($this->once())
            ->method('getShippingAmount')
            ->willReturn(10.00);
        $this->orderMock->expects($this->once())
            ->method('formatPrice')
            ->with(20.00)
            ->willReturn('$20.00');

        $this->helperRate->expects($this->once())
            ->method('getMktShippingTotalAmount')
            ->willReturn(10.00);

        // Call the method being tested
        $result = $this->orderHistoryEnhancementMock->getOrderShippingTotal($this->orderMock);

        // Assert the expected result
        $this->assertEquals('$20.00', $result);
    }

    public function testGetOrderItemStatusNew()
    {
        $this->orderItem->method('getStatus')->willReturn(OrderDetailsDataMapper::CHECK_NEW);
        $result = $this->orderHistoryEnhancementMock->getOrderItemStatus($this->orderItem, $this->orderMock);
        $this->assertEquals(OrderDetailsDataMapper::STATUS_ORDERED, $result);
    }

    public function testGetOrderItemStatusCanceled()
    {
        $this->orderItem->method('getStatus')->willReturn(OrderDetailsDataMapper::CHECK_CANCELED);
        $result = $this->orderHistoryEnhancementMock->getOrderItemStatus($this->orderItem, $this->orderMock);
        $this->assertEquals(OrderDetailsDataMapper::STATUS_PROCESSING, $result);
    }

    /**
     * @return void
     */
    public function testIsMixedOrder()
    {
        $this->miraklOrderHelperMock->expects($this->once())
            ->method('isMiraklOrder')
            ->willReturn(true);

        $this->miraklOrderHelperMock->expects($this->once())
            ->method('isFullMiraklOrder')
            ->willReturn(false);

        $this->assertTrue($this->orderHistoryEnhancementMock->isMixedOrder($this->orderMock));
    }

    public function testIsPickupOrder()
    {
        $isMixedOrderMock = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->onlyMethods(['isMixedOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $isMixedOrderMock->method('isMixedOrder')->willReturn(false);

        $this->orderMock->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');

        $this->assertTrue($this->orderHistoryEnhancementMock->isPickupOrder($this->orderMock));
    }

    public function testIsMarketplaceCommercialToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isMarketplaceCommercialToggleEnabled());
    }

    public function testGetTrackOrderUrl()
    {
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn('test');
        $this->assertNotNull($this->orderHistoryEnhancementMock->getTrackOrderUrl());
    }

    public function testGetRecipientEmailAddressLimit1()
    {
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn(5);
        $this->assertIsInt($this->orderHistoryEnhancementMock->getRecipientEmailAddressLimit());
    }

    public function testGetMiraklShippingAmount()
    {
        $this->helperRate->expects($this->any())->method('getMktShippingTotalAmount')->willReturn(5);
        $this->orderMock->expects($this->any())->method('formatPrice')->with(5)->willReturnSelf();

        $this->assertNotNull($this->orderHistoryEnhancementMock->getMiraklShippingAmount($this->orderMock));
    }

    public function testGetMiraklItemsCount()
    {
        $this->orderItemCollectionMock->expects($this->any())->method('getItems')->willReturn([$this->orderMock]);
        $this->orderMock->expects($this->any())->method('getData')->willReturn('657');

        $this->assertNotNull($this->orderHistoryEnhancementMock->getMiraklItemsCount($this->orderItemCollectionMock));
    }

    /**
     * Test case for isNewDocumentImageToggleEnabled
     */
    public function testIsNewDocumentImageToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertNotNull($this->orderHistoryEnhancementMock->isNewDocumentImageToggleEnabled());
    }


    /**
     * Test isOrderApprovalB2bEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bEnabled()
    {
        $returnValue = true;
        $this->orderApprovalAdminConfigHelper
        ->expects($this->once())->method('isOrderApprovalB2bEnabled')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->orderHistoryEnhancementMock->isOrderApprovalB2bEnabled());
    }

    /**
     * Test isOrderApprovalB2bEnabled
     *
     * @return void
     */
    public function testCheckIsReviewActionSet()
    {
        $returnValue = true;
        $this->orderApprovalAdminConfigHelper
        ->expects($this->once())->method('checkIsReviewActionSet')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->orderHistoryEnhancementMock->checkIsReviewActionSet());
    }

    /**
     * Assert getItemsByOrderId
     *
     * @return [Object]
     */
    public function testGetOrderById()
    {
        $orderId = 45;
        $this->orderRepositoryMock->expects($this->any())->method('get')->willReturn($this->orderMock);
        $this->orderHistoryEnhancementMock->getOrderById($orderId);
    }

    /**
     * Assert Check Legacy Document for reorder
     *
     */
    public function testCheckLegacyDocumentForReorder()
    {
        $this->orderHistoryhelperMock->expects($this->any())->method('checkOrderHasLegacyDocument')->willReturn(true);
        $this->orderHistoryEnhancementMock->checkLegacyDocumentForReorder(10);
    }

    /**
     * Test Function - toggle button to handle the legacy document for reorder scetion
     *
     */
    public function testCheckLegacyDocReorderSectionToggle()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->checkLegacyDocReorderSectionToggle());
    }

    /**
     * Test Function - verifies the D-233959 toggle which fixes blank order history details
     *
     */
    public function testIsD233959Enabled_ReturnsTrue()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->assertTrue($this->orderHistoryEnhancementMock->isD233959Enabled());
    }

    /**
     * Test Function - Verifies that when toggles are disabled, the standard contact address is returned.
     *
     * @return void
     */
    public function testGetContactAddress_TogglesDisabled_ReturnsStandardAddress()
    {
        $quoteId = 123;

        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($quoteId);

        $expectedAddress = ['street' => '123 Main St'];
        $this->orderHistoryhelperMock->expects($this->once())
            ->method('getContactAddress')
            ->with($quoteId)
            ->willReturn($expectedAddress);

        $orderHistoryEnhancementMock = $this->getMockBuilder(\Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement::class)
            ->setConstructorArgs([
                'orderRepository' => $this->orderRepositoryMock,
                'imageHelper' => $this->imageHelperMock,
                'orderHistoryhelper' => $this->orderHistoryhelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'selfRegHelper' => $this->selfRegHelperMock,
                'customerSession' => $this->customerSessionMock,
                'duncCall' => $this->duncMock,
                'toggleConfig' => $this->toggleConfigMock,
                'miraklHelper' => $this->miraklHelper,
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock,
                'checkoutConfig' => $this->cartCheckoutConfigMock,
                'itemCollectionFactory' => $this->orderItemCollectionFactory,
                'jsonSerializer' => $this->jsonSerializer,
                'orderDetailsDataMapper' => $this->orderDetailsDataMapperMock,
                'miraklOrderHelper' => $this->miraklOrderHelperMock,
                'dataObjectFactory' => $this->dataObjectFactoryMock,
                'helperRate' => $this->helperRate,
                'shipmentStatusFactory' => $this->shipmentFactoryMock,
                'redirectInterface' => $this->redirectInterfaceMock,
                'catalogDocumentRefranceApi' => $this->catalogDocRef,
                'orderApprovalAdminConfigHelper' => $this->orderApprovalAdminConfigHelper,
                'shopManagement' => $this->shopManagementMock,
                'helper' => $this->helperDataMock,
                '_urlBuilder' => $this->urlBuilderMock,
                'handleMktCheckout' => $this->handleMktCheckoutMock,
                'config' => $this->marketplaceConfigMock,
                'priceHelper' => $this->priceHelperMock,
                'cartHelper' => $this->cartHelper,
                'quoteRepository' => $this->quoteRepositoryMock,
                'instoreConfig' => $this->instoreConfigMock,
            ])
            ->onlyMethods(['isD219344OrderInfoAlternateAddressFixEnabled', 'isD233959Enabled', 'getContactAddressWithAlternateConditionCheck'])
            ->getMock();

        $orderHistoryEnhancementMock->expects($this->once())
            ->method('isD219344OrderInfoAlternateAddressFixEnabled')
            ->willReturn(false);

        $orderHistoryEnhancementMock->expects($this->never())
            ->method('getContactAddressWithAlternateConditionCheck');

        $orderHistoryEnhancementMock->expects($this->never())
            ->method('isD233959Enabled');

        $result = $orderHistoryEnhancementMock->getContactAddress($orderMock);

        $this->assertEquals($expectedAddress, $result);
    }

    /**
     * Test Function - Verifies that when the quote is not found, the contact address for the order is returned.
     *
     * @return void
     */
    public function testGetContactAddressWithAlternateConditionCheck_QuoteNotFound_ReturnsContactAddressForOrder()
    {
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getQuoteId')->willReturn(123);

        $this->orderHistoryEnhancementMock = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isD233959Enabled', 'isD219344OrderInfoAlternateAddressFixEnabled'])
            ->getMock();

        $this->orderHistoryEnhancementMock->method('isD233959Enabled')->willReturn(true);
        $this->orderHistoryEnhancementMock->method('isD219344OrderInfoAlternateAddressFixEnabled')->willReturn(true);

        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->quoteRepositoryMock->method('get')->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $reflection = new \ReflectionClass(OrderHistoryEnhacement::class);
        $property = $reflection->getProperty('quoteRepository');
        $property->setAccessible(true);
        $property->setValue($this->orderHistoryEnhancementMock, $this->quoteRepositoryMock);

        $orderHistoryHelperMock = $this->createMock(OrderHistoryHelper::class);
        $orderHistoryHelperMock->expects($this->once())
            ->method('getContactAddressForOrder')
            ->with($orderMock)
            ->willReturn('contactAddressForOrder');
        $property = $reflection->getProperty('orderHistoryhelper');
        $property->setAccessible(true);
        $property->setValue($this->orderHistoryEnhancementMock, $orderHistoryHelperMock);

        $result = $this->orderHistoryEnhancementMock->getContactAddressWithAlternateConditionCheck($orderMock);

        $this->assertEquals('contactAddressForOrder', $result);
    }

     /**
     * Test Function - Verifies that when the quote is marked as alternate, the order's shipping address is returned.
     *
     * @return void
     */
    public function testGetContactAddressWithAlternateConditionCheck_QuoteIsAlternate_ReturnsOrderShippingAddress()
    {
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getQuoteId')->willReturn(123);

        $this->orderHistoryEnhancementMock = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isD233959Enabled', 'isD219344OrderInfoAlternateAddressFixEnabled'])
            ->getMock();

        $this->orderHistoryEnhancementMock->method('isD233959Enabled')->willReturn(true);
        $this->orderHistoryEnhancementMock->method('isD219344OrderInfoAlternateAddressFixEnabled')->willReturn(true);

        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteMock->method('getData')->with('is_alternate')->willReturn(true);

        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->quoteRepositoryMock->method('get')->willReturn($quoteMock);

        $reflection = new \ReflectionClass(OrderHistoryEnhacement::class);
        $property = $reflection->getProperty('quoteRepository');
        $property->setAccessible(true);
        $property->setValue($this->orderHistoryEnhancementMock, $this->quoteRepositoryMock);

        $orderHistoryHelperMock = $this->createMock(OrderHistoryHelper::class);
        $orderHistoryHelperMock->expects($this->once())
            ->method('getOrderShippingAddress')
            ->with($orderMock)
            ->willReturn('orderShippingAddress');
        $property = $reflection->getProperty('orderHistoryhelper');
        $property->setAccessible(true);
        $property->setValue($this->orderHistoryEnhancementMock, $orderHistoryHelperMock);

        $result = $this->orderHistoryEnhancementMock->getContactAddressWithAlternateConditionCheck($orderMock);

        $this->assertEquals('orderShippingAddress', $result);
    }

    /**
     * Test Function - Verifies that when the quote is not marked as alternate, the contact address for the order is returned.
     *
     * @return void
     */
    public function testGetContactAddressWithAlternateConditionCheck_QuoteIsNotAlternate_ReturnsContactAddressForOrder()
    {
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getQuoteId')->willReturn(123);

        $this->orderHistoryEnhancementMock = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isD233959Enabled', 'isD219344OrderInfoAlternateAddressFixEnabled'])
            ->getMock();

        $this->orderHistoryEnhancementMock->method('isD233959Enabled')->willReturn(true);
        $this->orderHistoryEnhancementMock->method('isD219344OrderInfoAlternateAddressFixEnabled')->willReturn(true);

        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteMock->method('getData')->with('is_alternate')->willReturn(false);

        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->quoteRepositoryMock->method('get')->willReturn($quoteMock);

        $reflection = new \ReflectionClass(OrderHistoryEnhacement::class);
        $property = $reflection->getProperty('quoteRepository');
        $property->setAccessible(true);
        $property->setValue($this->orderHistoryEnhancementMock, $this->quoteRepositoryMock);

        $orderHistoryHelperMock = $this->createMock(OrderHistoryHelper::class);
        $orderHistoryHelperMock->expects($this->once())
            ->method('getContactAddressForOrder')
            ->with($orderMock)
            ->willReturn('contactAddressForOrder');
        $property = $reflection->getProperty('orderHistoryhelper');
        $property->setAccessible(true);
        $property->setValue($this->orderHistoryEnhancementMock, $orderHistoryHelperMock);

        $result = $this->orderHistoryEnhancementMock->getContactAddressWithAlternateConditionCheck($orderMock);

        $this->assertEquals('contactAddressForOrder', $result);
    }

}
