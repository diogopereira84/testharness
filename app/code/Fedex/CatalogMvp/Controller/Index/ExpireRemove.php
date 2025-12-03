<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Model\SessionFactory;
use \Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ExpireRemove implements ActionInterface
{
    /**
     * @var Set
     */
    protected $attributeSet;

    /**
     * ExpireRemove Product Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Product $product
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param Set $attributeSet
     * @param SessionFactory $sessionFactory
     * @param ProductRepository $productRepository
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Context $context,
        protected Registry $registry,
        protected Product $product,
        protected LoggerInterface $logger,
        protected CollectionFactory $productCollectionFactory,
        protected SessionFactory $sessionFactory,
        private CatalogMvp $catalogMvpHelper,
        protected ProductRepository $productRepository,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    public function execute()
    {
        $todayDate = date("Y-m-d");
        $productCollection = $this->productCollectionFactory->create();
        $attributeSetFilterId= $this->catalogMvpHelper->getAttrSetIdByName('PrintOnDemand');
        $productCollection->getSelect()->where("pod2_0_editable = 1 AND product_document_expire_date is NOT NULL AND product_document_expire_date < '$todayDate' AND attribute_set_id=".$attributeSetFilterId);
        $customerSession = $this->sessionFactory->create();
        try {
            $this->registry->register('isSecureArea', true);
            $customerSession->setFromMvpProductCreate(true);
            foreach ($productCollection->getData() as $product) {
                    
                       $productLoad=   $this->productRepository->getById($product['entity_id'],false,0);
                       $this->productRepository->delete($productLoad);
                    
                    $this->logger->debug(__METHOD__ . ":" . __LINE__ . " Catalog MVP Expired Product deleted " . $product['entity_id']);
            }
            $customerSession->setFromMvpProductCreate(false);
        } catch (\Exception $e) {
            $customerSession->setFromMvpProductCreate(false);
            $this->logger->debug(__METHOD__ . ":" . __LINE__ . " Product not deleted " . $e->getMessage());
        }
    }
}
