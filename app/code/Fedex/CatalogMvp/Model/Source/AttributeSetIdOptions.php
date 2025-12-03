<?php
declare(strict_types=1);
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Model\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;

class AttributeSetIdOptions extends AbstractSource
{
    /**
     * @param EntityType $entityType
     * @param AttributeSetCollectionFactory $attributeSetCollectionFactory
     */
    public function __construct(
        private EntityType $entityType,
        private AttributeSetCollectionFactory $attributeSetCollectionFactory
    ) {
    }

    /**
     * Retrieve all product attribute sets as options
     *
     * @return array
     */
    public function getAllOptions(): array
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        $entityTypeId = $this->entityType->loadByCode(Product::ENTITY)->getId();

        $attributeSetCollection = $this->attributeSetCollectionFactory->create()
            ->setEntityTypeFilter($entityTypeId)
            ->setOrder('attribute_set_name', 'ASC');

        $this->_options = [];

        foreach ($attributeSetCollection as $attributeSet) {
            $this->_options[] = [
                'value' => $attributeSet->getId(),
                'label' => $attributeSet->getAttributeSetName()
            ];
        }

        return $this->_options;
    }
}
