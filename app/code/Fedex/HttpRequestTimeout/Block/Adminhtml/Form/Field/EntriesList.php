<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;

class EntriesList extends AbstractFieldArray
{
    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Factory $elementFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('entry_name', ['label' => __('Entry')]);
        $this->_addAfter = false;
        $this->_template = 'Fedex_HttpRequestTimeout::system/config/form/field/entriesList.phtml';

        parent::_construct();
    }
}
