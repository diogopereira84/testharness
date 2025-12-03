<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Fedex\SelfReg\Model\Config as SelfRegConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class MoveProduct
 * Handle the MoveProduct of the CatalogMvp
 */
class MoveProduct implements ActionInterface
{
    public const MOVE_MESSAGE = 'Your item has been moved. The item will be available in the new folder shortly.';
    public const TECH_TITANS_B2559087_CONFIGURABLE_TOAST_MESSAGE = 'tech_titans_B_2559087';
    /**
     * MoveProduct Constructor
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param CatalogMvpHelper $catalogMvpHelper
     * @param CatalogMvpHelper $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param RequestInterface $request
     * @param SelfRegConfig $selfRegConfig
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Context $context,
        protected LoggerInterface $logger,
        protected JsonFactory $resultJsonFactory,
        protected CatalogMvpHelper $catalogMvpHelper,
        protected ProductRepositoryInterface $productRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        protected RequestInterface $request,
        protected SelfRegConfig $selfRegConfig,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    public function execute()
    {
        $newCategoryId = $this->request->getParam('cat_id');
        $productId = $this->request->getParam('id');
        $product = $this->productRepository->getById($productId);
        $productSku = $product->getSku();
        $resultJsonData = $this->resultJsonFactory->create();
        $result = [];
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable() && $this->catalogMvpHelper->isSharedCatalogPermissionEnabled()) {
            try {
                if ($newCategoryId && $productSku) {
                    $category = $this->categoryRepository->get($newCategoryId);
                    $response = $this->catalogMvpHelper->assignProductToCategory($productSku, $newCategoryId);
                    if ($response) {
                        $url = $this->catalogMvpHelper->getCategoryUrl($category);
                        
                        $message = self::MOVE_MESSAGE;
                        
                        $toastToggleEnabled = $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B2559087_CONFIGURABLE_TOAST_MESSAGE);
                        if ($toastToggleEnabled) {
                            // Get dynamic message from configuration or use default
                            $configMessage = $this->selfRegConfig->getMoveCatalogItemMessage();
                            $message = (!empty($configMessage) && trim($configMessage) !== '') ? $configMessage : self::MOVE_MESSAGE;
                        }

                        $result = ['status' => true, 'message' => __($message), 'url' => $url];
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ . 'Error while assign product to category'. $exception->getMessage()
                );
    
                $result = ['status' => false, 'message' => $exception->getMessage()];
            }
        }

        return $resultJsonData->setData($result);
    }
}
