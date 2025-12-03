<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Plugin\Mirakl\Helper\Product;

use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\MessageQueue\PublisherInterface;
use Mirakl\Mci\Helper\Product\Image as ImageCore;
use Mirakl\Process\Model\Process;

/**
 * Class Image
 */
class Image
{
    /**
     * @param Data $marketplaceCheckoutHelper
     * @param PublisherInterface $publisher
     */
    public function __construct(
        private Data                $marketplaceCheckoutHelper,
        private PublisherInterface  $publisher
    ) {}

    /**
     * @param ImageCore $subject
     * @param $result
     * @param Process $process
     * @param ProductCollection $collection
     * @return mixed
     */
    public function afterImportProductsImages(ImageCore $subject, $result, Process $process, ProductCollection $collection)
    {
        if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled() && $collection->getSize()) {
            $productIds = [];
            /** @var Product $product */
            foreach ($collection as $product) {
                $productIds[] = $product->getId();
            }

            if(!empty($productIds)) {
                $this->publisher->publish('fedex.marketplaceproduct.image',
                    json_encode(['product_ids' => $productIds])
                );
            }
        }

        return $result;
    }
}
