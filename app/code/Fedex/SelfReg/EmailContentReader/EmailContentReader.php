<?php
namespace Fedex\SelfReg\EmailContentReader;

use Magento\Framework\Filesystem\Io\File as FileReader;
use Magento\Framework\Module\Dir\Reader as DirReader;
use Psr\Log\LoggerInterface;

class EmailContentReader
{
    /**
     * Folder with html files for CMS Block
     */
    const CMS_CONTENT_FOLDER = 'EmailContentReader/emailContent';

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
     * @param string $fileName,
     * @return string
     */
    public function getContent($fileName)
    {
        try {
            $contentDir = $this->dirReader->getModuleDir(false, 'Fedex_SelfReg') .
                DIRECTORY_SEPARATOR .
                self::CMS_CONTENT_FOLDER .
                DIRECTORY_SEPARATOR;
            $filePath = $contentDir . $fileName;
            if ($this->fileReader->fileExists($filePath)) {
                return $this->fileReader->read($filePath);
            } else {
                return '';
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
