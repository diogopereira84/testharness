<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Fedex\Catalog\Model\Config;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

class WysiwygAttributeConfiguration extends AbstractModifier
{
    /**
     * WysiwygAttributeConfiguration constructor.
     * @param ArrayManager $arrayManager
     * @param Config $catalogConfig
     */
    public function __construct(
        protected ArrayManager $arrayManager,
        protected Config $catalogConfig
    )
    {
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $wysiwygAttributeList = $this->catalogConfig->wysiwygAttributeList();
        foreach ($wysiwygAttributeList as $attributeCode) {

            $elementPath = $this->arrayManager->findPath($attributeCode, $meta, null, 'children');
            $containerPath = $this->arrayManager->findPath(
                static::CONTAINER_PREFIX . $attributeCode,
                $meta,
                null,
                'children'
            );

            if (!$elementPath) {
                continue;
            }

            $meta = $this->arrayManager->merge(
                $containerPath,
                $meta,
                [
                    'children'  => [
                        $attributeCode => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'wysiwygConfigData' => [
                                            'current_attribute_code' => $attributeCode
                                        ],
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

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
