<?php


/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves
 */

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Controller\AddToCart;

use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;
use Fedex\MarketplaceProduct\Model\AddToCartContext;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Index implements ActionInterface, HttpPostActionInterface
{
    /**
     * @param AddToCartContext $context
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param ResponseInterface $response
     * @param ProductRepositoryInterface $productRepository
     * @param CheckoutHelper $checkoutHelper
     */
    public function __construct(
        private AddToCartContext $context,
        private LoggerInterface $logger,
        private ManagerInterface $messageManager,
        private ResponseInterface $response,
        private ProductRepositoryInterface $productRepository,
        private CheckoutHelper $checkoutHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $requestData = $this->context->getRequestInterface()->getParams();
        $resultRedirect = $this->context->getRedirectFactory()->create();

        try {
            $this->context->getQuoteProductAdd()->setCart($this->context->getSession()->getQuote());
            $product = $this->productRepository->get($requestData['sku']);
            if (!isset($requestData['sku'])) {
                $this->messageManager->addErrorMessage(__('An error occurred'));
                return $resultRedirect->setPath('*/*/index');
            }

            if (isset($requestData['action']) && $requestData['action'] == 'cancel') {
                return $resultRedirect->setPath($product->getUrlModel()->getUrl($product));
            }
            $params = [
                'sku' => $requestData['sku'],
                'qty' => 1,
                'isMarketplaceProduct' => true
            ];
            if($product->getTypeId() == Configurable::TYPE_CODE && $this->checkoutHelper->isEssendantToggleEnabled()){
                $superAttributes = $requestData['super_attribute']??[];
                $params['super_attribute'] = $this->checkoutHelper->getSuperAttributeArray($superAttributes);
            }
            $this->context->getQuoteProductAdd()->addItemToCart($params);

            return $resultRedirect->setPath('checkout/cart');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage(__($e->getMessage()));

            $product = $this->productRepository->get($requestData['sku']);
            return $resultRedirect->setPath($product->getUrlModel()->getUrl($product));
        }
    }
}
