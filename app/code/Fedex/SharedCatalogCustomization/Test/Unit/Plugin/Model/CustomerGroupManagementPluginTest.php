<?php
/**
 * @category     Fedex
 * @package      Fedex_SharedCatalogCustomization
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Matias Hidalgo <matias.hidalgo.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Plugin\Model;

use Fedex\SharedCatalogCustomization\Plugin\Model\CustomerGroupManagementPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use PHPUnit\Framework\TestCase;

class CustomerGroupManagementPluginTest extends TestCase
{
    private ToggleConfig $toggleConfigMock;
    private CustomerGroupManagement $customerGroupManagementMock;
    private CustomerGroupManagementPlugin $plugin;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->customerGroupManagementMock = $this->createMock(CustomerGroupManagement::class);
        $this->plugin = new CustomerGroupManagementPlugin($this->toggleConfigMock);
    }

    public function testDoesNotCacheGetSharedCatalogGroupIdsWhenToggleIsDisabled(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('D_233466_cache_shared_catalog_group_ids')
            ->willReturn(false);

        $proceed = fn() => [1, 2, 3];

        $result1 = $this->plugin->aroundGetSharedCatalogGroupIds($this->customerGroupManagementMock, $proceed);
        $result2 = $this->plugin->aroundGetSharedCatalogGroupIds($this->customerGroupManagementMock, fn() => [4, 5, 6]);

        $this->assertNotSame($result1, $result2);
        $this->assertSame([1, 2, 3], $result1);
        $this->assertSame([4, 5, 6], $result2);
    }

    public function testCachesGetSharedCatalogGroupIdsResultOnlyOnceWhenToggleIsEnabled(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('D_233466_cache_shared_catalog_group_ids')
            ->willReturn(true);

        $proceed = fn() => [1, 2, 3];

        $result1 = $this->plugin->aroundGetSharedCatalogGroupIds($this->customerGroupManagementMock, $proceed);
        $result2 = $this->plugin->aroundGetSharedCatalogGroupIds($this->customerGroupManagementMock, fn() => [4, 5, 6]);

        $this->assertSame($result1, $result2);
        $this->assertSame([1, 2, 3], $result1);
    }

    public function testDoesNotCacheGetGroupIdsNotInSharedCatalogsWhenToggleIsDisabled(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('D_233466_cache_shared_catalog_group_ids')
            ->willReturn(false);

        $proceed = fn() => [1, 2, 3];

        $result1 = $this->plugin->aroundGetGroupIdsNotInSharedCatalogs($this->customerGroupManagementMock, $proceed);
        $result2 = $this->plugin->aroundGetGroupIdsNotInSharedCatalogs($this->customerGroupManagementMock, fn() => [4, 5, 6]);

        $this->assertNotSame($result1, $result2);
        $this->assertSame([1, 2, 3], $result1);
        $this->assertSame([4, 5, 6], $result2);
    }

    public function testCachesGetGroupIdsNotInSharedCatalogsResultOnlyOnceWhenToggleIsEnabled(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('D_233466_cache_shared_catalog_group_ids')
            ->willReturn(true);

        $proceed = fn() => [1, 2, 3];

        $result1 = $this->plugin->aroundGetGroupIdsNotInSharedCatalogs($this->customerGroupManagementMock, $proceed);
        $result2 = $this->plugin->aroundGetGroupIdsNotInSharedCatalogs($this->customerGroupManagementMock, fn() => [4, 5, 6]);

        $this->assertSame($result1, $result2);
        $this->assertSame([1, 2, 3], $result1);
    }

}
