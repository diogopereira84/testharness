<?php

namespace Fedex\ReorderInstance\Model;

use Fedex\Header\Helper\Data as HeaderData;
use Fedex\ReorderInstance\Api\ReorderMessageInterface;
use Fedex\ReorderInstance\Api\ReorderSubscriberInterface;
use Magento\Framework\HTTP\ClientInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Sales\Model\Order;
use Fedex\ReorderInstance\Helper;
use Fedex\Punchout\Helper\Data as PunchOutHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriodFactory;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\CollectionFactory as OrderRetationPeriodCollectionFactory;

class ReorderSubscriber implements ReorderSubscriberInterface
{
    const NEW_DOCUMENTS_API_IMAGE_PREVIEW = 'new_documents_api_image_preview_toggle';
    public const INFO_BUYREQUEST = 'info_buyRequest';
    public const EXTERNAL_PROD = 'external_prod';
    public const CONTENT_REFERENCE = 'contentReference';
    public const CONTENT_ASSOCIATIONS = 'contentAssociations';
    public const PROJECTS = 'projects';
    public const FILE_MGMT_STATE = 'fileManagementState';
    public const FILE_ITEMS = 'fileItems';
    public const CONVERSION_RESULT = 'conversionResult';
    public const PREVIW_URI = 'previewURI';

    /**
     * @var orderRetationPeriod
     */
    protected $orderRetationPeriod;

    /**
     * @var OrderRetationPeriodCollectionFactory
     */
    protected $orderRetationPeriodCollectionFactory;

    /**
     * ReorderSubscriber constructor.
     *
     * @param ClientInterface          $curl
     * @param LoggerInterface          $logger
     * @param ScopeConfigInterface     $configInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionFactory        $collectionFactory
     * @param Order                    $order
     * @param PunchOutHelper           $punchOutHelper
     * @param ToggleConfig             $toggleConfig
     * @param HeaderData               $headerData
     * @param CatalogDocumentRefranceApi   $catalogDocumentRefranceApi
     * @param OrderRetationPeriodFactory   $orderRetationPeriodFactory
     * @param OrderRetationPeriodCollectionFactory $OrderRetationPeriodCollectionFactory
     */
    public function __construct(
        protected ClientInterface $curl,
        protected LoggerInterface $logger,
        protected ScopeConfigInterface $configInterface,
        protected OrderRepositoryInterface $orderRepository,
        protected CollectionFactory $collectionFactory,
        protected Order $order,
        private PunchOutHelper $punchOutHelper,
        protected ToggleConfig $toggleConfig,
        protected HeaderData $headerData,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        OrderRetationPeriodFactory $orderRetationPeriodFactory,
        OrderRetationPeriodCollectionFactory $OrderRetationPeriodCollectionFactory
    ) {
        $this->orderRetationPeriod = $orderRetationPeriodFactory;
        $this->orderRetationPeriodCollectionFactory = $OrderRetationPeriodCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function processMessage(ReorderMessageInterface $message)
    {
        try {
            $orderId = $message->getMessage();
            $newDocumentApiImageToggle = $this->toggleConfig->getToggleConfigValue(static::NEW_DOCUMENTS_API_IMAGE_PREVIEW);
            if($newDocumentApiImageToggle){
                // Extend Document life api call
                $this->documentLifeExtendApiCallAndInsert($orderId);
            }

            $allContentReferencesData = $this->getOrderData($orderId);
            if (!empty($allContentReferencesData)) {
                $this->manageReorderInstance($orderId, $allContentReferencesData);
            }
            // Update order with reorderable Flag.
            $orderObject = $this->order->load($orderId);
            $orderObject->setReorderable(1);
            $orderObject->save();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in processing reorder Instance queue: ' .
            $e->getMessage());
        }
    }


