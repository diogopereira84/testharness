<?php
/**
 * @category     Fedex
 * @package      Fedex_ProductGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Brajmohan Rajput <brajmohan.rajput.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ProductGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\EavGraphQl\Model\Resolver\Query\Type;
use Magento\EavGraphQl\Model\Resolver\Query\Attribute;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\Product as ProductModel;

class GetProductEngineProperties implements ResolverInterface
{
    /**
     * @param Type $type
     * @param Attribute $attribute
     * @param EavConfig $eavConfig
     * @param ProductModel $productModel
     */
    public function __construct(
        protected Type $type,
        protected Attribute $attribute,
        protected EavConfig $eavConfig,
        protected ProductModel $productModel
    )
    {
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $product = $value['model'];
        $productObj = $this->productModel->load($product->getId());
        $attributes = [];
        $productEngineSelectedAttributes = [];
        $productAttributeValues = $productObj->getVisibleAttributes() ?? null;
        if ($productAttributeValues) {
            $productEngineSelectedAttributes = $this->convertStringToArrayAttributeValue($productAttributeValues);
            foreach ($productEngineSelectedAttributes as $attributeCode) {
                $attributeValues = $productObj->getData($attributeCode) ?? null;
                if ($attributeValues) {
                    $attributeObj = $this->attribute->getAttribute($attributeCode, 'catalog_product');
                    $typeObj = $this->type->getType($attributeCode, 'catalog_product');
                    $attributeSelectedOptions = $this->convertStringToArrayAttributeValue($attributeValues);
                    $attributes[] = [
                        'attribute_code' => $attributeCode,
                        'attribute_label' => $productObj->getResource()->getAttribute($attributeCode)->getStoreLabel(),
                        'attribute_type' => ucfirst($typeObj),
                        'input_type' => isset($attributeObj) ? $attributeObj->getFrontendInput() : null,
                        'attribute_options' => $this->getAttributeOptions($attributeCode, $attributeSelectedOptions)
                    ];
                }
            }
        }

        return $attributes;
    }

    /**
     *  Get attribute options details
     *
     * @param string|int|null $attributeCode
     * @param array $attributeSelectedOptions
     * @return array
     */
    public function getAttributeOptions($attributeCode, $attributeSelectedOptions)
    {
        $attributeObj = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        $options = $attributeObj->getSource()->getAllOptions();
        $arr = [];
        $selectedOptionId = '';
        if (strpos(end($attributeSelectedOptions), 'default-') !== false){
            $defaultSelectedOptions = explode('default-', end($attributeSelectedOptions));
            $selectedOptionId = $defaultSelectedOptions[1];
        }

        foreach ($options as $option) {
            if ($option['value'] > 0 && in_array($option['value'], $attributeSelectedOptions)) {
                $option['default'] = false;
                if ($option['value'] == $selectedOptionId) {
                    $option['default'] = true;
                }
                $arr[] = $option;
            }
        }

        return $arr;
    }

    /**
     *  Convert string to array attribute value
     *
     * @param string|int|null $productAttributeValues
     * @return array
     */
    public function convertStringToArrayAttributeValue($productAttributeValues)
    {
        $arttributeValueArray = [];
        if (strpos($productAttributeValues, ',') !== false) {
            $arttributeValueArray = explode(',', $productAttributeValues);
        } elseif (strpos($productAttributeValues, '|') !== false) {
            $arttributeValueArray = explode('|', $productAttributeValues);
        } else {
            $arttributeValueArray[] = $productAttributeValues;
        }

        return $arttributeValueArray;
    }
}
