<?php

declare(strict_types=1);

namespace Fedex\InBranch\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Fedex\InBranch\Model\Config\E366082DocumentLevelRouting;

class Location extends AbstractModifier
{
    public function __construct(
        protected LocatorInterface            $locator,
        protected UrlInterface                $urlBuilder,
        protected ArrayManager                $arrayManager,
        protected E366082DocumentLevelRouting $toggle,
    ) {
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
        if ($this->toggle->isActive()) {
            $fieldCode = 'product_location_branch_number';
            $elementPath = $this->arrayManager->findPath($fieldCode, $meta, null, 'children');
            $containerPath = $this->arrayManager->findPath(static::CONTAINER_PREFIX . $fieldCode, $meta, null, 'children');
            if (!$elementPath) {
                return $meta;
            }
            return $this->arrayManager->merge(
                $containerPath,
                $meta,
                ['arguments' => [
                    'data' => [
                        'config' => [
                            'sortOrder' => 20
                        ]
                    ]
                ], 'children' => [
                    $fieldCode => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'additionalClasses' => 'location-input',
                                    'elementTmpl' => 'Fedex_InBranch/form/element/location-input',
                                    'component' => 'Fedex_InBranch/js/form/element/location-input',
                                    'validation' => [
                                        'store-validate-location' => true
                                    ],
                                    'tooltip' => [
                                        'description' => __('Assigning a product location will automatically route the entire order to the assigned product location.')
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
                ]
            );

        } else {
            return $meta;
        }
    }
}
