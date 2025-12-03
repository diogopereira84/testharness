<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Controller\Adminhtml\NewCategory;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Save class for create new category popup
 */
class Save implements ActionInterface
{
    /**
     * Save Class Constructor
     *
     * @param Context $context
     * @param CategoryFactory $categoryFactory
     * @param JsonFactory $resultJsonFactory
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        protected Context $context,
        protected CategoryFactory $categoryFactory,
        protected JsonFactory $resultJsonFactory,
        protected CookieManagerInterface $cookieManager
    )
    {
    }

    /**
     * Execute class
     *
     * @return Json
     */
    public function execute():Json
    {
        $data = $this->context->getRequest()->getParam('data');
        $error = false;
        $message = '';
        $createdCategory = null;
        $groupId = $this->cookieManager->getCookie('group_id');
        $parentId = isset($data['parent']) ? $data['parent'] : null;
        $categoryName = isset($data['name']) ? $data['name'] : '';
        $resultJson = $this->resultJsonFactory->create();

        try {
            if ($parentId) {
                $parentCategory = $this->categoryFactory->create()->load($parentId);
    
                // Check if the parent category exists
                if ($parentCategory && $parentCategory->getId()) {
                    $category = $this->categoryFactory->create();
                    $cate = $category->getCollection()
                        ->addAttributeToFilter('name', $categoryName)
                        ->addAttributeToFilter('parent_id', $parentId)
                        ->getFirstItem();
    
                    if (!$cate->getId()) {
                        $category->setPath($parentCategory->getPath())
                            ->setParentId($parentId)
                            ->setName($categoryName)
                            ->setIsActive(true);
                        $category->save();
                    }
    
                    // Get Id for newly created category
                    $cate = $category->getCollection()
                        ->addAttributeToFilter('name', $categoryName)
                        ->getFirstItem();
                    $createdCategory = $cate->getId();
                }
            } else {
                $error = true;
                $message = 'Parent ID is null.';
            }

        } catch (Exception $e) {
            $error = true;
            $message = $e->getMessage();
        }
        $resultJson->setData([
            'messages' => $message,
            'error' => $error,
            'data' => [
                'groupId' => $groupId,
                'new_category_id' => $createdCategory
            ]
        ]);
        return $resultJson;
    }
}
