<?php
declare(strict_types=1);

namespace Fedex\ProductEngine\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeModel;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

class VisibleAttributesModifier extends AbstractModifier
{
    const IS_MULTISELECT = 'multiselect';
    const IS_PRODUCT_LEVEL_DEFAULT_ENABLED = 1;

    public function __construct(
        protected ArrayManager $arrayManager,
        protected CollectionFactory $attributesCollection
    )
    {
    }

    public function modifyMeta(array $meta)
    {
        $attributeList = $this->attributeWithProductLevelDefault();
        /** @var AttributeModel $attribute */
        foreach ($attributeList as $attribute) {
            $fieldCode = $attribute->getAttributeCode();
            $elementPath = $this->arrayManager->findPath($fieldCode, $meta, null, 'children');
            $containerPath = $this->arrayManager->findPath(static::CONTAINER_PREFIX . $fieldCode, $meta, null, 'children');

            if (!$elementPath) {
                continue;
            }

            $meta = $this->arrayManager->merge(
                $containerPath,
                $meta,
                [
                    'children'  => [
                        $fieldCode => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'component'     => 'Fedex_ProductEngine/js/form/element/multiselect-with-default-select',
                                        'elementTmpl'   => 'Fedex_ProductEngine/form/element/multiselect-with-default-select',
                                    ],
                                ],
                            ],
                        ]
                    ]
                ]
            );
        }

        return $meta;
    }

    public function modifyData(array $data)
    {
        return $data;
    }

    private function attributeWithProductLevelDefault() {
        $attrCollection = $this->attributesCollection->create();
        $attrCollection->addFieldToFilter('frontend_input', self::IS_MULTISELECT);
        $attrCollection->addFieldToFilter('is_product_level_default', self::IS_PRODUCT_LEVEL_DEFAULT_ENABLED);

        return $attrCollection->getItems();
    }
}
