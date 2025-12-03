<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\CatalogMvp\Controller\Index;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ActionInterface;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class UpdateCatalogExpiryDate implements ActionInterface
{
    /**
     * @param Context
     * @param JsonFactory
     * @param ProductRepositoryInterface
     * @param LoggerInterface
     * @param CatalogMvp
     */
    public function __construct(
        protected Context $context,
        protected JsonFactory $jsonFactory,
        protected ProductRepositoryInterface $productRepository,
        protected LoggerInterface $logger,
        protected CatalogMvp $helper,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $productId = $this->context->getRequest()->getParam('id');
        $todayDate = new \DateTime();
        $toDate = $todayDate->format('Y-m-d H:i:s');
        try {
            //Update 'required_options' to save the 'updated_at' data in product
            $productObj = $this->productRepository->getById($productId);
            $productObj->setData('required_options', false);
            $productObj->setProductUpdatedDate($toDate);
            
                 $this->productRepository->save($productObj);
            
            $this->helper->insertProductActivity($productObj->getId(), "RENEW");
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . 'Product has been renewed successfully:'. $productId
            );
            return $result->setData(['status' => 'success']);
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . 'Error occurred while renewing product.'. $productId
            );
            return $result->setData(['status' => 'Failure', 'message' => $e->getMessage()]);
        }
    }
}
