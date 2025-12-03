<?php
declare (strict_types = 1);

namespace Fedex\Catalog\Observer;

use Fedex\Catalog\Model\Config;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\LiveSearch\Model\SharedCatalogSkip;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;


class ProductSaveBeforeObserver implements ObserverInterface
{
    private const TYPE_BUNDLE = 'bundle';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly LoggerInterface $logger,
        private readonly SharedCatalogSkip $sharedCatalogSkip,
        private readonly ToggleConfig $toggleConfig,
        private readonly Config $catalogConfig,
        private readonly ConfigInterface $productBundleConfig
    ) {
    }

    /**
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        $postData = $this->request->getPostValue();
        try {
            if($this->sharedCatalogSkip->getLivesearchProductListingEnable()){
                $this->updatePageLayoutSearch($product, $postData);
            }
        } catch (\Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error saving the image: ' . $error->getMessage());
        }

        if($this->productBundleConfig->isTigerE468338ToggleEnabled() && $product->getTypeId() === self::TYPE_BUNDLE) {
            try {
                $this->setBundleProductPrice($product, $postData);
            } catch (\Exception $error) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    ' Error saving bundle product price: ' . $error->getMessage());
            }
        }

        if($this->catalogConfig->getTigerDisplayUnitCost3P1PProducts() && !$product->getMiraklMcmProductId()) {
            try {
                $this->setUnitCost($product);
            } catch (\Exception $error) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    ' Error saving product price to unit cost: ' . $error->getMessage());
            }
        }

        return $this;
    }

    private function updatePageLayoutSearch($product, $postData): void
    {
        if (isset($postData['product']['page_layout'])) {
            $pageLayoutSearch = $postData['product']['page_layout'];
            $product->setData('page_layout_search', $pageLayoutSearch);
        }
    }

    private function setBundleProductPrice($product, $postData): void
    {
        $totalPrice = 0.0;
        if (isset($postData['bundle_options']['bundle_options']) && is_array($postData['bundle_options']['bundle_options'])) {
            foreach ($postData['bundle_options']['bundle_options'] as $bundleOption) {
                if (isset($bundleOption['bundle_selections']) && is_array($bundleOption['bundle_selections'])) {
                    foreach ($bundleOption['bundle_selections'] as $selection) {
                        if (isset($selection['selection_price_value'])) {
                            $totalPrice += (float)$selection['selection_price_value'];
                        }
                    }
                }
            }
        }
        if ($totalPrice > 0) {
            $product->setPrice($totalPrice);
        }
    }

    private function setUnitCost($product): void
    {
        $productPrice = $product->getData('price');
        $product->setUnitCost($productPrice);
    }
}
