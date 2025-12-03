<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);
namespace Fedex\TrackOrder\Block\Link;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class TrackOrder extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @param Context $context
     * @param array $data
     */

    public function __construct(
        Context $context,
        protected \Fedex\TrackOrder\ViewModel\TrackOrderHome $trackOrderHome,
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
        return '<div class="block block-collapsible-nav">
            <div class="content block-collapsible-nav-content" id="block-collapsible-nav">
                <ul class="nav items">
                <li><a ' . $this->getLinkAttributes() . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>
                </ul>
            </div>
        </div>';
    }

    public function getLabel()
    {
        return $this->trackOrderHome->getLabelText();
    }

}
