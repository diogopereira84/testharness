<?php
/**
 * @category  Fedex
 * @package   Fedex_CustomerGroup
 * @author    Anuj Kumar <anuj.kumar.osv@fedex.com>
 * @copyright 2024 Fedex
 */
namespace Fedex\CustomerGroup\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class CategorySavePrepareObserver implements ObserverInterface
{
    /**
     * @param CatalogMvp $catalogMvpHelper
     * @param LoggerInterface $logger
     * @param FolderPermission $folderPermission
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        protected LoggerInterface $logger,
        protected FolderPermission $folderPermission,
        protected CollectionFactory $productCollectionFactory
    )
    {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $category = $observer->getCategory();
        $request = $observer->getRequest()->getPostValue();

        $groupIds = $this->folderPermission->getCustomerGroupIds([$category->getId()]);
        if (!empty($groupIds)) {
            foreach ($groupIds as $groupId) {
                if (isset($request['vm_category_products'])) {
                    $productIds = array_keys((array) json_decode($request['vm_category_products']));
                    $unAssignProductIds = $this->getUnassignedProductIds($category->getId(), $productIds);
                    if (!empty($unAssignProductIds)) {
                        $this->folderPermission->unAssignCustomerGroupId($unAssignProductIds, $groupId, true);
                    }
                    $this->folderPermission->assignCustomerGroupId($productIds, $groupId, true);
                } else {
                    $this->folderPermission->assignCustomerGroupId([$category->getId()], $groupId);
                }
            }
        }
        return $this;
    }

    /**
     * Get Unassigned Product Ids
     * @param $categoryId
     * @param $productIds
     * @return array
     */
    public function getUnassignedProductIds($categoryId, $productIds)
    {
        $productCollection = $this->productCollectionFactory->create()
                                ->addFieldToSelect('entity_id')
                                ->addCategoriesFilter(['eq' => $categoryId])
                                ->load();
        $productCollectionIds = [];
        foreach ($productCollection as $product) {
            $productCollectionIds[] = $product->getId();
        }
        $unAssignProductIds = array_diff($productCollectionIds, $productIds);
        
        return $unAssignProductIds;
    }
}
