<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;

class Product implements ArrayInterface
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeSetCollectionFactory $attributeSetCollection
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected AttributeSetCollectionFactory $attributeSetCollection
    )
    {
    }
    
    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $attributeSetName = 'FXOPrintProducts';
        $attributeSetId = $this->getAttrSetIdByName($attributeSetName);
        if ($attributeSetId !== null) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(
                    ProductInterface::STATUS,
                    Status::STATUS_ENABLED
                )
                ->addFilter('attribute_set_id', $attributeSetId)
                ->create();
            $products = $this->productRepository->getList($searchCriteria);

            foreach ($products->getItems() as $product) {
                $label = $product->getName() . ' (SKU: ' . $product->getSku() . ')';
                $options[] = [
                    'value' => $product->getId(),
                    'label' => $label
                ];
            }
        }

        return $options;
    }

    /**
     * Get attribute set id by name
     *
     * @param varchar $attributeSetName
     * @return int
     */
    public function getAttrSetIdByName($attributeSetName)
    {
        $attributeSet = $this->attributeSetCollection->create()
            ->addFieldToFilter('attribute_set_name', $attributeSetName)
            ->getFirstItem();

        $attributeSetId = null;

        if (is_object($attributeSet)) {
            $attributeSetId = $attributeSet->getAttributeSetId();
        }

        return $attributeSetId;
    }
}
