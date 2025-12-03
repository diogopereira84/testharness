<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Block\Adminhtml\Auth;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Url as FrontendUrlHelper;

class Logout extends Template
{
    /**
     * Logout constructor.
     *
     * @param FrontendUrlHelper $frontendUrlHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        private FrontendUrlHelper $frontendUrlHelper,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return FrontendUrlHelper
     */
    public function getFrontendUrlHelper()
    {
        return $this->frontendUrlHelper;
    }
}
