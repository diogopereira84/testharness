<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Magento\Framework\App\ActionInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class UpdateProduct
 * Handle the UpdateProduct of the CatalogMvp
 */
class UpdateProduct implements ActionInterface
{
    /**
     * UpdateProduct Constructor
     *
     * @param Product $product
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param ToggleConfig  $toggleConfig
     * @param RequestInterface $request
     */
    public function __construct(
        protected Product $product,
        protected LoggerInterface $logger,
        private CatalogMvp $catalogMvp,
        protected ProductRepository $productRepository,
        protected ToggleConfig $toggleConfig,
        protected RequestInterface $request,
        protected JsonFactory $jsonFactory
    )
    {
    }

    public function execute()
    {
        $productId= $this->request->getParam('id');
        $resultJson = $this->jsonFactory->create();

        $product = $this->productRepository->getById($productId);
        if ($product->getPublished()) {
            $product->setPublished(0);
        } else {
            $product->setPublished(1);
        }
        $this->catalogMvp->setProductVisibilityValue($product, $product->getAttributeSetId());
        $this->catalogMvp->insertProductActivity($product->getId(), "UPDATE");
        try {
            $this->productRepository->save($product);

            return $resultJson->setData(['success' => true, 'message' => 'Product updated successfully.']);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
            return $resultJson->setData(['success' => true, 'message' => $exception->getMessage()]);
        }
    }
}
