<?php
/**
 * @category    Fedex
 * @package     Fedex_CIDPSG
 * @copyright   Copyright (c) 2023 Fedex
 */

namespace Fedex\CIDPSG\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class use to delete authorize excel file
 */
class DeleteAuthorizedExcelFile
{
    public const AUTHORIZED_CSV_DIRECTORY = 'cidpsg';

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
     * Delete authorize excel file
     *
     * @return this
     */
    public function deleteAuthorizedExcelFile()
    {
        try {
            $directoryPath = $this->fileSystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath() . static::AUTHORIZED_CSV_DIRECTORY;
            $isDirectory = $this->fileSystem
            ->getDirectoryWrite(DirectoryList::MEDIA)
            ->isDirectory(static::AUTHORIZED_CSV_DIRECTORY);
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
                __METHOD__ . ':' . __LINE__. ': Error while deleting authorize excel file by cron '
                . $e->getMessage()
            );
        }

        return false;
    }
}
