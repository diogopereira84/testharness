<?php

declare(strict_types=1);

namespace Fedex\WebAnalytics\Block\Adminhtml\Form\Field\Widget;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class DynamicRows
 * @package Fedex\WebAnalytics\Block\Adminhtml\Widget
 */
class DynamicRowsFromUrl extends AbstractFieldArray
{
    public const REQUEST_PARAM = 'request_param';
    public const PARAMETER_TO_URL = 'parameter_to_url';

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn(self::REQUEST_PARAM, [
            'label' => __('Parameter from Request URL')
        ]);

        $this->addColumn(self::PARAMETER_TO_URL, [
            'label' => __('Parameter to Redirect URL')
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
