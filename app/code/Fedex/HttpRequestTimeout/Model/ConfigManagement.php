<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Model;

use Exception;
use Fedex\HttpRequestTimeout\Api\ConfigManagementInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;

class ConfigManagement implements ConfigManagementInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Json $serializer
     * @param RequestInterface $requestInterface
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private WriterInterface $configWriter,
        private TypeListInterface $cacheTypeList,
        private Json $serializer,
        private RequestInterface $requestInterface,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isFeatureEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_TO_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function isDefaultTimeoutEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_TO_DEFAULT_TIMEOUT_ENABLED);
    }


    /**
     * @inheritDoc
     */
    public function getDefaultTimeout(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_DEFAULT_TIMEOUT) ?? self::DEFAULT_TIMEOUT;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentEntriesValueUnserialized(): array
    {
        $currentValue = $this->scopeConfig->getValue(self::XML_PATH_TO_ENTRIES_LIST) ?? [];
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
        return $this->scopeConfig->getValue(
            self::XML_PATH_TO_ENTRIES_LIST
        ) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function saveEntries($serializedUpdatedValue): void
    {
        $this->configWriter->save(
            self::XML_PATH_TO_ENTRIES_LIST,
            $serializedUpdatedValue
        );
        $this->cacheTypeList->cleanType('config');
        $this->scopeConfig->clean();
    }

    /**
     * @inheritDoc
     */
    public function updatedEntries(): mixed
    {
        $entryKey = $this->getEntryKey();
        $newEntry = $this->requestInterface->getParam('entry') ?? '';

        if (empty($entryKey)) {
            $explodedValue = explode(',', $newEntry);
            $entryKey = $explodedValue[0] ?? '';
        }
        if (empty($entryKey)) {
            throw new Exception('Entry value is empty.');
        }

        $explodedValue = explode(',', $newEntry);
        $timeout = $explodedValue[1] ?? '';
        if (empty($timeout)) {
            throw new Exception('Timeout value is empty.');
        }

        $currentValue = $this->getCurrentEntriesValueUnserialized();

        $currentValue[$entryKey] = ['timeout' => (int) $timeout];

        return $this->serializer->serialize($currentValue);
    }

    /**
     * @inheritDoc
     */
    public function removedEntries(): string|bool
    {
        $entryKey = $this->getEntryKey();
        $currentValue = $this->getCurrentEntriesValueUnserialized();
        if ($entryKey && $currentValue && key_exists($entryKey, $currentValue)) {
            unset($currentValue[$entryKey]);

            return $this->serializer->serialize($currentValue);
        }

        throw new Exception('Entry Key doest not exist.');
    }

    /**
     * @return string
     */
    private function getEntryKey(): string
    {
        return $this->requestInterface->getParam('entry_key') ?? '';
    }
}
