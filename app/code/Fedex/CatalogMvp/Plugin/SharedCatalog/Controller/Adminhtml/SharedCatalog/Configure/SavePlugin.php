<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Save;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SavePlugin
{
    public const SHARED_CATALOG_CUSTOM_ATTRIBUTE ='tigerteam_shared_catalog_custom_attribute';

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param Action $productAction
     * @param WizardFactory $wizardStorageFactory
     * @param ToggleConfig $toggleConfig
     * @param CatalogMvp $catalogMvpHelper
     */
    public function __construct(
        protected CollectionFactory $productCollectionFactory,
        protected Action $productAction,
        protected WizardFactory $wizardStorageFactory,
        private readonly ToggleConfig $toggleConfig,
        protected CatalogMvp $catalogMvpHelper
    )
    {
    }

    /**
     * @param Save $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(Save $subject, $result) {
        //NOSONAR
        $sharedCatalogId = $subject->getRequest()->getParam('catalog_id');
        $currentStorage = $this->wizardStorageFactory->create(['key' => $subject->getRequest()->getParam('configure_key')]);
        $assignedProductSkus = $currentStorage->getAssignedProductSkus();

        if (!empty($assignedProductSkus)) {
            $this->applyAssignedLogic($sharedCatalogId, $assignedProductSkus);
        }

        $unassignedProductSkus = $currentStorage->getUnassignedProductSkus();
        if (!empty($unassignedProductSkus)) {
            $this->applyUnassignedLogic($sharedCatalogId, $unassignedProductSkus);
        }

        return $result;
    }

    /**
     * @param $sharedCatalogId
     * @param $assignedProductSkus
     * @return void
     */
    private function applyAssignedLogic($sharedCatalogId, $assignedProductSkus)
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
                                ['shared_catalogs' => implode(',', array_unique($currentSharedCatalogs))],
                                0
                            );
                        }else {
                            $this->productAction->updateAttributes(
                                [$product->getId()],
                                ['shared_catalogs' => implode(',', $currentSharedCatalogs)],
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
    private function applyUnassignedLogic($sharedCatalogId, $unassignedProductSkus)
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
    protected function getProductCollection($skuList)
    {
        return $this->productCollectionFactory->create()
            ->addAttributeToSelect('shared_catalogs')
            ->addFieldToFilter('sku', ['IN' => $skuList])
            ->load();
    }
}
