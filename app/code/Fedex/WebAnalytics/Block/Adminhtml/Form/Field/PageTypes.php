<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class PageTypes extends AbstractFieldArray
{
    public const LABEL_FORM_KEY = 'label';
    public const VALUE_FORM_KEY = 'value';

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            self::LABEL_FORM_KEY,
            [
                'label' => __('Page Type Label'),
                'class' => 'required-entry'
            ]
        );
        $this->addColumn(
            self::VALUE_FORM_KEY,
            [
                'label' => __('Page Type Value'),
                'class' => 'required-entry'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
