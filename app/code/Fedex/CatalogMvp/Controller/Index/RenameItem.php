<?php

namespace Fedex\CatalogMvp\Controller\Index;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class RenameItem extends \Magento\Framework\App\Action\Action

{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Action $action
     * @param CatalogMvp $catalogMvp
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     */

    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        protected Action $action,
        protected CatalogMvp $catalogMvp,
        protected StoreManagerInterface $storeManager,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected LoggerInterface $logger
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPost();
        $json = json_encode($data);
        $dataArray = json_decode($json, true);
        $isToggleEnable = $this->catalogMvp->isMvpSharedCatalogEnable();
        $isD231833ToggleEnabled = $this->catalogMvp->isD231833FixEnabled();
        $isAdminUser = $this->catalogMvp->isSharedCatalogPermissionEnabled();
        $customerSessionId = null;
        if ($isToggleEnable && $isAdminUser && $dataArray && isset($dataArray['id']) && isset($dataArray['name'])) {
            $id = $dataArray['id']; //product id
            $newName = $dataArray['name'];
            $storeId = '0'; // Get Store ID
            $resultJson = $this->resultJsonFactory->create();
            try {
                $attributes = ['name' => $newName];
                if($isD231833ToggleEnabled) {
                    $newUrlKey = $this->catalogMvp->generateUrlKey($newName);
                    $attributes['url_key'] = $newUrlKey;
                }
                $this->action->updateAttributes([$id], $attributes, $storeId);
                $currentStore = $this->storeManager->getStore()->getStoreId();
                $this->action->updateAttributes([$id], $attributes, $currentStore);
                
                $product = $this->productRepositoryInterface->getById($id);
                $externalProd = $product->getData('external_prod');
                $externalProd = json_decode((string) $externalProd , true);
                if(isset($externalProd['userProductName'])) {
                    $externalProd['userProductName'] = $newName;
                }
                else if(isset($externalProd['fxoProductInstance']['productConfig']['product']['userProductName'])) {
                    $externalProd['fxoProductInstance']['productConfig']['product']['userProductName'] = $newName;
                }
                $externalProd =  json_encode($externalProd);
                $this->action->updateAttributes([$id], ['external_prod' => $externalProd], $storeId);
                $this->action->updateAttributes([$id], ['external_prod' => $externalProd], $currentStore);
                
                $this->catalogMvp->insertProductActivity($id, "UPDATE");

                
                    $customerSessionId = $this->catalogMvp->getCustomerSessionId();
                    $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId . ' Catalog Mvp item has been renamed');
                

                return $resultJson->setData(['status' => 'success', 'message' => 'Item has been renamed.']);
            } catch (\Exception $e) {
                $customerSessionId = $this->catalogMvp->getCustomerSessionId();
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . $customerSessionId . 
                    ' Error encounter while product rename : ' . $e->getMessage());

                return $resultJson->setData(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }

    }

}
