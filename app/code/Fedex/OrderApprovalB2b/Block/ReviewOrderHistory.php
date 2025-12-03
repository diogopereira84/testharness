<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Block;

use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Customer\Block\Account\SortLinkInterface;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;

class ReviewOrderHistory extends HtmlLink implements SortLinkInterface
{

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param AdminConfigHelper $adminConfigHelper
     * @param RevieworderHelper $revieworderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected UrlInterface $urlBuilder,
        protected AdminConfigHelper $adminConfigHelper,
        protected RevieworderHelper $revieworderHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * To render html output
     */
    protected function _toHtml()
    {
        $currentUrl = $this->urlBuilder->getCurrentUrl();
        $currentClass = '';
        if (str_contains((string) $currentUrl, (string) $this->getPath())) {
            $currentClass .= ' current';
        }
        if ($this->adminConfigHelper->isOrderApprovalB2bEnabled() &&
        $this->revieworderHelper->checkIfUserHasReviewOrderPermission()) {
            $this->setLabel("Review Orders");
            if ($currentUrl !== null && strpos($currentUrl, 'orderb2b/revieworder/history')) {
                $currentClass .= ' current';
            }
            return '<li class="nav item ' . $currentClass . '"><a ' . $this->getLinkAttributes()
                . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
        }
    }
}
