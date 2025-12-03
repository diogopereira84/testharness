<?php

namespace Fedex\CatalogMvp\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Boolean;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class Pod2Editable extends AbstractModifier
{
    /**
     * Dataprovider Constructor.
     *
     * @param LocatorInterface $locator
     */

    public function __construct(
        private LocatorInterface $locator,
        private CatalogMvp $catalogMvpHelper
    )
    {
    }

    /**
     * B-1765357
     *
     * modifyData for the edit page
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        $productId = $product->getId();
        $pod20editable = $this->catalogMvpHelper->isProductPodEditAbleById($productId);
        $fxoMenuId = $this->catalogMvpHelper->getFxoMenuId($productId);
        $data = array_replace_recursive(
            $data,
            [
                $productId => [
                    'product' => [
                        'pod2_0_editable' => (int)$pod20editable
                    ],
                    'extraconfiguratorvalue' => [
                        'fxo_menu_id' => $fxoMenuId,
                        'entity_id' => $productId
                    ]
                ]
            ]
        );
        return $data;
    }

    /**
     * B-1765357
     *
     * modifyMeta for the edit page
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                'product-details' => [
                    'children' => [
                    'pod2_0_editable' => $this->getPod20EditableField()
                    ],
                ]
            ]
        );
        return $meta;
    }

    /**
     * B-1765357
     *
     * get Field Data for the POD2.0 Editable
     */
    public function getPod20EditableField()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('POD2.0 Editable'),
                        'componentType' => Field::NAME,
                        'formElement' => Checkbox::NAME,
                        'dataScope' => 'pod2_0_editable',
                        'prefer' => 'toggle',
                        'dataType' => Boolean::NAME,
                        'sortOrder' => 60,
                        'valueMap' => [
                            'true' => 1,
                            'false' => 0,
                        ],
                    ],
                ],
            ],
        ];
    }
}
