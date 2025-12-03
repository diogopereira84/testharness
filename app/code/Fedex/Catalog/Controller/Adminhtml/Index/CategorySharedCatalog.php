<?php

/**
 * Fedex_Catalog
 *
 * @category   Fedex
 * @package    Fedex_Catalog
 * @author     Bhairav Singh
 * @email      bhairav.singh.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

namespace Fedex\Catalog\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;

/**
 * CategorySharedCatalog Controller Class
 *
 * @method object excute()
 */
class CategorySharedCatalog implements ActionInterface
{
    private CollectionFactory $sharedCatalogCollectionFactory;

    /**
     * CategorySharedCatalog Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Http $request
     * @param CompanyFactory $companyFactory
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param CategoryRepositoryInterface $categoryRepository
     * @return void
     */
    public function __construct(
        protected Context $context,
        protected JsonFactory $resultJsonFactory,
        private Http $request,
        private CompanyFactory $companyFactory,
        CollectionFactory $collectionFactory,
        private LoggerInterface $logger,
        protected CategoryRepositoryInterface $categoryRepository
    ) {
        $this->sharedCatalogCollectionFactory = $collectionFactory;

    }

    /**
     * Execute controller action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $categoryId = $this->context->getRequest()->getParam('category_id');
        $sharedCatalogId = 0;
        $sharedCatalogCategoryId = (int)$this->getSharedCatalogCategoryId($categoryId);
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('shared_catalog_id', ['eq' => $sharedCatalogCategoryId])
            ->getFirstItem();

        if (!empty($companyObj->getId())) {
            $customerGroupId = $companyObj->getCustomerGroupId();
            // Get Shared Catalog Id by Customer group id
            $sharedCatalogId = $this->getSharedCatalog($customerGroupId);
        }

        return $this->resultJsonFactory->create()->setData(["shared_catalog_id" => $sharedCatalogId]);
    }

    /**
     * Get Shared Catalog
     * @param int $customerGroupId
     * @return null|int
     */
    public function getSharedCatalog($customerGroupId)
    {
        $sharedCatalogId = null;
        $collection = $this->sharedCatalogCollectionFactory->create();
        $collection->addFieldToFilter('customer_group_id', ['eq' => $customerGroupId]);

        if ($collection->getSize()) {
            $sharedCatalog = $collection->getFirstItem();
            $sharedCatalogId = $sharedCatalog->getId();
        }

        return $sharedCatalogId;
    }

    /**
     * get SharedCatalogCategoryId
     *
     * @param int $categoryId
     * @return int
     */
    public function getSharedCatalogCategoryId($categoryId)
    {
        $categoryPathIds = [];
        $sharedCatalogCategoryId = 0;
        $category = $this->categoryRepository->get($categoryId);
        $categoryPathIds = $category->getPath() ? explode('/', $category->getPath()) : [];
        if(!empty($categoryPathIds) && count($categoryPathIds) >= 2) {
            $sharedCatalogCategoryId = $categoryPathIds[2];
        }
        return $sharedCatalogCategoryId;
    }
}
