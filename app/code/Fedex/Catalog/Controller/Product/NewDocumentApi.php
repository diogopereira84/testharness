<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Catalog\Controller\Product;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\Exception\LocalizedException;

class NewDocumentApi implements HttpPostActionInterface
{
    public const ERRORS = 'errors';

    /**
     * NewDocumentApi Constructor
     */
    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly Curl $curl,
        private readonly RequestInterface $request,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger,
        private readonly UploaderFactory $uploaderFactory,
        private readonly Filesystem $filesystem,
        private readonly DateTime $dateTime,
        private readonly PunchoutHelper $punchoutHelper,
        private readonly HeaderData $headerData,
    ) {
    }

    /**
     * Execute Method.
     */
    public function execute()
    {
        $supportedTypes = $this->scopeConfig->getValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_supported_types'
        );
        $supportedTypes = explode(",", $supportedTypes);
        $allowedExtensions = [];
        foreach ($supportedTypes as $type) {
            $allowedExtensions[] = trim($type);
        }
        $productSku = $this->request->getParam('sku');
        
        if (isset($this->request->getFiles('filepath')['name']) && $this->request->getFiles('filepath')['name'] != '') {
            try {
                $uploaderFactories = $this->uploaderFactory->create(['fileId' => 'filepath']);
                $uploaderFactories->setAllowedExtensions($allowedExtensions);
                $uploaderFactories->setAllowRenameFiles(true);
                $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                $destinationPath = $mediaDirectory->getAbsolutePath(DirectoryList::TMP);
                $result = $uploaderFactories->save($destinationPath);
                
                if (!$result) {
                    $this->logger->error(
                        "File could not be saved for the product sku: ". $productSku.' with file name: '
                        .$this->request->getFiles('filepath')['name']
                    );

                    $documentApiResponse = [
                        self::ERRORS => "Error found while saving files with request change files for product sku: "
                        .$productSku
                    ]; 

                    throw new LocalizedException(
                        __('File cannot be saved to path: $1', $destinationPath)
                    ); 
                } else {
                    $documentApiResponse = $this->callNewDocumentApi($result, $productSku);
                }
            } catch (\Exception $error) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ .
                    "Error found while uploading request change files for product sku: "
                    .$productSku ." is: ".$error->getMessage()
                );

                $documentApiResponse = [
                    self::ERRORS => "Error found while uploading request change files for product sku: "
                    .$productSku ." is: "  .$error->getMessage()
                ];
            }
        }
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($documentApiResponse);
    }

    /**
     * Call new document API
     *
     * @param array $result
     * @param string $productSku
     */
    public function callNewDocumentApi($result, $productSku)
    {
        $apiUrl = $this->scopeConfig->getValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_api_url'
        );
        $expirationDays = $this->scopeConfig->getValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_expiration'
        );
        $checkPdf = $this->scopeConfig->getValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_check_pdf'
        );
        $headers = [
            "Content-Type: multipart/form-data",
            $this->headerData->getAuthHeaderValue() . $this->punchoutHelper->getAuthGatewayToken()
        ];
        
        $fileNameWithFullPath = $result['path'] . '/' . $result['file'];

        $expirationTime = [
            "units" => "DAYS",
            "value" => $expirationDays
        ];
        $postFields = [
            'document'     => new \CURLFile($fileNameWithFullPath),
            'documentName' => $result['name'],
            'checkPdfForm' => $checkPdf,
            'expiration'   => json_encode($expirationTime) // 30 days
        ];
        // set current date as well in API response obj that would be used to store in workspace
        try {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_ENCODING => '',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => $postFields,
                    CURLOPT_HTTPHEADER => $headers
                ]
            );
            $this->curl->post($apiUrl, $postFields);
            $output = $this->curl->getBody();
            $outputData = json_decode($output, true);

            if (isset($outputData['output']['document'])) {
                // Append current date time for tracking in workspace
                $outputData['output']['document']['currentDateTime'] 
                    = $this->dateTime->gmtDate();
            }

            return $outputData;
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Exception occurred while calling New Document API for product sku: '
                .$productSku.' with replace files: ' .$e->getMessage()
            );
        }

        return [];
    }
}
