<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Model\Entity\Attribute\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\AttributeFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * @api
 * @since 100.0.2
 */
class MultiselectAttributes extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    protected AttributeFactory $_eavAttrEntity;

    public function __construct(
        AttributeFactory $eavAttrEntity,
        protected AttributeRepositoryInterface $attributeRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected SortOrderBuilder $sortOrderBuilder
    ) {
        $this->_eavAttrEntity = $eavAttrEntity;
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {

            $this->_options = [];

            $sortOrder = $this->sortOrderBuilder
                ->setField('position')
                ->setAscendingDirection()
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('frontend_input', 'multiselect')
                ->addFilter('attribute_code', ['visible_attributes', 'canva_size'], 'nin')
                ->addSortOrder($sortOrder);

            if($this->_attribute && $attrSetId = $this->_attribute->getAttributeSetId()) {
                $searchCriteria->addFilter('attribute_set_id', $attrSetId);
            }

            $searchCriteria = $searchCriteria->create();
            $attributesList = $this->attributeRepository->getList(Product::ENTITY, $searchCriteria);

            foreach ($attributesList->getItems() as $attribute) {

                $this->_options[] = [
                    'label' => $attribute->getDefaultFrontendLabel(),
                    'value' => $attribute->getAttributeCode()
                ];
            }
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|int $value
     * @return string|false
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();

        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => '',
                'extra' => null,
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 1,
                'nullable' => false,
                'comment' => $attributeCode . ' column',
            ],
        ];
    }

    /**
     * Retrieve Indexes(s) for Flat
     *
     * @return array
     */
    public function getFlatIndexes()
    {
        $indexes = [];

        $index = 'IDX_' . strtoupper($this->getAttribute()->getAttributeCode());
        $indexes[$index] = ['type' => 'index', 'fields' => [$this->getAttribute()->getAttributeCode()]];

        return $indexes;
    }
}
