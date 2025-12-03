<?php
/**
 * @category Fedex
 * @package  Fedex_Company
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Company\Model;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to Company Module database configuration.
 */
class Config implements ConfigInterface
{
    /**
     * Company Store Relation
     */
    public const XML_PATH_COMPANY_STORE_RELATION = 'ondemand_setting/company_settings/store_relation';

    /**
     * E-414712 - Hero Banner Carousel For Commercial
     */
    public const XML_PATH_E_414712_HERO_BANNER_CAROUSEL_FOR_COMMERCIAL = 'e414712_hero_banner_carousel_for_commercial';

    public const XML_PATH_D_173846_CATEGORY_EDIT_ISSUE = 'tech_titans_d_173846_category_edit_issue';
    private const SHARED_CATALOGS_MAPPING_ISSUE_FIX = "shared_catalog_mapping_issue_fix";
    private const D190859_ISSUE_FIX = "tiger_team_d_190859_issue_fix";

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getCompanyStoreRelation(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_COMPANY_STORE_RELATION,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * D-173846 - Toggle for category name is not updating
     * @return bool|int|null
     */
    public function getCategoryEditD173846Toggle(): bool|int|null
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_D_173846_CATEGORY_EDIT_ISSUE);
    }

    /**
     * @inheriDoc
     */
    public function getE414712HeroBannerCarouselForCommercial(): bool|int|null
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_E_414712_HERO_BANNER_CAROUSEL_FOR_COMMERCIAL);
    }

    /**
     * @inheriDoc
     */
    public function isToggleEnableForD190859IssueFix()
    {
        return $this->toggleConfig->getToggleConfigValue(self::D190859_ISSUE_FIX);
    }

    /**
     * @inheriDoc
     */
    public function getSharedCatalogsMapIssueFixToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(self::SHARED_CATALOGS_MAPPING_ISSUE_FIX);
    }
}
