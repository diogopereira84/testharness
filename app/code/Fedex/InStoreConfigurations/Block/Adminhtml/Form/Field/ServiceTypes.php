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
 * Class ServiceTypes
 */
class ServiceTypes extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('service_type', ['label' => __('Service Types')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
