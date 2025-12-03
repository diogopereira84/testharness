<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Block\Product;

use Fedex\ProductEngine\Model\Config\Backend as PeBackendConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Psr\Log\LoggerInterface;

class LoadProductEngine extends Template
{
    /**
     * @var ProductInterface|null
     */
    protected ?ProductInterface $product = null;

    public function __construct(
        Template\Context $context,
        protected PeBackendConfig $peBackendConfig,
        protected ProductRepositoryInterface $productRepository,
        protected LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isValidProduct()
    {
        try {
            $productSku = $this->_request->getParam('id');
            if ($productSku && !$this->_request->getParam('access_token')) {

                $productLoaded = $this->productRepository->get($productSku);
                if ($this->getPeProductId($productLoaded)) {

                    $this->product = $productLoaded;
                    return true;
                }
            }
        } catch (NoSuchEntityException | \Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        return false;
    }

    /**
     * @return ProductInterface
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return ProductInterface
     */
    public function getPeProductId($product)
    {
        return $product->getPeProductId();

    }

    /**
     * @return string
     */
    public function getProductEngineUrl(): string
    {
        return (string)$this->peBackendConfig->getProductEngineUrl();
    }
}
