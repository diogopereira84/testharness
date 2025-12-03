<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToggleCsv\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;

class CsvProcessor
{
    private const EXPECTED_HEADERS = ['Key', 'Value'];

    /**
     * @param FileDriver $fileDriver
     * @param WriterInterface $configWriter
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly FileDriver $fileDriver,
        private readonly WriterInterface $configWriter,
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Validate CSV headers.
     *
     * @param string $filePath
     */
    public function validateHeaders(string $filePath): void
    {
        $handle = $this->fileDriver->fileOpen($filePath, 'r');
        if (!$handle) {
            throw new LocalizedException(__('Could not open the uploaded file.'));
        }

        $headers = $this->fileDriver->fileGetCsv($handle, 0);
        $this->fileDriver->fileClose($handle);

        $normalized = array_map(fn($header) => strtolower(trim($header)), $headers);

        if ($normalized !== array_map('strtolower', self::EXPECTED_HEADERS)) {
            throw new LocalizedException(
                __('Invalid CSV headers. Expected: %1', implode(', ', self::EXPECTED_HEADERS))
            );
        }
    }

    /**
     * Validate CSV content.
     *
     * @param string $filePath
     */
    public function validateContent(string $filePath): void
    {
        $handle = $this->fileDriver->fileOpen($filePath, 'r');
        if (!$handle) {
            throw new LocalizedException(__('Unable to read the uploaded file.'));
        }

        // Skip header row
        $this->fileDriver->fileGetCsv($handle, 0);
        $line = 2;

        while (!$this->fileDriver->endOfFile($handle)) {
            $row = $this->fileDriver->fileGetCsv($handle, 0);

            if (!$row || $row === [null]) {
                $line++;
                continue;
            }

            // Normalize row length
            if (count($row) !== 2) {
                $this->fileDriver->fileClose($handle);
                throw new LocalizedException(__('Invalid column count at line %1. Expected exactly 2 columns.', $line));
            }

            $key = trim($row[0] ?? '');
            $value = strtoupper(trim($row[1] ?? ''));

            // Validate key is not empty and value is YES/NO
            if ($key === '') {
                $this->fileDriver->fileClose($handle);
                throw new LocalizedException(__('Empty key found at line %1.', $line));
            }

            if (!in_array($value, ['YES', 'NO'], true)) {
                $this->fileDriver->fileClose($handle);
                throw new LocalizedException(__('Invalid value at line %1. Only "YES" or "NO" allowed.', $line));
            }

            $line++;
        }

        $this->fileDriver->fileClose($handle);
    }

    /**
     * Create directory if it does not exist.
     *
     * @param string $dir
     */
    public function createDirectoryIfNotExists(string $dir): void
    {
        if (!$this->fileDriver->isExists($dir)) {
            try {
                $this->fileDriver->createDirectory($dir);
            } catch (\Exception $e) {
                throw new LocalizedException(__('Cannot create directory: %1', $dir));
            }
        }
    }

    /**
     * Apply updates to the configuration.
     *
     * @param array $updates
     * @return array
     */
    public function applyListCsvUpdates(array $updates): array
    {
        if (empty($updates)) {
            return [];
        }

        $keys = [];
        foreach ($updates as $update) {
            $keys[] = $update['key'];
        }

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('core_config_data');

        $select = $connection->select();
        $select->from($tableName, ['path']);

        $whereConditions = [];
        foreach ($keys as $key) {
            $whereConditions[] = $connection->quoteInto('path LIKE ?', "%/$key");
        }

        if (count($whereConditions) > 0) {
            $select->where(implode(' OR ', $whereConditions));
        }

        $matchedPaths = $connection->fetchCol($select);

        $pathMap = [];
        foreach ($matchedPaths as $fullPath) {
            $parts = explode('/', $fullPath);
            $lastPart = end($parts);
            $pathMap[$lastPart] = $fullPath;
        }
        
        // Keep track of invalid and applied keys
        $invalidKeys = [];
        $appliedKeys = [];
        
        foreach ($updates as $update) {
            $key = $update['key'];
            $value = $update['value'];
            if (!isset($pathMap[$key])) {
                $invalidKeys[] = [
                    'key' => $key,
                    'line' => $update['line'] ?? 'unknown'
                ];
                continue;
            }

            $fullPath = $pathMap[$key];
            $this->configWriter->save($fullPath, $value);
            $appliedKeys[] = $key;
        }
        
        return [
            'invalid' => $invalidKeys,
            'applied' => $appliedKeys
        ];
    }
}