    /**
     * Call documentLifeExtendApiCall API for each item, life Extend and insert into DB
     *
     * @param Int   $orderId
     */
    public function documentLifeExtendApiCallAndInsert($orderId)
    {
        try {
                $orderObject = $this->orderRepository->get($orderId);
                $allContentReferences = [];
                 foreach ($orderObject->getAllVisibleItems() as $item) {
                       $productInstance = $item->getProductOptions();
                       if(isset($productInstance[self::INFO_BUYREQUEST][self::EXTERNAL_PROD][0][self::CONTENT_ASSOCIATIONS])){
                            $productInstanceContentAssocions = $productInstance[self::INFO_BUYREQUEST][self::EXTERNAL_PROD][0][self::CONTENT_ASSOCIATIONS];
                            
                            $legacyDocumentNotReorderableToggle = $this->toggleConfig
                             ->getToggleConfigValue('techtitans_B2353493_legacy_documents_not_reorderable_nightly_job');

                            if ($legacyDocumentNotReorderableToggle && $this->checkContentAssociations($productInstanceContentAssocions)) {
                                continue;
                            }

                           foreach($productInstanceContentAssocions as $productInstanceContentAssocionEach){
                                $response = $this->catalogDocumentRefranceApi->documentLifeExtendApiCallWithDocumentId($productInstanceContentAssocionEach['contentReference']);
                                if (!empty($response) && array_key_exists('output', $response)) {
                                        $documentExpiryDate = $response['output']['document']['expirationTime'];

                                         $orderRetationPeriodCollection = $this->orderRetationPeriodCollectionFactory->create();
                                         $orderRetationPeriodCollection = $orderRetationPeriodCollection->addFieldToFilter('document_id', ['eq' => $productInstanceContentAssocionEach['contentReference']]);

                                         //Update Data
                                         if ($orderRetationPeriodCollection->getSize()){
                                            $orderRetationPeriodCollectionData = $orderRetationPeriodCollection->getFirstItem();
                                             $orderRetationPeriodModel = $this->orderRetationPeriod->create();
                                             $orderRetationPeriodModel->load($orderRetationPeriodCollectionData->getId(), "id");
                                             $orderRetationPeriodModel->setOrderItemId($item->getItemId());
                                             $orderRetationPeriodModel->setExtendedDate(date("Y-m-d", strtotime($documentExpiryDate)));
                                             $orderRetationPeriodModel->setExtendedFlag(1);
                                             $orderRetationPeriodModel->save();
                                         }else{
                                         //Insert Data
                                            $orderRetationPeriodModel = $this->orderRetationPeriod->create();
                                             $orderRetationPeriodModel->setOrderItemId($item->getItemId());
                                             $orderRetationPeriodModel->setDocumentId($productInstanceContentAssocionEach['contentReference']);
                                             $orderRetationPeriodModel->setExtendedDate(date("Y-m-d", strtotime($documentExpiryDate)));
                                             $orderRetationPeriodModel->setExtendedFlag(1);
                                             $orderRetationPeriodModel->save();
                                         }
                                         //Log for debug purpose
                                         $this->logger->info(__METHOD__.':'.__LINE__.' Queue document life extend for order_item_id : ' . $item->getItemId());
                                }
                           }

                       }
                 }
          } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in processing document life extend api queue: ' .
            $e->getMessage());
        }
    }
    
    /**
     * Check ContentAssociatins
     * @param array $externalProducts 
     * 
     * @return boolean
     */
    private function checkContentAssociations($externalProducts): bool
    {   
        foreach ($externalProducts as $content) {
            if (isset($content['contentReference']) && is_numeric($content['contentReference'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Call Reorder API for each item content references
     *
     * @param Int   $orderId
     * @param Array $allContentReferencesData
     */
    public function manageReorderInstance($orderId, $allContentReferencesData)
    {
        foreach ($allContentReferencesData as $itemId => $contentReferences) {

            //call ReorderInstance API for each item content references
            $reorderApiOutputData = $this->callReorderApi($contentReferences);

            if (isset($reorderApiOutputData['output']['reorderableDocumentVO'])) {
                $reorderInstanceData = $reorderApiOutputData['output']['reorderableDocumentVO'];
                $this->updateOrderItem($orderId, $itemId, $reorderInstanceData);
            }
        }
    }

    /**
     * Update Orde item
     *
     * @param Int   $orderId
     * @param Int   $orderItemId
     * @param Array $reorderInstanceData
     */
    public function updateOrderItem($orderId, $orderItemId, $reorderInstanceData)
    {
        $orderItem = $this->collectionFactory->create()->addFieldToFilter('item_id', $orderItemId)->getFirstItem();
        $productOptionData = $orderItem->getData('product_options');

        $productOptionData[self::INFO_BUYREQUEST][self::EXTERNAL_PROD][0]
            ['preview_url'] = $reorderInstanceData[0]['id'];

        $reorderInstanceDataCount = count($reorderInstanceData);
        for ($count = 0; $count < $reorderInstanceDataCount; $count++) {
            $productOptionData[self::INFO_BUYREQUEST][self::EXTERNAL_PROD][0]
            [self::CONTENT_ASSOCIATIONS][$count][self::CONTENT_REFERENCE] = $reorderInstanceData[$count]['id'];
            if (!isset($productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS])) {
                continue;
            }
            $productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS][0]
                [self::FILE_ITEMS][$count]['convertedFileItem']['fileId'] = $reorderInstanceData[$count]['id'];
            $productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS][0]
                [self::FILE_ITEMS][$count][self::CONVERSION_RESULT]['documentId'] = $reorderInstanceData[$count]['id'];
            $productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS][0]
                [self::FILE_ITEMS][$count]['contentAssociation']
                [self::CONTENT_REFERENCE] = $reorderInstanceData[$count]['id'];

            if (isset($productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS][0]
                [self::FILE_ITEMS][$count][self::CONVERSION_RESULT][self::PREVIW_URI])) {
                $previewUrl = $productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS][0]
                [self::FILE_ITEMS][$count][self::CONVERSION_RESULT][self::PREVIW_URI];
                $previewUrlArray = explode("/", $previewUrl);
                $updatedPreviewUrl = str_replace(
                    $previewUrlArray['7'],
                    $reorderInstanceData[$count]['id'],
                    $previewUrl
                );
                $productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS][0]
                    [self::FILE_ITEMS][$count][self::CONVERSION_RESULT][self::PREVIW_URI] = $updatedPreviewUrl;
            }

            $productOptionData[self::INFO_BUYREQUEST][self::FILE_MGMT_STATE][self::PROJECTS][0]['productConfig']
                ['product'][self::CONTENT_ASSOCIATIONS][$count]
                [self::CONTENT_REFERENCE] = $reorderInstanceData[$count]['id'];
        }

        $orderItem->setData('product_options', $productOptionData);
        $orderItem->save();
    }

    /**
     * Load Order data by order_id
     *
     * @param  Int   $orderId
     * @return Array $allContentReferences
     */
    public function getOrderData($orderId)
    {
        try {
            $orderObject = $this->orderRepository->get($orderId);
            $allContentReferences = [];
            foreach ($orderObject->getAllVisibleItems() as $item) {
                $contentReferences = [];
                $productInstance = $item->getProductOptions();
                // get Content Asssociations from the product Instance
                if (!isset($productInstance[self::INFO_BUYREQUEST][self::EXTERNAL_PROD][0]
                [self::CONTENT_ASSOCIATIONS])) {
                    continue;
                }
                $contentAssociations = $productInstance[self::INFO_BUYREQUEST][self::EXTERNAL_PROD][0]
                    [self::CONTENT_ASSOCIATIONS];
                foreach ($contentAssociations as $contentAssociation) {
                    $contentReferences[] = '"'.$contentAssociation[self::CONTENT_REFERENCE].'"';
                }
                $allContentReferences[$item->getId()] = implode(",", $contentReferences);
            }
            return $allContentReferences;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in retrieving order detail: ' .
            $e->getMessage());
        }
    }

    /**
     * Call Reorder API.
     *
     * @param Array $contentReferences
     *
     * @return Boolean|Array
     */
    public function callReorderApi($contentReferences)
    {
        $tazToken = $this->punchOutHelper->getTazToken();
        $gatewayToken = $this->punchOutHelper->getAuthGatewayToken();

        try {
            $apiUrl = $this->configInterface->getValue('fedex/general/reorder_instance_api_url');

            $dataString = '{"reorderableDocumentsRequest":
                { "sourceDocumentIds":
                    ['.$contentReferences.']
                }
            }';
            $authHeaderVal = $this->headerData->getAuthHeaderValue();
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Cookie: Bearer=" . $tazToken,
                $authHeaderVal . $gatewayToken,
                "Content-Length: " . strlen($dataString)
            ];

            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );
            $this->curl->post($apiUrl, $dataString);
            $output = $this->curl->getBody();
            $reorderApiOutputData = json_decode($output, true);
            if (isset($reorderApiOutputData['alerts'])) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error found with Reorderable Api: ' .
                $reorderApiOutputData['alerts'][0]['message']);
                return false;
            }
            if (isset($reorderApiOutputData['errors'])) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error found with Reorderable Api: ' .
                $reorderApiOutputData['errors'][0]['message']);
                return false;
            }
            return $reorderApiOutputData;
        } catch (\Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' No data was found in Reorderable API: ' .
            $error->getMessage());
        }
    }
}
