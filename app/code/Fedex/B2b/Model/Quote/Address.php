<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\B2b\Model\Quote;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\Punchout\Api\Data\ConfigInterface as PunchoutConfigInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Address\Mapper;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\CustomAttributeListInterface;
use Magento\Quote\Model\Quote\Address\ItemFactory;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Address\Total\CollectorFactory;
use Magento\Quote\Model\Quote\Address\TotalFactory;
use Magento\Quote\Model\Quote\Address\Validator;
use Magento\Quote\Model\Quote\TotalsReader;
use Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory As ItemCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate\CollectionFactory;
use Magento\Shipping\Model\CarrierFactoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Quote\Model\Quote\TotalsCollector;

/**
 * Sales Quote address model
 *
 * @api
 * @method int getQuoteId()
 * @method Address setQuoteId(int $value)
 * @method string getCreatedAt()
 * @method Address setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method Address setUpdatedAt(string $value)
 * @method AddressInterface getCustomerAddress()
 * @method Address setCustomerAddressData(AddressInterface $value)
 * @method string getAddressType()
 * @method Address setAddressType(string $value)
 * @method int getFreeShipping()
 * @method Address setFreeShipping(int $value)
 * @method bool getCollectShippingRates()
 * @method Address setCollectShippingRates(bool $value)
 * @method Address setShippingMethod(string $value)
 * @method string getShippingDescription()
 * @method Address setShippingDescription(string $value)
 * @method float getWeight()
 * @method Address setWeight(float $value)
 * @method float getSubtotal()
 * @method Address setSubtotal(float $value)
 * @method float getBaseSubtotal()
 * @method Address setBaseSubtotal(float $value)
 * @method Address setSubtotalWithDiscount(float $value)
 * @method Address setBaseSubtotalWithDiscount(float $value)
 * @method float getTaxAmount()
 * @method Address setTaxAmount(float $value)
 * @method float getBaseTaxAmount()
 * @method Address setBaseTaxAmount(float $value)
 * @method float getShippingAmount()
 * @method float getBaseShippingAmount()
 * @method float getShippingTaxAmount()
 * @method Address setShippingTaxAmount(float $value)
 * @method float getBaseShippingTaxAmount()
 * @method Address setBaseShippingTaxAmount(float $value)
 * @method float getDiscountAmount()
 * @method Address setDiscountAmount(float $value)
 * @method float getBaseDiscountAmount()
 * @method Address setBaseDiscountAmount(float $value)
 * @method float getGrandTotal()
 * @method Address setGrandTotal(float $value)
 * @method float getBaseGrandTotal()
 * @method Address setBaseGrandTotal(float $value)
 * @method string getCustomerNotes()
 * @method Address setCustomerNotes(string $value)
 * @method string getDiscountDescription()
 * @method Address setDiscountDescription(string $value)
 * @method null|array getDiscountDescriptionArray()
 * @method Address setDiscountDescriptionArray(array $value)
 * @method float getShippingDiscountAmount()
 * @method Address setShippingDiscountAmount(float $value)
 * @method float getBaseShippingDiscountAmount()
 * @method Address setBaseShippingDiscountAmount(float $value)
 * @method float getSubtotalInclTax()
 * @method Address setSubtotalInclTax(float $value)
 * @method float getBaseSubtotalTotalInclTax()
 * @method Address setBaseSubtotalTotalInclTax(float $value)
 * @method int getGiftMessageId()
 * @method Address setGiftMessageId(int $value)
 * @method float getDiscountTaxCompensationAmount()
 * @method Address setDiscountTaxCompensationAmount(float $value)
 * @method float getBaseDiscountTaxCompensationAmount()
 * @method Address setBaseDiscountTaxCompensationAmount(float $value)
 * @method float getShippingDiscountTaxCompensationAmount()
 * @method Address setShippingDiscountTaxCompensationAmount(float $value)
 * @method float getBaseShippingDiscountTaxCompensationAmnt()
 * @method Address setBaseShippingDiscountTaxCompensationAmnt(float $value)
 * @method float getShippingInclTax()
 * @method Address setShippingInclTax(float $value)
 * @method float getBaseShippingInclTax()
 * @method \Magento\SalesRule\Model\Rule[] getCartFixedRules()
 * @method int[] getAppliedRuleIds()
 * @method Address setBaseShippingInclTax(float $value)
 *
 * @property $objectCopyService \Magento\Framework\DataObject\Copy
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Address extends Quote\Address
{
    const RATES_FETCH = 1;

    const RATES_RECALCULATE = 2;

    const ADDRESS_TYPE_BILLING = 'billing';

    const ADDRESS_TYPE_SHIPPING = 'shipping';

    /**
     * @param SessionManagerInterface $sessionManagerInterface
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $directoryData
     * @param Config $eavConfig
     * @param AddressConfig $addressConfig
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param AddressMetadataInterface $metadataService
     * @param AddressInterfaceFactory $addressDataFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param ItemFactory $addressItemFactory
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param RateFactory $addressRateFactory
     * @param RateCollectorInterfaceFactory $rateCollector
     * @param CollectionFactory $rateCollectionFactory
     * @param RateRequestFactory $rateRequestFactory
     * @param CollectorFactory $totalCollectorFactory
     * @param TotalFactory $addressTotalFactory
     * @param Copy $objectCopyService
     * @param CarrierFactoryInterface $carrierFactory
     * @param Validator $validator
     * @param Mapper $addressMapper
     * @param CustomAttributeListInterface $attributeList
     * @param Quote\TotalsCollector $totalsCollector
     * @param TotalsReader $totalsReader
     * @param QuoteHelper $quoteHelper
     * @param PunchoutConfigInterface $punchoutConfigInterface
     * @param ToggleConfig $toggleConfig
     * @param RequestQueryValidator $requestQueryValidator
     * @param InstoreConfig $instoreConfig
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        protected SessionManagerInterface         $sessionManagerInterface,
        protected Context                         $context,
        protected Registry                        $registry,
        protected ExtensionAttributesFactory      $extensionFactory,
        AttributeValueFactory                     $customAttributeFactory,
        protected Data                            $directoryData,
        protected Config                          $eavConfig,
        protected AddressConfig                   $addressConfig,
        protected RegionFactory                   $regionFactory,
        protected CountryFactory                  $countryFactory,
        AddressMetadataInterface                  $metadataService,
        AddressInterfaceFactory                   $addressDataFactory,
        RegionInterfaceFactory                    $regionDataFactory,
        DataObjectHelper                          $dataObjectHelper,
        protected ScopeConfigInterface            $scopeConfig,
        protected ItemFactory                     $addressItemFactory,
        protected ItemCollectionFactory           $itemCollectionFactory,
        protected RateFactory                     $addressRateFactory,
        protected RateCollectorInterfaceFactory   $rateCollector,
        protected CollectionFactory               $rateCollectionFactory,
        protected RateRequestFactory              $rateRequestFactory,
        protected CollectorFactory                $totalCollectorFactory,
        protected TotalFactory                    $addressTotalFactory,
        protected Copy                            $objectCopyService,
        protected CarrierFactoryInterface         $carrierFactory,
        Validator                                 $validator,
        Mapper                                    $addressMapper,
        CustomAttributeListInterface              $attributeList,
        TotalsCollector                           $totalsCollector,
        TotalsReader                              $totalsReader,
        Private QuoteHelper                       $quoteHelper,
        protected PunchoutConfigInterface         $punchoutConfigInterface,
        protected ToggleConfig                    $toggleConfig,
        private readonly RequestQueryValidator    $requestQueryValidator,
        private readonly InstoreConfig            $instoreConfig,
        AbstractResource                          $resource = null,
        AbstractDb                                $resourceCollection = null,
        array                                     $data = [],
        Json                                      $serializer = null,
        StoreManagerInterface                     $storeManager = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $directoryData,
            $eavConfig,
            $addressConfig,
            $regionFactory,
            $countryFactory,
            $metadataService,
            $addressDataFactory,
            $regionDataFactory,
            $dataObjectHelper,
            $scopeConfig,
            $addressItemFactory,
            $itemCollectionFactory,
            $addressRateFactory,
            $rateCollector,
            $rateCollectionFactory,
            $rateRequestFactory,
            $totalCollectorFactory,
            $addressTotalFactory,
            $objectCopyService,
            $carrierFactory,
            $validator,
            $addressMapper,
            $attributeList,
            $totalsCollector,
            $totalsReader,
            $resource,
            $resourceCollection,
            $data,
            $serializer,
            $storeManager
        );
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Returns true if shipping address is same as billing
     *
     * @return bool
     */
    protected function _isSameAsBilling()
    {
        if ($this->instoreConfig->isUnableToPlaceOrderDueToRemovedPreferenceFix()) {
            $quoteSameAsBilling = $this->getSameAsBilling();

            return $this->getAddressType() == Address::ADDRESS_TYPE_SHIPPING &&
                ($this->_isNotRegisteredCustomer() || $this->_isDefaultShippingNullOrSameAsBillingAddress()) &&
                ($quoteSameAsBilling || $quoteSameAsBilling === 0 || $quoteSameAsBilling === null);
        }

        return parent::_isSameAsBilling();

    }


    /**
     * Returns true if shipping address is same as billing or it is undefined
     *
     * @return bool
     */
    protected function _isDefaultShippingNullOrSameAsBillingAddress()
    {
        if ($this->instoreConfig->isUnableToPlaceOrderDueToRemovedPreferenceFix()) {
            $customer = $this->getQuote()->getCustomer();
            $customerId = $customer->getId();
            $defaultBillingAddress = null;
            $defaultShippingAddress = null;

            /* we should load data from the quote if customer is not saved yet */
            $defaultBillingAddress = $customer->getDefaultBilling();
            $defaultShippingAddress = $customer->getDefaultShipping();


            return !$defaultShippingAddress
                || $defaultBillingAddress
                && $defaultShippingAddress
                && $defaultBillingAddress == $defaultShippingAddress;
        }

        return parent::_isDefaultShippingNullOrSameAsBillingAddress();
    }
    //@codeCoverageIgnoreEnd




    /**
     * @inheritdoc
     */
    public function getEmail()
    {
        $isGraphQl = $this->requestQueryValidator->isGraphQl();
        if ($isGraphQl && $this->instoreConfig->isEnableServiceTypeForRAQ()) {
            $email = $this->getData(static::KEY_EMAIL);
            return $email;
        }

        return parent::getEmail();
    }
}
