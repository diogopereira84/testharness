<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model\Source\Type;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;

/**
 * Abstract class for import source types
 */
abstract class AbstractType extends DataObject
{

    /**
     * Temp directory for downloaded files
     */
    protected const IMPORT_DIR = 'var/import';

    /**
     * Temp directory for downloaded images
     */
    protected const MEDIA_IMPORT_DIR = 'pub/media/import';

    /**
     * @var mixed
     */
    protected $client;

    /**
     * Source type code
     * @var string
     */
    protected $code;

    /**
     * Prepare temp dir for import files
     *
     * @return string
     */
    protected function getImportPath()
    {
        return self::IMPORT_DIR . '/' . $this->code;
    }

    /**
     * Prepare temp dir for import images
     *
     * @return string
     */
    protected function getMediaImportPath()
    {
        return self::MEDIA_IMPORT_DIR . '/' . $this->code;
    }

    /**
     * Get file path
     *
     * @return bool|string
     */
    public function getImportFilePath()
    {
        //@codeCoverageIgnoreStart
        if ($sourceType = $this->getImportSource()) {
            return $this->getData($sourceType . '_file_path');
        }
        //@codeCoverageIgnoreEnd
        return false;
    }

    /**
     * Get source type code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get Client
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set client
     *
     * @param mixed $client
     * @return mixed
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * Upload source
     */
    abstract protected function uploadSource();

    /**
     * Import Image
     *
     * @param string|mixed $importImage
     * @param string $imageSting
     */
    abstract protected function importImage($importImage, $imageSting);

    /**
     * Check modified
     *
     * @param string|mixed $timestamp
     */
    abstract protected function checkModified($timestamp);

    /**
     * Get source client
     */
    abstract protected function getSourceClient();
}
