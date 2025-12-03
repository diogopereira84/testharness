<?php

namespace Fedex\CatalogMvp\Plugin;

use Fedex\Delivery\Helper\Data as Delivery;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\SharedCatalog\Model\ProductItem;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Fedex\SelfReg\Helper\SelfReg;

class Elasticsearch
{
    /**
     * @param Session $customerSession
     * @param ProductItem $productItem
     * @param ProductFactory $productFactory
     * @param CategoryFactory $categoryFactory
     * @param Registry $registry
     * @param Http $request
     * @param Delivery $deliveryHelper
     * @param CatalogMvp $catalogMvpHelper
     * @param CollectionFactory $productCollectionFactory
     */

    public function __construct(
        protected Session $customerSession,
        protected ProductItem $productItem,
        protected ProductFactory $productFactory,
        protected CategoryFactory $categoryFactory,
        protected Http $request,
        protected Delivery $deliveryHelper,
        protected CatalogMvp $catalogMvpHelper,
        protected CollectionFactory $productCollectionFactory,
        private SelfReg $selfreg
    )
    {
    }

    /**
     * @param Magento\Elasticsearch7\Model\Client\Elasticsearch $subject
     * @param array $query
     */
    public function beforeQuery($subject, $query)
    {
        $action = $this->request->getFullActionName();
        if ($action == "catalog_category_view" && $this->deliveryHelper->isCommercialCustomer()) {
            $category = $this->catalogMvpHelper->getCurrentCategory();
            $catProductIds = $category->getProductCollection()
                ->addAttributeToSelect('*')
                ->getColumnValues('entity_id');

            /* B-1646917 */
            $filteredItem = $this->catalogMvpHelper->getFilteredCategoryItem($category->getProductCollection());
            if(!empty($filteredItem)) {
                $catProductIds = $filteredItem;
            }

            $customer = $this->customerSession->getCustomer();
            $groupId = $customer->getData('group_id');

            $allowSku = $this->productItem->getCollection()
                ->addFieldToFilter('customer_group_id', $groupId)
                ->getColumnValues('sku');

            $productIdsToExclude = [];
            if (!empty($allowSku)) {

                $isSelfRegCustomer = $this->selfreg->isSelfRegCustomer();
                $isSelfRegCategory = $this->catalogMvpHelper->checkPrintCategory();

                    if ($isSelfRegCustomer && !$isSelfRegCategory) {
                        $attributeSetId = $this->catalogMvpHelper->getAttrSetIdByName("PrintOnDemand");
                        $allowProductIds = $this->productFactory->create()
                        ->getCollection()->addAttributeToFilter('sku', ['in' => $allowSku])
                        ->addFieldToFilter('attribute_set_id', ['eq' => $attributeSetId])
                        ->getColumnValues('entity_id');

                        $nonEditableProducts = [];
                        $categoryNonEditableProductCollection = $this->productCollectionFactory->create();
                        $categoryNonEditableProductCollection->getSelect()->where("pod2_0_editable = 0");

                        foreach ($categoryNonEditableProductCollection as $product) {
                            $nonEditableProducts[] = $product->getId();
                        }

                        $allowProductIds = array_diff($allowProductIds, $nonEditableProducts);
                    } else {
                        $allowProductIds = $this->productFactory->create()
                        ->getCollection()->addAttributeToFilter('sku', ['in' => $allowSku])
                        ->getColumnValues('entity_id');
                    }

                foreach ($catProductIds as $catProductId) {
                    if (!in_array($catProductId, $allowProductIds)) {
                        $productIdsToExclude[] = $catProductId;
                    }
                }
            }
            if (!empty($productIdsToExclude)) {
                $mustNot = $query['body']['query']['bool']['must_not'];
                $mustNot[] = [
                    'ids' => ['values' => $productIdsToExclude],
                ];
                $query['body']['query']['bool']['must_not'] = $mustNot;
            }

            $must = $query['body']['query']['bool']['must'];
            $must[] = [
                'ids' => ['values' => $catProductIds],
            ];
            $query['body']['query']['bool']['must'] = $must;
        }
        return [$query];
    }
}
