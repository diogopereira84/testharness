<?php

declare(strict_types=1);

namespace Fedex\CSP\Model;

use Fedex\CSP\Api\CspManagementInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class CspManagement implements CspManagementInterface
{
    public const XML_PATH_TO_ENABLED = 'fedex_csp/csp_whitelist/enabled';
    public const XML_PATH_TO_ENTRIES = 'fedex_csp/csp_whitelist/entries';
    public const XML_PATH_TO_ENTRIES_LIST = 'fedex_csp/csp_whitelist/entries_list';
    public const XML_PATH_TO_CREATE_ENTRY = 'fedex_csp/csp_whitelist/create_entry';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Json $serializer
     * @param RequestInterface $requestInterface
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private WriterInterface $configWriter,
        private TypeListInterface $cacheTypeList,
        private Json $serializer,
        private RequestInterface $requestInterface,
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function isCspWhitelistEnabled($storeId = 1): bool
    {
        $scope = $storeId ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        return $this->scopeConfig->isSetFlag(self::XML_PATH_TO_ENABLED, $scope, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentEntriesValueUnserialized($storeId = 1): array
    {
        $scope = $storeId ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        $currentValue = $this->scopeConfig->getValue(self::XML_PATH_TO_ENTRIES, $scope, $storeId) ?? [];
        if ($currentValue) {
            $currentValue = $this->serializer->unserialize($currentValue);
        }

        return is_array($currentValue) ? $currentValue : [];
    }

    /**
     * @inheritDoc
     */
    public function getCurrentEntriesValueForListing(): string
    {
        $storeId = $this->requestInterface->getParam('store');
        if ($storeId) {

            return $this->scopeConfig->getValue(
                self::XML_PATH_TO_ENTRIES,
                ScopeInterface::SCOPE_STORES,
                $storeId
            ) ?? '';
        } else {

            return $this->scopeConfig->getValue(
                self::XML_PATH_TO_ENTRIES
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function saveEntries($serializedUpdatedValue): void
    {
        $storeId = $this->requestInterface->getParam('store_id') ?? 0;
        $scope = $storeId ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        $this->configWriter->save(
            self::XML_PATH_TO_ENTRIES,
            $serializedUpdatedValue,
            $scope,
            $storeId
        );

        $this->configWriter->save(
            self::XML_PATH_TO_ENTRIES_LIST,
            $serializedUpdatedValue,
            $scope,
            $storeId
        );

        $this->configWriter->save(
            self::XML_PATH_TO_CREATE_ENTRY,
            1,
            $scope,
            $storeId
        );

        $this->cacheTypeList->cleanType('config');
        $this->scopeConfig->clean();
    }

    /**
     * @inheritDoc
     */
    public function updatedEntries(): mixed
    {
        $storeId = $this->requestInterface->getParam('store_id') ?? 1;
        $entryKey = $this->getEntryKey(true);
        $newEntry = $this->requestInterface->getParam('entry');
        $newPolicies = $this->requestInterface->getParam('policies') ?? [];

        $currentValue = $this->getCurrentEntriesValueUnserialized($storeId);
        $currentValue[$entryKey]['entry'] = $newEntry;
        $currentValue[$entryKey]['policies'] = $newPolicies;

        return $this->serializer->serialize($currentValue);
    }

    /**
     * @inheritDoc
     */
    public function removedEntries(): string|bool
    {
        $storeId = $this->requestInterface->getParam('store_id') ?? 1;
        $entryKey = $this->getEntryKey();
        $currentValue = $this->getCurrentEntriesValueUnserialized($storeId);
        if ($entryKey && $currentValue && key_exists($entryKey, $currentValue)) {
            unset($currentValue[$entryKey]);

            return $this->serializer->serialize($currentValue);
        }

        throw new \Exception('Entry Key doest not exist.');
    }

    protected function getEntryKey($generateEntryKey = false): string
    {
        $entryKey = $this->requestInterface->getParam('entry_key');
        if (!$entryKey && $generateEntryKey) {
            $entryKey = '_' . time() . '_' . substr((string)time(), -3);
        }

        return $entryKey;
    }
}
