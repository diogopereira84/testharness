<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\B2b\Test\Unit\Model\Quote;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Customer\Model\Address\Mapper;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\DataObject\Copy\Config;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\AddressExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\Collector;
use Magento\Quote\Model\Quote\Address\Total\CollectorFactory;
use Magento\Quote\Model\Quote\Address\TotalFactory;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate\CollectionFactory;
use Magento\Shipping\Model\CarrierFactoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\B2b\Model\Quote\Address;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class AddressTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Model\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    /**
     * @var (\Magento\Framework\Api\ExtensionAttributesFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $extensionAttributesFactoryMock;
    /**
     * @var (\Magento\Framework\Api\AttributeValueFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attributeValueFactoryMock;
    /**
     * @var (\Magento\Directory\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $directoryDataMock;
    /**
     * @var (\Magento\Eav\Model\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eavConfigMock;
    protected $customerAddressConfigMock;
    /**
     * @var (\Magento\Directory\Model\RegionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $regionFactoryMock;
    /**
     * @var (\Magento\Directory\Model\CountryFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $countryFactoryMock;
    /**
     * @var (\Magento\Customer\Api\AddressMetadataInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressMetadataInterfaceMock;
    protected $addressInterfaceFactoryMock;
    protected $addressInterface;
    /**
     * @var (\Magento\Customer\Api\Data\RegionInterfaceFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $regionInterfaceFactoryMock;
    protected $dataObjectHelperMock;
    protected $scopeConfigInterfaceMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Address\ItemFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $itemFactoryMock;
    protected $toggleConfigMock;
    protected $itemCollectionMock;
    protected $addressRateFactoryMock;
    protected $addressRateMock;
    protected $rateCollectorInterfaceFactoryMock;
    protected $rateCollectorInterfaceMock;
    /**
     * @var (\Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rateCollection;
    protected $rateRequestFactoryMock;
    protected $rateRequestMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Address\Total\CollectorFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addresscollectorFactoryMock;
    protected $addressTotalFactoryMock;
    protected $copyServiceMock;
    /**
     * @var (\Magento\Shipping\Model\CarrierFactoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $carrierFactoryInterfaceMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Address\Validator & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressValidatorMock;
    /**
     * @var (\Magento\Customer\Model\Address\Mapper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressMapperMock;
    protected $attributeListInterfaceMock;
    /**
     * @var (\Magento\Quote\Model\Quote\TotalsCollector & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteTotalsCollectorMock;
    /**
     * @var (\Magento\Quote\Model\Quote\TotalsReader & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteTotalsReaderMock;
    /**
     * @var (\Magento\Framework\Model\ResourceModel\AbstractResource & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractResourceMock;
    /**
     * @var (\Magento\Framework\Data\Collection\AbstractDb & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractDbMock;
    protected $quoteMock;
    protected $storeManagerInterfaceMock;
    protected $storeInterfaceMock;
    protected $websiteInterfaceMock;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonMock;
    protected $sessionManagerInterfaceMock;
    protected $quoteAddressMock;
    protected $directoryRegionFactoryMock;
    protected $directoryRegionMock;
    protected $rateResultMock;
    protected $rateResultMethodMock;
    protected $currencyMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Item\AbstractItem & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractItemMock;
    /**
     * @var (\Magento\Framework\DataObject & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataObj;
    /**
     * @var (\Magento\Framework\Api\ExtensionAttributesInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressExtension;
    /**
     * @var (\Magento\Framework\Model\AbstractExtensibleModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressExtensible;
    protected $addressExtensionInterface;
    /**
     * @var (\Magento\Quote\Model\Quote\Address\Total & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressTotal;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $quoteAddressObject;
    const RATES_FETCH = 1;

    const RATES_RECALCULATE = 2;

    const ADDRESS_TYPE_BILLING = 'billing';

    const ADDRESS_TYPE_SHIPPING = 'shipping';

    /**
     * Prefix of model events
     *
     * @var string
     */
    protected $_eventPrefix = 'sales_quote_address';

    /**
     * Name of event object
     *
     * @var string
     */
    protected $_eventObject = 'quote_address';

    /**
     * Quote object
     *
     * @var Quote
     */
    protected $_items;

    /**
     * Quote object
     *
     * @var Quote
     */
    protected $_quote;

    /**
     * Sales Quote address rates
     *
     * @var Rate
     */
    protected $_rates;

    /**
     * Total models collector
     *
     * @var Collector
     */
    protected $_totalCollector;

    /**
     * Total data as array
     *
     * @var array
     */
    protected $_totals = [];

    /**
     * @var array
     */
    protected $_totalAmounts = [];

    /**
     * @var array
     */
    protected $_baseTotalAmounts = [];

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Quote\Model\Quote\Address\ItemFactory
     */
    protected $_addressItemFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory
     */
    protected $_itemCollectionFactory;

    /**
     * @var RateCollectorInterfaceFactory
     */
    protected $_rateCollector;

    /**
     * @var CollectionFactory
     */
    protected $_rateCollectionFactory;

    /**
     * @var CollectorFactory
     */
    protected $_totalCollectorFactory;

    /**
     * @var TotalFactory
     */
    protected $_addressTotalFactory;

    /**
     * @var RateFactory
     * @since 101.0.0
     */
    protected $_addressRateFactory;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var Address\Validator
     */
    protected $validator;

    /**
     * @var Mapper
     */
    protected $addressMapper;

    /**
     * @var Address\RateRequestFactory
     */
    protected $_rateRequestFactory;

    /**
     * @var Address\CustomAttributeListInterface
     */
    protected $attributeList;

    /**
     * @var TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var TotalsReader
     */
    protected $totalsReader;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Copy
     */
    private $objectCopyService;

    /**
     * @var /Magento\Shipping\Model\CarrierFactoryInterface
     */
    private $carrierFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManagerInterface;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
                                        ->disableOriginalConstructor()
                                            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
                                    ->disableOriginalConstructor()
                                        ->getMock();

        $this->extensionAttributesFactoryMock = $this->getMockBuilder(ExtensionAttributesFactory::class)
                                    ->disableOriginalConstructor()
                                            ->getMock();

        $this->attributeValueFactoryMock = $this->getMockBuilder(AttributeValueFactory::class)
                                                ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();

        $this->directoryDataMock = $this->getMockBuilder(Data::class)
                                                ->disableOriginalConstructor()
                                                    ->getMock();

        $this->eavConfigMock = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
                                                ->disableOriginalConstructor()
                                                    ->getMock();

        $this->customerAddressConfigMock = $this->getMockBuilder(\Magento\Customer\Model\Address\Config::class)
            ->setMethods(['getEmail'])
                                                ->disableOriginalConstructor()
                                                    ->getMock();

        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
                                                ->disableOriginalConstructor()
                                                    ->getMock();

        $this->countryFactoryMock = $this->getMockBuilder(CountryFactory::class)
                                                ->disableOriginalConstructor()
                                                    ->getMock();

        $this->addressMetadataInterfaceMock = $this->getMockBuilder(AddressMetadataInterface::class)
                                                ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();

        $this->addressInterfaceFactoryMock = $this->getMockBuilder(AddressInterfaceFactory::class)
            ->setMethods(['create'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
            ->setMethods(['getCustomerId','getCustomer'])
                                        ->disableOriginalConstructor()
                                        ->getMockForAbstractClass();

        $this->regionInterfaceFactoryMock = $this->getMockBuilder(RegionInterfaceFactory::class)
                                                ->disableOriginalConstructor()
                                                    ->getMock();

        $this->dataObjectHelperMock = $this->getMockBuilder(DataObjectHelper::class)
            ->setMethods(['populateWithArray'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->itemFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\ItemFactory::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->_itemCollectionFactory = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory::class)
                                        ->setMethods(['create'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
                                        ->setMethods(['getToggleConfigValue'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->itemCollectionMock = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Address\Item\Collection::class)
                                        ->setMethods(['setAddressFilter'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->addressRateFactoryMock = $this->getMockBuilder(RateFactory::class)
                                ->setMethods(['create'])
                                ->disableOriginalConstructor()
                                    ->getMock();

        $this->addressRateMock = $this->getMockBuilder(Rate::class)
                            ->setMethods(['importShippingRate'])
                                ->disableOriginalConstructor()
                                    ->getMock();

        $this->rateCollectorInterfaceFactoryMock = $this->getMockBuilder(RateCollectorInterfaceFactory::class)
                                ->setMethods(['create'])
                                ->disableOriginalConstructor()
                                    ->getMock();

        $this->rateCollectorInterfaceMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\RateCollectorInterface::class)
                                ->setMethods(['collectRates', 'getResult','setAddressFilter'])
                                ->disableOriginalConstructor()
                                    ->getMock();

        $this->_rateCollectionFactory = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Address\Rate\CollectionFactory::class)
                                    ->setMethods(['create','setAddressFilter','addItem'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->rateCollection = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection::class)
                                    ->setMethods(['create','setAddressFilter','addItem'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->rateRequestFactoryMock = $this->getMockBuilder(RateRequestFactory::class)
                                    ->setMethods(['create'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->rateRequestMock = $this->getMockBuilder(RateRequest::class)
                                    ->setMethods(['setAllItems'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->addresscollectorFactoryMock = $this->getMockBuilder(CollectorFactory::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->addressTotalFactoryMock = $this->getMockBuilder(TotalFactory::class)
            ->setMethods(['create','setData','setAddress','getCode'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->copyServiceMock = $this->getMockBuilder(Copy::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->carrierFactoryInterfaceMock = $this->getMockBuilder(CarrierFactoryInterface::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->addressValidatorMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Validator::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->addressMapperMock = $this->getMockBuilder(Mapper::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->attributeListInterfaceMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\CustomAttributeListInterface::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['getAttributes'])
                                    ->getMockForAbstractClass();

        $this->quoteTotalsCollectorMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\TotalsCollector::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->quoteTotalsReaderMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\TotalsReader::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->abstractResourceMock = $this->getMockBuilder(AbstractResource::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->abstractDbMock = $this->getMockBuilder(AbstractDb::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
                                    ->setMethods(['getStoreId', 'getItemsCollection', 'setStoreId', 'getId', 'getCustomerId', 'getCustomer', 'toArray', 'getIsVirtual'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['getStore', 'getWebsite'])
                                    ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['getId', 'getBaseCurrency', 'getCurrentCurrency', 'getCurrentCurrencyCode'])
                                    ->getMockForAbstractClass();

        $this->websiteInterfaceMock = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['getId'])
                                    ->getMockForAbstractClass();

        $this->jsonMock = $this->getMockBuilder(Json::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->sessionManagerInterfaceMock = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['start', 'getAdminQuoteView'])
                                    ->getMockForAbstractClass();

        $this->quoteAddressMock = $this->getMockBuilder(Address::class)
                                    ->setMethods(['getCollectShippingRates', 'setCollectShippingRates', 'removeAllShippingRates',
                                        'getCountryId', 'requestShippingRates', 'setShippingAmount', 'setBaseShippingAmount', 'setShippingMethod',
                                            'setShippingDescription'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->directoryRegionFactoryMock = $this->getMockBuilder(\Magento\Directory\Model\Region\Factory::class)
                                    ->setMethods(['create'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->directoryRegionMock = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
                                    ->setMethods(['loadByCode'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->rateResultMock = $this->getMockBuilder(\Magento\Shipping\Model\Rate\Result::class)
                                    ->setMethods(['getAllRates'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->rateResultMethodMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\RateResult\Method::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->currencyMock = $this->getMockBuilder(\Magento\Directory\Model\Currency::class)
                                    ->setMethods(['convert'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->abstractItemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\AbstractItem::class)
                                    ->setMethods(['getQuote','setBaseShippingAmount', 'getAddress', 'getOptionByCode'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->dataObj = $this->getMockBuilder(\Magento\Framework\DataObject::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->addressExtension = $this->getMockBuilder(\Magento\Framework\Api\ExtensionAttributesInterface::class)
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->addressExtensible = $this->getMockBuilder(\Magento\Framework\Model\AbstractExtensibleModel::class)
                                    ->setMethods(['_getExtensionAttributes'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->addressExtensionInterface = $this->getMockBuilder(\Magento\Quote\Api\Data\AddressExtensionInterface::class)
                                    ->setMethods(['_setExtensionAttributes'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->addressTotal = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();


        $this->objectManager = new ObjectManager($this);

        $this->quoteAddressObject = $this->objectManager->getObject(
            Address::class,
            [
                '_scopeConfig' => $this->scopeConfigInterfaceMock,
                '_addressItemFactory' => $this->itemFactoryMock,
                '_itemCollectionFactory' => $this->_itemCollectionFactory,
                '_addressRateFactory' => $this->addressRateFactoryMock,
                '_rateCollector' => $this->rateCollectorInterfaceFactoryMock,
                '_rateCollectionFactory' => $this->_rateCollectionFactory,
                '_rateRequestFactory' => $this->rateRequestFactoryMock,
                '_totalCollectorFactory' => $this->addresscollectorFactoryMock,
                '_addressTotalFactory' => $this->addressTotalFactoryMock,
                '_regionFactory' => $this->directoryRegionFactoryMock,
                '_quote' => $this->quoteMock,
                'objectCopyService' => $this->copyServiceMock,
                'carrierFactory' => $this->carrierFactoryInterfaceMock,
                'addressDataFactory' => $this->addressInterfaceFactoryMock,
                'validator' => $this->addressValidatorMock,
                'addressMapper' => $this->addressMapperMock,
                'attributeList' => $this->attributeListInterfaceMock,
                'totalsCollector' => $this->quoteTotalsCollectorMock,
                'totalsReader' => $this->quoteTotalsReaderMock,
                'serializer' => $this->jsonMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'sessionManagerInterface' => $this->sessionManagerInterfaceMock,
                'rateCollectorInterfaceMock' => $this->rateCollectorInterfaceMock,
                'rateCollection' => $this->rateCollection
            ]
        );
    }

    /**
     * @test CollectShippingRates
     *
     * @return $this
     */

    public function testCollectShippingRates()
    {
        $this->quoteAddressObject->setData('collect_shipping_rates', true);
        $this->quoteAddressObject->setData('country_id', true);

        $this->sessionManagerInterfaceMock->expects($this->any())->method('start')->willReturnSelf();
        $this->sessionManagerInterfaceMock->expects($this->any())->method('getAdminQuoteView')->willReturn([]);
        $this->quoteAddressMock->expects($this->any())->method('setCollectShippingRates')->with(false)->willReturnSelf();

        $this->_rateCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->_rateCollectionFactory->expects($this->any())->method('setAddressFilter')->willReturnSelf();

        $this->_itemCollectionFactory->expects($this->any())->method('create')->willReturn($this->itemCollectionMock);
        $this->itemCollectionMock->expects($this->any())->method('setAddressFilter')->willReturnSelf();

        $this->rateRequestFactoryMock->expects($this->any())->method('create')->willReturn($this->rateRequestMock);

        $this->rateRequestMock->expects($this->any())->method('setAllItems')->willReturnSelf();

        $this->attributeListInterfaceMock->expects($this->any())->method('getAttributes')->willReturn([]);

        $this->directoryRegionFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryRegionMock);
        $this->directoryRegionMock->expects($this->any())->method('loadByCode')->willReturn($this->directoryRegionMock);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);

        $this->quoteMock->expects($this->any())->method('getItemsCollection')->willReturn([]);
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn(true);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterfaceMock);
        $this->websiteInterfaceMock->expects($this->any())->method('getId');

        $this->storeInterfaceMock->expects($this->any())->method('getBaseCurrency')->willReturn($this->currencyMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCurrentCurrency');//->willReturn($this->websiteInterfaceMock);

        $this->rateCollectorInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->rateCollectorInterfaceMock);
        $this->rateCollectorInterfaceMock->expects($this->any())->method('collectRates')->willReturnSelf();

        $this->rateCollectorInterfaceMock->expects($this->any())->method('getResult')->willReturn($this->rateResultMock);

        $this->rateResultMock->expects($this->any())->method('getAllRates')->willReturn([$this->rateResultMethodMock]);

        $this->addressRateFactoryMock->expects($this->any())->method('create')->willReturn($this->addressRateMock);
        $this->addressRateMock->expects($this->any())->method('importShippingRate')->willReturnSelf();

        $this->storeInterfaceMock->expects($this->any())->method('getCurrentCurrencyCode');
        $this->currencyMock->expects($this->any())->method('convert');

        $this->quoteAddressMock->expects($this->any())->method('requestShippingRates')->willReturn(false);
        $this->quoteAddressMock->expects($this->any())->method('setShippingAmount')->with(0)->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setBaseShippingAmount')->with(0)->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingMethod')->with('')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingDescription')->with('')->willReturnSelf();

        $this->assertInstanceOf(\Fedex\B2b\Model\Quote\Address::class, $this->quoteAddressObject->collectShippingRates());
        $this->assertInstanceOf(\Fedex\B2b\Model\Quote\Address::class, $this->quoteAddressObject->collectShippingRates());
    }

    /**
     * @test testCollectShippingRatesWithFalseValue
     *
     * @return $this
     */

    public function testCollectShippingRatesWithFalseValue()
    {
        $this->quoteAddressObject->setData('collect_shipping_rates', true);
        $this->quoteAddressObject->setData('country_id', true);

        $this->sessionManagerInterfaceMock->expects($this->any())->method('start')->willReturnSelf();
        $this->sessionManagerInterfaceMock->expects($this->any())->method('getAdminQuoteView')->willReturn([]);
        $this->quoteAddressMock->expects($this->any())->method('setCollectShippingRates')->with(false)->willReturnSelf();

        $this->_rateCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->_rateCollectionFactory->expects($this->any())->method('setAddressFilter')->willReturnSelf();

        $this->_itemCollectionFactory->expects($this->any())->method('create')->willReturn($this->itemCollectionMock);
        $this->itemCollectionMock->expects($this->any())->method('setAddressFilter')->willReturnSelf();

        $this->rateRequestFactoryMock->expects($this->any())->method('create')->willReturn($this->rateRequestMock);
        $this->rateRequestMock->expects($this->any())->method('setAllItems')->willReturnSelf();

        $this->attributeListInterfaceMock->expects($this->any())->method('getAttributes')->willReturn([]);

        $this->directoryRegionFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryRegionMock);
        $this->directoryRegionMock->expects($this->any())->method('loadByCode')->willReturn($this->directoryRegionMock);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);

        $this->quoteMock->expects($this->any())->method('getItemsCollection')->willReturn([]);
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn(true);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterfaceMock);
        $this->websiteInterfaceMock->expects($this->any())->method('getId');//->willReturn();

        $this->storeInterfaceMock->expects($this->any())->method('getBaseCurrency')->willReturn($this->currencyMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCurrentCurrency');//->willReturn($this->websiteInterfaceMock);

        $this->rateCollectorInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->rateCollectorInterfaceMock);
        $this->rateCollectorInterfaceMock->expects($this->any())->method('collectRates')->willReturnSelf();

        // changed return value to meet condition
        $this->rateCollectorInterfaceMock->expects($this->any())->method('getResult')->willReturn(false);

        $this->rateResultMock->expects($this->any())->method('getAllRates')->willReturn([$this->rateResultMethodMock]);

        $this->addressRateFactoryMock->expects($this->any())->method('create')->willReturn($this->addressRateMock);
        $this->addressRateMock->expects($this->any())->method('importShippingRate')->willReturnSelf();

        $this->storeInterfaceMock->expects($this->any())->method('getCurrentCurrencyCode');
        $this->currencyMock->expects($this->any())->method('convert');

        $this->quoteAddressMock->expects($this->any())->method('requestShippingRates')->willReturn(false);
        $this->quoteAddressMock->expects($this->any())->method('setShippingAmount')->with(0)->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setBaseShippingAmount')->with(0)->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingMethod')->with('')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingDescription')->with('')->willReturnSelf();

        $this->assertInstanceOf(\Fedex\B2b\Model\Quote\Address::class, $this->quoteAddressObject->collectShippingRates());
    }

    /**
     * @test testCollectShippingRatesWithFalseQuoteStoreId
     *
     * @return $this
     */
    public function testCollectShippingRatesWithFalseQuoteStoreId()
    {
        $this->quoteAddressObject->setData('collect_shipping_rates', true);
        $this->quoteAddressObject->setData('country_id', true);

        $this->sessionManagerInterfaceMock->expects($this->any())->method('start')->willReturnSelf();
        $this->sessionManagerInterfaceMock->expects($this->any())->method('getAdminQuoteView')->willReturn([]);
        $this->quoteAddressMock->expects($this->any())->method('setCollectShippingRates')->with(false)->willReturnSelf();

        $this->_rateCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->_rateCollectionFactory->expects($this->any())->method('setAddressFilter')->willReturnSelf();

        $this->_itemCollectionFactory->expects($this->any())->method('create')->willReturn($this->itemCollectionMock);
        $this->itemCollectionMock->expects($this->any())->method('setAddressFilter')->willReturnSelf();

        $this->rateRequestFactoryMock->expects($this->any())->method('create')->willReturn($this->rateRequestMock);
        $this->rateRequestMock->expects($this->any())->method('setAllItems')->willReturnSelf();

        $this->attributeListInterfaceMock->expects($this->any())->method('getAttributes')->willReturn([]);

        $this->directoryRegionFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryRegionMock);
        $this->directoryRegionMock->expects($this->any())->method('loadByCode')->willReturn($this->directoryRegionMock);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);

        $this->quoteMock->expects($this->any())->method('getItemsCollection')->willReturn([]);

        // changed return value to meet condition
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterfaceMock);
        $this->websiteInterfaceMock->expects($this->any())->method('getId');//->willReturn();

        $this->storeInterfaceMock->expects($this->any())->method('getBaseCurrency')->willReturn($this->currencyMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCurrentCurrency');//->willReturn($this->websiteInterfaceMock);

        $this->rateCollectorInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->rateCollectorInterfaceMock);
        $this->rateCollectorInterfaceMock->expects($this->any())->method('collectRates')->willReturnSelf();

        $this->rateCollectorInterfaceMock->expects($this->any())->method('getResult')->willReturn(false);

        $this->rateResultMock->expects($this->any())->method('getAllRates')->willReturn([$this->rateResultMethodMock]);

        $this->addressRateFactoryMock->expects($this->any())->method('create')->willReturn($this->addressRateMock);
        $this->addressRateMock->expects($this->any())->method('importShippingRate')->willReturnSelf();

        $this->storeInterfaceMock->expects($this->any())->method('getCurrentCurrencyCode');
        $this->currencyMock->expects($this->any())->method('convert');

        $this->quoteAddressMock->expects($this->any())->method('requestShippingRates')->willReturn(false);
        $this->quoteAddressMock->expects($this->any())->method('setShippingAmount')->with(0)->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setBaseShippingAmount')->with(0)->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingMethod')->with('')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingDescription')->with('')->willReturnSelf();

        $this->assertInstanceOf(\Fedex\B2b\Model\Quote\Address::class, $this->quoteAddressObject->collectShippingRates());
    }

    /**
     * @test testCollectShippingRatesWithFalseCountryId
     *
     * @return $this
     */
    public function testCollectShippingRatesWithFalseCountryId()
    {
        $this->quoteAddressObject->setData('collect_shipping_rates', true);
        $this->quoteAddressObject->setData('country_id', false);

        $this->sessionManagerInterfaceMock->expects($this->any())->method('start')->willReturnSelf();
        $this->sessionManagerInterfaceMock->expects($this->any())->method('getAdminQuoteView')->willReturn([]);
        $this->quoteAddressMock->expects($this->any())->method('setCollectShippingRates')->with(false)->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('getId')->willReturn(2);
        $this->_rateCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->_rateCollectionFactory->expects($this->any())->method('setAddressFilter')->willReturnSelf();

        $this->assertInstanceOf(\Fedex\B2b\Model\Quote\Address::class, $this->quoteAddressObject->collectShippingRates());
    }

    /**
     * @test testSetQuote()
     *
     * @return $this
     */
    public function testSetQuote()
    {
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(2);
        $this->quoteAddressObject->setQuote($this->quoteMock);
    }

    /**
     * @test testSetTotalAmount()
     *
     * @return $this
     */
    public function testSetTotalAmount()
    {
        //$this->quoteMock->expects($this->any())->method('getId')->willReturn(2);
        $total = 'total';
        $amount = 10;
        $this->quoteAddressObject->setTotalAmount($total, $amount);
    }

    /**
     * @test testSetBaseTotalAmount()
     *
     * @return $this
     */
    public function testSetBaseTotalAmount()
    {
        $total = 'total';
        $amount = 10;
        $this->quoteAddressObject->setBaseTotalAmount($total, $amount);
    }

    public function testGetSubtotalWithDiscount()
    {
        $this->quoteAddressObject->getSubtotalWithDiscount();
    }

    public function testSetExtensionAttributes()
    {
        $this->addressExtensionInterface->expects($this->any())->method('_setExtensionAttributes')->willReturnSelf();
        $this->quoteAddressObject->setExtensionAttributes($this->addressExtensionInterface);
    }

    public function test__clone()
    {
        $this->quoteAddressObject->__clone();
    }

    /**
     * Checks if it was set
     *
     * @return bool
     */
    public function testItemsCollectionWasSet()
    {
        $this->quoteAddressObject->itemsCollectionWasSet();
    }

    /**
     * Checks if it was set
     *
     * @return bool
     */
    public function testShippingRatesCollectionWasSet()
    {
        $this->quoteAddressObject->shippingRatesCollectionWasSet();
    }

    public function testSetAppliedTaxes()
    {
        $data = 'company';
        $this->quoteAddressObject->setAppliedTaxes($data);
    }

    public function testgetAppliedTaxes()
    {
        $this->quoteAddressObject->getAppliedTaxes();
    }
    /**
     * @test testImportCustomerAddressData()
     *
     * @return $this
     */
    public function testImportCustomerAddressData()
    {
        $this->copyServiceMock->copyFieldsetToTarget('fieldset', 'aspect', [], 'target');
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(2);
        $this->addressInterface->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->quoteMock->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->quoteMock->expects($this->any())->method('getCustomer')->willReturn($this->customerAddressConfigMock);
        $this->customerAddressConfigMock->expects($this->any())->method('getEmail')->willReturn('test@gmail.com');
        $this->quoteAddressObject->importCustomerAddressData($this->addressInterface);
    }
    /**
     * @test testExportCustomerAddressData()
     *
     * @return $this
     */
    public function testExportCustomerAddress()
    {
        $downloadableData = ['region' => 1,'region_code' => 'TX' ,'region_id' => 2];
        $this->copyServiceMock->expects($this->any())->method('getDataFromFieldset')->willReturn($downloadableData);
        $this->addressInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->addressInterface);
        $this->dataObjectHelperMock->expects($this->any())->method('populateWithArray')->with('rar')->willReturn(true);
        $this->quoteAddressObject->exportCustomerAddress();
    }
    /**
     * Add total data or model
     *
     * @param Total|array $total
     * @return $this
     */
    public function testAddTotal()
    {
        $this->addressTotalFactoryMock->expects($this->any())->method('create')->with(Total::class)->willReturnSelf();
        $this->addressTotalFactoryMock->expects($this->any())->method('getCode')->willReturn(43);
        $this->quoteAddressObject->addTotal(new Total);
    }
    /**
     * Add total data or model
     *
     * @param Total|array $total
     * @return $this
     */
    public function testAddTotalwithArray()
    {

        $this->addressTotalFactoryMock->expects($this->any())->method('create')->with(Total::class)->willReturnSelf();
        $this->addressTotalFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->quoteAddressObject->addTotal(['total']);
    }

    /**
     * Validate minimum amount
     *
     * @return bool
     */
    public function testValidateMinimumAmount()
    {
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn(3);
        $this->scopeConfigInterfaceMock->expects($this->any())->method('isSetFlag')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getIsVirtual')->willReturn(3);
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getvalue')->willReturnSelf();
        $this->testGetSubtotalWithDiscount();
        $this->quoteAddressObject->validateMinimumAmount();
    }
}
