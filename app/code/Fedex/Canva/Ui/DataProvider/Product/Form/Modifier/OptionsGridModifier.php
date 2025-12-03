<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * @class OptionsGridModifier
 * This class provide a way to customize the default component view
 */
class OptionsGridModifier extends AbstractModifier
{
    /**
     * Canva size attribute code
     */
    public const ATTRIBUTE_CODE = 'canva_size';

    /**
     * element component path
     */
    public const ELEMENT_COMPONENT = 'Fedex_Canva/js/form/element/options';

    /**
     * element template path
     */
    public const ELEMENT_TEMPLATE = 'Fedex_Canva/form/element/options';

    /**
     * @param ArrayManager $arrayManager
     * @param CollectionFactory $attributesCollection
     */
    public function __construct(
        protected ArrayManager $arrayManager,
        protected CollectionFactory $attributesCollection
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta): array
    {
        $containerPath = $this->arrayManager->findPath(
            static::CONTAINER_PREFIX . self::ATTRIBUTE_CODE,
            $meta,
            null,
            'children'
        );
        if (!$containerPath) {
            return $meta;
        }
        return $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
                'children'  => [
                    self::ATTRIBUTE_CODE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'component'     => self::ELEMENT_COMPONENT,
                                    'elementTmpl'   => self::ELEMENT_TEMPLATE,
                                ],
                            ],
                        ],
                    ]
                ]
            ]
        );
    }
}
