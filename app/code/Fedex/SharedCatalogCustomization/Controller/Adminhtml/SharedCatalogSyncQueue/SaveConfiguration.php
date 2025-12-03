<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Controller\Adminhtml\SharedCatalogSyncQueue;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\ResponseInterface;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationFactory;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\CollectionFactory
 as ConfigurationCollectionFactory;
use Psr\Log\LoggerInterface;

 /**
  * SaveConfiguration Controller Class
  *
  * @SuppressWarnings(PHPMD.NumberOfChildren)
  */
class SaveConfiguration implements ActionInterface
{
    /**
     * SaveConfiguration Constructor.
     *
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param SharedCatalogSyncQueueConfigurationFactory $sharedCatSynConfigFactory
     * @param SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfigRepository
     * @param ConfigurationCollectionFactory $sharedCatalogSyncConfcollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private RedirectFactory $resultRedirectFactory,
        private ManagerInterface $messageManager,
        protected SharedCatalogSyncQueueConfigurationFactory $sharedCatSynConfigFactory,
        protected SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfigRepository,
        protected ConfigurationCollectionFactory $sharedCatalogSyncConfcollectionFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Execute action to save configuration.
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception
     */
    public function execute()
    {
        $configformData = $this->request->getParams();
        $sharedCatalogId = $configformData['catalog_sync_config']['shared_catalog_id'];
        $legacyCatalogRootFolderId = $configformData['catalog_sync_config']['legacy_catalog_root_folder_id'];
        $categoryId = $configformData['catalog_sync_config']['category_id'];
        $status = $configformData['catalog_sync_config']['status'];

        $sharedCatalogSyncConfcollectionFactory = $this->sharedCatSynConfigFactory->create();
        $sharedCatSynConfig = $sharedCatalogSyncConfcollectionFactory;

        try {
            $configCollectionData = $sharedCatalogSyncConfcollectionFactory->getCollection()
                                    ->addFieldToFilter("shared_catalog_id", $sharedCatalogId)
                                    ->getFirstItem();

            if ($id = $configCollectionData->getId()) {
                // Update existing configuration
                $sharedCatSynConfigRepo = $this->sharedCatalogConfigRepository->getById($id);

                $sharedCatSynConfigRepo->setSharedCatalogId($sharedCatalogId);
                $sharedCatSynConfigRepo->setLegacyCatalogRootFolderId($legacyCatalogRootFolderId);
                $sharedCatSynConfigRepo->setCategoryId($categoryId);
                $sharedCatSynConfigRepo->setStatus($status);
                $this->sharedCatalogConfigRepository->save($sharedCatSynConfigRepo)->getId();
                $this->messageManager->addSuccessMessage(__("Configuration data updated successfully."));
            } else {
                // Creating new configuration
                $sharedCatSynConfig->setSharedCatalogId($sharedCatalogId);
                $sharedCatSynConfig->setLegacyCatalogRootFolderId($legacyCatalogRootFolderId);
                $sharedCatSynConfig->setCategoryId($categoryId);
                $sharedCatSynConfig->setStatus($status);

                $this->sharedCatalogConfigRepository->save($sharedCatSynConfig);

                $this->messageManager->addSuccessMessage(__("Configuration data saved successfully."));
            }

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Not able to submit user request: ' . $e->getMessage());
            $this->messageManager
                ->addErrorMessage(__("We can\'t submit your request, Please try again.".$e->getMessage()));
        }

         $resultRedirect = $this->resultRedirectFactory->create();
         $resultRedirect->setRefererOrBaseUrl();

         return $resultRedirect;
    }
}
