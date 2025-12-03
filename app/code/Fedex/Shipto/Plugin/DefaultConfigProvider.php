<?php
/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Plugin;

use Magento\Customer\Model\Session;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\ResourceModel\Quote\Item\Option\CollectionFactory;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Helper\Image;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data AS MarketplaceCheckoutHelper;

class DefaultConfigProvider
{
    private const EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION = 'explorers_restricted_and_recommended_production';
    private const RECOMMENDED_LOCATION_ALL_LOCATIONS = 'recommended_location_all_locations';
    private const RECOMMENDED_STORES_ALL_LOCATIONS ='recommended_stores_all_location';
    private const EXPLORERS_SITE_LEVEL_QUOTING_STORES = 'explorers_site_level_quoting_stores';
    private const MAZEGEEKS_ALLOW_CUSTOMER_CHOOSE_PRODUCTION_LOCATION = 'mazegeeks_e_482379_allow_customer_to_choose_production_location_updates';

    /**
     * Data construct
     *
     * @param Session $customerSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param CollectionFactory $itemOption
     * @param TimezoneInterface $timezone
     * @param DateTime $date
     * @param ToggleConfig $toggleConfig
     * @param ProductionLocationFactory $productionLocationFactory
     * @param SelfReg $selfRegHelper
     * @param CheckoutSession $checkoutSession
     * @param Image $imageHelper
     * @param DeliveryHelper $deliveryHelper
     * @param Product $productModel
     * @param AdminConfigHelper $adminConfigHelper
     * @param InBranchValidation $inBranchValidation
     * @param ProductRepositoryInterface $productRepository
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        protected Session $customerSession,
        protected CompanyRepositoryInterface $companyRepository,
        protected CollectionFactory $itemOption,
        protected TimezoneInterface $timezone,
        protected DateTime $date,
        protected ToggleConfig $toggleConfig,
        protected ProductionLocationFactory $productionLocationFactory,
        protected SelfReg $selfRegHelper,
        protected CheckoutSession $checkoutSession,
        protected Image $imageHelper,
        protected DeliveryHelper $deliveryHelper,
        protected Product $productModel,
        protected AdminConfigHelper $adminConfigHelper,
        protected InBranchValidation $inBranchValidation,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private readonly ProductCollectionFactory $productCollectionFactory
    )
    {
    }

     /**
      * Return configuration array
      *
      * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
      * @param array|mixed $result
      * @return array|mixed $result
      * @SuppressWarnings(PHPMD.UnusedFormalParameter)
      */
    public function afterGetConfig($subject, $result)
    {
        $quote = $this->checkoutSession->getQuote();
        $allItems = $quote->getAllVisibleItems();
        $result['is_personal_address_book'] = false;
        $isEproLogin = false;
        $isPersonalAddressBookToggle = $this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book');
        $isPerformanceImprovementProductLoadToggle = $this->toggleConfig
            ->getToggleConfigValue('hawks_d_227849_performance_improvement_checkout_product_load');
        if ($isPerformanceImprovementProductLoadToggle) {
            /** @var ProductCollection $productCollection */
            $productCollection = $this->productCollectionFactory->create();
            /**
             * This flag is used to indicate that the product collection has been filtered
             * by shared catalog.
             * It prevents the collection from being filtered since every product in the cart
             * must be available for the customer.
             */
            $productCollection->setFlag('has_shared_catalog_filter', true);
            $productCollection->addFieldToFilter(
                'entity_id',
                ['in' => array_column($allItems, 'product_id')]
            );
            $productCollection->addMediaGalleryData();
            $productCollection->addAttributeToFilter('customizable', ['neq' => 1]);

            $itemImageData = [];
            foreach ($allItems as $item) {
                $itemId = $item->getItemId();
                $productId = $item->getProductId();
                /** @var Product $product */
                $product = $productCollection->getItemById($productId);
                if (null === $product) {
                    continue;
                }
                if ($this->deliveryHelper->getProductAttributeName($product->getAttributeSetId()) !== "PrintOnDemand") {
                    continue;
                }
                $itemImageData[$itemId] =  $this->imageHelper
                    ->init($product, 'cart_page_product_thumbnail')
                    ->setImageFile($product->getFile())
                    ->getUrl();
            }
        } else {
            $itemImageData = [];
            foreach ($allItems as $item) {
                $itemId = $item->getItemId();
                $productId = $item->getProductId();
                if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
                    $product =  $this->productRepository->getById($productId);
                } else {
                    $product = $this->productModel->load($productId);
                }
                $attributeSetId = $product->getAttributeSetId();
                $attributeSetName = $this->deliveryHelper->getProductAttributeName($attributeSetId);
                $isCustomize = $this->deliveryHelper->getProductCustomAttributeValue($productId, 'customizable');
                if ($attributeSetName == "PrintOnDemand" && !$isCustomize) {
                    $itemImageData[$itemId] =  $this->imageHelper->init($product, 'cart_page_product_thumbnail')->setImageFile($product->getFile())->getUrl();
                }
            }
        }
        $result['product_image_data'] = $itemImageData;

