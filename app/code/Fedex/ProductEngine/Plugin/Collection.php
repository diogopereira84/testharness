<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Plugin;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as CoreOptionCollection;
use Fedex\Company\Api\Data\ConfigInterface;

/**
 * class Collection for plugin
 */
class Collection
{
     /**
     * @param ConfigInterface $configInterface
     */
    public function __construct(
        protected ConfigInterface $configInterface
    )
    {
    }

    /**
     * After plugin method for `toOptionArray` of `CoreOptionCollection`
     *
     * @param CoreOptionCollection $subject
     * @param array $result
     * @param string $valueKey
     * @return array
     */
    public function afterToOptionArray(
        CoreOptionCollection $subject,
        array $result,
        $valueKey = 'value'
    ) {
            $option = $this->_toOptionArray(
                $subject,
                'option_id',
                $valueKey,
                ['choice_id' => 'choice_id', 'option_id' => 'option_id']
            );
            return $option;
    }

    /**
     * Convert items array to array for select options
     *
     * @param Obj $subject
     * @param string $valueField
     * @param string $labelField
     * @param array $additional
     * @return array
     */
    protected function _toOptionArray(
        CoreOptionCollection $subject,
        $valueField = 'id',
        $labelField = 'name',
        $additional = []
    ) {
        $res = [];
        $additional['value'] = $valueField;
        $additional['label'] = $labelField;

        foreach ($subject as $item) {
            foreach ($additional as $code => $field) {
                $data[$code] = $item->getData($field);
            }
            $res[] = $data;
        }

        return $res;
    }
}
