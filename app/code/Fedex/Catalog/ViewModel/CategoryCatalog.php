<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Catalog\ViewModel;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\UrlInterface;

/**
 * B-1915354 : POD2.0:  automatically select the associated shared catalog
 */
class CategoryCatalog implements ArgumentInterface
{
    private const EXPLORER_ENABLE_DISABLE_CATALOG_CREATION_CTC_ADMIN_UPDATE = 
        'explorers_enable_disable_catalog_creation_ctc_admin_update';

    public const TECH_TITANS_E484727_COMMERCIAL_CATALOG_TYPE = 'tech_titans_e_484727';

    /**
     * CategoryCatalog constructor
     *
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected UrlInterface $urlInterface
    )
    {
    }

    /**
     * Get Taz Token
     *
     * @return string|null
     */
    public function getAdminUpdateToggleValue()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(
            static::EXPLORER_ENABLE_DISABLE_CATALOG_CREATION_CTC_ADMIN_UPDATE
        );
    }

    /**
     * Get Current Url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->urlInterface->getCurrentUrl();
    }

    /**
     * Get Toggle Value for Category and Shared Catalog Sorting
     *
     */
    public function getToggleValueForCategorySorting()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(
            static::TECH_TITANS_E484727_COMMERCIAL_CATALOG_TYPE
        );
    }
}