        $customerData = [];
        $shipTo = 0;

        $config = [];
        $result['is_commercial'] = false;

        // start code for covid peak season pop up show on shipping page
        $currentTimeStamp = $this->timezone->date($this->date->gmtDate())->format('Y-m-d H:i:s');
        $peakSeasonEndDate = $this->timezone->date($this->date->gmtDate())->format('2022-01-31 00:00:00');
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();

        $result['is_covidPeakSeason'] = false;
        if (strtotime($peakSeasonEndDate) >= strtotime($currentTimeStamp)) {
            $result['is_covidPeakSeason'] = true;
        }
        // end code for covid peak season pop up

        $result['is_production_location'] = false;

        $result['restricted_production_location'] = false;
        $result['hco_price_update'] = true;
        $result['recommended_production_location'] = false;
        $companyId = $this->customerSession->getCustomerCompany();
        $isInstoreDocument = false;
        if ($companyId != null && $companyId > 0) {
            $result['is_commercial'] = true;

            $customerRepo = $this->companyRepository->get((int) $companyId);
            $companyLoginType = $customerRepo->getStorefrontLoginMethodOption();
            if ($companyLoginType == 'commercial_store_epro') {
                $shipTo = $customerRepo->getRecipientAddressFromPo();
                $isEproLogin = true;
            }
            if ($shipTo || $isSelfRegCustomer) {
                $result['customerData']['addresses'] = [];
            }
                $isEproUser = $this->inBranchValidation->isInBranchUser();
                if ($isEproUser) {
                    $locationNumber = $this->inBranchValidation->getAllowedInBranchLocation();
                    if ($locationNumber && $locationNumber != '') {
                        $isInstoreDocument = $locationNumber;
                    }
                }

        }

        $items = $result['totalsData']['items'];
        $items = $this->prepareItemsArray($items);
        $result['totalsData']['items'] = $items;
        $uploadToQuote = $this->adminConfigHelper->isUploadToQuoteToggle();
        $result['quoteData']['price_dash'] = 0;
        if ($uploadToQuote) {
            $quoteObject = $this->checkoutSession->getQuote();
            $result['quoteData']['price_dash']= $this->adminConfigHelper->checkoutQuotePriceisDashable($quoteObject);
            $result = $this->adminConfigHelper->checkoutQuoteItemPriceableValue($result, $quoteObject);
        }

        if (!$isInstoreDocument && $companyId != null && $companyId > 0) {
            // B-937111 | Off Production location feature globally
            $result = $this->addProductionLocationInfo($result, $customerRepo, $companyId);
        }

