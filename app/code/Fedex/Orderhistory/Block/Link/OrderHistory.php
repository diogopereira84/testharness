<?php
namespace Fedex\Orderhistory\Block\Link;

use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class OrderHistory extends \Magento\Framework\View\Element\Html\Link
{
  /**
     * @param Context $context
     * @param Data|null $helperData
     * @param array $data
     */
     
    public function __construct(
        Context $context,
        protected Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    
    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->helperData->isModuleEnabled()) {
            return '<li><a ' . $this->getLinkAttributes() . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
        }
        return '';
    }
}
