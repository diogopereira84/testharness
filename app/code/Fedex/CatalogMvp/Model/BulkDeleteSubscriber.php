<?php

namespace Fedex\CatalogMvp\Model;

use Fedex\CatalogMvp\Api\BulkDeleteMessageInterface;
use Fedex\CatalogMvp\Api\BulkDeleteSubscriberInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Controller\Index\BulkDelete;
use Magento\Framework\Registry;


class BulkDeleteSubscriber implements BulkDeleteSubscriberInterface
{
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
     * @param Registry $Registry
     */
    public function __construct(
        Json $serializerJson,
        protected LoggerInterface $logger,
        protected BulkDelete $bulkDelete,
        protected Registry $registry
    ) {
        $this->serializerJson   = $serializerJson;
    }

    /**
     * @inheritdoc
     */
    public function processMessageBulkDelete(BulkDeleteMessageInterface $message)
    {
        $messages = $message->getMessage();
        $messageArray = $this->serializerJson->unserialize($messages);
          if ($messageArray) {
            try {
                $this->registry->register('isSecureArea', true);
                foreach ($messageArray as $productId) {
                    $this->bulkDelete->deleteProduct($productId['produtId']);
                }
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ":" . __LINE__ . " Product not deleted " . $e->getMessage());
            }
        }
    }
}
