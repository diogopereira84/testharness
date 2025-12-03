<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Controller\Cart;

use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Model\Cart\AddBundleToCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class AddFromAls extends Action
{
    private const BUNDLE_TYPE = 'bundle';

    public function __construct(
        protected Context $context,
        protected PageFactory $pageFactory,
        protected AddBundleToCart $addBundleToCart,
        protected LoggerInterface $logger,
        protected \Magento\Framework\Registry $coreRegistry,
        protected ProductRepositoryInterface $productRepository,
        protected ConfigInterface $productBundleConfig
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        try{
            if(!$this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                $this->messageManager->addErrorMessage(
                    __('Bundle Product feature is disabled.')
                );
                return $this->_redirect($this->_redirect->getRefererUrl());
            }

            $this->getProduct();
            return $this->pageFactory->create();
        } catch (\Exception $e) {
            $this->logger->error(
                'Error adding bundle product to cart: ' . $e->getMessage(),
                ['exception' => $e]
            );
            $this->messageManager->addErrorMessage(
                __('The requested product is not a bundle product.')
            );
            return $this->_redirect($this->_redirect->getRefererUrl());
        }
    }


    /**
     * Retrieve current product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if (!$this->coreRegistry->registry('product') && $this->getProductSku()) {
            $product = $this->productRepository->get($this->getProductSku());
            if ($product->getTypeId() !== self::BUNDLE_TYPE) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The requested product is not a bundle product.')
                );
            }
            $this->coreRegistry->register('product', $product);
            $this->coreRegistry->register('current_product', $product);
        }
        return $this->coreRegistry->registry('product');
    }

    /**
     * Retrieve product SKU from request parameters
     *
     * @return string|null
     */
    protected function getProductSku()
    {
        return $this->getRequest()->getParam('sku');
    }

}
