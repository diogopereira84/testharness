<?php

namespace Fedex\CatalogMvp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data;
use \Magento\Catalog\Model\ProductFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Catalog\Model\ProductRepository;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Model\DocRefMessage;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CatalogMvp\HTTP\Client\Curl as FedexCurl;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\App\ResourceConnection;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

class CatalogDocumentRefranceApi extends AbstractHelper
{
    const DOCUMENT_API_URL   = "fedex/catalogmvp/document";
    const TIME_ZONE = "America/Los_Angeles";
    const EXTEND_DOCUMENT_LIFE_DAYS         = 180; //6 month
    const PREVIEW_API_URL   = "fedex/catalogmvp/preview_api_url";
    const STORE_CODE = 'ondemand';

    protected $fedexDelete;
    protected $fedexDeleteCurl;

    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        protected ScopeConfigInterface $scopeConfigInterface,
        protected Data $punchoutHelper,
        protected ProductFactory $productFactory,
        protected Curl $curl,
        protected ProductRepository $productRepository,
        protected CatalogMvp $catalogMvp,
        protected LoggerInterface $logger,
        protected Json $serializerJson,
        protected DocRefMessage $message,
        protected PublisherInterface $publisher,
        FedexCurl $fedexDelete,
        protected CollectionFactory $productCollectionFactory,
        protected HeaderData $headerData,
        protected ResourceConnection $resourceConnection,
        protected CompanyFactory $companyFactory,
        protected CustomerFactory $customerFactory,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected SharedCatalogRepositoryInterface $sharedCatalogRepository,
        protected ProductItemRepositoryInterface $sharedCatalogItemRepository,
        protected FilterBuilder $filterBuilder,
        protected CategoryRepositoryInterface $categoryRepository,
        protected CompanyRepositoryInterface $companyRepository,
        protected CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->fedexDeleteCurl = $fedexDelete;
    }

    /**
     * Get Current product is POD Edit able
     *
     * @return boolean
     */
    public function getExtendDocumentLifeForPodEitableProduct()
    {
        try {
            $rabbitMqJson = [];
            $toDate = date("Y-m-d") . ' 23:59:59';
            $toDate = date('Y-m-d H:i:s', strtotime($toDate. ' + 2 days'));
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->getSelect()->where("pod2_0_editable = 1 AND `e`.`product_document_expire_date` < '" . $toDate . "'");
            if ($productCollection) {
                foreach ($productCollection->getData() as $product) {
                    $productId = $product['entity_id'];
                    $productObj = $this->productRepository->getById($productId);
                    if ($productObj) {
                        $externalProductData = $productObj->getExternalProd();
                        if ($externalProductData) {
                            $externalProd = json_decode((string)$externalProductData, true);
                            if (isset($externalProd['contentAssociations'])) {
                                $arrProData = (array) $externalProd['contentAssociations'];
                                foreach ($arrProData as $proData) {
                                    if (array_key_exists('contentReference', $proData)) {
                                        $rabbitMqJson[] = [
                                            'documentId' => $proData['contentReference'],
                                            'produtId' => $productId,
                                        ];
                                    }
                                }
                            }
                        }
                        $customizationFields = $productObj->getData('customization_fields');
                        if ($customizationFields) {
                            $customDocProductData = json_decode((string)$customizationFields, true);
                            foreach ($customDocProductData as $customProdData) {
                                if (array_key_exists('documentId', $customProdData)) {
                                    $rabbitMqJson[] = [
                                        'documentId'=> $customProdData['documentId'],
                                        'productId' => $productId
                                    ];
                                }
                                // Form Fields Document ID
                                if (isset($customProdData['formFields']) && is_array($customProdData['formFields'])) {
                                    foreach ($customProdData['formFields'] as $formField) {
                                        if (isset($formField['documentId'])) {
                                            $rabbitMqJson[] = [
                                                'documentId' => $formField['documentId'],
                                                'productId' => $productId
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
            $this->message->setMessage($rabbitMqStr);
            $this->publisher->publish('docRefExtandExpire', $this->message);
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . __('Erro while extend document life: ') . ' ' . $error->getMessage()
            );
        }

        return true;
    }

    /**
     * Cron Method: Check if product has content association and expiry date to be expire
     *
     * @return boolean
     */
    public function extendDocumentLifeForProducts()
    {
        try {
            $rabbitMqJson = [];
            $toDate = date("Y-m-d") . ' 23:59:59';
            $toDate = date('Y-m-d H:i:s', strtotime($toDate. ' + 2 days'));
            $productCollection = $this->productCollectionFactory->create();

            $d209736Toggle = $this->toggleConfig
                ->getToggleConfigValue('techtitans_D209736_migrated_document_expire_date_null_fix');

            if($this->toggleConfig->getToggleConfigValue('techTitans_customDocExpiry_fix')) {
                $fromDate = date("Y-m-d") . ' 00:00:00';
                $productCollection->getSelect()->where("`e`.`product_document_expire_date` BETWEEN '" . $fromDate ."' AND '" . $toDate . "'");
                // Fix issue D-209736: if is_document_expire already set then that case document will not be go for extends date
                if ($d209736Toggle) {
                    $productCollection->getSelect()->where("`e`.`is_document_expire` != 1");
                }
            } else {
                $productCollection->getSelect()->where("`e`.`product_document_expire_date` < '" . $toDate . "'");
            }

            if ($productCollection) {
                if ($d209736Toggle) {
                    $chunks = array_chunk($productCollection->getData(), 50); // Split into chunks of 50
                    foreach ($chunks as $chunk) {
                        foreach ($chunk as $product) {
                            $productData = $this->getDocumentsJson($product['entity_id']);
                            if (!empty($productData)) {
                                $rabbitMqJson[] = $productData;
                            }
                        }

                        if (!empty($rabbitMqJson)) {
                            $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);;
                            $this->message->setMessage($rabbitMqStr);
                            $this->publisher->publish('docRefExtandExpire', $this->message);
                            $this->logger->info(__METHOD__ . ':' . __LINE__ . __(' Documents queued for extend document life: ' . $rabbitMqStr));
                        }
                        $rabbitMqJson = [];
                        usleep(100000);
                    }
                } else {
                foreach ($productCollection->getData() as $product) {
                    $rabbitMqJson[] = $this->getDocumentsJson($product['entity_id']);
                }

            if (!empty($rabbitMqJson)) {
                $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
                $this->message->setMessage($rabbitMqStr);
                $this->publisher->publish('docRefExtandExpire', $this->message);
            }
                }
            }

        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                    . __(' Error while extend document life with cron: ') . ' ' . $error->getMessage()
            );
        }

        return true;
    }

    /**
     * Get Documents Json Data
     *
     * @param int $productId
     * @return array
     */
    public function getDocumentsJson($productId)
    {
        $productDocumentIdsJson = [];
        if ($this->toggleConfig->getToggleConfigValue('techtitans_D209736_migrated_document_expire_date_null_fix')) {
            try {
                $product = $this->productFactory->create()->load($productId);
                $externalProductData = $this->getExternalProductData($product);
                $productDocumentIdsJson = array_merge($productDocumentIdsJson, $externalProductData);
                $customizationData = $this->getCustomizationFieldsData($product);
                $productDocumentIdsJson = array_merge($productDocumentIdsJson, $customizationData);
            } catch (LocalizedException $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . __(
                    'Magento Error in getDocumentsJson: %1',
                    [$e->getMessage()]
                ));
            } catch (\Exception $e) {
                $this->logger->critical(__('Error in getDocumentsJson for ProductId: %1 - %2', [$productId, $e->getMessage()]));
            }

            return $productDocumentIdsJson;
        } else {
            $productObj = $this->productFactory->create()->load($productId);
            $externalProductData =  $productObj->getExternalProd();

        if ($externalProductData) {
            $externalProd = json_decode((string)$externalProductData, true);
            if (isset($externalProd['contentAssociations'])) {
                $arrProData = (array) $externalProd['contentAssociations'];
                foreach ($arrProData as $proData) {
                    if (array_key_exists('contentReference', $proData)) {
                        $productDocumentIdsJson[] = [
                            'documentId' => $proData['contentReference'],
                            'produtId' => $productId
                        ];
                    }
                }
            }
        }

            if($this->toggleConfig->getToggleConfigValue('techTitans_customDocExpiry_fix')) {
                $customizationFields = $productObj->getData('customization_fields');
                if ($customizationFields) {
                    $customDocProductData = json_decode((string)$customizationFields, true);
                    if($customDocProductData == null){
                        $this->logger->critical(
                            __METHOD__ . ':' . __LINE__
                            . $productObj->getId().' '. $productObj->getData('customization_fields')
                        );
                    }
                    foreach ($customDocProductData as $customProdData) {
                            if (is_array($customProdData) && array_key_exists('documentId', $customProdData)) {
                            $productDocumentIdsJson[] = [
                                'documentId'=> $customProdData['documentId'],
                                'produtId' => $productId
                            ];
                        }
                        // Form Fields Document ID
                        if (isset($customProdData['formFields']) && is_array($customProdData['formFields'])) {
                            foreach ($customProdData['formFields'] as $formField) {
                                if (isset($formField['documentId'])) {
                                    $productDocumentIdsJson[] = [
                                        'documentId' => $formField['documentId'],
                                        'produtId' => $productId
                                    ];
                                }
                            }
                        }
                    }
                }

            }

            return $productDocumentIdsJson;
        }
    }

    /**
     * Get product ExternalData
     * @param object $product
     *
     * @return array
     */
    private function getExternalProductData($product): array
    {
        $productDocumentIdsJson = [];

        $externalProductData = $product->getExternalProd();
        if ($externalProductData) {
            $externalProd = json_decode((string)$externalProductData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LocalizedException(__(
                    'Error decoding external product data for product ID: %1 - %2',
                    [$product->getId(), json_last_error_msg()]
                ));
            }

            if (!empty($externalProd['contentAssociations']) && is_array($externalProd['contentAssociations'])) {
                foreach ($externalProd['contentAssociations'] as $proData) {
                    if (is_array($proData) && array_key_exists('contentReference', $proData) && !empty($proData['contentReference'])) {
                        $productDocumentIdsJson[] = [
                            'documentId' => $proData['contentReference'],
                            'produtId' => $product->getId()
                        ];
                    }
                }
            }
        }

        return $productDocumentIdsJson;
    }

    /**
     * Get product customization Feilds Data
     * @param object $product
     *
     * @return array
     */
    private function getCustomizationFieldsData($product): array
    {
        $productDocumentIdsJson = [];
        $customizationFields = $product->getData('customization_fields');

        if ($customizationFields) {
            $customDocProductData = json_decode((string)$customizationFields, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LocalizedException(__('Error decoding customization fields for product ID: %1 - %2',
                    [$product->getId(), json_last_error_msg()]
                ));
            }

            foreach ($customDocProductData as $customProdData) {
                if (is_array($customProdData) && array_key_exists('documentId', $customProdData) && !empty($customProdData['documentId'])) {
                    $productDocumentIdsJson[] = [
                        'documentId' => $customProdData['documentId'],
                        'produtId' => $product->getId()
                    ];
                }

                if (isset($customProdData['formFields']) && is_array($customProdData['formFields'])) {
                    foreach ($customProdData['formFields'] as $formField) {
                        if (isset($formField['documentId']) && !empty($formField['documentId'])) {
                            $productDocumentIdsJson[] = [
                                'documentId' => $formField['documentId'],
                                'produtId' => $product->getId()
                            ];
                        }
                    }
                }
            }
        }

        return $productDocumentIdsJson;
    }



    /**
     * UpdateProductDocumentEndDate
     *
     * @return boolean
     */
    public function updateProductDocumentEndDate($product, $mode='admin')
    {
        $produtId = $product->getId();
        $isPodEditable = $this->catalogMvp->isProductPodEditAbleById($produtId);
        if ($isPodEditable || $mode == 'customer_admin') {
            $endDate = date("Y-m-d H:i:s");
            $this->setProductDocumentExpireDate($endDate, $produtId);
        }
        return true;
    }

    /**
     * DocumentLifeExtendApiCall
     *
     * @throws Exception
     * @return mixed
     */
    public function documentLifeExtendApiCall($documentId, $productId)
    {
        try {
            if ($documentId) {
                $apiRequestData = [
                    'documentId' => $documentId,
                    'expiration' => [
                        'units' => 'DAYS',
                        'value' => self::EXTEND_DOCUMENT_LIFE_DAYS
                    ],
                ];

                if ($this->toggleConfig->getToggleConfigValue('maegeeks_d172040_fix')) {
                    $apiRequestData['expiration']['value'] = 30;
                }
                $setupURL = $this->getApiUrl(self::DOCUMENT_API_URL) . 'v2/extenddocumentlife';
                $response = $this->curlCall($apiRequestData, $setupURL, 'POST');
                $product = $this->getProductObjectById($productId);

                // Fix issue D-209736
                $expirationDocumentDateNullToggle = $this->toggleConfig->getToggleConfigValue(
                    'techtitans_D209736_migrated_document_expire_date_null_fix'
                );
                // Fix issue D-209736; If document already expired and removed from system that case it will no more retry
                if ($expirationDocumentDateNullToggle &&
                    !empty($response) &&
                    !empty($response['output']['alerts']) &&
                    in_array("ERROR.METADATA.RETRIEVEFAILED", array_column($response['output']['alerts'], 'code'))
                ) {
                    $product->setIsDocumentExpire(1);
                    $this->productRepository->save($product);
                    return;
                }

                if (!empty($response)
                    && array_key_exists('output', $response)
                    && array_key_exists('document', $response['output'])
                    && array_key_exists('expirationTime', $response['output']['document'])
                    && $product) {
                    $documentExpiryDate = $response['output']['document']['expirationTime'];
                    $product->setProductDocumentExpireDate(date("Y-m-d H:i:s", strtotime($documentExpiryDate)));
                    $this->productRepository->save($product);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . __(' Document life extended for productID: ' . $productId));
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__ . ' ' . __(
                            'Document life extended for productID: %1, and expiration date is %2',
                            [$productId, $documentExpiryDate]
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ': Extend Document Life Cron failed for document ID: ' .$documentId. ' and product ID: ' .$productId. ' with message :'  . $e->getMessage());
        }
    }

    /**
     * DocumentLifeExtendApiCall
     *
     * @throws Exception
     * @return mixed
     */
    public function documentLifeExtendApiCallWithDocumentId($documentId)
    {
        try {
            if ($documentId) {
                $apiRequestData = [
                    'documentId' => $documentId,
                    'expiration' => [
                        'units' => 'DAYS',
                        'value' => self::EXTEND_DOCUMENT_LIFE_DAYS
                    ],
                ];
                if ($this->toggleConfig->getToggleConfigValue('maegeeks_d172040_fix')) {
                    $apiRequestData['expiration']['value'] = 30;
                }
                $setupURL = $this->getApiUrl(self::DOCUMENT_API_URL) . 'v2/extenddocumentlife';
                $response = $this->curlCall($apiRequestData, $setupURL, 'POST');

                return $response;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ': Extend Document Life Cron failed for document ID: ' .$documentId. ' with message:'  . $e->getMessage());
        }
    }

    /**
     * Add refernce to the document id
     */
    public function addRefernce($documentId, $produtId)
    {
        try {
            $apiRequestData = [
                "references" => [
                    "POD2.0:Catalog:" . $produtId
                ]
            ];
            $setupURL = $this->getApiUrl(self::DOCUMENT_API_URL);
            $setupURL = $setupURL . 'v2/documents/' . $documentId . '/references';

            $rabbitMqJson = ['apiRequestData' => $apiRequestData, 'setupUrl' => $setupURL, 'method' => 'POST'];
            $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
            $this->message->setMessage($rabbitMqStr);
            $this->publisher->publish('docRefAdd', $this->message);
        } catch (\Exception $e) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Add reference API :'  . $e->getMessage());
        }
    }

    /**
     * Read zip file content
     * @param string $downloadUrl
     * @return string
     */
    public function readZipFileContent($downloadUrl)
    {
        $downloadUrl = base64_decode($downloadUrl);
        $this->logger->info("\n".__METHOD__ . ':' . __LINE__ . ':Reading Zip File');
        $this->logger->info("\n".__METHOD__ . ':' . __LINE__ . 'Decoded Download Url:'.$downloadUrl);
        $tazToken = $this->punchoutHelper->getTazToken();
        $headers = [];
        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        $headers = [
            "Cookie: Bearer=". $tazToken,
            "client_id:". $gateWayToken
        ];
        $this->logger->info("\n".__METHOD__ . ':' . __LINE__ . ':Api Headers:'.print_r($headers,true));
        $this->curl->setOptions(
            [
                CURLOPT_VERBOSE => 1,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_AUTOREFERER => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => $headers,
            ]
        );
        $this->curl->get($downloadUrl);
        return $this->curl->getBody();
    }

    /**
     * Function to run the curl
     */
    public function curlCall($apiRequestData, $setupURL, $method, $tazRequired = false)
    {
        $dataString = json_encode($apiRequestData);
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $accessToken = $this->punchoutHelper->getAuthGatewayToken();
        if ($method=="DELETE") {
            //$authorization = 'Bearer ' . $accessToken;
            $this->fedexDeleteCurl->addHeader($authHeaderVal, $accessToken);
            $this->fedexDeleteCurl->delete($setupURL);
            return $this->fedexDeleteCurl->getBody();
        } else {
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Content-Length: " . strlen($dataString),
                $authHeaderVal . $accessToken
            ];

            if ($tazRequired) {
                $tazToken = $this->punchoutHelper->getTazToken();
                $headers[] = "Cookie: Bearer=" . $tazToken;
            }

            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );
            $this->curl->post($setupURL, $dataString);
            $output = $this->curl->getBody();
            return json_decode($output, true);
        }
    }

    /**
     * Function to get API url
     * @return String
     */
    public function getApiUrl($urlConfig)
    {
        return $this->scopeConfigInterface->getValue($urlConfig);
    }

    /**
     * Function to get document id from external product
     * @return array
     */
    public function getDocumentId($extrenalProductData)
    {
        $documentId = [];
        if ($extrenalProductData) {
            $externalProd = json_decode($extrenalProductData, true);
            $contentAssociations = $externalProd['contentAssociations'] ?? [];
            foreach ($contentAssociations as $contentAssociation) {
                $documentId[] = $contentAssociation['parentContentReference'];
                $documentId[] = $contentAssociation['contentReference'];
            }
        }
        return $documentId;
    }

    /**
     * Function to get product object
     * @return string
     */
    public function getProductObjectById($produtId)
    {
        try {
            return $this->productRepository->getById($produtId);
        } catch (\Exception $error) {
            $this->logger->critical(
                __(' Error while product retrival by id: ') . __FILE__ . ':' . __METHOD__ . ' ' . $error->getMessage()
            );
        }
        return false;
    }

    /**
     * Function to get deleteProductRef product
     * @return string
     */
    public function deleteProductRef(int $productId, $documentId)
    {
        try {
            if (!empty($documentId)) {
                $setupURL = $this->getApiUrl(self::DOCUMENT_API_URL);
                $setupURL = $setupURL . 'v2/documents/' . $documentId . '/references?reference=POD2.0:Catalog:' . $productId;
                $apiRequestData = [
                    'documentId' => $documentId,
                ];
                $rabbitMqJson[] = [
                    'api_request_data' => $apiRequestData,
                    'setupURL' => $setupURL,
                    'method' => "DELETE"
                ];
                $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
                $this->message->setMessage($rabbitMqStr);
                $this->publisher->publish('docRefDelete', $this->message);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Function to run the curl For Preview
     */
    public function curlCallForPreviewApi($documentId)
    {
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_b_2421984_remove_preview_calls_from_catalog_flow')){
            return true;
        }
        $setupURL = $this->getApiUrl(self::PREVIEW_API_URL);
        $setupURL = $setupURL.'v2/documents/'.$documentId.'/previewpages/1';
        $tazToken = $this->punchoutHelper->getTazToken();
        $headers = [];
        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        $headers = [
            "Cookie: Bearer=". $tazToken,
            "client_id:". $gateWayToken
        ];
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );
        $this->curl->get($setupURL);
        return $this->curl->getBody();
    }

    /**
     * Function to get preview image Url
     */
    public function getPreviewImageUrl($documentId)
    {
        $apiURL = $this->getApiUrl(self::PREVIEW_API_URL);
        $setupURL = $apiURL.'v2/documents/'.$documentId.'/previewpages/1';
        return $setupURL;
    }

    /**
     * DocumentLifeExtendApiSyncCall
     * @param objProduct
     * @throws Exception
     * @return mixed
     */
    public function extendDocLifeApiSyncCall($objProduct)
    {
        try {
            $documentCustomIds = $documentIds = [];
            $documentExtendDate = date("Y-m-d H:i:s");
            if ($objProduct && ($objProduct->getExternalProd() || $objProduct->getData('customization_fields'))) {
                $setupURL = $this->getApiUrl(self::DOCUMENT_API_URL) . 'v2/extenddocumentlife';
                if ($objProduct->getExternalProd()) {
                    $documentIds = $this->getContentReferenceId($objProduct->getExternalProd());
                }
                if ($objProduct->getData('customization_fields')) {
                    $documentCustomIds = $this->getCustomDocDocumentIds($objProduct->getData('customization_fields'));
                }
                $documentIds = array_merge($documentIds, $documentCustomIds);

                if (is_array($documentIds)) {
                    foreach ($documentIds as $documentId) {
                        if ($documentId) {
                            $apiRequestData = [
                                'documentId' => $documentId,
                                'expiration' => [
                                    'units' => 'DAYS',
                                    'value' => self::EXTEND_DOCUMENT_LIFE_DAYS
                                ],
                            ];
                            if ($this->toggleConfig->getToggleConfigValue('maegeeks_d172040_fix')) {
                                $apiRequestData['expiration']['value'] = 30;
                            }
                            $response = $this->curlCall($apiRequestData, $setupURL, 'POST');
                            if (!empty($response) && array_key_exists('output', $response)) {
                                $documentExtendDate = $response['output']['document']['expirationTime'];
                            }
                        }
                    }

                    if ($documentExtendDate) {
                        $this->setProductDocumentExpireDate($documentExtendDate, $objProduct->getId());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Extend Document Life Cron :'  . $e->getMessage());
        }
    }

    /**
     * ExtendDocLifeQueueProccess
     *
     * @param object $productObj
     * @return boolean
     */
    public function extendDocLifeQueueProccess($productObj)
    {
        $rabbitMqJson = [];
        try {
            $documentCustomIds = $documentIds = [];
            if ($productObj && ($productObj->getExternalProd() || $productObj->getData('customization_fields'))) {
                $productId = $productObj->getId();
                if ($productObj->getExternalProd()) {
                    $documentIds = $this->getContentReferenceId($productObj->getExternalProd());
                }
                if ($productObj->getData('customization_fields')) {
                    $documentCustomIds = $this->getCustomDocDocumentIds($productObj->getData('customization_fields'));
                }
                $documentIds = array_merge($documentIds, $documentCustomIds);

                if (is_array($documentIds)) {
                    foreach ($documentIds as $documentId) {
                        $rabbitMqJson[] = [
                            'documentId' => $documentId,
                            'produtId' => $productId,
                        ];
                    }
                }
            }

            $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
            $this->message->setMessage($rabbitMqStr);
            $this->publisher->publish('docRefExtandExpire', $this->message);
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . __('Erro while push the extend document life queue: ')
                . ' ' . $error->getMessage()
            );
        }

        return true;
    }

    public function setProductDocumentExpireDate($documentExtendDate, $productId)
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogEntity = $this->resourceConnection->getTableName('catalog_product_entity');
        $updateData = ['product_document_expire_date' => date("Y-m-d H:i:s", strtotime($documentExtendDate))];
        $connection->update(
            $catalogEntity,
            $updateData,
            ['entity_id = ?' => (int) $productId]
        );
    }

    /**
     * Function to getContentReference id from external product
     * @return array
     */
    public function getContentReferenceId($extrenalProductData)
    {
        $documentId = [];
        if ($extrenalProductData) {
            $externalProd = json_decode($extrenalProductData, true);
            $contentAssociations = $externalProd['contentAssociations'] ?? [];
            foreach ($contentAssociations as $contentAssociation) {
                $documentId[] = $contentAssociation['contentReference'];
            }
        }
        return $documentId;
    }

    public function getCustomDocDocumentIds($customizationFields)
    {
        $documentIds = [];
        $customDocProductData =json_decode((string)$customizationFields, true);
        if (is_array($customDocProductData)) {
            foreach ($customDocProductData as $customProdData) {
                if (array_key_exists('documentId', $customProdData)) {
                    $documentIds[] = $customProdData['documentId'];
                }
                if (isset($customProdData['formFields']) && is_array($customProdData['formFields'])) {
                    foreach ($customProdData['formFields'] as $formField) {
                        if (isset($formField['documentId'])) {
                            $documentIds[] = $formField['documentId'];
                        }
                    }
                }
            }
        }
        return $documentIds;
    }

    /**
     * Cron Method: Checks the list of Expiring Catalog within two months.
     *
     * @return array
     */
    public function getExpiryDocuments()
    {
        $twoMonthExpiryCatalogdata = [];

        $currentDate = new \DateTime();
        $currentDate->modify('- 11 months');
        $currentDate->setTimezone(new \DateTimeZone(self::TIME_ZONE));
        $fromDate = $currentDate->format('Y-m-d H:i:s'); // 2023-07-18 02:02:30

        $todayDate = new \DateTime();
        $todayDate->modify('- 13 months');
        $todayDate->setTimezone(new \DateTimeZone(self::TIME_ZONE));
        $toDate = $todayDate->format('Y-m-d H:i:s'); // 2023-05-18 02:02:30

        try {
            // Get collections for renew catalogs within 2 months
            $productCollection = $this->productCollectionFactory->create();
            $subquery = "select product_id from sales_order_item where updated_at >'".$toDate."'";

            $productCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('updated_at', ['lt' => $fromDate])
            ->addAttributeToFilter('updated_at', ['gt' => $toDate]);

            $productCollection->getSelect()->where('e.entity_id NOT IN(?)', new \Zend_Db_Expr(($subquery)));

            if ($productCollection->getSize() > 0) {
                foreach ($productCollection->getData() as $product) {
                    $product = $this->productRepository->getById($product['entity_id']);
                    $productInSharedCatalogs = $product->getData('shared_catalogs');
                    $productCategories = $product->getData('category_ids');
                    $productCompanies = $this->getCompanyByProduct($productCategories, $productInSharedCatalogs);
                    $twoMonthExpiryCatalogdata = $this->getExpirationData(
                        $product,
                        $productCompanies,
                        $twoMonthExpiryCatalogdata
                    );
                }
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ .' '."No Catalog Expiration Data Found");
            }
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                    . __(' Error while fetching catalog expiration data: ') . ' ' . $error->getMessage()
            );
        }

        return $twoMonthExpiryCatalogdata;
    }

    /**
     * get CompanyByProduct
     *
     * @param array $productCategories
     * @param string $productInSharedCatalogs
     * @return array|mixed
     */
    public function getCompanyByProduct($productCategories, $productInSharedCatalogs)
    {
        $sharedCatalogCustomerGroupIds = [];
        $filter = $this->filterBuilder->setField('entity_id')
            ->setConditionType('in')
            ->setValue($productInSharedCatalogs)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$filter])
            ->create();
        $sharedCatalog = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();
        foreach ($sharedCatalog as $item) {
            $sharedCatalogCustomerGroupIds[] = $item->getCustomerGroupId();
        }
        $companyData = $this->getProductCompanyData($sharedCatalogCustomerGroupIds, $productCategories);
        return $companyData;
    }

    /**
     * get ProductCompanyData
     *
     * @param array $sharedCatalogCustomerGroupIds
     * @return array
     */
    public function getProductCompanyData($sharedCatalogCustomerGroupIds): array
    {
        $companyData = $companies = [];
        $sharedCatalogIdFilter = $this->filterBuilder->setField('shared_catalog_id')
            ->setConditionType('neq')
            ->setValue(0)
            ->create();
        $customerGroupIdFilter = $this->filterBuilder->setField('customer_group_id')
            ->setConditionType('in')
            ->setValue($sharedCatalogCustomerGroupIds)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$sharedCatalogIdFilter])
            ->addFilters([$customerGroupIdFilter])
            ->create();
        $companies = $this->companyRepository->getList($searchCriteria)->getItems();
        foreach ($companies as $company) {
            $companyId = $company->getId() != null ? $company->getId() :'';
            $companyUrl = $company->getCompanyUrlExtention() != null ? self::STORE_CODE.'/'.$company->getCompanyUrlExtention():'';
            $superUserId = $company->getSuperUserId() != null ? $company->getSuperUserId() :'';
            $sharedCatalogId = $company->getSharedCatalogId() != null ? $company->getSharedCatalogId() :'';
            $customerGroupId = $company->getCustomerGroupId() != null ? $company->getCustomerGroupId() :'';
            $companyData[] = [
                'company_id' => $companyId,
                'super_user_id' => $superUserId,
                'category_id' => $sharedCatalogId,
                'customer_group_id' => $customerGroupId,
                'company_url_extention' => $companyUrl
            ];
        }
        return $companyData;
    }

    /**
     * get ExpirationData
     *
     * @param int $productId
     * @param array $productCompanies
     * @param array $allExpiryData
     * @return array
     */
    public function getExpirationData($product, $productCompanies, $allExpiryData): array
    {
        $expiryData = [];
        $expiryData['name'] = $product->getName();
        $expiryData['product_id'] = $product->getId();
        $productUpdatedAt = $product->getUpdatedAt();
        $updatedAtDate = new \DateTime($productUpdatedAt);
        $updatedAtDate->modify('+ 13 months');
        $updatedAtDate->setTimezone(new \DateTimeZone(self::TIME_ZONE));
        $catalogExpirationDate = $updatedAtDate->format('m/d/Y');
        $expiryData['expiration_date'] = $catalogExpirationDate;
        foreach ($productCompanies as $productCompany) {
            $customerObj = $this->customerRepository->getById($productCompany['super_user_id']);
            $customerName = $customerObj->getFirstname(). ' ' . $customerObj->getLastname();
            $secondaryEmail = !empty($customerObj->getCustomAttribute('secondary_email')) ?
            $customerObj->getCustomAttribute('secondary_email')->getValue() : null;
            if ($customerObj != null && $customerName && $secondaryEmail) {
                $expiryData['user_id'] = $customerName.','
                .$secondaryEmail;
                $expiryData['company_id'] = $productCompany['company_id'];
                $expiryData['company_url_extention'] = $productCompany['company_url_extention'];
                $productCategories = $product->getData('category_ids');
                $categoryUrlPath = $this->getCategoryUrlPath($productCompany['category_id'], $productCategories);
                if (!$categoryUrlPath) {
                    continue;
                }
                $expiryData['category_url_path'] = $categoryUrlPath;
                $allExpiryData[] = $expiryData;
            }
        }
        return $allExpiryData;
    }

    /**
     * get CategoryUrlPath
     *
     * @param int $productCompanyCategoryId
     * @param array $productCategories
     * @return string
     */
    public function getCategoryUrlPath($productCompanyCategoryId, $productCategories)
    {
        $categoryUrlPath = '';
        $category = $this->categoryRepository->get($productCompanyCategoryId);
        $childCategoryIds = $category->getAllChildren() ? explode(',', $category->getAllChildren()) : [];
        foreach ($productCategories as $productCategoryId) {
            if ($productCompanyCategoryId == $productCategoryId) {
                $categoryObj = $this->categoryRepository->get($productCategoryId);
                $categoryUrlPath = $categoryObj->getUrlPath();
                break;
            } elseif ($childCategoryIds && in_array($productCategoryId, $childCategoryIds)) {
                $categoryObj = $this->categoryRepository->get($productCategoryId);
                $categoryUrlPath = $categoryObj->getUrlPath();
                break;
            }
        }
        return $categoryUrlPath;
    }
}
