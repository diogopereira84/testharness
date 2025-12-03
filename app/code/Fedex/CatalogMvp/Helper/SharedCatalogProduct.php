<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class SharedCatalogProduct extends AbstractHelper
{
    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Action $productAction
     * @param CatalogMvp $catalogMvpHelper
     */
    public function __construct(
        Context $context,
        protected CollectionFactory $productCollectionFactory,
        protected Action $productAction,
        protected CatalogMvp $catalogMvpHelper
    ) {
        parent::__construct($context);
    }

    /**
     * @param $sharedCatalogId
     * @param $assignedProductSkus
     * @return void
     */
    public function applyAssignedLogic($sharedCatalogId, $assignedProductSkus)
    {
        $assignedProductsCollection = $this->getProductCollection($assignedProductSkus);
        if ($assignedProductsCollection->getSize()) {

            /** @var Product $product */
            foreach ($assignedProductsCollection->getItems() as $product) {

                $currentSharedCatalogs = $product->getData('shared_catalogs');

                if ($currentSharedCatalogs) {
                    $currentSharedCatalogs = explode(',', $currentSharedCatalogs);

                    if (!in_array($sharedCatalogId, $currentSharedCatalogs)) {
                        $currentSharedCatalogs[] = $sharedCatalogId;
                       if($this->catalogMvpHelper->isD216406Enabled()){
                           $this->productAction->updateAttributes(
                               [$product->getId()],
                               ['shared_catalogs' => implode(',',array_unique($currentSharedCatalogs))],
                               0
                           );
                       }else{
                           $this->productAction->updateAttributes(
                               [$product->getId()],
                               ['shared_catalogs' => implode(',',$currentSharedCatalogs)],
                               0
                           );
                       }
                    }
                }
            }
        }
    }

    /**
     * @param $sharedCatalogId
     * @param $unassignedProductSkus
     * @return void
     */
    public function applyUnassignedLogic($sharedCatalogId, $unassignedProductSkus)
    {
        $unassignedProductsCollection = $this->getProductCollection($unassignedProductSkus);
        if ($unassignedProductsCollection && $unassignedProductsCollection->getSize()) {

            /** @var Product $product */
            foreach ($unassignedProductsCollection->getItems() as $product) {

                $currentSharedCatalogs = $product->getData('shared_catalogs');
                if ($currentSharedCatalogs) {

                    $currentSharedCatalogs = explode(',', $currentSharedCatalogs);
                    if (in_array($sharedCatalogId, $currentSharedCatalogs)) {
                        $key = array_search($sharedCatalogId, $currentSharedCatalogs);
                        unset($currentSharedCatalogs[$key]);
                        if($this->catalogMvpHelper->isD216406Enabled()){
                            $this->productAction->updateAttributes(
                                [$product->getId()],
                                ['shared_catalogs' => implode(',',array_unique($currentSharedCatalogs))],
                                0
                            );
                        }else{
                            $this->productAction->updateAttributes(
                                [$product->getId()],
                                ['shared_catalogs' => implode(',',$currentSharedCatalogs)],
                                0
                            );
                        }

                    }
                }
            }
        }
    }

    /**
     * @param $skuList
     * @return Collection|AbstractDb
     */
    public function getProductCollection($skuList)
    {
        return $this->productCollectionFactory->create()
            ->addAttributeToSelect('shared_catalogs')
            ->addFieldToFilter('sku', ['IN' => $skuList])
            ->load();
    }
}
