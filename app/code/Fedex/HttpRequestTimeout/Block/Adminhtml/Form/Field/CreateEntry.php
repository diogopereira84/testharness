<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Block\Adminhtml\Form\Field;

use Fedex\HttpRequestTimeout\Model\ConfigManagement;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class CreateEntry extends Field
{
    public function __construct(
        Context $context,
        private ConfigManagement $configManagement,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    protected function _construct()
    {
        $this->_template = 'Fedex_HttpRequestTimeout::system/config/form/field/createEntryButton.phtml';

        parent::_construct();
    }

    public function getCreateEntryButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData([
            'id'    => 'fedex_http_request_timeout_create_entry_button',
            'label' => __('Create Timeout')
        ]);

        return $button->toHtml();
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getEntriesValue()
    {
        return $this->configManagement->getCurrentEntriesValueForListing();
    }

    public function getCreateEntryUrl()
    {
        return $this->getUrl('http_request_timeout/entry/create');
    }

    public function getRemoveEntryUrl()
    {
        return $this->getUrl('http_request_timeout/entry/remove');
    }
}
