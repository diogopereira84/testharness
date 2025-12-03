<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Magento\Framework\App\RequestInterface;

/**
 * Class CategoryAssignedToCategory
 * Handle the CategoryAssignedToCategory of the CatalogMvp
 */
class CategoryAssignedToCategory implements ActionInterface
{
    /**
     * CategoryAssignedToCategory Constructor
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param CatalogMvpHelper $catalogMvpHelper
     * @param RequestInterface $request
     */
    public function __construct(
        protected Context $context,
        protected LoggerInterface $logger,
        protected JsonFactory $resultJsonFactory,
        protected CatalogMvpHelper $catalogMvpHelper,
        private RequestInterface $request
    )
    {
    }

    /**
     * Handling folder move on folder to another
     */
    public function execute()
    {
        $parentCategoryId = $this->request->getParam('parent_category_id');
        $categoryId = $this->request->getParam('category_id');

        $parentCategoryId = preg_replace("/[^0-9]+/", "", trim($parentCategoryId));
        $categoryId = preg_replace("/[^0-9]+/", "", trim($categoryId));

        $resultJsonData = $this->resultJsonFactory->create();
        $result = [];
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable() && $this->catalogMvpHelper->isSharedCatalogPermissionEnabled()) {
            try {
                if ($parentCategoryId && $categoryId) {
                    $this->catalogMvpHelper->assignCategoryToCategory($parentCategoryId, $categoryId);
                    $result = ['status' => true, 'message' => 'Folder has been moved successfully'];
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ .
                    'Error while folder moving.'.
                    $exception->getMessage()
                );

                $result = ['status' => false, 'message' => $exception->getMessage()];
            }
        }

        return $resultJsonData->setData($result);
    }
}
