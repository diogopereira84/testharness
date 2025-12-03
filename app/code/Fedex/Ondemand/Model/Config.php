<?php
/**
 * @category Fedex
 * @package  Fedex_Company
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Ondemand\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to Ondemand Module database configuration.
 */
class Config implements ConfigInterface
{
    /**
     * B2B Default Store
     */
    public const XML_PATH_B2B_DEFAULT_STORE = 'ondemand_setting/category_setting/b2b_default_store';
    public const XML_PATH_B2B_PRINT_PRODUCT_CATEGORY = 'ondemand_setting/category_setting/epro_print';
    public const XML_PATH_B2B_OFFICE_SUPPLIES_CATEGORY = 'ondemand_setting/category_setting/office_supplies_category';
    public const XML_PATH_B2B_OFFICE_SUPPLIES_CATEGORY_LABEL = 'ondemand_setting/category_setting/office_supplies_category_label';
    public const XML_PATH_B2B_SPM_SUPPLIES_CATEGORY = 'ondemand_setting/category_setting/spm_supplies_category';
    public const XML_PATH_B2B_SPM_SUPPLIES_CATEGORY_LABEL = 'ondemand_setting/category_setting/spm_supplies_category_label';
    public const XML_PATH_DEFAULT_SHARED_CATALOG = 'ondemand_setting/category_setting/default_shared_catalog';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getB2bDefaultStore(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_B2B_DEFAULT_STORE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getB2bPrintProductsCategory(): string|int|null
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_B2B_PRINT_PRODUCT_CATEGORY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getB2bOfficeSuppliesCategory(): string|int|null
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_B2B_OFFICE_SUPPLIES_CATEGORY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getB2bOfficeSuppliesCategoryLabel(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_B2B_OFFICE_SUPPLIES_CATEGORY_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getB2bSPMSuppliesCategory(): string|int|null
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_B2B_SPM_SUPPLIES_CATEGORY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getB2bSPMSuppliesCategoryLabel(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_B2B_SPM_SUPPLIES_CATEGORY_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getGlobalB2BCategories(): array
    {
        return array_filter([
            $this->getB2bPrintProductsCategory(),
            $this->getB2bOfficeSuppliesCategory(),
            $this->getB2bSPMSuppliesCategory()
        ], fn($category) => $category !== null);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultSharedCatalog(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_SHARED_CATALOG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function isTigerD239305ToggleEnabled(): string|bool|int|null
    {
        return $this->toggleConfig->getToggleConfigValue('tiger_d239305');
    }

    /**
     * Get My Account Tab Name Value
     *
     * @return string
     */
    public function getMyAccountTabNameValue(): string
    {
        return ConfigInterface::MY_ACCOUNT_TAB_NAME_TITLE;
    }

    /**
     * Get Manage Users Tab Name Value
     *
     * @return string
     */
    public function getManageUsersTabNameValue(): string
    {
        return ConfigInterface::MANAGE_USERS_TAB_NAME_TITLE;
    }

    /**
     * Get Company Users Tab Name Value
     *
     * @return string
     */
    public function getCompanyUsersTabNameValue(): string
    {
        return ConfigInterface::COMPANY_USERS_TAB_NAME_TITLE;
    }

    /**
     * Get Site Payments Tab Name Value
     *
     * @return string
     */
    public function getSitePaymentsTabNameValue(): string
    {
        return ConfigInterface::SITE_PAYMENTS_TAB_NAME_TITLE;
    }

    /**
     * Get Homepage Tab Name Value
     *
     * @return string
     */
    public function getHomepageTabNameValue(): string
    {
        return ConfigInterface::HOMEPAGE_TAB_NAME_TITLE;
    }

    /**
     * Get Ondemand Homepage Tab Name Value
     *
     * @return string
     */
    public function getOndemandHomepageTabNameValue(): string
    {
        return ConfigInterface::ONDEMAND_HOMEPAGE_TAB_NAME_TITLE;
    }

    /**
     * Get Browse Print Products Tab Name Value
     *
     * @return string
     */
    public function getBrowsePrintProductsTabNameValue(): string
    {
        return ConfigInterface::BROWSE_PRINT_PRODUCTS_TAB_NAME_TITLE;
    }

    /**
     * Get FedEx Shared Catalog Tab Name Value
     *
     * @return string
     */
    public function getFedexSharedCatalogTabNameValue(): string
    {
        return ConfigInterface::FEDEX_SHARED_CATALOG_TAB_NAME_TITLE;
    }

    /**
     * Get Shared Catalog Tab Name Value
     *
     * @return string
     */
    public function getSharedCatalogTabNameValue(): string
    {
        return ConfigInterface::SHARED_CATALOG_TAB_NAME_TITLE;
    }

    /**
     * Get Orders Tab Name Value
     *
     * @return string
     */
    public function getOrdersTabNameValue(): string
    {
        return ConfigInterface::ORDERS_TAB_NAME_TITLE;
    }

    /**
     * Get Shared Orders Tab Name Value
     *
     * @return string
     */
    public function getSharedOrdersTabNameValue(): string
    {
        return ConfigInterface::SHARED_ORDERS_TAB_NAME_TITLE;
    }
}
