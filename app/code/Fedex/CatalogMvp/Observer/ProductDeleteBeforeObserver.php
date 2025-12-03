<?php
namespace Fedex\CatalogMvp\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\Session as catalogSession;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;


class ProductDeleteBeforeObserver implements ObserverInterface
{
    /**
    * @var Magento\Catalog\Model\ProductFactory
     */
    protected $product;

    /**
     * @param \Fedex\CatalogMvp\Helper\CatalogMvp $catalogMvpHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepository
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param Magento\Catalog\Model\ProductFactory $product
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param ToggleConfig  $toggleConfig
     * 
     */
    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        protected LoggerInterface $logger,
        protected AttributeSetRepositoryInterface $attributeSetRepository,
        protected catalogSession $catalogSession,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        ProductFactory $product,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected ToggleConfig $toggleConfig
    ) {
        $this->product = $product;
     }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        try {
            if ($this->catalogMvpHelper->isMvpCtcAdminEnable()) {
                $product = $observer->getEvent()->getProduct();
                $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
                if (CatalogMvp::PRINT_ON_DEMAND ==$attributeSet->getAttributeSetName()) {
                    
                       $productModel= $this->productRepositoryInterface->getById($product->getId());
                    
                    $documentId = $this->catalogDocumentRefranceApi->getDocumentId($productModel->getExternalProd());
                    $this->catalogSession->setDocumentId($documentId);
                    $this->catalogSession->setProductName($productModel->getName());
                }
            }
       } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
