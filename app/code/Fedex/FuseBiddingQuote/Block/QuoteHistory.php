<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Block;

use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * Quote History Block Class
 */
class QuoteHistory extends HtmlLink implements SortLinkInterface
{

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param FuseBidHelper $fuseBidHelper
     * @param SsoConfiguration $ssoConfiguration
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected UrlInterface $urlBuilder,
        protected FuseBidHelper $fuseBidHelper,
        protected SsoConfiguration $ssoConfiguration,
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
        if ($this->fuseBidHelper->isFuseBidGloballyEnabled() && $this->ssoConfiguration->isRetail()) {
            $this->setLabel("My Quotes");
            if ($currentUrl !== null && strpos($currentUrl, 'uploadtoquote/index/quotehistory')) {
                $currentClass .= ' current';
            }
            return '<li class="nav item ' . $currentClass . '"><a ' . $this->getLinkAttributes()
                . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
        }
    }
}
