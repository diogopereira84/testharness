<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;

/**
 * Class CreateCategory
 * Handle the CreateCategory of the CatalogMvp
 */
class CreateCategory implements ActionInterface
{

    protected $categoryFactory;
    protected $catalogMvpHelper;
    private CategoryFactory $_categoryFactory;


    /**
     * CreateCategory Constructor
     *
     * @param Context $context,
     * @param CategoryFactory $categoryFactory,
     * @param LoggerInterface $logger,
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param JsonFactory $jsonFactory
     * @param CatalogMvp $catalogMvpHelper
     * @param ToggleConfig $toggleConfig
     * @param CatalogMvpConfigInterface $catalogMvpConfigInterface
     * @param EtagHelper $etagHelper
     */
    public function __construct(
        protected Context $context,
        CategoryFactory $categoryFactory,
        protected LoggerInterface $logger,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected JsonFactory $jsonFactory,
        CatalogMvp $catalogMvpHepler,
        protected ToggleConfig $toggleConfig,
        protected CatalogMvpConfigInterface $catalogMvpConfigInterface,
        protected EtagHelper $etagHelper
    ) {
        $this->_categoryFactory = $categoryFactory;
        $this->catalogMvpHelper = $catalogMvpHepler;
    }
    /**
     * Exceute Funtion
     */
    public function execute()
    {
        $json = $this->jsonFactory->create();
        $categoryCreated = 0;

        /* B-2371268 Create ETag for catalog pages */
        $isB2371268enabled = $this->catalogMvpConfigInterface->isB2371268ToggleEnabled();

        $categoryName = $this->context->getRequest()->getParam('name');
        $id = $this->context->getRequest()->getParam('id');
        try {
            $category = $this->_categoryFactory->create();
            $category->setName($this->catalogMvpHelper->generateCategoryName($categoryName, $id));
            $category->setParentId($id);
            $category->setStoreId(0);
            $category->setIsActive(true);
            $category->setCustomAttributes([
                'pod2_0_editable' => 1,
                'is_anchor' => 0,
                'is_publish' => 1
            ]);
            if ($isB2371268enabled) {
                // Handle ETag for the new category
                $etag = $this->etagHelper->generateEtag($category);
                $category->setEtag($etag);

                // Handle ETag for the parent category, if ID is provided
                if ($id) {
                    $parentCategory = $this->categoryRepositoryInterface->get($id);
                    $parentEtag = $this->etagHelper->generateEtag($parentCategory);
                    $parentCategory->setData('etag', $parentEtag);
                    $parentCategory->save();
                }
            }
            $this->categoryRepositoryInterface->save($category);
            $categoryCreated = 1;
        } catch (\Exception $e) {
             $this->logger->critical(__METHOD__.':'.__LINE__.':Error found while creating category: '
            . $categoryName . ' ' . $e->getMessage());
            $categoryCreated = 0;
        }
        $json->setData($categoryCreated);
        return $json;
    }
}
