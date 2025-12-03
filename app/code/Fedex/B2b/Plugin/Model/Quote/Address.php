<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\B2b\Plugin\Model\Quote;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Fedex\Punchout\Api\Data\ConfigInterface as PunchoutConfigInterface;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Quote\Model\Quote\TotalsReader;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Magento\Store\Model\StoreManagerInterface;

class Address
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RateRequestFactory $rateRequestFactory
     * @param PunchoutConfigInterface $punchoutConfigInterface
     * @param RateCollectorInterfaceFactory $rateCollector
     * @param SessionManagerInterface $sessionManagerInterface
     * @param QuoteHelper $quoteHelper
     * @param TotalsReader $totalsReader
     * @param ToggleConfig $toggleConfig
     * @param RateFactory $addressRateFactory
     * @param RequestQueryValidator $requestQueryValidator
     * @param InstoreConfig $instoreConfig
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected RateRequestFactory $rateRequestFactory,
        protected PunchoutConfigInterface $punchoutConfigInterface,
        private RateCollectorInterfaceFactory $rateCollector,
        protected SessionManagerInterface $sessionManagerInterface,
        private QuoteHelper $quoteHelper,
        protected TotalsReader $totalsReader,
        protected ToggleConfig $toggleConfig,
        private RateFactory $addressRateFactory,
        private readonly RequestQueryValidator $requestQueryValidator,
        private readonly InstoreConfig $instoreConfig,
        private ?StoreManagerInterface $storeManager = null
    ) {
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * After plugin for the getEmail method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param string $result
     * @return string
     */
    public function afterGetEmail(\Magento\Quote\Model\Quote\Address $subject, $result)
    {
        $isGraphQl = $this->requestQueryValidator->isGraphQl();
        if ($isGraphQl && $this->instoreConfig->isEnableServiceTypeForRAQ()) {
            return $result;
        }

        $email = $subject->getData($subject::KEY_EMAIL);
        if (!$email && $subject->getQuote()) {
            $email = $subject->getQuote()->getCustomerEmail();
            $subject->setEmail($email);
        }
        return $email;

    }

    /**
     * After plugin for the getBaseSubtotalWithDiscount method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param float $result
     * @return float
     */
    public function afterGetBaseSubtotalWithDiscount(\Magento\Quote\Model\Quote\Address $subject, $result)
    {

        return $subject->getBaseSubtotal() + $subject->getBaseDiscountAmount();

    }

    /**
     * After plugin for validateMinimumAmount method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param bool $result
     * @return bool
     */
    public function afterValidateMinimumAmount(\Magento\Quote\Model\Quote\Address $subject, $result)
    {

            $storeId = $subject->getStoreId();
            $validateEnabled = $this->scopeConfig->isSetFlag(
                'sales/minimum_order/active',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if (!$validateEnabled) {
                return true;
            }

            if (!$subject->getIsVirtual() xor $subject->getAddressType() == \Magento\Quote\Model\Quote\Address::TYPE_SHIPPING) {
                return true;
            }

            $includeDiscount = $this->scopeConfig->getValue(
                'sales/minimum_order/include_discount_amount',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $amount = $this->scopeConfig->getValue(
                'sales/minimum_order/amount',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $taxInclude = $this->scopeConfig->getValue(
                'sales/minimum_order/tax_including',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $taxes = $taxInclude ? $subject->getBaseTaxAmount() : 0;

            $result = $includeDiscount ?
                ($subject->getBaseSubtotalWithDiscount() + $taxes >= $amount) :
                ($subject->getBaseSubtotal() + $taxes >= $amount);


        return $result;
    }
    //@codeCoverageIgnoreStart
    /**
     * After plugin for the getTotals method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param array $result
     * @return array
     */
    public function afterGetTotals(\Magento\Quote\Model\Quote\Address $subject, $result)
    {

            $totalsData = array_merge($subject->getData(), ['address_quote_items' => $subject->getAllItems()]);
            $totals = $this->totalsReader->fetch($subject->getQuote(), $totalsData);
            foreach ($totals as $total) {
                $subject->addTotal($total);
            }

            return $result;

    }

    /**
     * Around plugin for the requestShippingRates method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param callable $proceed
     * @param AbstractItem|null $item
     * @return mixed
     */
    public function aroundRequestShippingRates(
        \Magento\Quote\Model\Quote\Address $subject,
        callable $proceed,
        AbstractItem $item = null
    ) {
            /** @var $request RateRequest */
            $request = $this->rateRequestFactory->create();
            $request->setAllItems($item ? [$item] : $subject->getAllItems());
            $request->setDestCountryId($subject->getCountryId());
            $request->setDestRegionId($subject->getRegionId());
            $request->setDestRegionCode($subject->getRegionCode());
            $request->setDestStreet($subject->getStreetFull());
            $request->setDestCity($subject->getCity());
            $request->setDestPostcode($subject->getPostcode());
            $request->setPackageValue($item ? $item->getBaseRowTotal() : $subject->getBaseSubtotal());
            $packageWithDiscount = $item ? $item->getBaseRowTotal() -
                $item->getBaseDiscountAmount() : $subject->getBaseSubtotalWithDiscount();
            $request->setPackageValueWithDiscount($packageWithDiscount);
            $request->setPackageWeight($item ? $item->getRowWeight() : $subject->getWeight());
            $request->setPackageQty($item ? $item->getQty() : $subject->getItemQty());

            /**
             * Need for shipping methods that use insurance based on price of physical products
             */
            $packagePhysicalValue = $item ? $item->getBaseRowTotal() : $subject->getBaseSubtotal() -
                $subject->getBaseVirtualAmount();
            $request->setPackagePhysicalValue($packagePhysicalValue);

            $request->setFreeMethodWeight($item ? 0 : $subject->getFreeMethodWeight());

            /**
             * Store and website identifiers specified from StoreManager
             */
            if ($subject->getQuote()->getStoreId()) {
                $storeId = $subject->getQuote()->getStoreId();
                $request->setStoreId($storeId);
                $request->setWebsiteId($this->storeManager->getStore($storeId)->getWebsiteId());
            } else {
                $request->setStoreId($this->storeManager->getStore()->getId());
                $request->setWebsiteId($this->storeManager->getWebsite()->getId());
            }
            $request->setFreeShipping($subject->getFreeShipping());
            /**
             * Currencies need to convert in free shipping
             */
            $request->setBaseCurrency($this->storeManager->getStore()->getBaseCurrency());
            $request->setPackageCurrency($this->storeManager->getStore()->getCurrentCurrency());
            $request->setLimitCarrier($subject->getLimitCarrier());
            $baseSubtotalInclTax = $subject->getBaseSubtotalTotalInclTax();
            $request->setBaseSubtotalInclTax($baseSubtotalInclTax);
            $request->setBaseSubtotalWithDiscountInclTax($subject->getBaseSubtotalWithDiscount() + $subject->getBaseTaxAmount());

            $quote = $subject->getQuote();
            $companyId = $quote?->getCustomer()?->getExtensionAttributes()?->getCompanyAttributes()?->getCompanyId();
            if ($companyId && $this->punchoutConfigInterface->getMigrateEproNewPlatformOrderCreationToggle($companyId)
                && $subject->getQuote()) {
                $request->setQuote($subject->getQuote());
            }

            $result = $this->rateCollector->create()->collectRates($request)->getResult();

            $found = false;
            if ($result) {
                $shippingRates = $result->getAllRates();
                $found = $this->requestShippingRatesComplexity($subject, $shippingRates, $item, $found);
            }

            return $found;
    }

    /**
     * Handle complexity of shipping rates processing
     *
     * @param {FullClassName} $subject
     * @param array $shippingRates
     * @param AbstractItem|null $item
     * @param bool $found
     * @return bool
     */
    protected function requestShippingRatesComplexity($subject, $shippingRates, $item, $found)
    {
        foreach ($shippingRates as $shippingRate) {
            $rate = $this->addressRateFactory->create()->importShippingRate($shippingRate);
            if (!$item) {
                $subject->addShippingRate($rate);
            }

            if ($subject->getShippingMethod() == $rate->getCode()) {
                if ($item) {
                    $item->setBaseShippingAmount($rate->getPrice());
                } else {
                    /** @var StoreInterface */
                    $store = $this->storeManager->getStore();
                    $amountPrice = $store->getBaseCurrency()
                        ->convert($rate->getPrice(), $store->getCurrentCurrencyCode());
                    $subject->setBaseShippingAmount($rate->getPrice());
                    $subject->setShippingAmount($amountPrice);
                }

                $found = true;
            }
        }

        return $found;
    }


    /**
     * After plugin for the collectShippingRates method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     *
     */
    public function afterCollectShippingRates(
        \Magento\Quote\Model\Quote\Address $subject,
        $result
    ) {
            if (!$subject->getCollectShippingRates()) {
                return $result;
            }
            $this->sessionManagerInterface->start();

            if (empty($this->sessionManagerInterface->getAdminQuoteView())) { // If admin is viewing quote.
                $subject->setCollectShippingRates(false);

                $subject->removeAllShippingRates();

                if (!$subject->getCountryId()) {
                    return $result;
                }

                $found = $subject->requestShippingRates();
                if (!$found && !$this->quoteHelper->isMiraklQuote()) {
                    $subject->setShippingAmount(0)
                        ->setBaseShippingAmount(0)
                        ->setShippingMethod('')
                        ->setShippingDescription('');
                }
            }

        return $result;
    }

    /**
     * After plugin for the getAllItems method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllItems(
        \Magento\Quote\Model\Quote\Address $subject,
        $result
    ) {
        return $result;
    }

    /**
     * Custom logic for handling address items.
     */
    private function getAllItemsComplexityIf($subject, $addressItems, $items)
    {
        foreach ($addressItems as $aItem) {
            if ($aItem->isDeleted()) {
                continue;
            }

            if (!$aItem->getQuoteItemImported()) {
                $qItem = $subject->getQuote()->getItemById($aItem->getQuoteItemId());
                if ($qItem) {
                    $aItem->importQuoteItem($qItem);
                }
            }
            $items[] = $aItem;
        }
        return $items;
    }

    /**
     * Custom logic for handling quote items.
     */
    private function getAllItemsComplexityElse($subject, $quoteItems, $items)
    {
        /*
        * For virtual quote we assign items only to billing address, otherwise - only to shipping address
        */
        $addressType = $subject->getAddressType();
        $canAddItems = $subject->getQuote()->isVirtual()
            ? $addressType == \Magento\Quote\Model\Quote\Address::TYPE_BILLING
            : $addressType == \Magento\Quote\Model\Quote\Address::TYPE_SHIPPING;

        if ($canAddItems) {
            foreach ($quoteItems as $qItem) {
                if ($qItem->isDeleted()) {
                    continue;
                }
                $items[] = $qItem;
            }
        }
        return $items;
    }

    /**
     * After plugin for the getStreet method
     *
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param array|string $result
     * @return array|string
     */
    public function afterGetStreet(
        \Magento\Quote\Model\Quote\Address $subject,
        $result
    ) {

        $street = $subject->getData(\Magento\Quote\Model\Quote\Address::KEY_STREET);
        return is_array($street) ? $street : explode("\n", (string)$street);

    }
    //@codeCoverageIgnoreEnd
}
