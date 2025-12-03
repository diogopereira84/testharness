<?php

namespace Fedex\CatalogMvp\Controller\Index;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;

class RenameFolder extends \Magento\Framework\App\Action\Action
{
    /**
     * @param Context $context
     * @param Category $category
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param JsonFactory $resultJsonFactory
     * @param CatalogMvp $catalogMvp
     * @param StoreManagerInterface $storeManager
     * @param ToggleConfig $toggleConfig
     * @param CatalogMvpConfigInterface $catalogMvpConfigInterface
     * @param EtagHelper $etagHelper
     */

    public function __construct(
        Context $context,
        protected Category $category,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected JsonFactory $resultJsonFactory,
        protected CatalogMvp $catalogMvp,
        protected StoreManagerInterface $storeManager,
        protected ToggleConfig $toggleConfig,
        protected CatalogMvpConfigInterface $catalogMvpConfigInterface,
        protected EtagHelper $etagHelper
    ) {
        return parent::__construct($context);
    }

    /**
     * Execute Function
     */
    public function execute()
    {
        $data = $this->getRequest()->getPost();
        $json = json_encode($data);
        $dataArray = json_decode($json, true);
        $isToggleEnable = $this->catalogMvp->isMvpSharedCatalogEnable();
        $isD231833ToggleEnabled = $this->catalogMvp->isD231833FixEnabled();
        $isAdminUser = $this->catalogMvp->isSharedCatalogPermissionEnabled();
        /* B-2371268 Create ETag for catalog pages */
        $isB2371268enabled = $this->catalogMvpConfigInterface->isB2371268ToggleEnabled();

        if ($isToggleEnable && $isAdminUser && $dataArray && isset($dataArray['id']) && isset($dataArray['name'])) {
            $id = $dataArray['id']; //category id
            $newName = $dataArray['name'];
            $storeId = '0'; // Get Store ID
            $resultJson = $this->resultJsonFactory->create();
            $currentStore = $this->storeManager->getStore()->getStoreId();
            try {
                     $categoryData = $this->categoryRepositoryInterface->get($id);
                if ($categoryData->getName() != $newName) {
                    $newName = $this->catalogMvp->generateCategoryName($newName, $categoryData->getParentId());
                }
                     $categoryData->setName($newName);
                     if($isD231833ToggleEnabled) {
                        $newUrlKey = $this->catalogMvp->generateUrlKey($newName);
                        $categoryData->setUrlKey($newUrlKey);
                    }
                     $this->categoryRepositoryInterface->save($categoryData->setStoreId($storeId));
                     $categoryData = $this->categoryRepositoryInterface->get($id);
                     $categoryData->setName($newName);
                     if($isD231833ToggleEnabled) {
                        $categoryData->setUrlKey($newUrlKey);
                     }
                     $this->categoryRepositoryInterface->save($categoryData->setStoreId($currentStore));
                if ($isB2371268enabled) {
                    $etag = $this->etagHelper->generateEtag($categoryData);
                    $categoryData->setEtag($etag);
                    $categoryData->setStoreId($currentStore);
                    $categoryData->save();

                    // Handle ETag for the parent category, if ID is provided
                    $parentCategoryId = $categoryData->getParentId();
                    if ($parentCategoryId) {
                        $parentCategory = $this->categoryRepositoryInterface->get($parentCategoryId);
                        $parentEtag = $this->etagHelper->generateEtag($parentCategory);
                        $parentCategory->setEtag($parentEtag);
                        $parentCategory->save();
                    }
                }
                return $resultJson->setData(['status' => 'success', 'message' => 'Folder has been renamed.']);
            } catch (\Exception $e) {
                return $resultJson->setData(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }
}
