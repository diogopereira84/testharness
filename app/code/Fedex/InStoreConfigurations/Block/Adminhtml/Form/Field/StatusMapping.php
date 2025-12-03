<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class StatusMapping
 */
class StatusMapping extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('magento_status', ['label' => __('Magento Status')]);
        $this->addColumn('mapped_status', ['label' => __('Mapped Status')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
