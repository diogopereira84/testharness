<?php

namespace Fedex\CategoryLayout\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\CategoryFactory;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Update
 *
 * @package Fedex\CategoryLayout\Controller\Adminhtml\Index
 */
class Update implements ActionInterface
{
    /**
     * Constructor
     *
     * @param PageFactory        $resultPageFactory
     * @param JsonFactory        $resultJsonFactory,
     * @param RequestInterface   $request
     * @param CategoryFactory    $categoryFactory
     * @param MessageInterface   $messageInterface
     * @param PublisherInterface $publisherInterface
     * @param LoggerInterface    $logger
     */
    public function __construct(
        private readonly PageFactory        $resultPageFactory,
        private readonly JsonFactory        $resultJsonFactory,
        private readonly RequestInterface   $request,
        private readonly CategoryFactory    $categoryFactory,
        private readonly MessageInterface   $messageInterface,
        private readonly PublisherInterface $publisherInterface,
        private readonly LoggerInterface    $logger
    ) {
    }

    /**
     * Update action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $browseCatalogCatId = $this->request->getPostValue('category_id') ?? null;
        $this->logger->info('Browse catalog category was selected for anchor property disable: '. $browseCatalogCatId);

        if (!empty($browseCatalogCatId)) {
        
            $category = $this->categoryFactory->create()->load($browseCatalogCatId);
            $categoryIds = $category->getAllChildren(true);
           
            $this->handleCategoryUpdate($categoryIds, $browseCatalogCatId);

            return $this->resultJsonFactory->create()->setData([]);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Fedex_CategoryLayout::category_update');
        $resultPage->getConfig()->getTitle()->prepend(__("Category Update Manager"));

        return $resultPage;
    }

     /**
     * Publish message into category update queue
     * @param string $categoryIds
     * @param string $browseCatalogCatId
     */
    public function handleCategoryUpdate($categoryIds, $browseCatalogCatId)
    {
        foreach ($categoryIds as $categoryId) {
            $categoryUpdateData = [
                'categoryId'        => $categoryId,
                'browseCatalogCatId' => $browseCatalogCatId
            ];
    
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Publich Categories into
                 categoryAttributeUpdate for the the browse catalog id : ' . $browseCatalogCatId);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . json_encode($categoryUpdateData));
    
            // publish into category attribute update queue
            $this->messageInterface->setMessage(json_encode($categoryUpdateData));
            $this->publisherInterface->publish('categoryAttributeUpdate', $this->messageInterface);
        }
    }
}
