<?php
declare (strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Price;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\ArrayManager;

class DltThresholdAttribute extends AbstractModifier
{
    const FIELD_IS_DELETE = 'is_delete';

    const FIELD_SORT_ORDER_NAME = 'sort_order';

    /**
     * DLT attribute code
     */
    public const ATTRIBUTE_CODE = 'dlt_thresholds';

    /**
     * @param LocatorInterface $locator
     * @param LoggerInterface $logger
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        private LocatorInterface $locator,
        private LoggerInterface $logger,
        protected ArrayManager $arrayManager
    )
    {
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $fieldCode = 'dlt_threshold';
        
        $product = $this->locator->getProduct();
        $productId = $product->getId();
        try {
            $dltAttributeValue = $product->getCustomAttribute('dlt_thresholds');
            
            if ($dltAttributeValue) {
                $dltValue = $dltAttributeValue->getValue();
                $decodedDltValue = json_decode($dltValue, true);
                if (is_array($decodedDltValue) && isset($decodedDltValue['dlt_threshold_field'])) {
                    $data[$productId]['product']
                    [$fieldCode]['dlt_threshold_field'] = $decodedDltValue['dlt_threshold_field'];
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' Error in retrieving dlt threshold product attribute for the product: '
                . $productId . ' is: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
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
        
        if ($containerPath) {
            unset($meta['product-details']['children']['container_dlt_thresholds']);
        }
        
        $meta = array_replace_recursive(
            $meta,
            [
                'dlt_threshold' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('DLT Threshold'),
                                'componentType' => Fieldset::NAME,
                                'dataScope' => 'data.product.dlt_threshold',
                                'collapsible' => true,
                                'sortOrder' => 5,
                            ],
                        ],
                    ],
                    'children' => [
                        "dlt_threshold_field" => $this->getSelectTypeGridConfig(10),
                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * @param  $sortOrder
     */
    public function getSelectTypeGridConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'addButtonLabel' => __('Add'),
                        'componentType' => DynamicRows::NAME,
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows',
                        'additionalClasses' => 'admin__field-wide',
                        'deleteProperty' => static::FIELD_IS_DELETE,
                        'deleteValue' => '1',
                        'renderDefaultRecord' => false,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Container::NAME,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'positionProvider' => static::FIELD_SORT_ORDER_NAME,
                                'isTemplate' => true,
                                'is_collection' => true,
                            ],
                        ],
                    ],
                    'children' => [
                        'dlt_start' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Field::NAME,
                                        'formElement' => Input::NAME,
                                        'dataType' => Price::NAME,
                                        'label' => __('DLT Start'),
                                        'enableLabel' => true,
                                        'dataScope' => 'dlt_start',
                                        'sortOrder' => 20,
                                        'validation' => [
                                            'required-entry' => true,
                                            'validate-number' => true,
                                            'validate-zero-or-greater' => true,
                                            'validate-integer' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'dlt_end' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Field::NAME,
                                        'formElement' => Input::NAME,
                                        'dataType' => Price::NAME,
                                        'label' => __('DLT End'),
                                        'enableLabel' => true,
                                        'dataScope' => 'dlt_end',
                                        'sortOrder' => 40,
                                        'validation' => [
                                            'required-entry' => true,
                                            'validate-number' => true,
                                            'validate-zero-or-greater' => true,
                                            'validate-integer' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'dlt_hours' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Field::NAME,
                                        'formElement' => Input::NAME,
                                        'dataType' => Price::NAME,
                                        'label' => __('DLT Hours'),
                                        'enableLabel' => true,
                                        'dataScope' => 'dlt_hours',
                                        'sortOrder' => 60,
                                        'validation' => [
                                            'required-entry' => true,
                                            'validate-number' => true,
                                            'validate-zero-or-greater' => true,
                                            'validate-integer' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'actionDelete' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => 'actionDelete',
                                        'dataType' => Text::NAME,
                                        'label' => '',
                                        'sortOrder' => 70,
                                        'deleteProperty' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
