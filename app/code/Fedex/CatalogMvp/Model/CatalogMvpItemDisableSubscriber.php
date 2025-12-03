<?php

namespace Fedex\CatalogMvp\Model;

use Fedex\CatalogMvp\Api\CatalogMvpItemDisableMessageInterface;
use Fedex\CatalogMvp\Api\CatalogMvpItemDisableSubscriberInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CatalogMvpItemDisableSubscriber implements CatalogMvpItemDisableSubscriberInterface
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
     * @param ProductRepository $productRepositroy
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
    public function processMessage(CatalogMvpItemDisableMessageInterface $message)
    {
        $messages = $message->getMessage();
        $disabledProductIds = [];
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' -- Product Disabling start--');
        try {

            $messageArray = $this->serializerJson->unserialize($messages);
            foreach ($messageArray as $msg) {
                if (isset($msg['entity_id']) && $msg['entity_id']) {
                    $disabledProductIds[] = $msg['entity_id'];
                    $this->updateProductStatus($msg['entity_id']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." error in update product status");
        }
        if (!empty($disabledProductIds)) {
            $this->logger->info('Disabled Product IDs (with repeats): ' . implode(',', $disabledProductIds));
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
            $product->setPublished('0');
            $this->productRepository->save($product);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." Error in Disable Product Status");
        }
    }
}
