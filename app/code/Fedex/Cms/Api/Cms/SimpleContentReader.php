<?php

declare(strict_types=1);

namespace Fedex\Cms\Api\Cms;

use Magento\Framework\Filesystem\Io\File as FileReader;
use Magento\Framework\Module\Dir\Reader as DirReader;
use Psr\Log\LoggerInterface;

class SimpleContentReader
{
    /**
     * Folder with html files for CMS Block
     */
    public const CMS_CONTENT_FOLDER = 'content';

    /**
     * SimpleContentReader constructor.
     * @param DirReader $dirReader
     * @param FileReader $fileReader
     * @param LoggerInterface $logger
     */
    public function __construct(
        /**
         * Dir Reader instance
         */
        private DirReader $dirReader,
        private FileReader $fileReader,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Get File Content
     *
     * @param string $fileName
     * @return string
     */
    public function getContent($fileName)
    {
        try {
            $contentDir = $this->dirReader->getModuleDir(false, 'Fedex_Cms') .
                DIRECTORY_SEPARATOR .
                self::CMS_CONTENT_FOLDER .
                DIRECTORY_SEPARATOR;
            $filePath = $contentDir . $fileName;
            if ($this->fileReader->fileExists($filePath)) {
                return $this->fileReader->read($filePath);
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' File path does not exist.');
                return '';
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Unable to get file content. ' . $e->getMessage());
        }
    }
}
