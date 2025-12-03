<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Checkbox;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class PublishedToggle extends AbstractModifier
{
    private ?string $attributeSetName = null;

    public function __construct(
        private LocatorInterface $locator,
        private AttributeSetRepositoryInterface $attributeSetRepository,
        private ToggleConfig $toggleConfig
    ) {}

    public function modifyData(array $data): array
    {
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_e_484727')) {
            return $data;
        }

        $product   = $this->locator->getProduct();
        $key       = (int)($product->getId() ?: 0);

        $data[$key]['product'] = $data[$key]['product'] ?? [];

        $data[$key]['product']['published'] = (int)($product->getData('published') ?? 0);

        if ($this->getAttributeSetName() === 'PrintOnDemand') {
            $data[$key]['product']['published'] = 1;
        }

        return $data;
    }

    public function modifyMeta(array $meta): array
    {
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_e_484727')) {
        return $meta;
        }

        $config = [
            'label' => __('Published'),
            'componentType' => Field::NAME,
            'formElement' => Checkbox::NAME,
            'dataScope' => 'published',
            'dataType' => 'boolean',
            'prefer' => 'toggle',
            'sortOrder' => 61,
            'valueMap' => [
                'true' => 1,
                'false' => 0,
            ],
        ];

        if ($this->getAttributeSetName() === 'PrintOnDemand') {
            $config['default'] = 1;
        }

        return array_replace_recursive(
            $meta,
            [
                'product-details' => [
                    'children' => [
                        'published' => [
                            'arguments' => [
                                'data' => [
                                    'config' => $config,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    private function getAttributeSetName(): ?string
    {
        if ($this->attributeSetName !== null) {
            return $this->attributeSetName;
        }

        try {
            $product = $this->locator->getProduct();
            $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
            $this->attributeSetName = $attributeSet->getAttributeSetName();
        } catch (\Exception) {
            $this->attributeSetName = null;
        }

        return $this->attributeSetName;
    }
}
