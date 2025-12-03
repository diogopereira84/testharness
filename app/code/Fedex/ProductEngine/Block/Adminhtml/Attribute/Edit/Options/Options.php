<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Block\Adminhtml\Attribute\Edit\Options;

use Magento\Eav\Block\Adminhtml\Attribute\Edit\Options\Options as CoreOptions;

class Options extends CoreOptions
{
    protected $_template = 'Fedex_ProductEngine::catalog/product/attribute/options.phtml';

    /**
     * Prepare option values of user defined attribute
     * Function overrided to add choice_id to the array so it could be rendered on the attribute options list
     *
     * @param array|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option $option
     * @param string $inputType
     * @param array $defaultValues
     * @return array
     */
    protected function _prepareUserDefinedAttributeOptionValues($option, $inputType, $defaultValues)
    {
        $optionId = $option->getId();

        $value['checked'] = in_array($optionId, $defaultValues) ? 'checked="checked"' : '';
        $value['intype'] = $inputType;
        $value['id'] = $optionId;
        $value['sort_order'] = $option->getSortOrder();
        $value['choice_id'] = $option->getChoiceId() ?? '';

        foreach ($this->getStores() as $store) {
            $storeId = $store->getId();
            $storeValues = $this->getStoreOptionValues($storeId);
            $value['store' . $storeId] = isset(
                $storeValues[$optionId]
            ) ? $this->escapeHtml(
                $storeValues[$optionId]
            ) : '';
        }

        return [$value];
    }
}
