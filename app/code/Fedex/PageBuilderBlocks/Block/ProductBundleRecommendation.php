<?php

declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Block;

use Fedex\ProductEngine\Model\Catalog\Bundle\Products as ProductsBundle;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Bundle\Model\Product\Type;

/**
 * Product Recommendation Block
 */
class ProductBundleRecommendation extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Fedex_PageBuilderBlocks::product-bundle-recommendation-block.phtml';

    /**
     * @var float
     */
    protected $bundlePrice;

    /**
     * @var ProductInterface|null
     */
    protected $bundleProduct;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param PricingHelper $pricingHelper
     * @param ImageHelper $imageHelper
     * @param ProductsBundle $productsBundle
     * @param array $data
     */
    public function __construct(
        private readonly Context                    $context,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly PricingHelper              $pricingHelper,
        private readonly ImageHelper                $imageHelper,
        private readonly ProductsBundle             $productsBundle,
        array                                       $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * Get block data
     *
     * @return array
     */
    public function getBlockData(): array
    {
        $data = $this->getData('data');
        if (!$data) {
            $data = $this->getData();
        }

        if (is_string($data)) {
            return json_decode($data, true) ?: [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Get string data by key
     *
     * @param string $key
     * @return string|null
     */
    public function getStringData(string $key): ?string
    {
        $data = $this->getBlockData();

        if (!$this->getData('data')) {
            return $data[$key] ?? null;
        }

        if (empty($data[$key]) || !is_array($data[$key]) || empty($data[$key][0])) {
            return null;
        }

        return trim((string)$data[$key][0]);
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getStringData('title');
    }

    /**
     * Get SKUs array
     *
     * @return string|null
     */
    public function getSku(): ?string
    {
        return $this->getStringData('sku');
    }

    /**
     * Get custom price
     *
     * @return string|null
     */
    public function getPrice(): ?string
    {
        return $this->getStringData('price');
    }

    /**
     * Get Bundle Price
     *
     * @return string|null
     */
    public function getBundlePrice(): ?string
    {
        return $this->pricingHelper->currency($this->bundlePrice, true, false);
    }

    /**
     * Get Bundle URL
     *
     * @return string|null
     */
    public function getBundleUrl(): ?string
    {
        if ($this->getBundleProductBySku()) {
            return $this->bundleProduct->getProductUrl();
        }
        return null;
    }

    /**
     * Get Widget Message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->getStringData('message');
    }

    /**
     * Get CTA label
     *
     * @return string|null
     */
    public function getCtaLabel(): ?string
    {
        if ($this->getStringData('cta-label')) {
            return $this->getStringData('cta-label');
        }

        return $this->getStringData('cta_label');
    }

    /**
     * Get Products
     *
     * @return array
     */
    public function getProducts(): array
    {
        $bundleProduct = $this->getBundleProductBySku();

        if (!$bundleProduct) {
            return [];
        }

        $productsBundle = $this->productsBundle->getBundleChildProducts($bundleProduct);

        return $this->formatDataProducts($productsBundle);
    }

    private function formatDataProducts(array $productsBundle): array
    {
        $products = [];
        $totalPrice = 0.0;

        foreach ($productsBundle as $product) {
            if (!$product instanceof ProductInterface) {
                continue;
            }

            try {
                $products[] = [
                    'product' => $product,
                    'name' => $product->getName(),
                    'url' => $product->getProductUrl(),
                    'image_url' => $this->getProductImageUrl($product),
                ];
                $price = $product->getFinalPrice();
                $totalPrice += $price;
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }

        $this->bundlePrice = $totalPrice;

        return $products;
    }

    /**
     * Get Products by skus
     *
     * @return ProductInterface|null
     */
    private function getBundleProductBySku(): ?ProductInterface
    {
        $sku = $this->getSku();
        if (!$sku) {
            return null;
        }

        if ($this->bundleProduct) {
            return $this->bundleProduct;
        }

        try {
            $product = $this->productRepository->get($sku);
            if ($product->getTypeId() === Type::TYPE_CODE) {
                $this->bundleProduct = $product;
            }
            return $this->bundleProduct;
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    /**
     * Get product image URL
     *
     * @param ProductInterface $product
     * @return string
     */
    protected function getProductImageUrl(ProductInterface $product): string
    {
        return $this->imageHelper->init($product, 'product_base_image')->getUrl();
    }

    /**
     * Check if block has content to display
     *
     * @return bool
     */
    public function hasContent()
    {
        return !empty($this->getProducts()) || !empty($this->getTitle()) || !empty($this->getMessage());
    }

    /**
     * Get cache key info
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return array_merge(
            parent::getCacheKeyInfo(),
            [
                'FEDEX_PRODUCT_BUNDLE_RECOMMENDATION',
                $this->getTemplate(),
                serialize($this->getBlockData())
            ]
        );
    }
}
