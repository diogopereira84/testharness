<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Controller\Index;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Helper\Image;
use Fedex\CatalogMvp\Model\ProductActivity;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOCMConfigurator\ViewModel\FXOCMHelper;

class RightPanel implements HttpGetActionInterface
{

    /**
     * Index constructor.
     *
     * @param JsonFactory $jsonFactory
     * @param Context $context
     * @param Product $productModel
     * @param Image $imageHelper
     * @param CatalogMvp $catalogMvpHelper
     * @param MvpHelper $mvpHelper
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private JsonFactory $jsonFactory,
        private Context $context,
        private Product $productModel,
        private Image $imageHelper,
        protected ProductActivity $productActivity,
        protected CatalogMvp $catalogMvpHelper,
        protected ProductRepositoryInterface $productRepository,
        readonly private MvpHelper $mvpHelper,
        protected ToggleConfig $toggleConfig,
        protected FXOCMHelper $fxocmHelper
    )
    {
    }

    public function execute()
    {
        $itemId = $this->context->getRequest()->getParam("item_id");
        
        $json      = $this->jsonFactory->create();
        if($itemId) {
            $productData = $this->getProduct($itemId);
        } else if($itemSku = $this->context->getRequest()->getParam("itemsku")) {
            $productData = $this->getProductSku($itemSku);
            $data['external_prod'] = $productData->getData('external_prod');
            $data['pod2_0_editable'] = $productData->getData('pod2_0_editable');
            $data['attribute_set_id'] = $productData->getData('attribute_set_id');
            $data['customizable'] = $productData->getData('customizable');
            $data['catalog_description'] = $productData->getData('catalog_description');
            $data['related_keywords'] = null;
            if($productData->getData('related_keywords') != "") {
                $data['related_keywords'] = json_encode((array) explode(",",(string) $productData->getData('related_keywords')));
            }
            
            if ($data['external_prod']) {
                $externalProd = json_decode($data['external_prod'], true);
                if (isset($externalProd['contentAssociations']) && is_array($externalProd['contentAssociations']) && count($externalProd['contentAssociations']) > 0) {
                    foreach ($externalProd['contentAssociations'] as $key => $content) {
                        if (!isset($content['pageGroups']) || empty($content['pageGroups'])) {
                            $externalProd['contentAssociations'][$key]['pageGroups'] = $this->fxocmHelper->getPageGroupsPrintReady($content['contentReference'], true);
                        }
                    }
                }

                $data['external_prod'] = json_encode($externalProd);
            }

            $json->setData($data);
            return $json;
        }
        $productData = $this->getProduct($itemId);
        if(is_object($productData)) {
            $productSmallImageUrl = $this->imageHelper->init($productData, 'new_products_content_widget_grid')
            ->setImageFile($productData->getSmallImage())
            ->keepFrame(true)
            ->getUrl();
        } else {
            $productSmallImageUrl = $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
        }
        $productLastUpdatedAt = $productData->getUpdatedAt();
        $productData->setData('small_image_url', $productSmallImageUrl);
        if ($productLastUpdatedAt) {
            $productData->setData('last_modified_date', date("m/d/y", strtotime($productLastUpdatedAt)));
            $productData->setData('last_modified_time', date('h:i A', strtotime($productLastUpdatedAt)));
        }
        $productData->setData('modified_by', "");

        
            $activityData = $this->getProductActivityData($productData->getId());
            if (is_object($activityData) && !empty($activityData->getData())) {
                $productData->setData('last_modified_date', date("m/d/y", strtotime($activityData->getCreatedAt())));
                $productData->setData('last_modified_time', date('h:i A', strtotime($activityData->getCreatedAt())));
                $productData->setData('modified_by', $activityData->getUserName());
            }
        
        if ($this->mvpHelper->isCatalogExpiryNotificationToggle()) {
            $renewLink = $this->mvpHelper->getIsExpiryDocument($productData->getData('entity_id'));
            $isSharedCatalogEnabled = $this->mvpHelper->isSharedCatalogPermissionEnabled();
            if ($isSharedCatalogEnabled && $renewLink) {
                $productData->setData('renewLink', $renewLink);
            } else {
                $productData->setData('renewLink', false);
            }
            
        }
        if ($this->mvpHelper->isNonStandardCatalogToggleEnabled()) {
            $productData->setData('settingsLink', true);
            $productData->setData('sku', $productData->getData('sku'));
            $extProd = $productData->getExternalProd();
            $prodata = !empty($extProd) ? json_decode($extProd, true) : [];
            if (isset($prodata['externalSkus']) && !empty($prodata['externalSkus'])) {
                $productData->setData('requestChange', true);
            }
        }
        
        $productData->setData('previewLinkDisplay', true);
        if($this->toggleConfig->getToggleConfigValue('d_193118_epro_unable_to_add_inbranch_doc_to_cart')) {
            $extProd = $productData->getData('external_prod');
            $externalProd = json_decode($extProd, true);
            if (empty($externalProd['contentAssociations']) && isset($externalProd['contentAssociations'])) {
                $productData->setData('previewLinkDisplay', 'false');
            } 
        }
        
        $json->setData($productData->getData());
        return $json;
    }

    public function getProduct($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * get Product by SKU
     * @param string $sku
     */
    public function getProductSku($sku)
    {
        return $this->productRepository->get($sku);
    }

    /**
     * Get Product Activity Data
     */
    public function getProductActivityData($productId)
    {
        $collection = $this->productActivity->getCollection()->addFieldToFilter('product_id', $productId);
        $collection->setOrder('created_at','DESC');
        return $collection->getFirstItem();
    }
}
