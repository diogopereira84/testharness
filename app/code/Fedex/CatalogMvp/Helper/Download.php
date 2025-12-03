<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Download extends AbstractHelper
{
    public const CREATE_ZIP_API_URL = 'fedex/general/create_zip_download_file_api_url';

    /**
     * Download Class Constructor.
     *
     * @param Context $context
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        protected ScopeConfigInterface $scopeConfigInterface,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        protected LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Get create zip api url
     * 
     * @return string
     */
    public function getCreateZipApiUrl()
    {
        return (string) $this->scopeConfig->getValue(
            static::CREATE_ZIP_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Call create zip api to get
     * download file URL
     * 
     * @param string $productName
     * @param json $externalProd
     */
    public function getDownloadFileUrl($productName, $externalProd)
    {
        $productName = str_replace(" ", "_", $productName);
        $productName = $this->generateFileName($productName);
        $productName = strtolower($productName);
        $productName = trim($productName,"_");
        if(!$productName) {
            $productName = "download";
        }
        $request = $this->prepareRequestForZipApi(
            $productName, $externalProd);
        $createZipApiUrl = $this->getCreateZipApiUrl();
        $response = $this->callCreateZipApi($createZipApiUrl, $request);

        return $response;
    }

    /**
     * @param string $productName
     * @return string
     */
    public function generateFileName($productName)
    {
        return preg_replace("/[^a-zA-Z0-9\_]+/", "", trim($productName));
    }

    /**
     * Prepare request to send in create
     * zip api
     * 
     * @param string $productName
     * @param json $externalProd
     * 
     * @return array
     */
    public function prepareRequestForZipApi($productName, $externalProd)
    {
        $externalProd = json_decode((string) $externalProd, true);
        $sourceDocument = [];

        if (isset($externalProd['contentAssociations']) && is_array($externalProd['contentAssociations'])) {
            $contentAssociation = $externalProd['contentAssociations'];

            foreach($contentAssociation as $contentData) {
                if (isset($contentData['parentContentReference'])) {
                    $sourceDocument[] = [
                      "documentId"=> $contentData['parentContentReference'],
                      "pathEntry"=> $productName
                    ]; 
                }   
            }
        }

        $requestData = [
            "zipDocumentName" => $productName,
            "expiration" => [
            "units" => "SECONDS",
            "value" => 3600
            ],
            "sourceDocumentsPathEntries" => $sourceDocument
        ];

        return ["zipRequest" => $requestData];
    }

    /**
     * Call create zip api to get download url
     * 
     * @param string $createZipApiUrl
     * @param array $prepareRequest
     * 
     * @return string
     */
    public function callCreateZipApi($createZipApiUrl, $prepareRequest)
    {
        try {
            $tazRequired = true;
            $response = $this->catalogDocumentRefranceApi->curlCall(
                $prepareRequest, $createZipApiUrl, 'POST', $tazRequired);
            $documentUrl = null;
            if (!empty($response) && !empty($response['output']) && !empty($response['output']['document']) && isset($response['output']['document']['documentURL'])) {
                $documentUrl = $response['output']['document']['documentURL'];
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Create zip api request and response: ' . json_encode($prepareRequest) . json_encode($response));
            }

            return $documentUrl;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .' Create zip api not working: ' . $e->getMessage());
        }
    }
}
