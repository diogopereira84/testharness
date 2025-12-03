<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Model\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class AddBundleToCart
{
    /**
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        private Cart $cart,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger,
        private RequestInterface $request
    ) {}

    /**
     * Add bundle product to cart
     *
     * @param int $productId
     * @param array $bundleOptions  Example: [7 => [10, 13, 19, 22]]
     * @param int $qty
     * @return void
     * @throws LocalizedException
     */
    public function execute(int $productId, array $bundleOptions, int $qty = 1): void
    {
        $product = $this->productRepository->getById($productId);
        if (!$product || !$product->getId()) {
            throw new LocalizedException(__('Bundle product not found.'));
        }

        $bundleOptionsQty = $this->getBundleOptionsQty($product, $bundleOptions);

        $requestInfo = [
            'product' => $productId,
            'qty' => $qty,
            'bundle_option' => $bundleOptions,
            'bundle_option_qty' => $bundleOptionsQty
        ];

        try {
            $product->addCustomOption('bundle_instance_id_hash', $this->generateInstanceIdHash());
            $quote = $this->cart->getQuote();

            if (!$quote->getId()) {
                $quote->save();
            }

            $this->cart->addProduct($product, $requestInfo);
            $this->cart->saveQuote();
        } catch (\Exception $e) {
            $this->logger->error('Bundle Add Error: ' . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    private function getBundleOptionsQty($product, $bundleOptions): array
    {
        $bundleProductOptions = $product->getExtensionAttributes()?->getBundleProductOptions();
        $productsQtyData = json_decode($this->request->getParam('productsQtyData') ?? '', true);
        if ($bundleProductOptions && !empty($productsQtyData)) {
            foreach ($bundleProductOptions as $bundleProductOption) {
                $productLinks = $bundleProductOption->getProductLinks();
                foreach ($productLinks as $productLink) {
                    $selectionId = (string)$productLink->getId();
                    $bundleOptionQty[$selectionId] = $productsQtyData[$productLink->getSku()] ?? 1;
                }
            }
        } else {
            foreach ($bundleOptions as $optionId => $selections) {
                foreach ((array)$selections as $selectionId) {
                    $bundleOptionQty[$selectionId] = 1;
                }
            }
        }

        return $bundleOptionQty;
    }

    /**
     * Generate a hash based on instance IDs from productsData
     *
     * @return string
     * @throws LocalizedException
     */
    private function generateInstanceIdHash(): string
    {
        $productsData = $this->request->getParam('productsData') ?? null;
        if (is_string($productsData)) {
            $productsData = json_decode($productsData, true);
        }

        if (!is_array($productsData)) {
            throw new LocalizedException(__('Product data must be a valid JSON string.'));
        }

        $instanceIdHash = [];

        foreach ($productsData as $product) {
            $instanceIdHash[] = $product['instanceId'] ?? '';
        }

        return implode('_', $instanceIdHash);
    }
}