        if ($isPersonalAddressBookToggle && $this->customerSession->isLoggedIn() && !$isEproLogin){
            $result['is_personal_address_book'] = true;
        }
        return $result;
    }

    /**
     * Prepare Items array
     *
     * @param array $items
     * @return array|mixed $items
     */
    public function prepareItemsArray($items)
    {
        foreach ($items as $key => $item) {
            $infoBuyRequestArray = [];
            $infoBuyRequestValue = '';

            $quote = $this->itemOption->create()->addFieldToFilter("item_id", $item["item_id"])->load();
            foreach ($quote as $itemData) {
                if ($itemData->getCode() == "info_buyRequest") {
                    $infoBuyRequestValue = $itemData->getValue();
                }
            }
            if ($infoBuyRequestValue) {
                $infoBuyRequestArray = json_decode($infoBuyRequestValue, true);
                if (isset($infoBuyRequestArray["external_prod"][0]["userProductName"])) {
                    $items[$key]['name'] = $infoBuyRequestArray["external_prod"][0]["userProductName"];
                }
            }
        }
        return $items;
    }

    /**
     * Add production location info
     *
     * @param array $result
     * @param object $customerRepo
     * @param int $companyId
     * @return array|mixed $result
     */
    public function addProductionLocationInfo($result, $customerRepo, $companyId)
    {
        $isRestrictedRecommendedToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION);
        $isSiteLevelQuotingStoresToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_SITE_LEVEL_QUOTING_STORES);
        $isAllowCustomerChooseProductionLocationToggle = $this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_ALLOW_CUSTOMER_CHOOSE_PRODUCTION_LOCATION);
        // Explorers E-394577 - Restricted And Recommended production locations
         $allowsProductionLocation = $customerRepo->getAllowProductionLocation();
         $productionLocationOption = $customerRepo->getProductionLocationOption();
        
        // B-2616598 | Change Production Locations UI 
        $result['is_simplified_production_location'] = $allowsProductionLocation && $isAllowCustomerChooseProductionLocationToggle;
        
        // Check if a production location is selected
        $result['has_selected_prod_loc'] = false;
        $quote = $this->checkoutSession->getQuote();
        if ($quote && $quote->getId()) {
            $productionLocationId = $quote->getProductionLocationId();
            $result['has_selected_prod_loc'] = !empty($productionLocationId);
        } 
        if (($allowsProductionLocation || ($isSiteLevelQuotingStoresToggle && isset($result['quoteData']['price_dash']) && $result['quoteData']['price_dash']))
            && ($isRestrictedRecommendedToggle || $productionLocationOption == self::RECOMMENDED_LOCATION_ALL_LOCATIONS ||
                $productionLocationOption == self::RECOMMENDED_STORES_ALL_LOCATIONS)
            && !$isAllowCustomerChooseProductionLocationToggle) {
            $prodLocationModel = $this->productionLocationFactory->create();
            // Fetch both restricted and recommended locations
            $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
                ->addFieldToFilter('is_recommended_store', ['in' => [0, 1]]);
            $restrictedLocation = [];
            $recommendedLocation = [];
            foreach ($storesLocations as $storesLocation) {
                $locationData = $storesLocation->getData();
                if ($storesLocation->getData('is_recommended_store') == 1) {
                    $recommendedLocation[] = $locationData;
                } else {
                    $restrictedLocation[] = $locationData;
                }
            }
            if (!empty($restrictedLocation)) {
                $result['restricted_production_location'] = json_encode($restrictedLocation);
            }
            if (!empty($recommendedLocation)) {
                $result['recommended_production_location'] = json_encode($recommendedLocation);
            }
            $result['is_production_location'] = true;
        }

        if ($isAllowCustomerChooseProductionLocationToggle ) {
            $prodLocationModel = $this->productionLocationFactory->create();

            $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
                ->addFieldToFilter('is_recommended_store', ['in' => [0, 1]]);
            
            $restrictedLocation = [];
            $recommendedLocation = [];
            
            foreach ($storesLocations as $storesLocation) {
                $locationData = $storesLocation->getData();
                if ($storesLocation->getData('is_recommended_store') == 1) {
                    $recommendedLocation[] = $locationData;
                } else {
                    $restrictedLocation[] = $locationData;
                }
            }
            
            if (!empty($restrictedLocation)) {
                $result['restricted_production_location'] = json_encode($restrictedLocation);
            }
            if (!empty($recommendedLocation)) {
                $result['recommended_production_location'] = json_encode($recommendedLocation);
            }
            $result['is_production_location'] = true;
        }
        if ($isRestrictedRecommendedToggle && $productionLocationOption == self::RECOMMENDED_LOCATION_ALL_LOCATIONS) {
            $result['is_restricted_store_production_location_option'] = true;
        }

        if ($isSiteLevelQuotingStoresToggle && $productionLocationOption == self::RECOMMENDED_STORES_ALL_LOCATIONS) {
            $result['is_site_level_quoting_stores'] = true;
        }
        if ($isSiteLevelQuotingStoresToggle && $productionLocationOption == self::RECOMMENDED_LOCATION_ALL_LOCATIONS) {
            $result['is_site_level_quoting_location'] = true;
        }

        return $result;
    }
}
