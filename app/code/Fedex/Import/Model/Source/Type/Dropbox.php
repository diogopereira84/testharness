<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model\Source\Type;

use Psr\Log\LoggerInterface;

class Dropbox extends AbstractType
{
    public $_metadata;
    /**
     * @var string
     */
    protected $code = 'dropbox';

    /**
     * @var $directory
     */
    protected $directory;

    /**
     * @var null
     */
    protected $accessToken = null;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Function to upload source file
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        $sourceFilePath = $this->getData($this->code . '_file_path');
        $fileName = basename((string)$sourceFilePath);
        $filePath = $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
        try {
            $dirname = dirname($filePath);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Cannot create local file /var/import/dropbox. Please check file permissions.');
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    "Can't create local file /var/import/dropbox'. Please check files permissions. "
                    . $e->getMessage()
                )
            );
        }
        try {
            $fileContent = $this->downloadFile($sourceFilePath);
            file_put_contents($filePath, $fileContent);
            if ($fileContent) {
                return $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            }
        }catch(\Exception $e){
            //@codeCoverageIgnoreStart
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' File not found on Dropbox.');
            throw new \Magento\Framework\Exception\LocalizedException(__("File not found on Dropbox"));
        }   //@codeCoverageIgnoreEnd
    }

    /**
     * Function to import image
     *
     * @param string $importImage
     * @param string $imageSting
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $this->setUrl($importImage, $imageSting, $matches);
        } else {
            $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            $sourceDir = $this->getData($this->code . '_import_images_file_dir');
            //@codeCoverageIgnoreStart
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            //@codeCoverageIgnoreEnd
            try {
                $fileContent = $this->downloadFile($sourceDir . $importImage);
                file_put_contents($filePath, $fileContent);
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Dropbox API Exception: ' . $e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    "Dropbox API Exception: " . $e->getMessage()
                ));
            }
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     * @codeCoverageIgnore
     * @return bool|int
     */
    public function checkModified($timestamp)
    {

        $sourceFilePath = $this->getData($this->code . '_file_path');

        if (!$this->_metadata) {
            $this->_metadata = $this->getMetadata($sourceFilePath);
        }
        $modified = strtotime($this->_metadata['client_modified']);

        return ($timestamp != $modified) ? $modified : false;
    }

    /**
     * Set access token
     *
     * @param string $token
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * Get Source client
     *
     * @return bool
     */
    protected function getSourceClient()
    {
        $this->client = false;
        return $this->client;
    }

    /**
     * Get file content from dropbox
     *
     * @param string $filePath
     * @codeCoverageIgnore
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function downloadFile($filePath)
    {
        $url = 'https://content.dropboxapi.com/2/files/download';

        $resource = curl_init($url);

        curl_setopt($resource, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getData($this->code . '_access_token'),
            'Dropbox-API-Arg: {"path": "' . $filePath . '"}'
        ]);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($resource);
        curl_close($resource);

        if ($json = json_decode($result, true)) {
            if (!empty($json['error']['.tag'])) {
                $tag = $json['error']['.tag'];
                if ($tag == 'invalid_access_token') {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid Dropbox access token.');
                    $error = "Invalid Dropbox access token";
                } elseif ($tag == 'path') {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' File not found on Dropbox: ' . $filePath);
                    $error = "File not found on Dropbox: " . $filePath;
                } else {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Dropbox api error: ' . $result);
                    $error = "Dropbox api error: " . $result;
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($error));
            }
        }

        if ($result) {
            return $result;
        }

        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Cannot get file content from Dropbox.');

        return false;
    }

    /**
     * Get file metadata
     *
     * @param string $filePath
     *
     * @return bool|mixed
     */
    protected function getMetadata($filePath)
    {
        $url = 'https://api.dropboxapi.com/2/files/get_metadata';

        $resource = curl_init($url);

        curl_setopt($resource, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getData($this->code . '_access_token'),
            'Content-Type: application/json',
        ]);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_POST, true);
        curl_setopt($resource, CURLOPT_POSTFIELDS, '{"path": "' . $filePath . '"}');
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($resource);
        curl_close($resource);

        if ($result) {
            return json_decode($result, true);
        }

        //@codeCoverageIgnoreStart
        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Cannot get file metadata.');
        return false;
        //@codeCoverageIgnoreEnd
    }
}
