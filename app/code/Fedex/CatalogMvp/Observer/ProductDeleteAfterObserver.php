<?php

namespace Fedex\CatalogMvp\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Session as catalogSession;
use Fedex\CatalogMvp\Model\ProductActivity;
use Magento\Backend\Model\Auth\Session as AdminSession;

class ProductDeleteAfterObserver implements ObserverInterface
{
    /**
     * @param \Fedex\CatalogMvp\Helper\CatalogMvp $catalogMvpHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepository
     * @param \Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param \Magento\Catalog\Model\Session $catalogSession
     */
    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        protected LoggerInterface $logger,
        protected AttributeSetRepositoryInterface $attributeSetRepository,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        protected catalogSession $catalogSession,
        private ProductActivity $productActivity,
        private AdminSession $adminSession
    )
    {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if ($this->catalogMvpHelper->isMvpCtcAdminEnable()) {
                $product = $observer->getProduct();
                $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
                $productId = $product->getId();
                $productName = $this->catalogSession->getProductName();
                $this->catalogSession->unsProductName();
                if (CatalogMvp::PRINT_ON_DEMAND ==$attributeSet->getAttributeSetName()) {
                    $documentIds = $this->catalogSession->getDocumentId();
                    $this->catalogSession->unsDocumentId();
                    foreach ($documentIds as $documentId){
                        $this->catalogDocumentRefranceApi->deleteProductRef($product->getId(), $documentId);
                    }
                }

                /** add entry in product activity table */
                if ($this->adminSession->isLoggedIn()) {
                    $currentUser = $this->getCurrentUser();
                    $userName = $currentUser->getName();
                    $userId = $currentUser->getId();
                    $description = "DELETE";
                    $activityData = [];
                    $activityData['user_id'] = $userId;
                    $activityData['product_id'] = $productId;
                    $activityData['user_name'] = $userName;
                    $activityData['product_name'] = $productName;
                    $activityData['user_type'] = "2";
                    $activityData['description'] = $description;
                    try {
                        $this->productActivity->setData($activityData)->save();
                    } catch (\Exception $e) {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ .' Something happen bad while saving product activity from CTC admin: ' . $e->getMessage());
                    }
                }
            }
            return $this;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
       }
    }

    /**
     * Get current admin user detail
     */
    public function getCurrentUser()
    {
        return $this->adminSession->getUser();
    }
}
