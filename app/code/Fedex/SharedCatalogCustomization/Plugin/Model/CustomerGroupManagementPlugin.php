<?php
/**
 * @category     Fedex
 * @package      Fedex_SharedCatalogCustomization
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Matias Hidalgo <matias.hidalgo.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\SharedCatalogCustomization\Plugin\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\SharedCatalog\Model\CustomerGroupManagement;

class CustomerGroupManagementPlugin
{
    private array $sharedCatalogGroupIds = [];
    private array $notSharedCatalogGroupIds = [];

    public function __construct(
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Cache the result of getSharedCatalogGroupIds to avoid multiple calls
     *
     * @param CustomerGroupManagement $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetSharedCatalogGroupIds(CustomerGroupManagement $subject, callable $proceed)
    {
        if (!$this->toggleConfig->getToggleConfigValue('D_233466_cache_shared_catalog_group_ids')) {
            return $proceed();
        }

        if (empty($this->sharedCatalogGroupIds)) {
            $this->sharedCatalogGroupIds = $proceed();
        }
        return $this->sharedCatalogGroupIds;
    }

    /**
     * Cache the result of getGroupIdsNotInSharedCatalogs to avoid multiple calls
     *
     * @param CustomerGroupManagement $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetGroupIdsNotInSharedCatalogs(CustomerGroupManagement $subject, callable $proceed)
    {
        if (!$this->toggleConfig->getToggleConfigValue('D_233466_cache_shared_catalog_group_ids')) {
            return $proceed();
        }

        if (empty($this->notSharedCatalogGroupIds)) {
            $this->notSharedCatalogGroupIds = $proceed();
        }
        return $this->notSharedCatalogGroupIds;
    }
}
