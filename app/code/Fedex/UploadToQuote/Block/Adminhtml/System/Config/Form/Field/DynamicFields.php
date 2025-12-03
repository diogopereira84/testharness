<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * DynamicFields Block Class
 */
class DynamicFields extends AbstractFieldArray
{
    /**
     * Used for rendering the dynamic cloumn
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'text_field',
            [
                'label' => __('Reason for Declining'),
                'class' => 'required-entry'
            ]
        );
        $this->addColumn(
            'number_field',
            [
                'label' => __('Sort Order'),
                'class' => 'required-entry validate-number'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Row');
    }

    /**
     * Get column for unit testing
     *
     * @return array
     */
    public function getColumnsForTesting(): array
    {
        return $this->_columns;
    }
}
