<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;

class ContentReader
{
    /**
     * ContentReader constructor.
     * @param File $file
     * @param Reader $reader
     * @param LoggerInterface $logger
     */
    public function __construct(
        private File $file,
        private Reader $reader,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * Get File Content
     *
     * @param string $fileName
     * @return string
     */
    public function getContent(string $fileName) : string
    {
        $content = "";
        try {
            $filePath = $this->reader->getModuleDir(false, 'Fedex_Canva') .
                DIRECTORY_SEPARATOR . 'Setup' .
                DIRECTORY_SEPARATOR . 'data' .
                DIRECTORY_SEPARATOR . $fileName;
            if ($this->file->fileExists($filePath)) {
                $content = $this->file->read($filePath);
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' File path does not exist.');
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
        }
        return $content;
    }
}
