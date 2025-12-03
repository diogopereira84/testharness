<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FXOCMConfigurator\Controller\Newdocapi;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

class Index extends \Magento\Framework\App\Action\Action
{
    public $logger;
    protected FileDriver $fileDriver;

    public const ERRORS = 'Errors';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param Filesystem $filesystem
     * @param FileDriver $fileDriver
     * @param ToggleConfig $toggleConfig
     */

    public function __construct(
        Context $context,
        protected JsonFactory $jsonFactory,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        protected Filesystem $filesystem,
        FileDriver $fileDriver,
        protected ToggleConfig $toggleConfig
    )
    {
        $this->fileDriver = $fileDriver;
        return parent::__construct($context);
    }

    /**
     * Function to get image from new document API
     */

    public function execute()
    {
        $json = $this->jsonFactory->create();
        $documentId = $this->getRequest()->getParam('imageId');
        try {
            // B-2421984 remove preview API calls from Catalog and any other flows
            if ($this->toggleConfig->getToggleConfigValue('tech_titans_b_2421984_remove_preview_calls_from_catalog_flow')){
                $previewImageUrl = $this->catalogDocumentRefranceApi->getPreviewImageUrl($documentId);
                $imageData = $this->fileDriver->fileGetContents($previewImageUrl);
                if ($imageData === false) {
                    throw new Exception('Failed to fetch image from URL: ' . $previewImageUrl);
                }
            } else {
                $imageData = $this->catalogDocumentRefranceApi->curlCallForPreviewApi($documentId);
            }
            $base64ImageSrc = base64_encode($imageData);
            $output = [];
            $output['successful']= true;
            $output['output']['imageByteStream']= $base64ImageSrc;
        } catch (\Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error found no data from New document API: ' . $error->getMessage());
            $output = [self::ERRORS => "Error found no data from New document API"  . $error->getMessage()];
        }

        return $json->setData($output);
    }
}
