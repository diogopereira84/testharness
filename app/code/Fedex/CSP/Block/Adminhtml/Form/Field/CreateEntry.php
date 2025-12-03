<?php
declare(strict_types=1);
namespace Fedex\CSP\Block\Adminhtml\Form\Field;

use Fedex\CSP\Model\CspManagement;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class CreateEntry extends Field
{
    /**
     * @param Context $context
     * @param CspManagement $cspManagement
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        protected CspManagement $cspManagement,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    protected function _construct()
    {
        $this->_template = 'Fedex_CSP::system/config/form/field/createEntryButton.phtml';

        parent::_construct();
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return Create Entries Value from DB
     * @return string
     */
    public function getEntriesValue()
    {
        return $this->cspManagement->getCurrentEntriesValueForListing();
    }

    /**
     * Return Create Entry Url
     * @return string
     */
    public function getCreateEntryUrl()
    {
        return $this->getUrl('csp/entry/create');
    }

    /**
     * Return Remove Entry Url
     * @return string
     */
    public function getRemoveEntryUrl()
    {
        return $this->getUrl('csp/entry/remove');
    }

    /**
     * Render Create Entry button HTML
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCreateEntryButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData([
            'id'    => 'fedex_csp_create_entry_button',
            'label' => __('Create Entry')
        ]);

        return $button->toHtml();
    }
}
