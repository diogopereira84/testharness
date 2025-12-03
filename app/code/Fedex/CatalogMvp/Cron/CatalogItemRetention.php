<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Cron;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\ProductRepository;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Fedex\CatalogMvp\Model\ProductActivityFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CatalogItemRetention
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    private AttributeSetRepositoryInterface $attributeSet;

    /**
     * Constructor
     *
     * @param LoggerInterface $loggerInterface
     * @param ToggleConfig $toggleConfig
     * @param CollectionFactory $productCollectionFactory
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param Registry $registry
     * @param ProductActivityFactory $productActivityFactory
     * @param Product $product
     * @param AttributeSetRepositoryInterface $attributeSet
     * @param CatalogMvp $catalogMvpHelper
     */
    public function __construct(
        LoggerInterface $loggerInterface,
        protected ToggleConfig $toggleConfig,
        protected CollectionFactory $productCollectionFactory,
        private CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        private ScopeConfigInterface $scopeConfigInterface,
        private Registry $registry,
        private ProductActivityFactory $productActivityFactory,
        private Product $product,
        AttributeSetRepositoryInterface $attributeset,
        private CatalogMvp $catalogMvpHelper,
        protected ProductRepositoryInterface $productRepositoryInterface
    ) {
        $this->logger = $loggerInterface;
        $this->attributeSet = $attributeset;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $retentiondate = new \DateTime();
        $retentiontime=$this->scopeConfigInterface->getValue('fedex/config/retention_period');
        if(!empty($retentiontime)){
           $retentiondate->modify('-'.$retentiontime." months");
           $retentiondate->setTimezone(new \DateTimeZone('America/Los_Angeles'));
           $retentionformmateddate=$retentiondate->format('Y-m-d H:i:s');
           $collectionForActive = $this->productCollectionFactory->create();
           $attributeSetFilterId= $this->catalogMvpHelper->getAttrSetIdByName('PrintOnDemand');
           $subquery="select product_id from sales_order_item where updated_at>='".$retentionformmateddate."'";
           $collectionForActive->addAttributeToSelect('*')->addAttributeToFilter('updated_at',['lteq'=>$retentionformmateddate])
           ->addAttributeToFilter('pod2_0_editable','1')->addAttributeToFilter('attribute_set_id',$attributeSetFilterId);
           $collectionForActive->getSelect()->where('e.entity_id NOT IN(?)', new \Zend_Db_Expr(($subquery)));
           if($this->registry->registry('isSecureArea') === null) {
                 $this->registry->register('isSecureArea', true);
            }
            $activityData = [];
            foreach($collectionForActive as $collection)
            {
            try{
                    $product=$this->productRepositoryInterface->getById($collection->getEntityId());
                    $documentIds = $this->catalogDocumentRefranceApi->getDocumentId($product->getExternalProd());
                    $this->productRepositoryInterface->delete($product);
                     $productId=$collection->getEntityId();
                     $productName=$collection->getName();
                     $activityData['user_id'] = 1;
                     $activityData['product_id'] = $productId;
                     $activityData['user_name'] = 'retention cron';
                     $activityData['product_name'] = $productName;
                     $activityData['description'] = 'DELETE';
                     $activityData['user_type'] = "3";
                      $productActivity= $this->productActivityFactory->create();
                      $productActivity->getResource()->save($productActivity->setData($activityData));
                     $this->logger->info(__METHOD__ . ':' . __LINE__ .' '."product deleted by retention cron:".$productId);
                     foreach ($documentIds as $documentId){
                        $this->catalogDocumentRefranceApi->deleteProductRef($productId, $documentId);
                    }
                }
                catch(\Exception $e)
                {
                     $this->logger->error(__METHOD__ . ':' . __LINE__ .' '."product_id:".$collection->getEntityId().' '.$e->getMessage());
                }
           }
        }
     }
}
