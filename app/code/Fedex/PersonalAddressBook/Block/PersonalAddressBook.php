<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PersonalAddressBook\Block;

use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Customer\Block\Account\SortLinkInterface;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\SSO\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class PersonalAddressBook extends HtmlLink implements SortLinkInterface
{

    public const PERSONAL_ADDRESS_BOOK_COMMERCIAL = 'Personal Address Book';
    public const ADDRESS_BOOK_RETAIL = 'Address Book';

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param DeliveryDataHelper $deliveryHelper
     * @param Data $dataHelper
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected UrlInterface $urlBuilder,
        protected DeliveryDataHelper $deliveryHelper,
        protected Data $dataHelper,
        protected ToggleConfig $toggleConfig,
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
        if ($this->dataHelper->isSSOlogin()){
            return '';
        }

        $currentUrl = $this->urlBuilder->getCurrentUrl();
        $currentClass = '';
        if (str_contains((string) $currentUrl, (string) $this->getPath())) {
            $currentClass .= ' current';
        }
        if($this->deliveryHelper->isCommercialCustomer()) {
            $this->setLabel(self::PERSONAL_ADDRESS_BOOK_COMMERCIAL);
        } else {
            $this->setLabel(self::ADDRESS_BOOK_RETAIL);
        }
        if ($currentUrl !== null && strpos($currentUrl, 'personaladdressbook/index/view')) {
            $currentClass .= ' current';
        }
        return '<li class="nav item ' . $currentClass . '"><a ' . $this->getLinkAttributes()
            . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
    }
}
