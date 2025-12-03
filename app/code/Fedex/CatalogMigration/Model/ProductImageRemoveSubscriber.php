<?php

namespace Fedex\CatalogMigration\Model;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Psr\Log\LoggerInterface;

class ProductImageRemoveSubscriber implements SubscriberInterface
{
    
    /**
      * ProductImageRemoveSubscriber constructor.
      * @param CatalogMigrationHelper $catalogMigrationHelper
      * @param LoggerInterface $logger
      */
    public function __construct(
        protected CatalogMigrationHelper $catalogMigrationHelper,
        protected LoggerInterface $logger
    )
    {
    }
    
    /**
     * @inheritdoc
     */
    public function processMessage(MessageInterface $message)
    {
        $sku = $message->getMessage();

        try {

            // Delete product images
            $this->catalogMigrationHelper->removeProductImage($sku);

        } catch (\Exception $e) {

            $this->logger->error(__METHOD__.':'.__LINE__.
                'Remove product images error for sku: ' .
                $sku . var_export($e->getMessage(), true));
        }
    }
}
