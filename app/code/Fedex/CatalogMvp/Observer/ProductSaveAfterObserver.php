<?php

namespace Fedex\CatalogMvp\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Fedex\CatalogMvp\Model\ProductActivity;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\Product\Action as ProductAction;

class ProductSaveAfterObserver implements ObserverInterface
{
    private const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';

    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        protected LoggerInterface $logger,
        protected SharedCatalogRepositoryInterface $sharedCataloginterface,
        protected WebhookInterface $webhookInterface,
        protected CatalogDocumentRefranceApi $catalogdocumentrefapi,
        protected CategoryFactory $categoryFactory,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected RequestInterface $request,
        private Action $action,
        private ScopeOverriddenValue $scopeOverriddenValue,
        protected ProductActivity $productActivity,
        protected AdminSession $adminSession,
        protected ToggleConfig $toggleConfig,
        private ProductAction $productAction
    )
    {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();
        if ($this->catalogMvpHelper->isMvpCtcAdminEnable()) {
            $postData = $this->request->getPostValue();
            $podEditable = $this->catalogMvpHelper->isProductPodEditAbleById($product->getId());

            // Save Shared Catalog in custom attribute for Live Search
                if($this->catalogMvpHelper->isD216406Enabled()){
                    $sharedCatalogIds = is_array($product->getSharedCatalog())
                        ? implode(',', array_unique($product->getSharedCatalog()))
                        : ($product->getSharedCatalog() ?? '');
                }else{
                    $sharedCatalogIds = is_array($product->getSharedCatalog())
                        ? implode(',', $product->getSharedCatalog())
                        : ($product->getSharedCatalog() ?? '');
                }
                $product->setData('shared_catalogs', $sharedCatalogIds);

            if (array_key_exists('product', $postData) && (!isset($postData['back']) || (isset($postData['back']) && $product->getStatus() != 2))) {
                $this->catalogMvpHelper->setProductVisibilityValue($product, $product->getAttributeSetId());
            }

            if (array_key_exists('product', $postData) && $podEditable && isset($postData['product']['external_prod'])) {
                $this->updateCategory($product, $podEditable);
                $this->updateProductPriceFromRateApi($product);
                $extProductJson = $postData['product']['external_prod'];
                $extProductArray = $extProductJson ? json_decode($extProductJson, true) : null;

                if(!isset($extProductArray['fxoMenuId']) && isset($postData['extraconfiguratorvalue']['fxo_menu_id']) && !empty($postData['extraconfiguratorvalue']['fxo_menu_id'])) {
                    $fxoMenuId = $postData['extraconfiguratorvalue']['fxo_menu_id'];
                    $this->catalogMvpHelper->updateFxoMenuId($product->getId(), $fxoMenuId);
                }

                if(isset($extProductArray['fxoMenuId']) && isset($extProductArray['fxoProductInstance']['productConfig']['product'])) {
                    $fxoMenuId = $extProductArray['fxoMenuId'];
                    $this->catalogMvpHelper->updateFxoMenuId($product->getId(), $fxoMenuId);
                }

                // code added to create reference id
                if ($podEditable) {
                    $documentIds = $this->catalogdocumentrefapi->getDocumentId($product->getExternalProd());
                    foreach ($documentIds as $documentId) {
                        $this->catalogdocumentrefapi->addRefernce($documentId, $product->getId());
                    }
                }
            }
        }
        /** add entry in product activity table */
        if ($this->adminSession->isLoggedIn()) {
            $productId = $product->getId();
            $currentUser = $this->getCurrentUser();
            $userName = $currentUser->getName();
            $userId = $currentUser->getId();
            $productName = $product->getName();
            $description = "UPDATE";
            if($product->getCreatedAt() == $product->getUpdatedAt()) {
                $description = "CREATE";
            }
            $activityData = [];
            $activityData['user_id'] = $userId;
            $activityData['product_id'] = $productId;
            $activityData['user_name'] = $userName;
            $activityData['product_name'] = $productName;
            $activityData['user_type'] = "2";
            $activityData['description'] = $description;
            try {

                    $this->productActivity->getResource()->save($this->productActivity->setData($activityData));

            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .' Something happen bad while saving product activity from CTC admin: ' . $e->getMessage());
            }
        }
        return $this;
    }

    /**
     * Get current admin user detail
     */
    public function getCurrentUser()
    {
        return $this->adminSession->getUser();
    }

    public function updateProductPriceFromRateApi($product)
    {
        $sharedCatalogIDs = $product->getSharedCatalog() ?? null;
        if ($sharedCatalogIDs) {
            $requestData = [];
            foreach ($sharedCatalogIDs as $sharedCatalogID) {
                $sharedCatalog = $this->sharedCataloginterface->get($sharedCatalogID);
                $customerGroupId = $sharedCatalog->getData('customer_group_id');
                $requestData[] = [
                    "sku"                   => $product->getSku(),
                    "customer_group_id"     => $customerGroupId,
                    "shared_catalog_id"     => null,
                    'is_for_same_product'   => true
                ];
            }

            // D-180299:: Save price for ondemand storeview
            if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_NON_STANDARD_CATALOG)) {
                $storeId = $this->catalogMvpHelper->getOndemandStoreId();
                $this->productAction->updateAttributes(
                    [$product->getId()],
                    ['price' => $product->getPrice()],
                    $storeId
                );
            }

            $this->webhookInterface->addProductToRM($requestData);
            return true;
        }
    }

    /**
     * function to update category
     */
    public function updateCategory($product, $podEditable) {

        if((!empty($product->getCategoryIds())) && $podEditable) {
            $categoryIds = $product->getCategoryIds();
            foreach ($categoryIds as $categoryId) {

                 $category = $this->categoryRepositoryInterface->get($categoryId);

                $category->setCustomAttributes([
                    'pod2_0_editable' => 1,
                    'is_anchor' => 0
                ]);
                $this->categoryRepositoryInterface->save($category);
            }
        }
    }
}
