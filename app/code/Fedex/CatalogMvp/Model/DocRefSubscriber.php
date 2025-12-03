<?php

namespace Fedex\CatalogMvp\Model;

use Fedex\CatalogMvp\Api\DocRefMessageInterface;
use Fedex\CatalogMvp\Api\DocRefSubscriberInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class DocRefSubscriber implements DocRefSubscriberInterface
{
    /**
     * @var Item
     */
    protected $salesOrderItem;

    /**
     * @var Item
     */
    protected $serializerJson;

    /**
     * Subscriber constructor.
     *
     * @param \Magento\Framework\Serialize\Serializer\Json $serializerJson
     * @param LoggerInterface $logger
     * @param ProductFactory $productFactory
     */
    public function __construct(
        Json $serializerJson,
        protected LoggerInterface $logger,
        protected ProductFactory $productFactory,
        protected CatalogDocumentRefranceApi $catalogDocRefApi,
        private ToggleConfig $toggleConfig
    ) {
        $this->serializerJson   = $serializerJson;
    }

    /**
     * @inheritdoc
     */
    public function processMessageMetaData(DocRefMessageInterface $message)
    {
    }

    /**
     * @inheritdoc
     */
    public function processMessageDeleteRef(DocRefMessageInterface $message)
    {
        $messages = $message->getMessage();
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' -- Document Reference Delete Start--');
        try {
            $messageArray = $this->serializerJson->unserialize($messages);
            $this->catalogDocRefApi->curlCall($messageArray['api_request_data'], $messageArray['setupURL'], $messageArray['method']);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ . " error in Document Reference Delete");
        }
    }

    /**
     * @inheritdoc
     */
    public function processMessageExtandExpire(DocRefMessageInterface $message)
    {
        $messages = $message->getMessage();
	$documentId = null;
        $productId = null;

        try {
            $messageArray = $this->serializerJson->unserialize($messages);

            // Fix issue D-209736, reading toggle value
            $expirationDateNullFixToggle = $this->toggleConfig
                ->getToggleConfigValue(
                    'techtitans_D209736_migrated_document_expire_date_null_fix'
                );

            // Fix issue D-209736, in toggle ONN case checking if items should be more than 1 return 
            // true otherwise false, and toggle off case always will return true
            $documentItems = $expirationDateNullFixToggle && !empty($messageArray[0]) ? array_key_exists('documentId', $messageArray[0]) : false;

            foreach ($messageArray as $msg) {
                if ($this->toggleConfig->getToggleConfigValue('techTitans_customDocExpiry_fix')) {
                    if (is_array($msg) && !$documentItems) {
                        foreach ($msg as $msgData) {
                            $documentId = $msgData['documentId'];
                            $productId = $msgData['produtId'];
                            $this->catalogDocRefApi->documentLifeExtendApiCall($documentId, $productId);
                        }
                    } else {
                        if (isset($msg['produtId']) && $msg['documentId']) {
                            $documentId = $msg['documentId'];
                            $productId = $msg['produtId'];
                            $this->catalogDocRefApi->documentLifeExtendApiCall($documentId, $productId);
                        }
                    }
                } else {
                    if (isset($msg['produtId']) && $msg['documentId']) {
                        $documentId = $msg['documentId'];
                        $productId = $msg['produtId'];
                        $this->catalogDocRefApi->documentLifeExtendApiCall($documentId, $productId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ . " error in Document Reference Extand Expire for product ID " . $productId . " and document id $documentId " . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function processMessageAddRef(DocRefMessageInterface $message)
    {
        $messages = $message->getMessage();
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' -- Document Reference Add Start--');
        try {

            $messageArray = $this->serializerJson->unserialize($messages);

            $this->catalogDocRefApi->curlCall(
                $messageArray['apiRequestData'],
                $messageArray['setupUrl'],
                $messageArray['method']
            );
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ . " error in Document Reference Add");
        }
    }
}
