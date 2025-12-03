<?php

namespace Fedex\Shipto\Block\Checkout;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Company\Api\CompanyRepositoryInterface;

class DirectoryDataProcessor extends \Magento\Checkout\Block\Checkout\DirectoryDataProcessor
{
    /**
     * @var array
     */
    private $countryOptions;

    /**
     * @var array
     */
    private $regionOptions;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection
     * @param StoreResolverInterface $storeResolver @deprecated
     * @param DirectoryHelper $directoryHelper
     * @param StoreManagerInterface $storeManager
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection,
        StoreResolverInterface $storeResolver,
        private DirectoryHelper $directoryHelper,
        private \Magento\Customer\Model\Session $customerSession,
        private CompanyRepositoryInterface $companyRepository,
        StoreManagerInterface $storeManager = null
    ) {
        $this->countryCollectionFactory = $countryCollection;
        $this->regionCollectionFactory = $regionCollection;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        if (!isset($jsLayout['components']['checkoutProvider']['dictionaries'])) {
            $jsLayout['components']['checkoutProvider']['dictionaries'] = [
                'country_id' => $this->getCountryOptions(),
                'region_id' => $this->getRegionOptions(),
            ];
        }

        return $jsLayout;
    }

    /**
     * Get country options list.
     *
     * @return array
     */
    private function getCountryOptions()
    {
        if (!isset($this->countryOptions)) {
            $this->countryOptions = $this->countryCollectionFactory->create()->loadByStore(
                $this->storeManager->getStore()->getId()
            )->toOptionArray();
            $this->countryOptions = $this->orderCountryOptions($this->countryOptions);
        }

        return $this->countryOptions;
    }

    /**
     * Sort country options by top country codes.
     *
     * @param array $countryOptions
     * @return array
     * @codeCoverageIgnore
     */
    private function orderCountryOptions(array $countryOptions)
    {
        $topCountryCodes = $this->directoryHelper->getTopCountryCodes();
        if (empty($topCountryCodes)) {
            return $countryOptions;
        }

        $headOptions = [];
        $tailOptions = [[
            'value' => 'delimiter',
            'label' => '──────────',
            'disabled' => true,
        ]];
        foreach ($countryOptions as $countryOption) {
            if (empty($countryOption['value']) || in_array($countryOption['value'], $topCountryCodes)) {
                $headOptions[] = $countryOption;
            } else {
                $tailOptions[] = $countryOption;
            }
        }
        return array_merge($headOptions, $tailOptions);
    }

    /**
     * Get region options list.
     * @codeCoverageIgnore
     * @return array
     */
    private function getRegionOptions()
    {
        if (!isset($this->regionOptions)) {
            $regionOptions = $this->regionCollectionFactory->create()->addAllowedCountriesFilter(
                $this->storeManager->getStore()->getId()
            );
            $shipTo = 0;
            $companyId = $this->customerSession->getCustomerCompany();
            if ($companyId != null && $companyId > 0) {
                $customerRepo = $this->companyRepository->get((int) $companyId);
                $companyLoginType = $customerRepo->getStorefrontLoginMethodOption();
                if ($companyLoginType == 'commercial_store_epro') {
                    $shipTo = $customerRepo->getRecipientAddressFromPo();
                }

            }
            $this->regionOptions = $regionOptions->toOptionArray();
        }

        return $this->regionOptions;
    }

    /**
     * Convert collection items to select options array
     * @codeCoverageIgnore
     * @return array
     */
    private function toOptionArray($regionOptions)
    {
        $options = [];
        $propertyMap = [
            'value' => 'region_id',
            'title' => 'default_name',
            'country_id' => 'country_id',
        ];

        foreach ($regionOptions as $item) {
            $option = [];
            foreach ($propertyMap as $code => $field) {
                $option[$code] = $item->getData($field);
            }
            $option['label'] = $item->getCode();
            $options[] = $option;
        }

        if (count($options) > 0) {
            array_unshift(
                $options,
                ['title' => '', 'value' => '', 'label' => __('Please select a region, state or province.')]
            );
        }
        return $options;
    }
}
