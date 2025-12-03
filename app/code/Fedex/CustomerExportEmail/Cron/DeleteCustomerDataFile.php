<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class use to delete authorize excel file
 */
class DeleteCustomerDataFile
{
    public const CUSTOMERDATA_DIRECTORY = 'customerdata_export';

    /**
     * DeleteAuthorizeExcelFile constructor
     *
     * @param LoggerInterface $logger
     * @param Filesystem $fileSystem
     * @param File $file
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected Filesystem $fileSystem,
        protected File $file,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Delete customer data file
     *
     * @return this
     */
    public function deleteCustomerDataFile()
    {
        try {
            $directoryPath = $this->fileSystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath() . static::CUSTOMERDATA_DIRECTORY;
            $isDirectory = $this->fileSystem
            ->getDirectoryWrite(DirectoryList::MEDIA)
            ->isDirectory(static::CUSTOMERDATA_DIRECTORY);
            $nfrDeleteOperationEnabled = $this->toggleConfig
            ->getToggleConfigValue('xmen_B2041064_NFR_delete_operation');
            if (!$isDirectory) {
                return false;
            }
            if ($nfrDeleteOperationEnabled) {
                $this->file->deleteDirectory($directoryPath);
                return true;
            } else {
                $csvFiles = $this->file->readDirectory($directoryPath);
                
                if (!empty($csvFiles)) {
                    foreach ($csvFiles as $csvFile) {
                        if ($this->file->isExists($csvFile)) {
                            $this->file->deleteFile($csvFile);
                        }
                    }
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ': Error while deleting customer data file by cron '
                . $e->getMessage()
            );
        }

        return false;
    }
}
