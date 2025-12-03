<?php

namespace Fedex\CatalogMvp\Model;

use Fedex\CatalogMvp\Api\CatalogMvpItemEnableMessageInterface;
use Fedex\CatalogMvp\Api\CatalogMvpItemEnableSubscriberInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CatalogMvpItemEnableSubscriber implements CatalogMvpItemEnableSubscriberInterface
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
     * @param ProductRepository $productRepository
     * @param ToggleConfig  $toggleConfig
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializerJson,
        protected LoggerInterface $logger,
        protected ProductFactory $productFactory,
        protected ProductRepository $productRepository,
        protected ToggleConfig $toggleConfig
    ) {
        $this->serializerJson = $serializerJson;
    }

    /**
     * @inheritdoc
     */
    public function processMessage(CatalogMvpItemEnableMessageInterface $message)
    {
        $messages = $message->getMessage();
        $enabledProductIds = [];
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' -- Product Enabling start--');
        try {

            $messageArray = $this->serializerJson->unserialize($messages);
            foreach ($messageArray as $msg) {
                if (isset($msg['entity_id']) && $msg['entity_id']) {
                    $enabledProductIds[] = $msg['entity_id'];
                    $this->updateProductStatus($msg['entity_id']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." error in update product status");
        }
        if (!empty($enabledProductIds)) {
            $this->logger->info('Enabled Product IDs (with repeats):: ' . implode(',', $enabledProductIds));
        }
    }

    /**
     * Update sales_order_item table
     * @param int $itemId
     * @return void
     */
    public function updateProductStatus($productId)
    {
        try {
            $product=$this->productRepository->getById($productId);
            $product->setStoreId('0');
            $product->setPublished('1');
            $this->productRepository->save($product);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." Error in Enable Product Status");
        }
    }
}
